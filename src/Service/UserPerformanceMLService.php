<?php

namespace App\Service;

use Symfony\Component\HttpKernel\KernelInterface;

class UserPerformanceMLService
{
    public function __construct(private KernelInterface $kernel)
    {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPredictionForUser(int $userId): ?array
    {
        $payload = $this->readJsonFile($this->predictionFilePath());
        if (!is_array($payload)) {
            return null;
        }

        $byStringKey = $payload[(string) $userId] ?? null;
        if (is_array($byStringKey)) {
            return $byStringKey;
        }

        $byIntKey = $payload[$userId] ?? null;
        if (is_array($byIntKey)) {
            return $byIntKey;
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getModelInfo(): ?array
    {
        $payload = $this->readJsonFile($this->modelInfoFilePath());
        return is_array($payload) ? $payload : null;
    }

    public function predictionFilePath(): string
    {
        return $this->kernel->getProjectDir() . '/var/user_ai/predictions.json';
    }

    public function modelInfoFilePath(): string
    {
        return $this->kernel->getProjectDir() . '/var/user_ai/model_info.json';
    }

    /**
     * @return array<string, mixed>|list<mixed>|null
     */
    private function readJsonFile(string $path): array|null
    {
        if (!is_file($path)) {
            return null;
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }
}

