<?php

namespace App\Tests\Entity;

use App\Entity\Commande;
use PHPUnit\Framework\TestCase;

class CommandeTest extends TestCase
{
    public function testSyncDerivedFieldsBuildsIdentityKey(): void
    {
        $commande = (new Commande())
            ->setNom('  BEN ')
            ->setPrenom(' SALAH ')
            ->setNumtel(12345678)
            ->setStatut('draft');

        $commande->syncDerivedFields();

        $this->assertSame('ben|salah|12345678', $commande->getIdentityKey());
    }

    public function testPaidStatusClearsAiBlockingFields(): void
    {
        $blockedAt = new \DateTimeImmutable('-1 day');
        $blockedUntil = new \DateTimeImmutable('+2 days');

        $commande = (new Commande())
            ->setNom('User')
            ->setPrenom('One')
            ->setNumtel(11111111)
            ->setAiBlocked(true)
            ->setAiRiskScore(88.5)
            ->setAiBlockReason('High risk')
            ->markAiBlockedAt($blockedAt)
            ->defineAiBlockUntil($blockedUntil)
            ->setStatut('paid');

        $commande->syncDerivedFields();

        $this->assertFalse($commande->isAiBlocked());
        $this->assertNull($commande->getAiRiskScore());
        $this->assertNull($commande->getAiBlockReason());
        $this->assertNull($commande->getAiBlockedAt());
        $this->assertNull($commande->getAiBlockUntil());
    }
}

