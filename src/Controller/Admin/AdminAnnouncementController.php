<?php

namespace App\Controller\Admin;

use App\Entity\Announcement;
use App\Form\AnnouncementType;
use App\Repository\AnnouncementRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Exception\InvalidArgumentException as MimeInvalidArgumentException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/fil/admin/announcements')]
class AdminAnnouncementController extends AbstractController
{
    #[Route('/', name: 'fil_admin_announcement_index')]
    public function index(
        Request $request,
        AnnouncementRepository $announcementRepository,
        PaginatorInterface $paginator
    ): Response
    {
        $perPage = (int) $request->query->get('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }
        $page = max(1, (int) $request->query->get('page', 1));

        $filters = [
            'q' => trim((string) $request->query->get('q', '')),
            'date_from' => trim((string) $request->query->get('date_from', '')),
            'date_to' => trim((string) $request->query->get('date_to', '')),
            'sort' => trim((string) $request->query->get('sort', 'date')),
            'direction' => strtoupper(trim((string) $request->query->get('direction', 'DESC'))) === 'ASC' ? 'ASC' : 'DESC',
            'per_page' => $perPage,
        ];

        $announcements = $paginator->paginate(
            $announcementRepository->searchAdminQueryBuilder($filters),
            $page,
            $perPage,
            [
                'sortFieldParameterName' => 'knp_sort',
                'sortDirectionParameterName' => 'knp_direction',
            ]
        );

        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/announcement/_results.html.twig', [
                'announcements' => $announcements,
            ]);
        }

        return $this->render('admin/announcement/index.html.twig', [
            'announcements' => $announcements,
            'filters' => $filters,
        ]);
    }

    #[Route('/new', name: 'fil_admin_announcement_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        NotificationService $notificationService
    ): Response
    {
        $announcement = new Announcement();
        $form = $this->createForm(AnnouncementType::class, $announcement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$announcement->getCreatedAt()) {
                $announcement->setCreatedAt(new \DateTimeImmutable());
            }

            if (!$announcement->getMediaType()) {
                $announcement->setMediaType('text');
            }

            /** @var UploadedFile|null $uploadedFile */
            $uploadedFile = $form->get('mediaFile')->getData();
            if ($uploadedFile) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $extension = $uploadedFile->guessExtension() ?: $uploadedFile->getClientOriginalExtension();
                $filename = bin2hex(random_bytes(12)) . ($extension ? '.' . $extension : '');
                $shouldDetectMediaType = in_array($announcement->getMediaType(), ['text', 'link'], true);
                $mimeType = '';
                if ($shouldDetectMediaType) {
                    try {
                        $mimeType = $uploadedFile->getMimeType() ?? '';
                    } catch (MimeInvalidArgumentException $e) {
                        $mimeType = '';
                    }
                }

                try {
                    $uploadedFile->move($uploadDir, $filename);
                    $announcement->setMediaFilename($filename);

                    if ($shouldDetectMediaType) {
                        $announcement->setMediaType(str_starts_with($mimeType, 'video/') ? 'video' : 'image');
                    }
                } catch (FileException $e) {
                    $this->addFlash('error', 'Impossible d\'uploader le fichier.');
                }
            }

            $entityManager->persist($announcement);
            $entityManager->flush();

            $notificationService->notifyUsers(
                $userRepository->findAll(),
                'Nouvelle annonce',
                'Une nouvelle annonce a été publiée: ' . $announcement->getTitle(),
                $this->generateUrl('fil_home'),
                'announcement'
            );

            return $this->redirectToRoute('fil_admin_announcement_index');
        }

        return $this->render('admin/announcement/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'fil_admin_announcement_edit')]
    public function edit(Announcement $announcement, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AnnouncementType::class, $announcement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $uploadedFile */
            $uploadedFile = $form->get('mediaFile')->getData();
            if ($uploadedFile) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $extension = $uploadedFile->guessExtension() ?: $uploadedFile->getClientOriginalExtension();
                $filename = bin2hex(random_bytes(12)) . ($extension ? '.' . $extension : '');
                $shouldDetectMediaType = in_array($announcement->getMediaType(), ['text', 'link'], true);
                $mimeType = '';
                if ($shouldDetectMediaType) {
                    try {
                        $mimeType = $uploadedFile->getMimeType() ?? '';
                    } catch (MimeInvalidArgumentException $e) {
                        $mimeType = '';
                    }
                }

                try {
                    $uploadedFile->move($uploadDir, $filename);
                    $announcement->setMediaFilename($filename);

                    if ($shouldDetectMediaType) {
                        $announcement->setMediaType(str_starts_with($mimeType, 'video/') ? 'video' : 'image');
                    }
                } catch (FileException $e) {
                    $this->addFlash('error', 'Impossible d\'uploader le fichier.');
                }
            }

            $entityManager->flush();

            return $this->redirectToRoute('fil_admin_announcement_index');
        }

        return $this->render('admin/announcement/edit.html.twig', [
            'form' => $form,
            'announcement' => $announcement,
        ]);
    }

    #[Route('/{id}/delete', name: 'fil_admin_announcement_delete', methods: ['POST'])]
    public function delete(Announcement $announcement, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_announcement_' . $announcement->getId(), $request->request->get('_token'))) {
            $entityManager->remove($announcement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('fil_admin_announcement_index');
    }
}
