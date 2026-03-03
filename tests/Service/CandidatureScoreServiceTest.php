<?php

namespace App\Tests\Service;

use App\Entity\Candidature;
use App\Entity\Equipe;
use App\Entity\User;
use App\Service\CandidatureScoreService;
use PHPUnit\Framework\TestCase;

class CandidatureScoreServiceTest extends TestCase
{
    public function testScoreIsClampedTo100ForStrongCandidate(): void
    {
        $service = new CandidatureScoreService();

        $team = (new Equipe())
            ->setClassement('Or')
            ->setRegion('EUW')
            ->setNomEquipe('Team Alpha')
            ->setDescription('Competitive ranked team')
            ->setTag('ranked');

        $candidate = (new Candidature())
            ->setNiveau('Confirme')
            ->setReason(str_repeat('A', 130))
            ->setPlayStyle('Agressif strategique equipe')
            ->setMotivation(str_repeat('B', 50))
            ->setRegion('euw')
            ->setDisponibilite('elevee')
            ->setReasonAiScore(100);

        $user = (new User())
            ->setEmail('candidate@example.com')
            ->setPassword('hash')
            ->setNom('Candidate')
            ->setPseudo('pro_player');
        $candidate->setUser($user);

        $result = $service->score($candidate, $team);

        $this->assertSame(100, $result['score']);
        $this->assertNotEmpty($result['reasons']);
    }

    public function testScoreStaysLowForWeakCandidateProfile(): void
    {
        $service = new CandidatureScoreService();

        $team = (new Equipe())
            ->setClassement('Diamant')
            ->setRegion('EUW')
            ->setNomEquipe('Team Diamond')
            ->setDescription('High level team')
            ->setTag('elite');

        $candidate = (new Candidature())
            ->setNiveau('Debutant')
            ->setReason('short')
            ->setPlayStyle('casual')
            ->setMotivation('ok')
            ->setRegion('NA')
            ->setDisponibilite('faible');

        $result = $service->score($candidate, $team);

        $this->assertLessThanOrEqual(30, $result['score']);
        $this->assertGreaterThanOrEqual(0, $result['score']);
    }
}

