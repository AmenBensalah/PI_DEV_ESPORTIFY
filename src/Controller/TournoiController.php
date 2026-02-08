<?php

namespace App\Controller;

use App\Entity\Tournoi;
use App\Entity\ParticipationRequest;
use App\Repository\ParticipationRequestRepository;
use App\Repository\TournoiRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tournoi', name: 'tournoi_')]
class TournoiController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(Request $request, TournoiRepository $tournoiRepository, ParticipationRequestRepository $prRepository): Response
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

        if ($user) {
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
        ]);
    }

    #[Route('/participation/requests', name: 'participation_requests')]
    public function participationRequests(ParticipationRequestRepository $prRepository): Response
    {
        $requests = $prRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('tournoi/user/requests.html.twig', [
            'requests' => $requests,
        ]);
    }

    #[Route('/participation/requests/{id}/edit', name: 'participation_request_edit', methods: ['GET', 'POST'])]
    public function editParticipationRequest(ParticipationRequest $requestEntity, Request $request, EntityManagerInterface $em): Response
    {
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
    public function show(Tournoi $tournoi): Response
    {
        // Allow public viewing of tournament details
        return $this->render('tournoi/user/show.html.twig', [
            'tournoi' => $tournoi,
        ]);
    }

    #[Route('/{id}/participate', name: 'participate', methods: ['POST','GET'])]
    public function participate(Tournoi $tournoi, Request $request, EntityManagerInterface $em, ParticipationRequestRepository $prRepository): Response
    {
        $user = $this->getUser();

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

        $message = $data['message'] ?? null;
        $playerLevel = $data['player_level'] ?? null;
        $rulesAccepted = isset($data['rules_accepted']);

        $allowedLevels = ['amateur', 'debutant', 'pro'];
        if (!$rulesAccepted) {
            $this->addFlash('error', 'Vous devez accepter les regles du tournoi.');
            return $this->redirectToRoute('tournoi_participate', ['id' => $tournoi->getIdTournoi()]);
        }
        if ($playerLevel && !in_array($playerLevel, $allowedLevels, true)) {
            $this->addFlash('error', 'Niveau de joueur invalide.');
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
}
