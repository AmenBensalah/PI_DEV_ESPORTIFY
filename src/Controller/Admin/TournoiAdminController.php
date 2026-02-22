<?php

namespace App\Controller\Admin;

use App\Entity\TournoiMatch;
use App\Entity\TournoiMatchParticipantResult;
use App\Entity\Tournoi;
use App\Entity\User;
use App\Repository\CandidatureRepository;
use App\Repository\EquipeRepository;
use App\Repository\ParticipationRequestRepository;
use App\Repository\TournoiMatchParticipantResultRepository;
use App\Repository\TournoiMatchRepository;
use App\Repository\TournoiRepository;
use App\Repository\UserRepository;
use App\Service\SoloTournamentPredictionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin', name: 'admin_')]
class TournoiAdminController extends AbstractController
{
    #[Route('/tournoi/dashboard', name: 'tournoi_dashboard')]
    public function dashboard(TournoiRepository $tournoiRepository): Response
    {
        $tournois = $tournoiRepository->findAll();

        return $this->render('admin/dashboard.html.twig', [
            'tournois' => $tournois,
            'tournoiCount' => count($tournois),
            'equipeCount' => 12,
            'userCount' => 1250,
        ]);
    }

    #[Route('/tournoi', name: 'tournoi_index')]
    public function index(Request $request, TournoiRepository $tournoiRepository): Response
    {
        $filterStatus = trim((string)$request->query->get('status', ''));
        $sort = trim((string)$request->query->get('sort', ''));
        $order = strtoupper((string)$request->query->get('order', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $tournois = $tournoiRepository->findForAdminFilters([
            'q' => $request->query->get('q'),
            'game' => $request->query->get('game'),
            'type_tournoi' => $request->query->get('type_tournoi'),
            'type_game' => $request->query->get('type_game'),
            'sort' => $sort,
            'order' => $order,
        ]);

        if ($filterStatus !== '') {
            $tournois = array_values(array_filter($tournois, static function (Tournoi $tournoi) use ($filterStatus) {
                return $tournoi->getCurrentStatus() === $filterStatus;
            }));
        }

        return $this->render('admin/tournoi/index.html.twig', [
            'tournois' => $tournois,
            'filterQ' => $request->query->get('q', ''),
            'filterGame' => $request->query->get('game', ''),
            'filterTypeTournoi' => $request->query->get('type_tournoi', ''),
            'filterTypeGame' => $request->query->get('type_game', ''),
            'filterStatus' => $filterStatus,
            'currentSort' => $sort,
            'currentOrder' => $order,
        ]);
    }

    #[Route('/tournoi/search', name: 'tournoi_search', methods: ['GET'])]
    public function search(Request $request, TournoiRepository $tournoiRepository): JsonResponse
    {
        $filterStatus = trim((string)$request->query->get('status', ''));
        $sort = trim((string)$request->query->get('sort', ''));
        $order = strtoupper((string)$request->query->get('order', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $tournois = $tournoiRepository->findForAdminFilters([
            'q' => $request->query->get('q'),
            'game' => $request->query->get('game'),
            'type_tournoi' => $request->query->get('type_tournoi'),
            'type_game' => $request->query->get('type_game'),
            'sort' => $sort,
            'order' => $order,
        ]);

        if ($filterStatus !== '') {
            $tournois = array_values(array_filter($tournois, static function (Tournoi $tournoi) use ($filterStatus) {
                return $tournoi->getCurrentStatus() === $filterStatus;
            }));
        }

        $rowsHtml = $this->renderView('admin/tournoi/_table_rows.html.twig', [
            'tournois' => $tournois,
        ]);

        return $this->json([
            'rows' => $rowsHtml,
            'count' => count($tournois),
        ]);
    }

    #[Route('/tournoi/create', name: 'tournoi_create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $errors = [];
            
            // Validate name
            if (empty($data['name'])) {
                $errors[] = 'Le nom du tournoi est requis.';
            } elseif (strlen($data['name']) > 255) {
                $errors[] = 'Le nom du tournoi ne doit pas dépasser 255 caractères.';
            }
            
            // Validate type_tournoi
            if (empty($data['type_tournoi'])) {
                $errors[] = 'Le type de tournoi est requis.';
            } elseif (!in_array($data['type_tournoi'], ['solo', 'squad'])) {
                $errors[] = 'Le type de tournoi doit être "solo" ou "squad".';
            }
            
            // Validate type_game
            if (empty($data['type_game'])) {
                $errors[] = 'Le type de jeu est requis.';
            } elseif (!in_array($data['type_game'], ['FPS', 'Sports', 'Battle_royale', 'Mind'])) {
                $errors[] = 'Le type de jeu est invalide.';
            }
            
            // Validate game
            if (empty($data['game'])) {
                $errors[] = 'Le nom du jeu est requis.';
            } elseif (strlen($data['game']) > 255) {
                $errors[] = 'Le nom du jeu ne doit pas dépasser 255 caractères.';
            }
            
            // Validate startDate
            if (empty($data['startDate'])) {
                $errors[] = 'La date de début est requise.';
            } else {
                try {
                    $startDate = new \DateTime($data['startDate']);
                } catch (\Exception $e) {
                    $errors[] = 'La date de début est invalide.';
                    $startDate = null;
                }
            }
            
            // Validate endDate
            if (empty($data['endDate'])) {
                $errors[] = 'La date de fin est requise.';
            } else {
                try {
                    $endDate = new \DateTime($data['endDate']);
                } catch (\Exception $e) {
                    $errors[] = 'La date de fin est invalide.';
                    $endDate = null;
                }
            }
            
            // Validate endDate > startDate
            if (isset($startDate) && isset($endDate) && $endDate <= $startDate) {
                $errors[] = 'La date de fin doit être après la date de début.';
            }

            // Validate startDate and endDate are in the future
            $now = new \DateTime();
            if (isset($startDate) && $startDate <= $now) {
                $errors[] = 'La date de début doit être dans le futur.';
            }
            if (isset($endDate) && $endDate <= $now) {
                $errors[] = 'La date de fin doit être dans le futur.';
            }
            
            // Validate prize_won
            if (empty($data['prize_won'])) {
                $errors[] = 'La dotation est requise.';
            } else {
                $prize = filter_var($data['prize_won'], FILTER_VALIDATE_FLOAT);
                if ($prize === false || $prize < 0) {
                    $errors[] = 'La dotation doit être un nombre positif.';
                }
            }
            
            // Validate max_places (optional)
            if (!empty($data['max_places'])) {
                $maxPlaces = filter_var($data['max_places'], FILTER_VALIDATE_INT);
                if ($maxPlaces === false || $maxPlaces < 1) {
                    $errors[] = 'Le nombre de places doit être un entier positif.';
                }
            }
            
            // If there are validation errors, show them and redisplay the form
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('admin/tournoi/create.html.twig', [
                    'errors' => $errors,
                    'formData' => $data,
                ]);
            }
            
            // All validations passed, create the tournament
            $tournoi = new Tournoi();
            $tournoi->setName($data['name']);
            $tournoi->setTypeTournoi($data['type_tournoi']);
            $tournoi->setTypeGame($data['type_game']);
            $tournoi->setGame($data['game']);
            $tournoi->setStartDate(new \DateTime($data['startDate']));
            $tournoi->setEndDate(new \DateTime($data['endDate']));
            $tournoi->setStatus('planned');
            $tournoi->setPrizeWon((float)$data['prize_won']);
            $tournoi->setMaxPlaces(!empty($data['max_places']) ? (int)$data['max_places'] : null);
            
            $creator = $this->getUser();
            if (!$creator) {
                $creator = $userRepository->findOneBy(['email' => 'admin@tournoi.com']);
            }
            $tournoi->setCreator($creator);

            $em->persist($tournoi);
            $em->flush();

            $this->addFlash('success', 'Tournoi créé avec succès!');
            return $this->redirectToRoute('admin_tournoi_index');
        }

        return $this->render('admin/tournoi/create.html.twig');
    }

    #[Route('/tournoi/{id}/edit', name: 'tournoi_edit', methods: ['GET', 'POST'])]
    public function edit(Tournoi $tournoi, Request $request, EntityManagerInterface $em): Response
    {
        // only allow editing if the tournament is still planned
        if ($tournoi->getCurrentStatus() !== 'planned') {
            $this->addFlash('error', 'Ce tournoi ne peut pas être modifié (statut: ' . $tournoi->getCurrentStatus() . ').');
            return $this->redirectToRoute('admin_tournoi_show', ['id' => $tournoi->getIdTournoi()]);
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $errors = [];
            
            // Validate name
            if (empty($data['name'])) {
                $errors[] = 'Le nom du tournoi est requis.';
            } elseif (strlen($data['name']) > 255) {
                $errors[] = 'Le nom du tournoi ne doit pas dépasser 255 caractères.';
            }
            
            // Validate type_tournoi
            if (empty($data['type_tournoi'])) {
                $errors[] = 'Le type de tournoi est requis.';
            } elseif (!in_array($data['type_tournoi'], ['solo', 'squad'])) {
                $errors[] = 'Le type de tournoi doit être "solo" ou "squad".';
            }
            
            // Validate type_game
            if (empty($data['type_game'])) {
                $errors[] = 'Le type de jeu est requis.';
            } elseif (!in_array($data['type_game'], ['FPS', 'Sports', 'Battle_royale', 'Mind'])) {
                $errors[] = 'Le type de jeu est invalide.';
            }
            
            // Validate game
            if (empty($data['game'])) {
                $errors[] = 'Le nom du jeu est requis.';
            } elseif (strlen($data['game']) > 255) {
                $errors[] = 'Le nom du jeu ne doit pas dépasser 255 caractères.';
            }
            
            // Validate startDate
            if (empty($data['startDate'])) {
                $errors[] = 'La date de début est requise.';
            } else {
                try {
                    $startDate = new \DateTime($data['startDate']);
                } catch (\Exception $e) {
                    $errors[] = 'La date de début est invalide.';
                    $startDate = null;
                }
            }
            
            // Validate endDate
            if (empty($data['endDate'])) {
                $errors[] = 'La date de fin est requise.';
            } else {
                try {
                    $endDate = new \DateTime($data['endDate']);
                } catch (\Exception $e) {
                    $errors[] = 'La date de fin est invalide.';
                    $endDate = null;
                }
            }
            
            // Validate endDate > startDate
            if (isset($startDate) && isset($endDate) && $endDate <= $startDate) {
                $errors[] = 'La date de fin doit être après la date de début.';
            }

            // Validate startDate and endDate are in the future
            $now = new \DateTime();
            if (isset($startDate) && $startDate <= $now) {
                $errors[] = 'La date de début doit être dans le futur.';
            }
            if (isset($endDate) && $endDate <= $now) {
                $errors[] = 'La date de fin doit être dans le futur.';
            }
            
            // Validate prize_won
            if (empty($data['prize_won'])) {
                $errors[] = 'La dotation est requise.';
            } else {
                $prize = filter_var($data['prize_won'], FILTER_VALIDATE_FLOAT);
                if ($prize === false || $prize < 0) {
                    $errors[] = 'La dotation doit être un nombre positif.';
                }
            }
            
            // Validate max_places (optional)
            if (!empty($data['max_places'])) {
                $maxPlaces = filter_var($data['max_places'], FILTER_VALIDATE_INT);
                if ($maxPlaces === false || $maxPlaces < 1) {
                    $errors[] = 'Le nombre de places doit être un entier positif.';
                }
            }
            
            // If there are validation errors, show them and redisplay the form
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('admin/tournoi/edit.html.twig', [
                    'tournoi' => $tournoi,
                    'errors' => $errors,
                    'formData' => $data,
                ]);
            }
            
            // All validations passed, update the tournament
            $tournoi->setName($data['name']);
            $tournoi->setTypeTournoi($data['type_tournoi']);
            $tournoi->setTypeGame($data['type_game']);
            $tournoi->setGame($data['game']);
            $tournoi->setStartDate(new \DateTime($data['startDate']));
            $tournoi->setEndDate(new \DateTime($data['endDate']));
            $tournoi->setPrizeWon((float)$data['prize_won']);
            $tournoi->setMaxPlaces(!empty($data['max_places']) ? (int)$data['max_places'] : null);

            $em->flush();

            $this->addFlash('success', 'Tournoi mis à jour avec succès!');
            return $this->redirectToRoute('admin_tournoi_index');
        }

        return $this->render('admin/tournoi/edit.html.twig', [
            'tournoi' => $tournoi,
        ]);
    }

    #[Route('/tournoi/{id}/show', name: 'tournoi_show')]
    public function show(
        Tournoi $tournoi,
        TournoiMatchRepository $tournoiMatchRepository,
        SoloTournamentPredictionService $soloTournamentPredictionService,
        EquipeRepository $equipeRepository,
        CandidatureRepository $candidatureRepository
    ): Response
    {
        $participantTeams = $this->buildParticipantTeams($tournoi, $equipeRepository, $candidatureRepository);
        $participantScores = $this->buildParticipantScores(
            $tournoi,
            $tournoiMatchRepository->findByTournoiOrdered($tournoi),
            $participantTeams
        );
        $soloPrediction = $soloTournamentPredictionService->predictWinner($tournoi);

        return $this->render('admin/tournoi/show.html.twig', [
            'tournoi' => $tournoi,
            'participantScores' => $participantScores,
            'participantTeams' => $participantTeams,
            'soloPrediction' => $soloPrediction,
            'readonly' => false,
            'backRoute' => 'admin_tournoi_index',
        ]);
    }

    #[Route('/tournoi/{id}/preview', name: 'tournoi_preview', methods: ['GET'])]
    public function preview(
        Tournoi $tournoi,
        TournoiMatchRepository $tournoiMatchRepository,
        SoloTournamentPredictionService $soloTournamentPredictionService,
        EquipeRepository $equipeRepository,
        CandidatureRepository $candidatureRepository
    ): Response
    {
        $participantTeams = $this->buildParticipantTeams($tournoi, $equipeRepository, $candidatureRepository);
        $participantScores = $this->buildParticipantScores(
            $tournoi,
            $tournoiMatchRepository->findByTournoiOrdered($tournoi),
            $participantTeams
        );
        $soloPrediction = $soloTournamentPredictionService->predictWinner($tournoi);

        return $this->render('admin/tournoi/show.html.twig', [
            'tournoi' => $tournoi,
            'participantScores' => $participantScores,
            'participantTeams' => $participantTeams,
            'soloPrediction' => $soloPrediction,
            'readonly' => true,
            'backRoute' => 'admin_participation_index',
        ]);
    }

    #[Route('/tournoi/{id}/planning', name: 'tournoi_planning', methods: ['GET'])]
    public function planning(
        Tournoi $tournoi,
        TournoiMatchRepository $tournoiMatchRepository,
        SoloTournamentPredictionService $soloTournamentPredictionService,
        EquipeRepository $equipeRepository,
        CandidatureRepository $candidatureRepository
    ): Response
    {
        $participants = $this->getSortedParticipants($tournoi);
        $participantTeams = $this->buildParticipantTeams($tournoi, $equipeRepository, $candidatureRepository);
        $matches = $tournoiMatchRepository->findByTournoiOrdered($tournoi);
        $soloPrediction = $soloTournamentPredictionService->predictWinner($tournoi);

        return $this->render('admin/tournoi/planning.html.twig', [
            'tournoi' => $tournoi,
            'participants' => $participants,
            'participantTeams' => $participantTeams,
            'matches' => $matches,
            'soloPrediction' => $soloPrediction,
        ]);
    }

    #[Route('/tournoi/{id}/planning/match/{matchId}/participants', name: 'tournoi_match_participants', methods: ['GET'])]
    public function matchParticipants(
        Tournoi $tournoi,
        int $matchId,
        TournoiMatchRepository $tournoiMatchRepository,
        EquipeRepository $equipeRepository,
        CandidatureRepository $candidatureRepository
    ): Response {
        $match = $tournoiMatchRepository->find($matchId);
        if (!$match || $match->getTournoi()?->getIdTournoi() !== $tournoi->getIdTournoi()) {
            throw $this->createNotFoundException('Match introuvable pour ce tournoi.');
        }

        $participants = $this->getSortedParticipants($tournoi);
        $participantTeams = $this->buildParticipantTeams($tournoi, $equipeRepository, $candidatureRepository);
        $participantScores = $this->buildParticipantScores(
            $tournoi,
            $tournoiMatchRepository->findByTournoiOrdered($tournoi),
            $participantTeams
        );
        $matchPlacements = [];
        foreach ($match->getParticipantResults() as $result) {
            $participantId = $result->getParticipant()?->getId();
            if ($participantId !== null) {
                $matchPlacements[$participantId] = $result->getPlacement();
            }
        }

        return $this->render('admin/tournoi/match_participants.html.twig', [
            'tournoi' => $tournoi,
            'match' => $match,
            'participants' => $participants,
            'participantTeams' => $participantTeams,
            'participantScores' => $participantScores,
            'matchPlacements' => $matchPlacements,
        ]);
    }

    #[Route('/tournoi/{id}/planning/match/{matchId}/participants/{participantId}/placement', name: 'tournoi_match_participant_placement', methods: ['POST'])]
    public function setMatchParticipantPlacement(
        Tournoi $tournoi,
        int $matchId,
        int $participantId,
        Request $request,
        TournoiMatchRepository $tournoiMatchRepository,
        TournoiMatchParticipantResultRepository $participantResultRepository,
        EntityManagerInterface $em
    ): Response {
        $match = $tournoiMatchRepository->find($matchId);
        if (!$match || $match->getTournoi()?->getIdTournoi() !== $tournoi->getIdTournoi()) {
            throw $this->createNotFoundException('Match introuvable pour ce tournoi.');
        }

        $participant = null;
        foreach ($tournoi->getParticipants() as $candidate) {
            if ($candidate->getId() === $participantId) {
                $participant = $candidate;
                break;
            }
        }

        if (!$participant) {
            throw $this->createNotFoundException('Participant introuvable pour ce tournoi.');
        }

        $placement = strtolower(trim((string) $request->request->get('placement', '')));
        $pointsMap = [
            'first' => 3,
            'second' => 2,
            'third' => 1,
        ];

        if (!isset($pointsMap[$placement])) {
            return $this->redirectToRoute('admin_tournoi_match_participants', [
                'id' => $tournoi->getIdTournoi(),
                'matchId' => $match->getId(),
            ]);
        }

        $result = $participantResultRepository->findOneByMatchAndParticipant($match, $participant);
        if (!$result) {
            $result = new TournoiMatchParticipantResult();
            $result->setMatch($match);
            $result->setParticipant($participant);
            $em->persist($result);
        }

        $result->setPlacement($placement);
        $result->setPoints($pointsMap[$placement]);
        $match->setStatus('played');
        $em->flush();

        return $this->redirectToRoute('admin_tournoi_match_participants', [
            'id' => $tournoi->getIdTournoi(),
            'matchId' => $match->getId(),
        ]);
    }

    #[Route('/tournoi/{id}/planning/match/create', name: 'tournoi_match_create', methods: ['GET', 'POST'])]
    public function createMatch(
        Tournoi $tournoi,
        Request $request,
        EntityManagerInterface $em,
        EquipeRepository $equipeRepository,
        CandidatureRepository $candidatureRepository
    ): Response
    {
        $participants = $this->getSortedParticipants($tournoi);
        $participantTeams = $this->buildParticipantTeams($tournoi, $equipeRepository, $candidatureRepository);
        $formData = [];
        $errors = [];

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            $match = new TournoiMatch();
            $match->setTournoi($tournoi);

            $errors = $this->hydrateAndValidateMatch($match, $formData);

            if (empty($errors)) {
                $em->persist($match);
                $em->flush();

                return $this->redirectToRoute('admin_tournoi_planning', ['id' => $tournoi->getIdTournoi()]);
            }
        }

        return $this->render('admin/tournoi/match_form.html.twig', [
            'tournoi' => $tournoi,
            'participants' => $participants,
            'participantTeams' => $participantTeams,
            'matchNameSuggestions' => $this->buildMatchNameSuggestions($participants, $participantTeams, $tournoi->getTypeTournoi()),
            'errors' => $errors,
            'formData' => $formData,
            'isEdit' => false,
        ]);
    }

    #[Route('/tournoi/{id}/planning/match/{matchId}/edit', name: 'tournoi_match_edit', methods: ['GET', 'POST'])]
    public function editMatch(
        Tournoi $tournoi,
        int $matchId,
        Request $request,
        EntityManagerInterface $em,
        TournoiMatchRepository $tournoiMatchRepository,
        EquipeRepository $equipeRepository,
        CandidatureRepository $candidatureRepository
    ): Response {
        $match = $tournoiMatchRepository->find($matchId);

        if (!$match || $match->getTournoi()?->getIdTournoi() !== $tournoi->getIdTournoi()) {
            throw $this->createNotFoundException('Match introuvable pour ce tournoi.');
        }

        $participants = $this->getSortedParticipants($tournoi);
        $participantTeams = $this->buildParticipantTeams($tournoi, $equipeRepository, $candidatureRepository);
        $errors = [];
        $formData = [
            'home_name' => $match->getHomeName() ?? ($match->getPlayerA()?->getNom() ?? ''),
            'away_name' => $match->getAwayName() ?? ($match->getPlayerB()?->getNom() ?? ''),
            'scheduled_at' => $match->getScheduledAt() ? $match->getScheduledAt()->format('Y-m-d\TH:i') : '',
        ];

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            $errors = $this->hydrateAndValidateMatch($match, $formData);

            if (empty($errors)) {
                $em->flush();
                return $this->redirectToRoute('admin_tournoi_planning', ['id' => $tournoi->getIdTournoi()]);
            }
        }

        return $this->render('admin/tournoi/match_form.html.twig', [
            'tournoi' => $tournoi,
            'participants' => $participants,
            'participantTeams' => $participantTeams,
            'matchNameSuggestions' => $this->buildMatchNameSuggestions($participants, $participantTeams, $tournoi->getTypeTournoi()),
            'errors' => $errors,
            'formData' => $formData,
            'isEdit' => true,
            'match' => $match,
        ]);
    }

    #[Route('/tournoi/{id}/planning/match/{matchId}/result', name: 'tournoi_match_result', methods: ['POST'])]
    public function setMatchResult(
        Tournoi $tournoi,
        int $matchId,
        Request $request,
        TournoiMatchRepository $tournoiMatchRepository,
        EntityManagerInterface $em
    ): Response {
        $match = $tournoiMatchRepository->find($matchId);

        if (!$match || $match->getTournoi()?->getIdTournoi() !== $tournoi->getIdTournoi()) {
            throw $this->createNotFoundException('Match introuvable pour ce tournoi.');
        }

        $result = strtoupper(trim((string) $request->request->get('result', '')));
        if (!in_array($result, ['1', 'X', '2'], true)) {
            $this->addFlash('error', 'Resultat invalide. Choisissez 1, X ou 2.');
            return $this->redirectToRoute('admin_tournoi_planning', ['id' => $tournoi->getIdTournoi()]);
        }

        if ($result === '1') {
            $match->setScoreA(3);
            $match->setScoreB(0);
        } elseif ($result === '2') {
            $match->setScoreA(0);
            $match->setScoreB(3);
        } else {
            $match->setScoreA(1);
            $match->setScoreB(1);
        }

        $match->setStatus('played');

        $em->flush();

        return $this->redirectToRoute('admin_tournoi_planning', ['id' => $tournoi->getIdTournoi()]);
    }

    #[Route('/tournoi/{id}/planning/match/{matchId}/delete', name: 'tournoi_match_delete', methods: ['POST'])]
    public function deleteMatch(
        Tournoi $tournoi,
        int $matchId,
        TournoiMatchRepository $tournoiMatchRepository,
        EntityManagerInterface $em
    ): Response {
        $match = $tournoiMatchRepository->find($matchId);

        if (!$match || $match->getTournoi()?->getIdTournoi() !== $tournoi->getIdTournoi()) {
            throw $this->createNotFoundException('Match introuvable pour ce tournoi.');
        }

        $em->remove($match);
        $em->flush();

        return $this->redirectToRoute('admin_tournoi_planning', ['id' => $tournoi->getIdTournoi()]);
    }

    #[Route('/tournoi/{id}/delete', name: 'tournoi_delete', methods: ['POST'])]
    public function delete(
        Tournoi $tournoi,
        EntityManagerInterface $em,
        ParticipationRequestRepository $participationRequestRepository
    ): Response
    {
        try {
            $em->wrapInTransaction(function () use ($em, $tournoi, $participationRequestRepository): void {
                $requests = $participationRequestRepository->findBy(['tournoi' => $tournoi]);
                foreach ($requests as $request) {
                    $em->remove($request);
                }

                if ($tournoi->getResultat() !== null) {
                    $em->remove($tournoi->getResultat());
                }

                $em->remove($tournoi);
            });
        } catch (\Throwable) {
            $this->addFlash('error', 'Impossible de supprimer ce tournoi car des donnees liees existent encore.');
            return $this->redirectToRoute('admin_tournoi_index');
        }

        $this->addFlash('success', 'Tournoi supprime avec succes!');
        return $this->redirectToRoute('admin_tournoi_index');
    }

    #[Route('/tournoi/categorie/{typeGame}', name: 'tournoi_by_category')]
    public function byCategory(string $typeGame, TournoiRepository $tournoiRepository): Response
    {
        $tournois = $tournoiRepository->findBy(['type_game' => $typeGame]);

        return $this->render('admin/tournoi/category.html.twig', [
            'tournois' => $tournois,
            'category' => $typeGame,
        ]);
    }

    #[Route('/tournoi/categorie/{typeGame}/{typeTournoi}', name: 'tournoi_by_sub_category')]
    public function bySubCategory(string $typeGame, string $typeTournoi, TournoiRepository $tournoiRepository): Response
    {
        $tournois = $tournoiRepository->findBy(['type_game' => $typeGame, 'type_tournoi' => $typeTournoi]);

        return $this->render('admin/tournoi/sub_category.html.twig', [
            'tournois' => $tournois,
            'category' => $typeGame,
            'subCategory' => $typeTournoi,
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
     * @param array<string, mixed> $data
     * @return string[]
     */
    private function hydrateAndValidateMatch(TournoiMatch $match, array $data): array
    {
        $errors = [];
        $homeName = trim((string) ($data['home_name'] ?? ''));
        $awayName = trim((string) ($data['away_name'] ?? ''));
        $scheduledAtRaw = trim((string) ($data['scheduled_at'] ?? ''));
        $tournoi = $match->getTournoi();
        $isBattleRoyale = $tournoi && $tournoi->getTypeGame() === 'Battle_royale';

        if ($homeName === '') {
            $errors[] = $isBattleRoyale
                ? 'Le nom de la map est obligatoire.'
                : 'Le nom A domicile est obligatoire.';
        } else {
            $match->setHomeName($homeName);
        }

        if ($isBattleRoyale) {
            $match->setAwayName(null);
        } elseif ($awayName === '') {
            $errors[] = 'Le nom A l\'exterieur est obligatoire.';
        } else {
            $match->setAwayName($awayName);
        }

        if ($scheduledAtRaw === '') {
            $errors[] = 'La date du match est obligatoire.';
        } else {
            try {
                $scheduledAt = new \DateTime($scheduledAtRaw);
                $match->setScheduledAt($scheduledAt);

                if ($tournoi && $tournoi->getStartDate() && $tournoi->getEndDate()) {
                    $startDate = $tournoi->getStartDate();
                    $endDate = $tournoi->getEndDate();

                    if ($scheduledAt < $startDate || $scheduledAt > $endDate) {
                        $errors[] = sprintf(
                            'La date du match doit etre entre le %s et le %s.',
                            $startDate->format('d/m/Y H:i'),
                            $endDate->format('d/m/Y H:i')
                        );
                    }
                }
            } catch (\Throwable) {
                $errors[] = 'La date du match est invalide.';
            }
        }

        // Manual naming mode: user links and scores are not set from this form.
        $match->setPlayerA(null);
        $match->setPlayerB(null);
        if ($match->getStatus() === '') {
            $match->setStatus('planned');
        }
        $match->setScoreA(null);
        $match->setScoreB(null);

        return $errors;
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
     * @param array<int, User> $participants
     * @param array<int, string> $participantTeams
     * @return string[]
     */
    private function buildMatchNameSuggestions(array $participants, array $participantTeams, string $tournoiType): array
    {
        $values = [];

        if ($tournoiType === 'squad') {
            foreach ($participants as $participant) {
                $participantId = $participant->getId();
                if ($participantId === null || !isset($participantTeams[$participantId])) {
                    continue;
                }

                $teamName = trim((string) $participantTeams[$participantId]);
                if ($teamName !== '' && $teamName !== '-') {
                    $values[] = $teamName;
                }
            }
        } else {
            foreach ($participants as $participant) {
                $nom = trim((string) $participant->getNom());
                if ($nom !== '') {
                    $values[] = $nom;
                }
                $pseudo = trim((string) $participant->getPseudo());
                if ($pseudo !== '') {
                    $values[] = $pseudo;
                }
            }
        }

        $values = array_values(array_unique($values));
        sort($values, SORT_NATURAL | SORT_FLAG_CASE);

        return $values;
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

