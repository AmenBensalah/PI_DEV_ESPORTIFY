<?php

namespace App\Tests\Entity;

use App\Entity\Post;
use App\Entity\User;
use App\Enum\Role;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testSetRolesFallsBackToJoueurWhenInvalid(): void
    {
        $user = (new User())
            ->setEmail('u@example.com')
            ->setPassword('hash')
            ->setNom('User');

        $user->setRoles(['NOT_A_REAL_ROLE']);

        $this->assertSame([Role::JOUEUR->value], $user->getRoles());
        $this->assertSame(Role::JOUEUR, $user->getRole());
    }

    public function testSavedPostsHelpersWork(): void
    {
        $user = (new User())
            ->setEmail('u2@example.com')
            ->setPassword('hash')
            ->setNom('User2');
        $post = new Post();

        $this->assertFalse($user->hasSavedPost($post));

        $user->addSavedPost($post);
        $this->assertTrue($user->hasSavedPost($post));

        $user->removeSavedPost($post);
        $this->assertFalse($user->hasSavedPost($post));
    }
}

