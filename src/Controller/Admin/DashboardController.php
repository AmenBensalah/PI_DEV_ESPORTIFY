<?php

namespace App\Controller\Admin;

use App\Form\User1Type;
use App\Repository\AnnouncementRepository;
use App\Repository\CommentaireRepository;
use App\Repository\EquipeRepository;
use App\Repository\EventParticipantRepository;
use App\Repository\LikeRepository;
use App\Repository\ManagerRequestRepository;
use App\Repository\NotificationRepository;
use App\Repository\PaymentRepository;
use App\Repository\PostRepository;
use App\Repository\TournoiRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface as ORMEntityManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_root')]
    public function root(): Response
    {
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function index(
        UserRepository $userRepository,
        EquipeRepository $equipeRepository,
        ManagerRequestRepository $managerRequestRepository,
        PostRepository $postRepository,
        CommentaireRepository $commentaireRepository,
        AnnouncementRepository $announcementRepository,
        TournoiRepository $tournoiRepository,
        PaymentRepository $paymentRepository,
        NotificationRepository $notificationRepository,
        LikeRepository $likeRepository,
        EventParticipantRepository $eventParticipantRepository,
        ORMEntityManagerInterface $orm
    ): Response {
        $now = new \DateTimeImmutable('now');
        $periodStart = (new \DateTimeImmutable('first day of this month 00:00:00'))->modify('-5 months');
        $periodEnd = new \DateTimeImmutable('last day of this month 23:59:59');

        $monthKeys = [];
        $monthLabels = [];
        for ($i = 0; $i < 6; $i++) {
            $m = $periodStart->modify('+' . $i . ' months');
            $monthKeys[] = $m->format('Y-m');
            $monthLabels[] = $m->format('M Y');
        }

        $postsByMonth = $this->buildMonthlySeries(
            $this->fetchDateRows($postRepository, 'p', 'createdAt', $periodStart, $periodEnd),
            $monthKeys
        );
        $commentsByMonth = $this->buildMonthlySeries(
            $this->fetchDateRows($commentaireRepository, 'c', 'createdAt', $periodStart, $periodEnd),
            $monthKeys
        );
        $announcementsByMonth = $this->buildMonthlySeries(
            $this->fetchDateRows($announcementRepository, 'a', 'createdAt', $periodStart, $periodEnd),
            $monthKeys
        );
        $paymentsByMonth = $this->buildMonthlySeries(
            $this->fetchDateRows($paymentRepository, 'pay', 'createdAt', $periodStart, $periodEnd),
            $monthKeys
        );
        $managerRequestsByMonth = $this->buildMonthlySeries(
            $this->fetchDateRows($managerRequestRepository, 'mr', 'createdAt', $periodStart, $periodEnd),
            $monthKeys
        );
        $teamsByMonth = $this->buildMonthlySeries(
            $this->fetchDateRows($equipeRepository, 'e', 'dateCreation', $periodStart, $periodEnd),
            $monthKeys
        );
        $tournoisByMonth = $this->buildMonthlySeries(
            $this->fetchDateRows($tournoiRepository, 't', 'startDate', $periodStart, $periodEnd),
            $monthKeys
        );
        $notificationsByMonth = $this->buildMonthlySeries(
            $this->fetchDateRows($notificationRepository, 'n', 'createdAt', $periodStart, $periodEnd),
            $monthKeys
        );

        $roleDistributionRows = $orm->createQueryBuilder()
            ->select('u.role AS role, COUNT(u.id) AS total')
            ->from(\App\Entity\User::class, 'u')
            ->groupBy('u.role')
            ->getQuery()
            ->getScalarResult();
        $roleDistribution = [];
        foreach ($roleDistributionRows as $row) {
            $roleDistribution[(string) $row['role']] = (int) $row['total'];
        }

        $requestStatusRows = $orm->createQueryBuilder()
            ->select('mr.status AS status, COUNT(mr.id) AS total')
            ->from(\App\Entity\ManagerRequest::class, 'mr')
            ->groupBy('mr.status')
            ->getQuery()
            ->getScalarResult();
        $requestStatus = ['pending' => 0, 'accepted' => 0, 'rejected' => 0];
        foreach ($requestStatusRows as $row) {
            $key = strtolower((string) $row['status']);
            $requestStatus[$key] = (int) $row['total'];
        }

        $paymentStatusRows = $orm->createQueryBuilder()
            ->select('p.status AS status, COUNT(p.id) AS total')
            ->from(\App\Entity\Payment::class, 'p')
            ->groupBy('p.status')
            ->getQuery()
            ->getScalarResult();
        $paymentStatus = [];
        foreach ($paymentStatusRows as $row) {
            $paymentStatus[(string) $row['status']] = (int) $row['total'];
        }

        $topAuthorsRows = $orm->createQueryBuilder()
            ->select('COALESCE(u.pseudo, u.nom, u.email) AS authorName, COUNT(p.id) AS total')
            ->from(\App\Entity\Post::class, 'p')
            ->leftJoin('p.author', 'u')
            ->groupBy('u.id')
            ->orderBy('total', 'DESC')
            ->setMaxResults(6)
            ->getQuery()
            ->getScalarResult();

        $topAuthors = [];
        foreach ($topAuthorsRows as $row) {
            $topAuthors[] = [
                'name' => (string) ($row['authorName'] ?? 'Utilisateur'),
                'total' => (int) ($row['total'] ?? 0),
            ];
        }

        $paymentTotalRows = $orm->createQueryBuilder()
            ->select('COALESCE(SUM(p.amount), 0) AS total')
            ->from(\App\Entity\Payment::class, 'p')
            ->getQuery()
            ->getScalarResult();
        $paymentTotal = isset($paymentTotalRows[0]['total']) ? (float) $paymentTotalRows[0]['total'] : 0.0;

        $monthStart = new \DateTimeImmutable('first day of this month 00:00:00');
        $monthEnd = new \DateTimeImmutable('last day of this month 23:59:59');
        $paymentMonthRows = $orm->createQueryBuilder()
            ->select('COALESCE(SUM(p.amount), 0) AS total')
            ->from(\App\Entity\Payment::class, 'p')
            ->andWhere('p.createdAt BETWEEN :from AND :to')
            ->setParameter('from', $monthStart)
            ->setParameter('to', $monthEnd)
            ->getQuery()
            ->getScalarResult();
        $paymentMonth = isset($paymentMonthRows[0]['total']) ? (float) $paymentMonthRows[0]['total'] : 0.0;

        $totalUsers = $userRepository->count([]);
        $totalTeams = $equipeRepository->count([]);
        $totalPosts = $postRepository->count([]);
        $totalComments = $commentaireRepository->count([]);
        $totalAnnouncements = $announcementRepository->count([]);
        $totalTournois = $tournoiRepository->count([]);
        $totalNotifications = $notificationRepository->count([]);
        $totalLikes = $likeRepository->count([]);
        $totalParticipations = $eventParticipantRepository->count([]);
        $pendingRequests = $requestStatus['pending'] ?? 0;

        return $this->render('admin/dashboard/index.html.twig', [
            'kpis' => [
                'users' => $totalUsers,
                'teams' => $totalTeams,
                'posts' => $totalPosts,
                'comments' => $totalComments,
                'announcements' => $totalAnnouncements,
                'tournois' => $totalTournois,
                'notifications' => $totalNotifications,
                'pendingRequests' => $pendingRequests,
                'paymentsCount' => $paymentRepository->count([]),
                'paymentsTotal' => $paymentTotal,
                'paymentsMonth' => $paymentMonth,
                'likes' => $totalLikes,
                'participations' => $totalParticipations,
            ],
            'latestUsers' => $userRepository->findBy([], ['id' => 'DESC'], 6),
            'latestEquipes' => $equipeRepository->findBy([], ['id' => 'DESC'], 6),
            'topAuthors' => $topAuthors,
            'roleDistribution' => $roleDistribution,
            'requestStatus' => $requestStatus,
            'paymentStatus' => $paymentStatus,
            'chartData' => [
                'labels' => $monthLabels,
                'activity' => [
                    'posts' => $postsByMonth,
                    'comments' => $commentsByMonth,
                    'announcements' => $announcementsByMonth,
                ],
                'operations' => [
                    'requests' => $managerRequestsByMonth,
                    'payments' => $paymentsByMonth,
                    'teams' => $teamsByMonth,
                    'tournois' => $tournoisByMonth,
                    'notifications' => $notificationsByMonth,
                ],
            ],
            'generatedAt' => $now,
        ]);
    }

    #[Route('/admin/user-ai/retrain', name: 'admin_user_ai_retrain', methods: ['POST'])]
    public function retrainUserAi(Request $request): Response
    {
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('admin_user_ai_retrain', $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide. Reessayez.');
            return $this->redirectToRoute('admin_dashboard');
        }

        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $php = (defined('PHP_BINARY') && is_string(PHP_BINARY) && PHP_BINARY !== '') ? PHP_BINARY : 'php';

        $process = new Process([
            $php,
            $projectDir . '/bin/console',
            'app:user-ai:train',
            '--no-interaction',
        ]);
        $process->setTimeout(300);
        $process->run();

        $combinedOutput = trim($process->getOutput() . "\n" . $process->getErrorOutput());
        $combinedOutput = preg_replace('/\s+/', ' ', $combinedOutput ?? '') ?? '';
        if (strlen($combinedOutput) > 220) {
            $combinedOutput = substr($combinedOutput, 0, 220) . '...';
        }

        if ($process->isSuccessful()) {
            $this->addFlash('success', 'User AI retrain termine. ' . ($combinedOutput !== '' ? $combinedOutput : 'OK.'));
        } else {
            $this->addFlash('error', 'Echec du retrain User AI. ' . ($combinedOutput !== '' ? $combinedOutput : 'Consultez les logs.'));
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    /**
     * @return list<array{value:mixed}>
     */
    private function fetchDateRows(
        object $repository,
        string $alias,
        string $field,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): array {
        return $repository->createQueryBuilder($alias)
            ->select(sprintf('%s.%s AS value', $alias, $field))
            ->andWhere(sprintf('%s.%s BETWEEN :from AND :to', $alias, $field))
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * @param list<array{value:mixed}> $rows
     * @param list<string> $monthKeys
     * @return list<int>
     */
    private function buildMonthlySeries(array $rows, array $monthKeys): array
    {
        $bucket = array_fill_keys($monthKeys, 0);
        foreach ($rows as $row) {
            $value = $row['value'] ?? null;
            if ($value instanceof \DateTimeInterface) {
                $key = $value->format('Y-m');
            } elseif (is_string($value) && $value !== '') {
                try {
                    $key = (new \DateTimeImmutable($value))->format('Y-m');
                } catch (\Throwable) {
                    continue;
                }
            } else {
                continue;
            }

            if (array_key_exists($key, $bucket)) {
                $bucket[$key]++;
            }
        }

        return array_values($bucket);
    }

    #[Route('/admin/utilisateurs', name: 'admin_users')]
    public function users(Request $request, UserRepository $userRepository): Response
    {
        $query = $request->query->get('q');
        $role = $request->query->get('role');
        $sort = $request->query->get('sort', 'id');
        $direction = $request->query->get('direction', 'DESC');

        $users = $userRepository->searchAndSort($query, $role, $sort, $direction);
        if ($request->isXmlHttpRequest() || $request->query->getBoolean('ajax')) {
            return $this->render('admin/users/_table.html.twig', [
                'users' => $users,
            ]);
        }

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
            'currentQuery' => $query,
            'currentRole' => $role,
            'currentSort' => $sort,
            'currentDirection' => $direction
        ]);
    }

    #[Route('/admin/boutique', name: 'admin_boutique')]
    public function boutique(): Response
    {
        return $this->render('admin/boutique/index.html.twig', []);
    }

    #[Route('/admin/profile', name: 'admin_profile', methods: ['GET', 'POST'])]
    public function profile(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $form = $this->createForm(User1Type::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->validateAdminProfileForm($form, $user);

            if ($form->isValid()) {
                $plainPassword = $form->get('plainPassword')->getData();

                if ($plainPassword) {
                    $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($hashedPassword);
                }

                $entityManager->flush();

                $this->addFlash('success', 'Profil mis a jour avec succes!');
                return $this->redirectToRoute('admin_profile');
            }
        }

        return $this->render('admin/profile/profile.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    private function validateAdminProfileForm(FormInterface $form, $user): void
    {
        $email = trim((string) $form->get('email')->getData());
        if ($email === '') {
            $form->get('email')->addError(new FormError("L'email est obligatoire."));
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $form->get('email')->addError(new FormError("Le format de l'email est invalide."));
        }

        $nom = trim((string) $form->get('nom')->getData());
        if ($nom === '') {
            $form->get('nom')->addError(new FormError('Le nom est obligatoire.'));
        } else {
            $nomLength = strlen($nom);
            if ($nomLength < 2 || $nomLength > 100) {
                $form->get('nom')->addError(new FormError('Le nom doit contenir entre 2 et 100 caracteres.'));
            }
            if (!preg_match('/^[\p{L}\p{M}\s\'-]+$/u', $nom)) {
                $form->get('nom')->addError(new FormError('Le nom contient des caracteres invalides.'));
            }
        }

        if ($form->has('pseudo')) {
            $pseudo = trim((string) $form->get('pseudo')->getData());
            if ($pseudo !== '') {
                $pseudoLength = strlen($pseudo);
                if ($pseudoLength < 3 || $pseudoLength > 30) {
                    $form->get('pseudo')->addError(new FormError('Le pseudo doit contenir entre 3 et 30 caracteres.'));
                }
                if (!preg_match('/^[A-Za-z0-9_.-]+$/', $pseudo)) {
                    $form->get('pseudo')->addError(new FormError('Le pseudo contient des caracteres invalides.'));
                }
            }
        }

        $plainPassword = (string) $form->get('plainPassword')->getData();
        if (trim($plainPassword) !== '') {
            if (strlen($plainPassword) < 6) {
                $form->get('plainPassword')->addError(new FormError('Le mot de passe doit contenir au moins 6 caracteres.'));
            }
            if (!preg_match('/[A-Za-z]/', $plainPassword) || !preg_match('/\d/', $plainPassword)) {
                $form->get('plainPassword')->addError(new FormError('Le mot de passe doit contenir au moins une lettre et un chiffre.'));
            }
        }

        if ($email !== '') {
            $user->setEmail($email);
        }
        if ($nom !== '') {
            $user->setNom($nom);
        }
        if ($form->has('pseudo') && $pseudo !== '') {
            $user->setPseudo($pseudo);
        }
    }
}
