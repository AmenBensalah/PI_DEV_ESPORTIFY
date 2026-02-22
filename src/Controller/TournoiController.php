<?php

namespace App\Controller;

use App\Entity\Tournoi;
use App\Entity\TournoiMatch;
use App\Entity\User;
use App\Entity\ParticipationRequest;
use App\Repository\CandidatureRepository;
use App\Repository\EquipeRepository;
use App\Repository\ParticipationRequestRepository;
use App\Repository\TournoiMatchRepository;
use App\Repository\TournoiRepository;
use App\Service\SoloTournamentPredictionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tournoi', name: 'tournoi_')]
class TournoiController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(
        Request $request,
        TournoiRepository $tournoiRepository,
        ParticipationRequestRepository $prRepository,
        EquipeRepository $equipeRepository,
        CandidatureRepository $candidatureRepository
    ): Response
    {
        // Public listing with search and sorting
        $criteria = [];

        if ($request->query->get('game')) {
            $criteria['game'] = $request->query->get('game');
        }
        if ($request->query->get('type_tournoi')) {
            $criteria['type_tournoi'] = $request->query->get('type_tournoi');
        }
        if ($request->query->get('type_game')) {
            $criteria['type_game'] = $request->query->get('type_game');
        }

        // Sorting
        $allowedSorts = ['name', 'startDate'];
        $sort = $request->query->get('sort');
        $order = strtoupper($request->query->get('order', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        if ($sort && in_array($sort, $allowedSorts, true)) {
            $orderBy = [$sort => $order];
            $tournois = $tournoiRepository->findBy($criteria, $orderBy);
        } else {
            $tournois = $tournoiRepository->findBy($criteria);
        }

        // Filter by dynamic status if requested
        $filterStatus = $request->query->get('status');
        if ($filterStatus) {
            $tournois = array_filter($tournois, function($t) use ($filterStatus) {
                return $t->getCurrentStatus() === $filterStatus;
            });
        }

        $user = $this->getUser();
        $participantTournoiIds = [];
        $requestTournoiIds = [];
        $requestStatuses = [];
        $userHasTeam = false;

        if ($user) {
            $isManager = null !== $equipeRepository->findOneBy(['manager' => $user]);
            $acceptedMembership = $candidatureRepository->createQueryBuilder('c')
                ->select('c.id')
                ->andWhere('c.user = :user')
                ->andWhere('c.statut LIKE :acceptedPrefix')
                ->setParameter('user', $user)
                ->setParameter('acceptedPrefix', 'Accept%')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
            $userHasTeam = $isManager || (null !== $acceptedMembership);

            foreach ($tournois as $tournoi) {
                if ($tournoi->getParticipants()->contains($user)) {
                    $participantTournoiIds[$tournoi->getIdTournoi()] = true;
                }
            }

            $requests = $prRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
            foreach ($requests as $req) {
                $tournoiId = $req->getTournoi()->getIdTournoi();
                $requestTournoiIds[$tournoiId] = true;
                if (!isset($requestStatuses[$tournoiId])) {
                    $requestStatuses[$tournoiId] = $req->getStatus();
                }
            }
        }

        return $this->render('tournoi/user/index.html.twig', [
            'tournois' => $tournois,
            'filterGame' => $request->query->get('game', ''),
            'filterTypeTournoi' => $request->query->get('type_tournoi', ''),
            'filterTypeGame' => $request->query->get('type_game', ''),
            'filterStatus' => $filterStatus,
            'currentSort' => $sort,
            'currentOrder' => $order,
            'participantTournoiIds' => $participantTournoiIds,
            'requestTournoiIds' => $requestTournoiIds,
            'requestStatuses' => $requestStatuses,
            'userHasTeam' => $userHasTeam,
        ]);
    }

    #[Route('/participation/requests', name: 'participation_requests')]
    public function participationRequests(ParticipationRequestRepository $prRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Connecte-toi pour voir tes demandes.');
            return $this->redirectToRoute('tournoi_index');
        }

        $requests = $prRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('tournoi/user/requests.html.twig', [
            'requests' => $requests,
        ]);
    }

    #[Route('/participation/requests/{id}/edit', name: 'participation_request_edit', methods: ['GET', 'POST'])]
    public function editParticipationRequest(ParticipationRequest $requestEntity, Request $request, EntityManagerInterface $em): Response
    {
        if ($requestEntity->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Tu ne peux modifier que tes demandes.');
            return $this->redirectToRoute('tournoi_participation_requests');
        }

        if ($requestEntity->getStatus() !== 'pending') {
            $this->addFlash('error', 'Seules les demandes en attente peuvent etre modifiees.');
            return $this->redirectToRoute('tournoi_participation_requests');
        }

        if ($request->isMethod('GET')) {
            return $this->render('tournoi/user/request_edit.html.twig', [
                'requestEntity' => $requestEntity,
            ]);
        }

        $data = $request->request->all();
        $message = $data['message'] ?? null;
        $playerLevel = $data['player_level'] ?? null;
        $rulesAccepted = isset($data['rules_accepted']);

        $allowedLevels = ['amateur', 'debutant', 'pro'];
        if (!$rulesAccepted) {
            $this->addFlash('error', 'Vous devez accepter les regles du tournoi.');
            return $this->redirectToRoute('tournoi_participation_request_edit', ['id' => $requestEntity->getId()]);
        }
        if ($playerLevel && !in_array($playerLevel, $allowedLevels, true)) {
            $this->addFlash('error', 'Niveau de joueur invalide.');
            return $this->redirectToRoute('tournoi_participation_request_edit', ['id' => $requestEntity->getId()]);
        }

        $requestEntity->setMessage($message);
        $requestEntity->setPlayerLevel($playerLevel);
        $requestEntity->setRulesAccepted($rulesAccepted);
        $em->flush();

        $this->addFlash('success', 'Demande mise a jour.');
        return $this->redirectToRoute('tournoi_participation_requests');
    }

    #[Route('/participation/requests/{id}/delete', name: 'participation_request_delete', methods: ['POST'])]
    public function deleteParticipationRequest(ParticipationRequest $requestEntity, EntityManagerInterface $em): Response
    {
        if ($requestEntity->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Tu ne peux supprimer que tes demandes.');
            return $this->redirectToRoute('tournoi_participation_requests');
        }

        if ($requestEntity->getStatus() !== 'pending') {
            $this->addFlash('error', 'Seules les demandes en attente peuvent etre supprimees.');
            return $this->redirectToRoute('tournoi_participation_requests');
        }

        $em->remove($requestEntity);
        $em->flush();

        $this->addFlash('success', 'Demande supprimee.');
        return $this->redirectToRoute('tournoi_participation_requests');
    }

    #[Route('/categorie/{typeGame}', name: 'by_category')]
    public function byCategory(string $typeGame, TournoiRepository $tournoiRepository): Response
    {
        // Public category listing
        $tournois = $tournoiRepository->findBy(['type_game' => $typeGame]);

        return $this->render('tournoi/user/category.html.twig', [
            'tournois' => $tournois,
            'category' => $typeGame,
        ]);
    }

    #[Route('/{id}', name: 'show')]
    public function show(
        Tournoi $tournoi,
        TournoiMatchRepository $tournoiMatchRepository,
        SoloTournamentPredictionService $soloTournamentPredictionService,
        EquipeRepository $equipeRepository,
        CandidatureRepository $candidatureRepository
    ): Response
    {
        $participants = $this->getSortedParticipants($tournoi);
        $matches = $tournoiMatchRepository->findByTournoiOrdered($tournoi);
        $participantTeams = $this->buildParticipantTeams($tournoi, $equipeRepository, $candidatureRepository);
        $participantScores = $this->buildParticipantScores($tournoi, $matches, $participantTeams);
        $user = $this->getUser();
        $isParticipant = $user instanceof User && $tournoi->getParticipants()->contains($user);
        $soloPrediction = $soloTournamentPredictionService->predictWinner($tournoi);

        return $this->render('tournoi/user/show.html.twig', [
            'tournoi' => $tournoi,
            'participants' => $participants,
            'matches' => $matches,
            'participantScores' => $participantScores,
            'participantTeams' => $participantTeams,
            'isParticipant' => $isParticipant,
            'soloPrediction' => $soloPrediction,
        ]);
    }

    #[Route('/{id}/participate', name: 'participate', methods: ['POST','GET'])]
    public function participate(
        Tournoi $tournoi,
        Request $request,
        EntityManagerInterface $em,
        ParticipationRequestRepository $prRepository,
        EquipeRepository $equipeRepository,
        CandidatureRepository $candidatureRepository
    ): Response
    {
        $user = $this->getUser();

        if ($tournoi->getTypeTournoi() === 'squad') {
            if (!$user) {
                $this->addFlash('error', 'Tu dois etre dans une equipe pour participer');
                return $this->redirectToRoute('tournoi_show', ['id' => $tournoi->getIdTournoi()]);
            }

            $isManager = null !== $equipeRepository->findOneBy(['manager' => $user]);
            $acceptedMembership = $candidatureRepository->createQueryBuilder('c')
                ->select('c.id')
                ->andWhere('c.user = :user')
                ->andWhere('c.statut LIKE :acceptedPrefix')
                ->setParameter('user', $user)
                ->setParameter('acceptedPrefix', 'Accept%')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
            $isTeamMember = null !== $acceptedMembership;

            if (!$isManager && !$isTeamMember) {
                $this->addFlash('error', 'Tu dois etre dans une equipe pour participer');
                return $this->redirectToRoute('tournoi_show', ['id' => $tournoi->getIdTournoi()]);
            }
        }

        // prevent participation if tournament is not in planned state
        $status = $tournoi->getCurrentStatus();
        if ($status !== 'planned') {
            $this->addFlash('error', 'Vous ne pouvez pas participer a ce tournoi (statut: ' . $status . ').');
            return $this->redirectToRoute('tournoi_show', ['id' => $tournoi->getIdTournoi()]);
        }

        // prevent if no remaining places
        $remaining = $tournoi->getRemainingPlaces();
        if ($remaining !== null && $remaining <= 0) {
            $this->addFlash('error', 'Le nombre maximum de places est atteint.');
            return $this->redirectToRoute('tournoi_show', ['id' => $tournoi->getIdTournoi()]);
        }

        // show form
        if ($request->isMethod('GET')) {
            return $this->render('tournoi/user/participate.html.twig', [
                'tournoi' => $tournoi,
            ]);
        }

        // POST: create participation request
        $data = $request->request->all();

        // already participant?
        if ($user && $tournoi->getParticipants()->contains($user)) {
            $this->addFlash('success', 'Vous etes deja inscrit a ce tournoi.');
            return $this->redirectToRoute('tournoi_show', ['id' => $tournoi->getIdTournoi()]);
        }

        // existing request (any status)
        $existing = null;
        if ($user) {
            $existing = $prRepository->findOneBy(['user' => $user, 'tournoi' => $tournoi]);
        }
        if ($existing) {
            $this->addFlash('info', 'Vous avez deja une demande pour ce tournoi.');
            return $this->redirectToRoute('tournoi_show', ['id' => $tournoi->getIdTournoi()]);
        }

        $message = trim((string) ($data['message'] ?? ''));
        $playerLevel = $data['player_level'] ?? null;
        $rulesAccepted = isset($data['rules_accepted']);

        $allowedLevels = ['amateur', 'debutant', 'pro'];
        if (empty($playerLevel)) {
            $this->addFlash('error', 'Veuillez sélectionner votre niveau de joueur.');
            return $this->redirectToRoute('tournoi_participate', ['id' => $tournoi->getIdTournoi()]);
        }
        if (!$rulesAccepted) {
            $this->addFlash('error', 'Vous devez accepter les regles du tournoi.');
            return $this->redirectToRoute('tournoi_participate', ['id' => $tournoi->getIdTournoi()]);
        }
        if ($playerLevel && !in_array($playerLevel, $allowedLevels, true)) {
            $this->addFlash('error', 'Niveau de joueur invalide.');
            return $this->redirectToRoute('tournoi_participate', ['id' => $tournoi->getIdTournoi()]);
        }
        if ($message === '' || mb_strlen($message) < 10) {
            $this->addFlash('error', 'Veuillez saisir un message descriptif (min. 10 caractères).');
            return $this->redirectToRoute('tournoi_participate', ['id' => $tournoi->getIdTournoi()]);
        }

        $pr = new ParticipationRequest();
        if ($user) {
            $pr->setUser($user);
        }
        $pr->setPlayerLevel($playerLevel);
        $pr->setRulesAccepted($rulesAccepted);
        $pr->setTournoi($tournoi);
        $pr->setMessage($message);
        $pr->setStatus('pending');

        $em->persist($pr);
        $em->flush();

        $this->addFlash('success', 'Votre demande de participation a ete envoyee a l\'administration.');
        return $this->redirectToRoute('tournoi_show', ['id' => $tournoi->getIdTournoi()]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(Tournoi $tournoi): Response
    {
        return $this->render('tournoi/user/edit.html.twig', [
            'tournoi' => $tournoi,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete')]
    public function delete(Tournoi $tournoi): Response
    {
        return $this->render('tournoi/user/delete.html.twig', [
            'tournoi' => $tournoi,
        ]);
    }

    /**
     * @return array<int, User>
     */
    private function getSortedParticipants(Tournoi $tournoi): array
    {
        $participants = $tournoi->getParticipants()->toArray();
        usort($participants, static function (User $a, User $b): int {
            return strcasecmp((string) $a->getNom(), (string) $b->getNom());
        });

        return $participants;
    }

    /**
     * @param TournoiMatch[] $matches
     * @return array<int, int>
     */
    private function buildParticipantScores(Tournoi $tournoi, array $matches, array $participantTeams = []): array
    {
        $scoresById = [];
        $nameIndex = [];

        foreach ($tournoi->getParticipants() as $participant) {
            $participantId = $participant->getId();
            if ($participantId === null) {
                continue;
            }

            $scoresById[$participantId] = 0;

            $nomKey = $this->normalizeParticipantName($participant->getNom());
            if ($nomKey !== '') {
                $nameIndex[$nomKey][] = $participantId;
            }

            $pseudoKey = $this->normalizeParticipantName($participant->getPseudo());
            if ($pseudoKey !== '') {
                $nameIndex[$pseudoKey][] = $participantId;
            }

            if (isset($participantTeams[$participantId])) {
                $teamKey = $this->normalizeParticipantName((string) $participantTeams[$participantId]);
                if ($teamKey !== '' && $teamKey !== '-') {
                    $nameIndex[$teamKey][] = $participantId;
                }
            }
        }

        foreach ($matches as $match) {
            if ($match->getStatus() !== 'played' || $match->getScoreA() === null || $match->getScoreB() === null) {
                continue;
            }

            $homeKey = $this->normalizeParticipantName($match->getHomeName() ?? $match->getPlayerA()?->getNom());
            $awayKey = $this->normalizeParticipantName($match->getAwayName() ?? $match->getPlayerB()?->getNom());

            if ($homeKey !== '' && isset($nameIndex[$homeKey])) {
                foreach (array_unique($nameIndex[$homeKey]) as $participantId) {
                    $scoresById[$participantId] += $match->getScoreA();
                }
            }
            if ($awayKey !== '' && isset($nameIndex[$awayKey])) {
                foreach (array_unique($nameIndex[$awayKey]) as $participantId) {
                    $scoresById[$participantId] += $match->getScoreB();
                }
            }
        }

        foreach ($matches as $match) {
            foreach ($match->getParticipantResults() as $participantResult) {
                $participantId = $participantResult->getParticipant()?->getId();
                if ($participantId !== null && isset($scoresById[$participantId])) {
                    $scoresById[$participantId] += $participantResult->getPoints();
                }
            }
        }

        return $scoresById;
    }

    private function normalizeParticipantName(?string $value): string
    {
        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return '';
        }

        return mb_strtolower((string) preg_replace('/\s+/', ' ', $trimmed));
    }

    /**
     * @return array<int, string>
     */
    private function buildParticipantTeams(
        Tournoi $tournoi,
        EquipeRepository $equipeRepository,
        CandidatureRepository $candidatureRepository
    ): array {
        $teamsByParticipantId = [];
        if ($tournoi->getTypeTournoi() !== 'squad') {
            return $teamsByParticipantId;
        }

        foreach ($tournoi->getParticipants() as $participant) {
            $participantId = $participant->getId();
            if ($participantId === null) {
                continue;
            }

            $teamName = '-';
            $managerTeam = $equipeRepository->findOneBy(['manager' => $participant]);
            if ($managerTeam && $managerTeam->getNomEquipe()) {
                $teamName = (string) $managerTeam->getNomEquipe();
            } else {
                $acceptedMembership = $candidatureRepository->createQueryBuilder('c')
                    ->innerJoin('c.equipe', 'e')
                    ->andWhere('c.user = :user')
                    ->andWhere('c.statut LIKE :acceptedPrefix')
                    ->setParameter('user', $participant)
                    ->setParameter('acceptedPrefix', 'Accept%')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();

                if ($acceptedMembership && $acceptedMembership->getEquipe()?->getNomEquipe()) {
                    $teamName = (string) $acceptedMembership->getEquipe()->getNomEquipe();
                }
            }

            $teamsByParticipantId[$participantId] = $teamName;
        }

        return $teamsByParticipantId;
    }
}
