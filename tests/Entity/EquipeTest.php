<?php

namespace App\Tests\Entity;

use App\Entity\Candidature;
use App\Entity\Equipe;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class EquipeTest extends TestCase
{
    public function testGetMembresIncludesManagerAndExcludesPendingCandidates(): void
    {
        $manager = (new User())
            ->setEmail('manager@example.com')
            ->setPassword('hash')
            ->setNom('Manager');

        $pendingUser = (new User())
            ->setEmail('pending@example.com')
            ->setPassword('hash')
            ->setNom('Pending');

        $pending = (new Candidature())
            ->setStatut('En attente')
            ->setUser($pendingUser)
            ->setNiveau('Intermediaire')
            ->setMotivation('Motivation longue')
            ->setReason('Reason')
            ->setPlayStyle('Teamplay');

        $equipe = (new Equipe())
            ->setNomEquipe('Team Test')
            ->setDescription('Description')
            ->setClassement('Or')
            ->setManager($manager);

        $equipe->addCandidature($pending);

        $membres = $equipe->getMembres();

        $this->assertTrue($membres->contains($manager));
        $this->assertFalse($membres->contains($pendingUser));
        $this->assertSame(1, $membres->count());
    }

    public function testEstablishDateCreationUsesProvidedValue(): void
    {
        $date = new \DateTimeImmutable('2025-01-01 10:00:00');
        $equipe = (new Equipe())
            ->setNomEquipe('Team Date')
            ->setDescription('Desc')
            ->setClassement('Argent')
            ->establishDateCreation($date);

        $this->assertSame($date, $equipe->getDateCreation());
    }
}
