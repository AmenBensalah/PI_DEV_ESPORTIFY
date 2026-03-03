<?php

namespace App\Tests\Entity;

use App\Entity\EventParticipant;
use App\Entity\Post;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class PostTest extends TestCase
{
    public function testEventFullWhenParticipantsReachLimit(): void
    {
        $post = (new Post())->setMaxParticipants(2);

        $p1 = (new EventParticipant())->setUser(
            (new User())->setEmail('u1@example.com')->setPassword('x')->setNom('U1')
        );
        $p2 = (new EventParticipant())->setUser(
            (new User())->setEmail('u2@example.com')->setPassword('x')->setNom('U2')
        );
        $p3 = (new EventParticipant())->setUser(
            (new User())->setEmail('u3@example.com')->setPassword('x')->setNom('U3')
        );

        $post->addEventParticipant($p1);
        $this->assertSame(1, $post->getParticipantsCount());
        $this->assertFalse($post->isEventFull());

        $post->addEventParticipant($p2);
        $this->assertSame(2, $post->getParticipantsCount());
        $this->assertTrue($post->isEventFull());

        $post->addEventParticipant($p3);
        $this->assertSame(3, $post->getParticipantsCount());
        $this->assertTrue($post->isEventFull());
    }

    public function testEventNeverFullWhenNoLimit(): void
    {
        $post = (new Post())->setMaxParticipants(null);
        $this->assertFalse($post->isEventFull());
    }
}

