<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // Create admin user
        $admin = new User();
        $admin->setEmail('admin@tournoi.com');
        $admin->setUsername('Admin');
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin123'));
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $manager->persist($admin);

        // Create regular users
        $user1 = new User();
        $user1->setEmail('user1@tournoi.com');
        $user1->setUsername('Player1');
        $user1->setPassword($this->hasher->hashPassword($user1, 'user123'));
        $user1->setRoles(['ROLE_USER']);
        $manager->persist($user1);

        $user2 = new User();
        $user2->setEmail('user2@tournoi.com');
        $user2->setUsername('Player2');
        $user2->setPassword($this->hasher->hashPassword($user2, 'user123'));
        $user2->setRoles(['ROLE_USER']);
        $manager->persist($user2);

        $manager->flush();
    }
}
