<?php

namespace App\Controller\Admin;

use App\Entity\Tournoi;
use App\Repository\TournoiRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin', name: 'admin_')]
class TournoiAdminController extends AbstractController
{
    #[Route('/', name: 'dashboard')]
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
        $criteria = [];
        
        // Filter by game name (search)
        if ($request->query->get('game')) {
            $criteria['game'] = $request->query->get('game');
        }
        
        // Filter by tournament type (solo/squad)
        if ($request->query->get('type_tournoi')) {
            $criteria['type_tournoi'] = $request->query->get('type_tournoi');
        }
        
        // Filter by game type (FPS, Sports, etc.)
        if ($request->query->get('type_game')) {
            $criteria['type_game'] = $request->query->get('type_game');
        }
        
        // Filter by status (stored status column)
        $filterStatus = $request->query->get('status');
        
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
        
        // If filtering by current status (dynamic), filter in PHP
        if ($filterStatus) {
            $tournois = array_filter($tournois, function($tournoi) use ($filterStatus) {
                return $tournoi->getCurrentStatus() === $filterStatus;
            });
        }

        return $this->render('admin/tournoi/index.html.twig', [
            'tournois' => $tournois,
            'filterGame' => $request->query->get('game', ''),
            'filterTypeTournoi' => $request->query->get('type_tournoi', ''),
            'filterTypeGame' => $request->query->get('type_game', ''),
            'filterStatus' => $filterStatus,
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
    public function show(Tournoi $tournoi): Response
    {
        return $this->render('admin/tournoi/show.html.twig', [
            'tournoi' => $tournoi,
        ]);
    }

    #[Route('/tournoi/{id}/delete', name: 'tournoi_delete', methods: ['POST'])]
    public function delete(Tournoi $tournoi, EntityManagerInterface $em): Response
    {
        $em->remove($tournoi);
        $em->flush();

        $this->addFlash('success', 'Tournoi supprimé avec succès!');
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
}
