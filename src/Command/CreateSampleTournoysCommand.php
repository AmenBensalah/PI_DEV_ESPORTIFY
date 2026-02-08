<?php

namespace App\Command;

use App\Entity\Tournoi;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-sample-tournoys',
    description: 'Creates sample tournaments for the admin dashboard',
)]
class CreateSampleTournoysCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $admin = $this->userRepository->findOneBy(['email' => 'admin@tournoi.com']);
        if (!$admin) {
            $output->writeln('<error>Admin user not found!</error>');
            return Command::FAILURE;
        }

        $sampleTournoys = [
            [
                'name' => 'CS:GO Championship 2025',
                'type_game' => 'FPS',
                'type_tournoi' => 'squad',
                'game' => 'Counter-Strike 2',
                'status' => 'active',
                'prize' => 50000,
            ],
            [
                'name' => 'Valorant Masters',
                'type_game' => 'FPS',
                'type_tournoi' => 'squad',
                'game' => 'Valorant',
                'status' => 'planned',
                'prize' => 75000,
            ],
            [
                'name' => 'FIFA 25 World Cup',
                'type_game' => 'Sports',
                'type_tournoi' => 'solo',
                'game' => 'EA Sports FC 25',
                'status' => 'active',
                'prize' => 25000,
            ],
            [
                'name' => 'Warzone Battle Royale',
                'type_game' => 'Battle_royale',
                'type_tournoi' => 'squad',
                'game' => 'Warzone',
                'status' => 'active',
                'prize' => 100000,
            ],
            [
                'name' => 'Chess Masters 2025',
                'type_game' => 'Mind',
                'type_tournoi' => 'solo',
                'game' => 'Chess',
                'status' => 'completed',
                'prize' => 15000,
            ],
            [
                'name' => 'League of Legends Championship',
                'type_game' => 'Sports',
                'type_tournoi' => 'squad',
                'game' => 'League of Legends',
                'status' => 'planned',
                'prize' => 120000,
            ],
        ];

        foreach ($sampleTournoys as $data) {
            $tournoi = new Tournoi();
            $tournoi->setName($data['name']);
            $tournoi->setTypeGame($data['type_game']);
            $tournoi->setTypeTournoi($data['type_tournoi']);
            $tournoi->setGame($data['game']);
            $tournoi->setStatus($data['status']);
            $tournoi->setPrizeWon((float)$data['prize']);
            $tournoi->setStartDate(new \DateTime('-5 days'));
            $tournoi->setEndDate(new \DateTime('+10 days'));
            $tournoi->setCreator($admin);

            $this->em->persist($tournoi);
        }

        $this->em->flush();

        $output->writeln('<info>Sample tournaments created successfully!</info>');
        return Command::SUCCESS;
    }
}
