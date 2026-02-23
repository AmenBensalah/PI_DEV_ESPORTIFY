<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:feed-ai:auto-retrain',
    description: 'Periodic feed AI retraining with quality gates before model activation.',
)]
class FeedAiAutoRetrainCommand extends Command
{
    public function __construct(private KernelInterface $kernel)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('cooldown-minutes', null, InputOption::VALUE_OPTIONAL, 'Minimum delay between two auto retrains.', '180')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force retrain ignoring cooldown.')
            ->addOption('min-rows', null, InputOption::VALUE_OPTIONAL, 'Minimum dataset rows to consider activation.', '30')
            ->addOption('min-category-score', null, InputOption::VALUE_OPTIONAL, 'Minimum balanced accuracy for category model (sklearn).', '0.70')
            ->addOption('min-action-score', null, InputOption::VALUE_OPTIONAL, 'Minimum balanced accuracy for action model (sklearn).', '0.70')
            ->addOption('max-spam-mae', null, InputOption::VALUE_OPTIONAL, 'Maximum accepted train MAE for spam regressor (sklearn).', '12')
            ->addOption('with-web-data', null, InputOption::VALUE_OPTIONAL, 'Merge public web datasets during retrain (1/0).', '1')
            ->addOption('web-max-rows', null, InputOption::VALUE_OPTIONAL, 'Maximum rows imported from web datasets.', '45000')
            ->addOption('web-languages', null, InputOption::VALUE_OPTIONAL, 'Comma-separated web languages (fr,en,ar).', 'fr,en,ar')
            ->addOption('allow-fallback', null, InputOption::VALUE_NONE, 'Allow activation of fallback models when sklearn is unavailable.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectDir = $this->kernel->getProjectDir();
        $baseDir = $projectDir . '/var/feed_ai';
        $activeDir = $baseDir . '/models';
        $statePath = $baseDir . '/auto_retrain_state.json';

        if (!is_dir($baseDir) && !mkdir($baseDir, 0777, true) && !is_dir($baseDir)) {
            $output->writeln('<error>Unable to create var/feed_ai directory.</error>');
            return Command::FAILURE;
        }

        $force = (bool) $input->getOption('force');
        $cooldownMinutes = max(1, (int) $input->getOption('cooldown-minutes'));
        $state = $this->readJson($statePath);

        if (!$force && isset($state['last_attempt_at']) && is_string($state['last_attempt_at'])) {
            $lastAttempt = new \DateTimeImmutable($state['last_attempt_at']);
            $nextAllowed = $lastAttempt->modify(sprintf('+%d minutes', $cooldownMinutes));
            if ($nextAllowed > new \DateTimeImmutable('now')) {
                $output->writeln(sprintf('<comment>Cooldown active. Next retrain allowed at %s.</comment>', $nextAllowed->format(DATE_ATOM)));
                return Command::SUCCESS;
            }
        }

        $attemptAt = new \DateTimeImmutable('now');
        $stagingRoot = $baseDir . '/staging';
        $stagingDir = $stagingRoot . '/run_' . $attemptAt->format('Ymd_His') . '_' . bin2hex(random_bytes(4));
        $stagingModelDir = $stagingDir . '/models';
        $stagingDataset = $stagingDir . '/feed_ai_train.csv';
        $stagingMetadata = $stagingModelDir . '/feed_ai_model_info.json';
        $withWebData = trim((string) ($input->getOption('with-web-data') ?? '1'));
        $webMaxRows = max(0, (int) $input->getOption('web-max-rows'));
        $webLanguages = trim((string) ($input->getOption('web-languages') ?? 'fr,en,ar'));

        if (!is_dir($stagingModelDir) && !mkdir($stagingModelDir, 0777, true) && !is_dir($stagingModelDir)) {
            $output->writeln('<error>Unable to create staging model directory.</error>');
            return Command::FAILURE;
        }

        $php = (defined('PHP_BINARY') && is_string(PHP_BINARY) && PHP_BINARY !== '') ? PHP_BINARY : 'php';
        $process = new Process([
            $php,
            $projectDir . '/bin/console',
            'app:feed-ai:train',
            '--no-interaction',
            '--model-dir=' . $stagingModelDir,
            '--dataset-path=' . $stagingDataset,
            '--metadata-path=' . $stagingMetadata,
            '--with-web-data=' . $withWebData,
            '--web-max-rows=' . (string) $webMaxRows,
            '--web-languages=' . $webLanguages,
        ]);
        $process->setTimeout(2400);
        $process->run();

        $runOutput = trim($process->getOutput() . "\n" . $process->getErrorOutput());

        $state['last_attempt_at'] = $attemptAt->format(DATE_ATOM);
        $state['last_attempt_ok'] = $process->isSuccessful();
        $state['last_attempt_output'] = $runOutput;

        if (!$process->isSuccessful() || !is_file($stagingMetadata)) {
            $state['last_activation'] = 'rejected_training_failed';
            $this->writeJson($statePath, $state);
            $output->writeln('<error>Feed auto-retrain failed during training stage.</error>');
            if ($runOutput !== '') {
                $output->writeln($runOutput);
            }
            return Command::FAILURE;
        }

        $meta = $this->readJson($stagingMetadata);
        $gate = $this->evaluateQualityGate($meta, $input);

        if (!$gate['ok']) {
            $state['last_activation'] = 'rejected_quality_gate';
            $state['last_reject_reasons'] = $gate['reasons'];
            $this->writeJson($statePath, $state);
            $output->writeln('<comment>Auto-retrain completed but activation rejected by quality gate.</comment>');
            foreach ($gate['reasons'] as $reason) {
                $output->writeln('- ' . $reason);
            }
            return Command::SUCCESS;
        }

        if (!is_dir($activeDir) && !mkdir($activeDir, 0777, true) && !is_dir($activeDir)) {
            $output->writeln('<error>Unable to create active model directory.</error>');
            return Command::FAILURE;
        }

        $artifacts = [
            'category_model.joblib',
            'action_model.joblib',
            'toxicity_model.joblib',
            'hate_model.joblib',
            'spam_model.joblib',
            'category_model.json',
            'action_model.json',
            'toxicity_model.json',
            'hate_model.json',
            'spam_model.json',
            'feed_ai_model_info.json',
        ];
        $copied = 0;
        foreach ($artifacts as $artifact) {
            $src = $stagingModelDir . '/' . $artifact;
            if (!is_file($src)) {
                continue;
            }
            $dst = $activeDir . '/' . $artifact;
            if (@copy($src, $dst)) {
                $copied++;
            }
        }

        if ($copied === 0) {
            $state['last_activation'] = 'rejected_no_artifacts';
            $this->writeJson($statePath, $state);
            $output->writeln('<error>No model artifacts were copied to active directory.</error>');
            return Command::FAILURE;
        }

        $state['last_activation'] = 'activated';
        $state['last_activated_at'] = (new \DateTimeImmutable('now'))->format(DATE_ATOM);
        $state['last_reject_reasons'] = [];
        $state['active_models_copied'] = $copied;
        $this->writeJson($statePath, $state);

        $output->writeln('<info>Feed AI auto-retrain activated new model set.</info>');
        $output->writeln(sprintf('<info>Copied artifacts: %d</info>', $copied));

        return Command::SUCCESS;
    }

    /**
     * @return array{ok:bool,reasons:string[]}
     */
    private function evaluateQualityGate(array $meta, InputInterface $input): array
    {
        $reasons = [];
        $minRows = max(10, (int) $input->getOption('min-rows'));
        $minCategory = max(0.0, min(1.0, (float) $input->getOption('min-category-score')));
        $minAction = max(0.0, min(1.0, (float) $input->getOption('min-action-score')));
        $maxSpamMae = max(0.0, (float) $input->getOption('max-spam-mae'));
        $allowFallback = (bool) $input->getOption('allow-fallback');

        $rows = (int) ($meta['dataset_rows'] ?? 0);
        if ($rows < $minRows) {
            $reasons[] = sprintf('dataset_rows=%d is below min_rows=%d', $rows, $minRows);
        }

        $trainer = (string) ($meta['trainer'] ?? '');
        $models = is_array($meta['models'] ?? null) ? $meta['models'] : [];

        if ($trainer === 'sklearn') {
            $category = is_array($models['category'] ?? null) ? $models['category'] : [];
            $action = is_array($models['auto_action'] ?? null) ? $models['auto_action'] : [];
            $spam = is_array($models['spam_score'] ?? null) ? $models['spam_score'] : [];

            if (!(bool) ($category['trained'] ?? false)) {
                $reasons[] = 'category model not trained';
            } elseif ((float) ($category['balanced_accuracy_cv'] ?? 0.0) < $minCategory) {
                $reasons[] = sprintf('category balanced_accuracy_cv %.4f < %.4f', (float) ($category['balanced_accuracy_cv'] ?? 0.0), $minCategory);
            }

            if (!(bool) ($action['trained'] ?? false)) {
                $reasons[] = 'auto_action model not trained';
            } elseif ((float) ($action['balanced_accuracy_cv'] ?? 0.0) < $minAction) {
                $reasons[] = sprintf('auto_action balanced_accuracy_cv %.4f < %.4f', (float) ($action['balanced_accuracy_cv'] ?? 0.0), $minAction);
            }

            if ((bool) ($spam['trained'] ?? false) && isset($spam['mae_train']) && (float) $spam['mae_train'] > $maxSpamMae) {
                $reasons[] = sprintf('spam mae_train %.4f > %.4f', (float) $spam['mae_train'], $maxSpamMae);
            }
        } else {
            if (!$allowFallback) {
                $reasons[] = sprintf('trainer=%s rejected (enable --allow-fallback to accept)', $trainer !== '' ? $trainer : 'unknown');
            } else {
                $category = is_array($models['category'] ?? null) ? $models['category'] : [];
                $action = is_array($models['auto_action'] ?? null) ? $models['auto_action'] : [];
                if (!(bool) ($category['trained'] ?? false)) {
                    $reasons[] = 'fallback category model not trained';
                }
                if (!(bool) ($action['trained'] ?? false)) {
                    $reasons[] = 'fallback auto_action model not trained';
                }
            }
        }

        return [
            'ok' => $reasons === [],
            'reasons' => $reasons,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function readJson(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }
        $raw = @file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function writeJson(string $path, array $payload): void
    {
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new \RuntimeException('Unable to create directory: ' . $dir);
        }
        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
