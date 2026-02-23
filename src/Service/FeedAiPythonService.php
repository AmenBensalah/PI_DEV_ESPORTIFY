<?php

namespace App\Service;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

class FeedAiPythonService
{
    public function __construct(private KernelInterface $kernel)
    {
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>|null
     */
    public function runTask(string $task, array $payload): ?array
    {
        $script = $this->kernel->getProjectDir() . '/ml/feed_ai/feed_ai_engine.py';
        if (!is_file($script)) {
            return null;
        }

        $tmpDir = $this->kernel->getProjectDir() . '/var/feed_ai';
        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0777, true) && !is_dir($tmpDir)) {
            return null;
        }

        $uniq = bin2hex(random_bytes(8));
        $input = $tmpDir . '/in_' . $task . '_' . $uniq . '.json';
        $output = $tmpDir . '/out_' . $task . '_' . $uniq . '.json';
        file_put_contents($input, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $bins = ['python', 'python3', 'py'];
        foreach ($bins as $bin) {
            $process = new Process([$bin, $script, $task, $input, $output]);
            $process->setTimeout(20);
            $process->run();
            if (!$process->isSuccessful() || !is_file($output)) {
                continue;
            }

            $raw = file_get_contents($output);
            if (!is_string($raw) || trim($raw) === '') {
                continue;
            }
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                continue;
            }

            @unlink($input);
            @unlink($output);

            return $decoded;
        }

        @unlink($input);
        @unlink($output);

        return null;
    }
}

