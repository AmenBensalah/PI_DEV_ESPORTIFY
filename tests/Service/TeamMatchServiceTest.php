<?php

namespace App\Tests\Service;

use App\Entity\Equipe;
use App\Service\TeamMatchService;
use PHPUnit\Framework\TestCase;

class TeamMatchServiceTest extends TestCase
{
    public function testRankTeamsOrdersByCompatibilityScore(): void
    {
        $service = new TeamMatchService();

        $best = (new Equipe())
            ->setNomEquipe('Best Team')
            ->setDescription('valorant tactical goals world competition')
            ->setTag('valorant tactical world')
            ->setRegion('EUW')
            ->setClassement('Or')
            ->setIsPrivate(false)
            ->setIsActive(true);

        $medium = (new Equipe())
            ->setNomEquipe('Medium Team')
            ->setDescription('valorant casual')
            ->setTag('valorant')
            ->setRegion('EUW')
            ->setClassement('Argent')
            ->setIsPrivate(false)
            ->setIsActive(true);

        $inactive = (new Equipe())
            ->setNomEquipe('Inactive Team')
            ->setDescription('valorant tactical')
            ->setTag('valorant')
            ->setRegion('EUW')
            ->setClassement('Or')
            ->setIsPrivate(false)
            ->setIsActive(false);

        $prefs = [
            'region' => 'EUW',
            'niveau' => 'Confirme',
            'game' => 'valorant',
            'play_style' => 'tactical',
            'goals' => 'world competition',
        ];

        $ranked = $service->rankTeams($prefs, [$medium, $best, $inactive]);

        $this->assertCount(2, $ranked);
        $this->assertSame('Best Team', $ranked[0]['team']->getNomEquipe());
        $this->assertGreaterThanOrEqual($ranked[1]['score'], $ranked[0]['score']);
        $this->assertGreaterThanOrEqual($ranked[1]['compatibility'], $ranked[0]['compatibility']);
    }

    public function testPrivateTeamGetsPenaltyButStillReturnsBoundedCompatibility(): void
    {
        $service = new TeamMatchService();

        $privateTeam = (new Equipe())
            ->setNomEquipe('Private Team')
            ->setDescription('fps tactical')
            ->setTag('fps tactical')
            ->setRegion('EUW')
            ->setClassement('Or')
            ->setIsPrivate(true)
            ->setIsActive(true);

        $prefs = [
            'region' => 'EUW',
            'niveau' => 'Confirme',
            'game' => 'fps',
            'play_style' => 'tactical',
            'goals' => 'ranked grind',
        ];

        $ranked = $service->rankTeams($prefs, [$privateTeam]);

        $this->assertCount(1, $ranked);
        $this->assertGreaterThanOrEqual(0, $ranked[0]['compatibility']);
        $this->assertLessThanOrEqual(100, $ranked[0]['compatibility']);
    }
}
