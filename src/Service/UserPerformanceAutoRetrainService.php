<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

class UserPerformanceAutoRetrainService
{
    private const DEFAULT_COOLDOWN_SECONDS = 2;
    private const MAX_OUTPUT_CHARS = 2000;

    public function __construct(
        private KernelInterface $kernel,
        private LoggerInterface $logger
    ) {
    }

    public function trigger(string $reason): void
    {
        try {
            $workDir = $this->kernel->getProjectDir() . '/var/user_ai';
            if (!is_dir($workDir) && !mkdir($workDir, 0777, true) && !is_dir($workDir)) {
                return;
            }

            $lockHandle = fopen($workDir . '/auto_retrain.lock', 'c+');
            if ($lockHandle === false) {
                return;
            }

            if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
                fclose($lockHandle);
                return;
            }

            try {
                $statePath = $workDir . '/auto_retrain_state.json';
                $state = $this->readState($statePath);

                $now = time();
                $lastRunAt = (int) ($state['lastRunAt'] ?? 0);
                $cooldown = $this->cooldownSeconds();

                if ($lastRunAt > 0 && ($now - $lastRunAt) < $cooldown) {
                    $state['lastSkipAt'] = $now;
                    $state['lastSkipReason'] = 'cooldown';
                    $state['lastTriggerReason'] = $reason;
                    $state['cooldownSeconds'] = $cooldown;
                    $this->writeState($statePath, $state);
                    return;
                }

                $process = new Process([
                    $this->phpBinary(),
                    $this->kernel->getProjectDir() . '/bin/console',
                    'app:user-ai:train',
                    '--no-interaction',
                ]);
                $process->setTimeout(300);
                $process->run();

                $combinedOutput = trim($process->getOutput() . "\n" . $process->getErrorOutput());
                if (strlen($combinedOutput) > self::MAX_OUTPUT_CHARS) {
                    $combinedOutput = substr($combinedOutput, 0, self::MAX_OUTPUT_CHARS) . '...';
                }

                $state = [
                    'lastRunAt' => $now,
                    'lastTriggerReason' => $reason,
                    'cooldownSeconds' => $cooldown,
                    'status' => $process->isSuccessful() ? 'success' : 'failed',
                    'exitCode' => $process->getExitCode(),
                    'output' => $combinedOutput,
                ];
                $this->writeState($statePath, $state);

                if (!$process->isSuccessful()) {
                    $this->logger->error('User AI auto-retrain failed.', [
                        'reason' => $reason,
                        'exitCode' => $process->getExitCode(),
                        'output' => $combinedOutput,
                    ]);
                }
            } finally {
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
            }
        } catch (\Throwable $e) {
            $this->logger->error('User AI auto-retrain crashed.', [
                'reason' => $reason,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    private function cooldownSeconds(): int
    {
        $raw = $_ENV['USER_AI_AUTORETRAIN_COOLDOWN_SEC']
            ?? $_SERVER['USER_AI_AUTORETRAIN_COOLDOWN_SEC']
            ?? self::DEFAULT_COOLDOWN_SECONDS;

        $value = (int) $raw;
        return $value >= 0 ? $value : self::DEFAULT_COOLDOWN_SECONDS;
    }

    private function phpBinary(): string
    {
        if (defined('PHP_BINARY') && is_string(PHP_BINARY) && PHP_BINARY !== '') {
            return PHP_BINARY;
        }

        return 'php';
    }

    /**
     * @return array<string, mixed>
     */
    private function readState(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $state
     */
    private function writeState(string $path, array $state): void
    {
        file_put_contents($path, json_encode($state, JSON_PRETTY_PRINT));
    }
}

