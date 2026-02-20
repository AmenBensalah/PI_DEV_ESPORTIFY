<?php

namespace App\Service;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

class OrderAbuseMLService
{
    public function __construct(private KernelInterface $kernel)
    {
    }

    public function hasTrainedModel(): bool
    {
        return is_file($this->modelPath()) && is_file($this->metadataPath());
    }

    /**
     * @param array<string,int|float|string> $features
     * @return array{risk_score: float, source: string}|null
     */
    public function predictRiskScore(array $features): ?array
    {
        if (!$this->hasTrainedModel()) {
            return null;
        }

        $workDir = $this->workDir();
        if (!is_dir($workDir) && !mkdir($workDir, 0777, true) && !is_dir($workDir)) {
            return null;
        }

        $inputPath = $workDir . '/predict_input_' . uniqid('', true) . '.json';
        $outputPath = $workDir . '/predict_output_' . uniqid('', true) . '.json';

        file_put_contents($inputPath, json_encode($features, JSON_PRETTY_PRINT));

        $scriptPath = $this->kernel->getProjectDir() . '/ml/order_abuse_predict.py';
        if (!is_file($scriptPath)) {
            @unlink($inputPath);
            return null;
        }

        $pythonBinaries = ['python', 'python3', 'py'];
        $ran = false;

        foreach ($pythonBinaries as $python) {
            $process = new Process([$python, $scriptPath, $this->modelPath(), $inputPath, $outputPath]);
            $process->setTimeout(20);
            $process->run();

            if ($process->isSuccessful()) {
                $ran = true;
                break;
            }
        }

        if (!$ran || !is_file($outputPath)) {
            @unlink($inputPath);
            @unlink($outputPath);
            return null;
        }

        $raw = file_get_contents($outputPath);
        @unlink($inputPath);
        @unlink($outputPath);

        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !isset($decoded['risk_score'])) {
            return null;
        }

        return [
            'risk_score' => (float) $decoded['risk_score'],
            'source' => (string) ($decoded['source'] ?? 'trained_ml_model'),
        ];
    }

    public function modelPath(): string
    {
        return $this->workDir() . '/abuse_model.pkl';
    }

    public function metadataPath(): string
    {
        return $this->workDir() . '/abuse_model_metadata.json';
    }

    public function datasetPath(): string
    {
        return $this->workDir() . '/abuse_train.csv';
    }

    private function workDir(): string
    {
        return $this->kernel->getProjectDir() . '/var/order_abuse_ai';
    }
}

