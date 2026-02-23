<?php

namespace App\Command;

use App\Entity\Commentaire;
use App\Entity\Post;
use App\Repository\CommentaireRepository;
use App\Repository\FeedAiAnalysisRepository;
use App\Repository\PostRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:feed-ai:train',
    description: 'Train local ML models for feed intelligence (category, moderation action, risk scores).',
)]
class FeedAiTrainCommand extends Command
{
    public function __construct(
        private FeedAiAnalysisRepository $feedAiAnalysisRepository,
        private PostRepository $postRepository,
        private CommentaireRepository $commentaireRepository,
        private KernelInterface $kernel
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Max analyses rows to use.', '5000')
            ->addOption('days', null, InputOption::VALUE_OPTIONAL, 'Lookback window in days.', '365')
            ->addOption('min-rows', null, InputOption::VALUE_OPTIONAL, 'Minimum usable rows required for training.', '24')
            ->addOption('augment-factor', null, InputOption::VALUE_OPTIONAL, 'Dataset augmentation factor (1-8) before training.', '4')
            ->addOption('with-web-data', null, InputOption::VALUE_OPTIONAL, 'Merge public web datasets before training (1/0).', '1')
            ->addOption('web-max-rows', null, InputOption::VALUE_OPTIONAL, 'Maximum rows imported from web datasets.', '45000')
            ->addOption('web-languages', null, InputOption::VALUE_OPTIONAL, 'Comma-separated web languages (fr,en,ar).', 'fr,en,ar')
            ->addOption('model-dir', null, InputOption::VALUE_OPTIONAL, 'Target model directory (default: var/feed_ai/models).')
            ->addOption('dataset-path', null, InputOption::VALUE_OPTIONAL, 'Dataset CSV output path.')
            ->addOption('metadata-path', null, InputOption::VALUE_OPTIONAL, 'Metadata JSON output path.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = max(200, (int) $input->getOption('limit'));
        $days = max(30, (int) $input->getOption('days'));
        $minRows = max(10, (int) $input->getOption('min-rows'));
        $augmentFactor = max(1, min(8, (int) $input->getOption('augment-factor')));
        $withWebDataRaw = strtolower(trim((string) $input->getOption('with-web-data')));
        $withWebData = in_array($withWebDataRaw, ['1', 'true', 'yes', 'on'], true);
        $webMaxRows = max(0, (int) $input->getOption('web-max-rows'));
        $webLanguages = trim((string) ($input->getOption('web-languages') ?? 'fr,en,ar'));
        $since = (new \DateTimeImmutable('now'))->modify(sprintf('-%d days', $days));

        $analyses = $this->feedAiAnalysisRepository->createQueryBuilder('a')
            ->andWhere('a.updatedAt >= :since')
            ->setParameter('since', $since)
            ->orderBy('a.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        if ($analyses === []) {
            $output->writeln('<comment>No feed_ai_analysis rows found in lookback window.</comment>');
            return Command::SUCCESS;
        }

        $postIds = [];
        $commentIds = [];
        foreach ($analyses as $analysis) {
            if ($analysis->getEntityType() === 'post') {
                $postIds[] = $analysis->getEntityId();
            } elseif ($analysis->getEntityType() === 'comment') {
                $commentIds[] = $analysis->getEntityId();
            }
        }

        $posts = $postIds !== [] ? $this->postRepository->findBy(['id' => array_values(array_unique($postIds))]) : [];
        $comments = $commentIds !== [] ? $this->commentaireRepository->findBy(['id' => array_values(array_unique($commentIds))]) : [];

        $postMap = [];
        foreach ($posts as $post) {
            if ($post instanceof Post && $post->getId() !== null) {
                $postMap[$post->getId()] = $post;
            }
        }
        $commentMap = [];
        foreach ($comments as $comment) {
            if ($comment instanceof Commentaire && $comment->getId() !== null) {
                $commentMap[$comment->getId()] = $comment;
            }
        }

        $rows = [];
        foreach ($analyses as $analysis) {
            $entityType = (string) $analysis->getEntityType();
            $entityId = (int) $analysis->getEntityId();
            $text = '';

            if ($entityType === 'post') {
                $post = $postMap[$entityId] ?? null;
                if ($post instanceof Post) {
                    $text = trim(
                        (string) ($post->getContent() ?? '')
                        . ' '
                        . (string) ($post->getEventTitle() ?? '')
                        . ' '
                        . (string) ($post->getEventLocation() ?? '')
                    );
                }
            } elseif ($entityType === 'comment') {
                $comment = $commentMap[$entityId] ?? null;
                if ($comment instanceof Commentaire) {
                    $text = trim((string) $comment->getContent());
                }
            }

            if ($text === '') {
                continue;
            }

            $rows[] = [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'text' => $text,
                'category' => trim((string) ($analysis->getCategory() ?? 'general')) ?: 'general',
                'auto_action' => trim((string) $analysis->getAutoAction()) ?: 'allow',
                'toxicity_score' => $analysis->getToxicityScore(),
                'hate_speech_score' => $analysis->getHateSpeechScore(),
                'spam_score' => $analysis->getSpamScore(),
                'duplicate_score' => $analysis->getDuplicateScore(),
                'media_risk_score' => $analysis->getMediaRiskScore(),
            ];
        }

        if (count($rows) < $minRows) {
            $output->writeln(sprintf('<comment>Not enough usable rows to train feed models (need at least %d).</comment>', $minRows));
            $output->writeln(sprintf('<comment>Usable rows: %d</comment>', count($rows)));
            return Command::SUCCESS;
        }

        $workDir = $this->kernel->getProjectDir() . '/var/feed_ai';
        $modelDirInput = trim((string) ($input->getOption('model-dir') ?? ''));
        $datasetPathInput = trim((string) ($input->getOption('dataset-path') ?? ''));
        $metadataPathInput = trim((string) ($input->getOption('metadata-path') ?? ''));

        $modelDir = $modelDirInput !== '' ? $modelDirInput : ($workDir . '/models');
        $datasetPath = $datasetPathInput !== '' ? $datasetPathInput : ($workDir . '/feed_ai_train.csv');
        $metadataPath = $metadataPathInput !== '' ? $metadataPathInput : ($modelDir . '/feed_ai_model_info.json');
        $datasetDir = dirname($datasetPath);
        $metadataDir = dirname($metadataPath);

        if (!is_dir($modelDir) && !mkdir($modelDir, 0777, true) && !is_dir($modelDir)) {
            $output->writeln('<error>Unable to create model directory.</error>');
            return Command::FAILURE;
        }
        if (!is_dir($datasetDir) && !mkdir($datasetDir, 0777, true) && !is_dir($datasetDir)) {
            $output->writeln('<error>Unable to create dataset directory.</error>');
            return Command::FAILURE;
        }
        if (!is_dir($metadataDir) && !mkdir($metadataDir, 0777, true) && !is_dir($metadataDir)) {
            $output->writeln('<error>Unable to create metadata directory.</error>');
            return Command::FAILURE;
        }

        $headers = [
            'entity_type',
            'entity_id',
            'text',
            'category',
            'auto_action',
            'toxicity_score',
            'hate_speech_score',
            'spam_score',
            'duplicate_score',
            'media_risk_score',
        ];
        $this->writeCsv($datasetPath, $rows, $headers);
        $output->writeln(sprintf(
            '<info>Feed AI train profile: web_data=%s, web_max_rows=%d, web_languages=%s</info>',
            $withWebData ? 'on' : 'off',
            $webMaxRows,
            $webLanguages !== '' ? $webLanguages : 'fr,en,ar'
        ));

        $scriptPath = $this->kernel->getProjectDir() . '/ml/feed_ai/feed_ai_train.py';
        if (!is_file($scriptPath)) {
            $output->writeln('<error>Python trainer not found: ml/feed_ai/feed_ai_train.py</error>');
            return Command::FAILURE;
        }

        $pythonBinaries = ['python', 'python3', 'py'];
        $success = false;
        $lastError = '';

        foreach ($pythonBinaries as $python) {
            $process = new Process([
                $python,
                $scriptPath,
                $datasetPath,
                $modelDir,
                $metadataPath,
                (string) $augmentFactor,
                $withWebData ? '1' : '0',
                (string) $webMaxRows,
                $webLanguages,
            ]);
            $process->setTimeout(1800);
            $process->run();

            if ($process->isSuccessful()) {
                $success = true;
                $output->writeln(sprintf('<info>Feed AI training completed with %s.</info>', $python));
                $stdOut = trim($process->getOutput());
                if ($stdOut !== '') {
                    $output->writeln($stdOut);
                }
                break;
            }

            $lastError = trim($process->getErrorOutput() . "\n" . $process->getOutput());
        }

        if (!$success) {
            $output->writeln('<error>Unable to run feed AI trainer. Check Python, pandas, scikit-learn and joblib.</error>');
            if ($lastError !== '') {
                $output->writeln($lastError);
            }
            return Command::FAILURE;
        }

        $output->writeln('<info>Artifacts generated:</info>');
        $output->writeln('- ' . $datasetPath);
        foreach ([
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
        ] as $artifact) {
            $path = $modelDir . '/' . $artifact;
            if (is_file($path)) {
                $output->writeln('- ' . $path);
            }
        }
        $output->writeln('- ' . $metadataPath);

        return Command::SUCCESS;
    }

    /**
     * @param list<array<string,int|float|string>> $rows
     * @param string[] $headers
     */
    private function writeCsv(string $path, array $rows, array $headers): void
    {
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open file: ' . $path);
        }

        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $header) {
                $line[] = $row[$header] ?? '';
            }
            fputcsv($handle, $line);
        }
        fclose($handle);
    }
}
