<?php

namespace App\Controller\Admin;

use App\Entity\ParticipationRequest;
use App\Entity\User;
use App\Enum\Role;
use App\Repository\ParticipationRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin/participation', name: 'admin_participation_')]
class ParticipationAdminController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(ParticipationRequestRepository $repo): Response
    {
        $requests = $repo->findBy([], ['createdAt' => 'DESC']);
        return $this->render('admin/participation/index.html.twig', [
            'requests' => $requests,
        ]);
    }

    #[Route('/{id}/approve', name: 'approve')]
    public function approve(ParticipationRequest $requestEntity, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $tournoi = $requestEntity->getTournoi();
        $user = $requestEntity->getUser();

        if ($user === null) {
            // Create a guest user so approval can decrement places
            $user = new User();
            $user->setEmail('guest_' . $requestEntity->getId() . '@example.local');
            $user->setNom('Guest ' . $requestEntity->getId());
            $user->setPseudo('guest_' . $requestEntity->getId());
            $user->setRole(Role::JOUEUR);
            $user->setPassword($passwordHasher->hashPassword($user, bin2hex(random_bytes(16))));
            $em->persist($user);
            $requestEntity->setUser($user);
        }

        // check remaining places and status
        if ($tournoi->getCurrentStatus() !== 'planned') {
            $this->addFlash('error', 'Impossible d\'approuver: le tournoi n\'est pas en statut planifié.');
            $requestEntity->setStatus('rejected');
            $em->flush();
            return $this->redirectToRoute('admin_participation_index');
        }

        $remaining = $tournoi->getRemainingPlaces();
        if ($remaining !== null && $remaining <= 0) {
            $this->addFlash('error', 'Impossible d\'approuver: plus de places disponibles.');
            $requestEntity->setStatus('rejected');
            $em->flush();
            return $this->redirectToRoute('admin_participation_index');
        }

        // approve: add participant
        if (!$tournoi->getParticipants()->contains($user)) {
            $tournoi->addParticipant($user);
        }
        $requestEntity->setStatus('approved');
        $em->persist($tournoi);
        $em->flush();

        $this->addFlash('success', 'Demande approuvée et participant ajouté.');
        return $this->redirectToRoute('admin_participation_index');
    }

    #[Route('/{id}/reject', name: 'reject')]
    public function reject(ParticipationRequest $requestEntity, EntityManagerInterface $em): Response
    {
        $requestEntity->setStatus('rejected');
        $em->flush();

        $this->addFlash('success', 'Demande rejetée.');
        return $this->redirectToRoute('admin_participation_index');
    }
}
