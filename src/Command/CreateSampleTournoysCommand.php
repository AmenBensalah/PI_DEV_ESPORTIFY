<?php

namespace App\Command;

use App\Entity\Tournoi;
use App\Entity\TournoiMatch;
use App\Entity\TournoiMatchParticipantResult;
use App\Entity\User;
use App\Enum\Role;
use App\Repository\TournoiRepository;
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
        private UserRepository $userRepository,
        private TournoiRepository $tournoiRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $admin = $this->userRepository->findOneBy(['email' => 'admin@tournoi.com']);
        if (!$admin) {
            $admin = $this->userRepository->findOneBy(['role' => Role::ADMIN]);
        }
        if (!$admin) {
            $admin = $this->userRepository->findOneBy([]);
        }
        if (!$admin) {
            $output->writeln('<error>No user found to set as tournament creator.</error>');
            return Command::FAILURE;
        }
        if ($admin->getEmail() !== 'admin@tournoi.com') {
            $output->writeln(sprintf('<comment>Using %s as tournament creator.</comment>', (string) $admin->getEmail()));
        }

        $sampleTournoys = $this->sampleTournaments();
        $createdCount = 0;
        $skippedCount = 0;

        foreach ($sampleTournoys as $data) {
            if ($this->tournoiRepository->findOneBy(['name' => $data['name']]) !== null) {
                $skippedCount++;
                continue;
            }

            $this->em->persist($this->buildTournoi($admin, $data));
            $createdCount++;
        }

        // Ensure just-created tournaments are queryable before score seeding.
        $this->em->flush();

        $test1 = $this->resolveTestUser('test1');
        $test2 = $this->resolveTestUser('test2');
        $attachedParticipants = $this->attachTestUsersToSampleTournaments($sampleTournoys, $test1, $test2);

        [$extraTournois, $scoredMatches, $brResults] = $this->seedTestUsersScoredData($admin);
        $createdCount += $extraTournois;

        $this->em->flush();

        $output->writeln(sprintf('<info>Sample tournaments created: %d</info>', $createdCount));
        $output->writeln(sprintf('<comment>Skipped existing tournaments: %d</comment>', $skippedCount));
        $output->writeln(sprintf('<comment>test1/test2 participations attached: %d</comment>', $attachedParticipants));
        $output->writeln(sprintf('<comment>Scored test1/test2 matches ensured: %d</comment>', $scoredMatches));
        $output->writeln(sprintf('<comment>Battle royale placement rows ensured: %d</comment>', $brResults));
        return Command::SUCCESS;
    }

    /**
     * @return array<int, array{
     *   name:string,
     *   type_game:string,
     *   type_tournoi:string,
     *   game:string,
     *   status:string,
     *   prize:int,
     *   max_places:int,
     *   start_offset:string,
     *   end_offset:string
     * }>
     */
    private function sampleTournaments(): array
    {
        return [
            ['name' => 'CS2 Open Clash', 'type_game' => 'FPS', 'type_tournoi' => 'solo', 'game' => 'Counter-Strike 2', 'status' => 'active', 'prize' => 50000, 'max_places' => 16, 'start_offset' => '-3 days', 'end_offset' => '+4 days'],
            ['name' => 'Valorant Masters Prime', 'type_game' => 'FPS', 'type_tournoi' => 'solo', 'game' => 'Valorant', 'status' => 'planned', 'prize' => 75000, 'max_places' => 16, 'start_offset' => '+5 days', 'end_offset' => '+8 days'],
            ['name' => 'EAFC Solo Cup', 'type_game' => 'Sports', 'type_tournoi' => 'solo', 'game' => 'EA Sports FC 26', 'status' => 'active', 'prize' => 25000, 'max_places' => 32, 'start_offset' => '-1 day', 'end_offset' => '+2 days'],
            ['name' => 'Warzone Night Royale', 'type_game' => 'Battle_royale', 'type_tournoi' => 'solo', 'game' => 'Warzone', 'status' => 'active', 'prize' => 100000, 'max_places' => 24, 'start_offset' => '-2 days', 'end_offset' => '+5 days'],
            ['name' => 'Chess Masters Circuit', 'type_game' => 'Mind', 'type_tournoi' => 'solo', 'game' => 'Chess', 'status' => 'completed', 'prize' => 15000, 'max_places' => 32, 'start_offset' => '-25 days', 'end_offset' => '-20 days'],
            ['name' => 'League Rift Series', 'type_game' => 'Sports', 'type_tournoi' => 'solo', 'game' => 'League of Legends', 'status' => 'planned', 'prize' => 120000, 'max_places' => 20, 'start_offset' => '+7 days', 'end_offset' => '+12 days'],
            ['name' => 'Apex Frontier Showdown', 'type_game' => 'Battle_royale', 'type_tournoi' => 'solo', 'game' => 'Apex Legends', 'status' => 'planned', 'prize' => 42000, 'max_places' => 20, 'start_offset' => '+3 days', 'end_offset' => '+6 days'],
            ['name' => 'Rocket League Turbo League', 'type_game' => 'Sports', 'type_tournoi' => 'solo', 'game' => 'Rocket League', 'status' => 'active', 'prize' => 36000, 'max_places' => 16, 'start_offset' => '-2 days', 'end_offset' => '+4 days'],
            ['name' => 'Overwatch Payload Finals', 'type_game' => 'FPS', 'type_tournoi' => 'solo', 'game' => 'Overwatch 2', 'status' => 'planned', 'prize' => 47000, 'max_places' => 16, 'start_offset' => '+10 days', 'end_offset' => '+14 days'],
            ['name' => 'R6 Siege Breach Cup', 'type_game' => 'FPS', 'type_tournoi' => 'solo', 'game' => 'Rainbow Six Siege', 'status' => 'active', 'prize' => 54000, 'max_places' => 16, 'start_offset' => '-4 days', 'end_offset' => '+1 day'],
            ['name' => 'Tekken Iron Ladder', 'type_game' => 'Mind', 'type_tournoi' => 'solo', 'game' => 'Tekken 8', 'status' => 'planned', 'prize' => 18000, 'max_places' => 32, 'start_offset' => '+6 days', 'end_offset' => '+9 days'],
            ['name' => 'AI Duel FPS Score Cup', 'type_game' => 'FPS', 'type_tournoi' => 'solo', 'game' => 'Valorant', 'status' => 'completed', 'prize' => 5000, 'max_places' => 8, 'start_offset' => '-10 days', 'end_offset' => '-9 days'],
            ['name' => 'AI Duel Sports Score Cup', 'type_game' => 'Sports', 'type_tournoi' => 'solo', 'game' => 'EA Sports FC 26', 'status' => 'completed', 'prize' => 4500, 'max_places' => 8, 'start_offset' => '-8 days', 'end_offset' => '-7 days'],
            ['name' => 'AI Duel Battle Royale Cup', 'type_game' => 'Battle_royale', 'type_tournoi' => 'solo', 'game' => 'Fortnite', 'status' => 'completed', 'prize' => 6500, 'max_places' => 20, 'start_offset' => '-6 days', 'end_offset' => '-5 days'],
            ['name' => 'StarCraft Mind Arena', 'type_game' => 'Mind', 'type_tournoi' => 'solo', 'game' => 'StarCraft II', 'status' => 'completed', 'prize' => 16000, 'max_places' => 24, 'start_offset' => '-30 days', 'end_offset' => '-26 days'],
        ];
    }

    /**
     * @param array{
     *   name:string,
     *   type_game:string,
     *   type_tournoi:string,
     *   game:string,
     *   status:string,
     *   prize:int,
     *   max_places:int,
     *   start_offset:string,
     *   end_offset:string
     * } $data
     */
    private function buildTournoi(User $admin, array $data): Tournoi
    {
        $tournoi = new Tournoi();
        $tournoi->setName($data['name']);
        $tournoi->setTypeGame($data['type_game']);
        $tournoi->setTypeTournoi($data['type_tournoi']);
        $tournoi->setGame($data['game']);
        $tournoi->setStatus($data['status']);
        $tournoi->setPrizeWon((float) $data['prize']);
        $tournoi->setMaxPlaces($data['max_places']);
        $tournoi->setStartDate(new \DateTime($data['start_offset']));
        $tournoi->setEndDate(new \DateTime($data['end_offset']));
        $tournoi->setCreator($admin);

        return $tournoi;
    }

    /**
     * @return array{0:int,1:int,2:int} [created tournaments, played matches ensured, BR placement rows ensured]
     */
    private function seedTestUsersScoredData(User $admin): array
    {
        $test1 = $this->resolveTestUser('test1');
        $test2 = $this->resolveTestUser('test2');

        if (!$test1 || !$test2) {
            return [0, 0, 0];
        }

        $createdTournois = 0;
        $ensuredMatches = 0;
        $ensuredBrRows = 0;

        $fpsTournoi = $this->getOrCreateTournoi($admin, [
            'name' => 'AI Duel FPS Score Cup',
            'type_game' => 'FPS',
            'type_tournoi' => 'solo',
            'game' => 'Valorant',
            'status' => 'completed',
            'prize' => 5000,
            'max_places' => 8,
            'start_offset' => '-10 days',
            'end_offset' => '-9 days',
        ], $createdTournois);
        $fpsTournoi->addParticipant($test1)->addParticipant($test2);
        if ($this->ensureScoredMatch($fpsTournoi, $test1, $test2, 13, 10, '-10 days')) {
            $ensuredMatches++;
        }

        $sportsTournoi = $this->getOrCreateTournoi($admin, [
            'name' => 'AI Duel Sports Score Cup',
            'type_game' => 'Sports',
            'type_tournoi' => 'solo',
            'game' => 'EA Sports FC 26',
            'status' => 'completed',
            'prize' => 4500,
            'max_places' => 8,
            'start_offset' => '-8 days',
            'end_offset' => '-7 days',
        ], $createdTournois);
        $sportsTournoi->addParticipant($test1)->addParticipant($test2);
        if ($this->ensureScoredMatch($sportsTournoi, $test1, $test2, 2, 4, '-8 days')) {
            $ensuredMatches++;
        }

        $brTournoi = $this->getOrCreateTournoi($admin, [
            'name' => 'AI Duel Battle Royale Cup',
            'type_game' => 'Battle_royale',
            'type_tournoi' => 'solo',
            'game' => 'Fortnite',
            'status' => 'completed',
            'prize' => 6500,
            'max_places' => 20,
            'start_offset' => '-6 days',
            'end_offset' => '-5 days',
        ], $createdTournois);
        $brTournoi->addParticipant($test1)->addParticipant($test2);
        $brMatch = $this->findOrCreateMatch($brTournoi, $test1, $test2, '-6 days');
        $brMatch->setStatus('played');
        $brMatch->setScoreA(null);
        $brMatch->setScoreB(null);

        if ($this->ensureBrPlacement($brMatch, $test1, 'second', 1)) {
            $ensuredBrRows++;
        }
        if ($this->ensureBrPlacement($brMatch, $test2, 'first', 3)) {
            $ensuredBrRows++;
        }
        $ensuredMatches++;

        return [$createdTournois, $ensuredMatches, $ensuredBrRows];
    }

    private function resolveTestUser(string $name): ?User
    {
        $candidate = $this->userRepository->findOneBy(['pseudo' => $name]);
        if ($candidate instanceof User) {
            return $candidate;
        }

        $candidate = $this->userRepository->findOneBy(['nom' => $name]);
        if ($candidate instanceof User) {
            return $candidate;
        }

        return $this->userRepository->findOneBy(['email' => $name . '@tournoi.com']);
    }

    /**
     * @param array{
     *   name:string,
     *   type_game:string,
     *   type_tournoi:string,
     *   game:string,
     *   status:string,
     *   prize:int,
     *   max_places:int,
     *   start_offset:string,
     *   end_offset:string
     * } $data
     */
    private function getOrCreateTournoi(User $admin, array $data, int &$createdTournois): Tournoi
    {
        $existing = $this->tournoiRepository->findOneBy(['name' => $data['name']]);
        if ($existing instanceof Tournoi) {
            return $existing;
        }

        $tournoi = $this->buildTournoi($admin, $data);
        $this->em->persist($tournoi);
        $createdTournois++;

        return $tournoi;
    }

    private function ensureScoredMatch(Tournoi $tournoi, User $test1, User $test2, int $scoreA, int $scoreB, string $scheduledOffset): bool
    {
        if ($this->hasTestInTournamentName($tournoi)) {
            return false;
        }

        $match = $this->findOrCreateMatch($tournoi, $test1, $test2, $scheduledOffset);
        $match->setStatus('played');
        $match->setScoreA($scoreA);
        $match->setScoreB($scoreB);

        return true;
    }

    private function findOrCreateMatch(Tournoi $tournoi, User $test1, User $test2, string $scheduledOffset): TournoiMatch
    {
        $repo = $this->em->getRepository(TournoiMatch::class);
        $matches = $repo->findBy(['tournoi' => $tournoi]);

        foreach ($matches as $existing) {
            if (!$existing instanceof TournoiMatch) {
                continue;
            }

            $a = $existing->getPlayerA()?->getId();
            $b = $existing->getPlayerB()?->getId();
            $id1 = $test1->getId();
            $id2 = $test2->getId();

            if (($a === $id1 && $b === $id2) || ($a === $id2 && $b === $id1)) {
                return $existing;
            }
        }

        $match = new TournoiMatch();
        $match->setTournoi($tournoi);
        $match->setPlayerA($test1);
        $match->setPlayerB($test2);
        $match->setHomeName($this->displayName($test1));
        $match->setAwayName($this->displayName($test2));
        $match->setScheduledAt(new \DateTime($scheduledOffset));
        $this->em->persist($match);

        return $match;
    }

    private function ensureBrPlacement(TournoiMatch $match, User $participant, string $placement, int $points): bool
    {
        $tournoi = $match->getTournoi();
        if ($tournoi instanceof Tournoi && $this->hasTestInTournamentName($tournoi)) {
            return false;
        }

        $repo = $this->em->getRepository(TournoiMatchParticipantResult::class);
        $result = $repo->findOneBy([
            'match' => $match,
            'participant' => $participant,
        ]);

        if (!$result instanceof TournoiMatchParticipantResult) {
            $result = new TournoiMatchParticipantResult();
            $result->setMatch($match);
            $result->setParticipant($participant);
            $this->em->persist($result);
        }

        $result->setPlacement($placement);
        $result->setPoints($points);

        return true;
    }

    private function displayName(User $user): string
    {
        $pseudo = trim((string) $user->getPseudo());
        if ($pseudo !== '') {
            return $pseudo;
        }

        $nom = trim((string) $user->getNom());
        if ($nom !== '') {
            return $nom;
        }

        return (string) $user->getEmail();
    }

    /**
     * @param array<int, array{name:string}> $sampleTournoys
     */
    private function attachTestUsersToSampleTournaments(array $sampleTournoys, ?User $test1, ?User $test2): int
    {
        if (!$test1 || !$test2) {
            return 0;
        }

        $attached = 0;

        foreach ($sampleTournoys as $data) {
            $tournoi = $this->tournoiRepository->findOneBy(['name' => $data['name'] ?? '']);
            if (!$tournoi instanceof Tournoi) {
                continue;
            }

            if (!$tournoi->getParticipants()->contains($test1)) {
                $tournoi->addParticipant($test1);
                $attached++;
            }
            if (!$tournoi->getParticipants()->contains($test2)) {
                $tournoi->addParticipant($test2);
                $attached++;
            }
        }

        return $attached;
    }

    private function hasTestInTournamentName(Tournoi $tournoi): bool
    {
        return str_contains(mb_strtolower((string) $tournoi->getName()), 'test');
    }
}
