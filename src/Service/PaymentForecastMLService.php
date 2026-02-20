<?php

namespace App\Service;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

class PaymentForecastMLService
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
     * @return array{forecast_revenue_day: float, forecast_orders_day: int, forecast_failure_rate_day: float, source: string}|null
     */
    public function predictDailyForecast(array $features): ?array
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

        $scriptPath = $this->kernel->getProjectDir() . '/ml/payment_forecast_predict.py';
        if (!is_file($scriptPath)) {
            @unlink($inputPath);
            return null;
        }

        $pythonBinaries = ['python', 'python3', 'py'];
        $success = false;
        foreach ($pythonBinaries as $python) {
            $process = new Process([$python, $scriptPath, $this->modelPath(), $inputPath, $outputPath]);
            $process->setTimeout(30);
            $process->run();
            if ($process->isSuccessful()) {
                $success = true;
                break;
            }
        }

        if (!$success || !is_file($outputPath)) {
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
        if (!is_array($decoded)) {
            return null;
        }

        if (!isset($decoded['forecast_revenue_day'], $decoded['forecast_orders_day'], $decoded['forecast_failure_rate_day'])) {
            return null;
        }

        return [
            'forecast_revenue_day' => max(0.0, (float) $decoded['forecast_revenue_day']),
            'forecast_orders_day' => max(0, (int) round((float) $decoded['forecast_orders_day'])),
            'forecast_failure_rate_day' => min(100.0, max(0.0, (float) $decoded['forecast_failure_rate_day'])),
            'source' => (string) ($decoded['source'] ?? 'ml_forecast_model'),
        ];
    }

    public function modelPath(): string
    {
        return $this->workDir() . '/payment_forecast_model.pkl';
    }

    public function metadataPath(): string
    {
        return $this->workDir() . '/payment_forecast_metadata.json';
    }

    public function datasetPath(): string
    {
        return $this->workDir() . '/payment_forecast_train.csv';
    }

    private function workDir(): string
    {
        return $this->kernel->getProjectDir() . '/var/payment_forecast_ai';
    }
}
