<?php

namespace App\Tests\Entity;

use App\Entity\Tournoi;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class TournoiTest extends TestCase
{
    public function testRemainingPlacesWithLimit(): void
    {
        $tournoi = (new Tournoi())
            ->setName('Solo Cup')
            ->setTypeTournoi('Solo')
            ->setTypeGame('FPS')
            ->setGame('Valorant')
            ->setStatus('open')
            ->setPrizeWon(1000.0)
            ->setMaxPlaces(2)
            ->planSchedule(
                new \DateTimeImmutable('+1 day'),
                new \DateTimeImmutable('+2 days')
            );

        $u1 = (new User())->setEmail('a@example.com')->setPassword('x')->setNom('A');
        $u2 = (new User())->setEmail('b@example.com')->setPassword('x')->setNom('B');
        $u3 = (new User())->setEmail('c@example.com')->setPassword('x')->setNom('C');

        $tournoi->addParticipant($u1);
        $this->assertSame(1, $tournoi->getRemainingPlaces());

        $tournoi->addParticipant($u2);
        $this->assertSame(0, $tournoi->getRemainingPlaces());

        $tournoi->addParticipant($u3);
        $this->assertSame(0, $tournoi->getRemainingPlaces());
    }

    public function testCurrentStatusTransitions(): void
    {
        $planned = (new Tournoi())
            ->planSchedule(new \DateTimeImmutable('+1 day'), new \DateTimeImmutable('+2 days'));
        $this->assertSame('planned', $planned->getCurrentStatus());

        $finished = (new Tournoi())
            ->planSchedule(new \DateTimeImmutable('-3 days'), new \DateTimeImmutable('-1 day'));
        $this->assertSame('finished', $finished->getCurrentStatus());

        $ongoing = (new Tournoi())
            ->planSchedule(new \DateTimeImmutable('-1 hour'), new \DateTimeImmutable('+1 hour'));
        $this->assertSame('ongoing', $ongoing->getCurrentStatus());
    }
}

