<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-users',
    description: 'Creates test users for development',
)]
class CreateTestUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Create admin user
        $admin = new User();
        $admin->setEmail('admin@tournoi.com');
        $admin->setUsername('Admin');
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin123'));
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $this->em->persist($admin);

        // Create regular users
        $user1 = new User();
        $user1->setEmail('user1@tournoi.com');
        $user1->setUsername('Player1');
        $user1->setPassword($this->hasher->hashPassword($user1, 'user123'));
        $user1->setRoles(['ROLE_USER']);
        $this->em->persist($user1);

        $user2 = new User();
        $user2->setEmail('user2@tournoi.com');
        $user2->setUsername('Player2');
        $user2->setPassword($this->hasher->hashPassword($user2, 'user123'));
        $user2->setRoles(['ROLE_USER']);
        $this->em->persist($user2);

        $this->em->flush();

        $output->writeln([
            '<info>Test users created successfully!</info>',
            '',
            '<comment>Admin credentials:</comment>',
            'Email: admin@tournoi.com',
            'Password: admin123',
            '',
            '<comment>User 1 credentials:</comment>',
            'Email: user1@tournoi.com',
            'Password: user123',
            '',
            '<comment>User 2 credentials:</comment>',
            'Email: user2@tournoi.com',
            'Password: user123',
        ]);

        return Command::SUCCESS;
    }
}
