<?php

namespace App\Command;

use App\Entity\User;
use App\Enum\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create an admin user',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = new User();
        $user->setEmail('admin@admin.com');
        $user->setNom('Admin');
        $user->setRole(Role::ADMIN);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            'admin123'
        );

        $user->setPassword($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln('<info>Admin user created successfully!</info>');
        $output->writeln('Email: admin@admin.com');
        $output->writeln('Password: admin123');

        return Command::SUCCESS;
    }
}
