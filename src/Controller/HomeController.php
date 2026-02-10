<?php

namespace App\Controller;

use App\Entity\EventParticipant;
use App\Entity\Like;
use App\Entity\Commentaire;
use App\Entity\Post;
use App\Repository\AnnouncementRepository;
use App\Repository\EventParticipantRepository;
use App\Repository\LikeRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Email;

class HomeController extends AbstractController
{
    #[Route('/fil', name: 'fil_home')]
    public function index(PostRepository $postRepository, AnnouncementRepository $announcementRepository): Response
    {
        $savedIds = [];
        $user = $this->getUser();
        if ($user instanceof \App\Entity\User) {
            $savedIds = $user->getSavedPosts()->map(fn ($post) => $post->getId())->toArray();
        }

        return $this->render('home/index.html.twig', [
            'posts' => $postRepository->findBy([], ['createdAt' => 'DESC']),
            'announcements' => $announcementRepository->findBy([], ['createdAt' => 'DESC'], 6),
            'savedIds' => $savedIds,
        ]);
    }

    #[Route('/fil/posts/create', name: 'fil_post_create', methods: ['POST'])]
    public function createPost(
        Request $request,
        EntityManagerInterface $entityManager,
        Packages $packages,
        FileUploader $fileUploader,
        MailerInterface $mailer,
        UserRepository $userRepository
    ): JsonResponse
    {
        if (!$this->isCsrfTokenValid('fil_post_create', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'CSRF token invalide.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->getUser()) {
            return new JsonResponse(['error' => 'Vous devez être connecté pour publier.'], Response::HTTP_UNAUTHORIZED);
        }

        $content = trim((string) $request->request->get('content', ''));
        $videoUrl = trim((string) $request->request->get('videoUrl', ''));
        $imagePath = trim((string) $request->request->get('imagePath', ''));
        $isEvent = filter_var($request->request->get('isEvent', false), FILTER_VALIDATE_BOOLEAN);

        $eventTitle = trim((string) $request->request->get('eventTitle', ''));
        $eventDateRaw = trim((string) $request->request->get('eventDate', ''));
        $eventLocation = trim((string) $request->request->get('eventLocation', ''));
        $maxParticipantsRaw = trim((string) $request->request->get('maxParticipants', ''));

        $eventDate = null;
        if ($eventDateRaw !== '') {
            $eventDate = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $eventDateRaw) ?: null;
        }

        $maxParticipants = null;
        if ($maxParticipantsRaw !== '') {
            $maxParticipants = (int) $maxParticipantsRaw;
        }

        if ($isEvent) {
            if ($eventTitle === '' || !$eventDate || $eventLocation === '' || !$maxParticipants || $maxParticipants < 1) {
                return new JsonResponse(['error' => 'Veuillez compléter toutes les informations de l\'événement.'], Response::HTTP_BAD_REQUEST);
            }
        } else {
            if ($content === '' && $videoUrl === '' && $imagePath === '' && !$request->files->get('mediaFile')) {
                return new JsonResponse(['error' => 'Veuillez ajouter un contenu ou un média.'], Response::HTTP_BAD_REQUEST);
            }
        }

        $post = new Post();
        $post->setContent($content !== '' ? $content : null);
        $post->setVideoUrl($videoUrl !== '' ? $videoUrl : null);
        $post->setImagePath($imagePath !== '' ? $imagePath : null);
        $post->setCreatedAt(new \DateTimeImmutable());
        $post->setAuthor($this->getUser());
        $post->setIsEvent($isEvent);
        $post->setEventTitle($eventTitle !== '' ? $eventTitle : null);
        $post->setEventDate($eventDate);
        $post->setEventLocation($eventLocation !== '' ? $eventLocation : null);
        $post->setMaxParticipants($maxParticipants);

        /** @var UploadedFile|null $uploadedFile */
        $uploadedFile = $request->files->get('mediaFile');
        if ($uploadedFile) {
            try {
                $upload = $fileUploader->upload($uploadedFile);
                $post->setImagePath($upload['filename']);
                $post->setVideoUrl(null);
            } catch (FileException $e) {
                return new JsonResponse(['error' => 'Impossible d\'uploader le fichier.'], Response::HTTP_BAD_REQUEST);
            }
        }

        $entityManager->persist($post);
        $entityManager->flush();

        if ($post->isEvent()) {
            $recipients = array_filter(
                $userRepository->findAll(),
                fn ($user) => $user !== $post->getAuthor()
            );

            if ($recipients) {
                $email = (new Email())
                    ->from('noreply@esportify.local')
                    ->subject('Nouvel événement dans le fil d\'actualité')
                    ->text(sprintf(
                        "%s vient de créer un événement : %s (%s)",
                        $post->getAuthor()?->getPseudo() ?? 'Un membre',
                        $post->getEventTitle(),
                        $post->getEventDate()?->format('d/m/Y H:i')
                    ));

                foreach ($recipients as $recipient) {
                    if (!$recipient->getEmail()) {
                        continue;
                    }
                    try {
                        $mailer->send($email->to($recipient->getEmail()));
                    } catch (TransportExceptionInterface $e) {
                        // Ignore mail errors to avoid blocking event creation.
                    }
                }
            }
        }

        $imageUrl = null;
        if ($post->getImagePath()) {
            if (str_starts_with($post->getImagePath(), 'http')) {
                $imageUrl = $post->getImagePath();
            } else {
                $imageUrl = $packages->getUrl('uploads/' . $post->getImagePath());
            }
        }

        return new JsonResponse([
            'id' => $post->getId(),
            'content' => $post->getContent(),
            'imagePath' => $post->getImagePath(),
            'imageUrl' => $imageUrl,
            'videoUrl' => $post->getVideoUrl(),
            'createdAt' => $post->getCreatedAt()?->format('d/m/Y H:i'),
            'author' => $post->getAuthor()?->getPseudo() ?? $post->getAuthor()?->getNom() ?? 'Vous',
            'isEvent' => $post->isEvent(),
            'eventTitle' => $post->getEventTitle(),
            'eventDate' => $post->getEventDate()?->format('d/m/Y H:i'),
            'eventLocation' => $post->getEventLocation(),
            'maxParticipants' => $post->getMaxParticipants(),
            'participantsCount' => $post->getParticipantsCount(),
            'participateUrl' => $post->isEvent() ? $this->generateUrl('fil_event_participate', ['id' => $post->getId()]) : null,
            'likeUrl' => $this->generateUrl('fil_post_like', ['id' => $post->getId()]),
            'commentUrl' => $this->generateUrl('fil_post_comment', ['id' => $post->getId()]),
            'saveUrl' => $this->generateUrl('fil_post_save', ['id' => $post->getId()]),
        ]);
    }

    #[Route('/fil/posts/{id}/like', name: 'fil_post_like', methods: ['POST'])]
    public function like(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager,
        LikeRepository $likeRepository
    ): JsonResponse
    {
        if (!$this->isCsrfTokenValid('post_like', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'CSRF token invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Vous devez être connecté pour aimer.'], Response::HTTP_UNAUTHORIZED);
        }

        $existing = $likeRepository->findOneBy(['post' => $post, 'user' => $user]);
        if ($existing) {
            $entityManager->remove($existing);
        } else {
            $like = new Like();
            $like->setPost($post);
            $like->setUser($user);
            $like->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($like);
        }

        $entityManager->flush();

        return new JsonResponse([
            'count' => $likeRepository->count(['post' => $post]),
        ]);
    }

    #[Route('/fil/posts/{id}/comments', name: 'fil_post_comment', methods: ['POST'])]
    public function comment(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        if (!$this->isCsrfTokenValid('post_comment', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'CSRF token invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Vous devez être connecté pour commenter.'], Response::HTTP_UNAUTHORIZED);
        }

        $content = trim((string) $request->request->get('content', ''));
        if ($content === '') {
            return new JsonResponse(['error' => 'Commentaire vide.'], Response::HTTP_BAD_REQUEST);
        }

        $comment = new Commentaire();
        $comment->setPost($post);
        $comment->setAuthor($user);
        $comment->setContent($content);
        $comment->setCreatedAt(new \DateTimeImmutable());
        $entityManager->persist($comment);
        $entityManager->flush();

        return new JsonResponse([
            'content' => $comment->getContent(),
            'createdAt' => $comment->getCreatedAt()->format('d/m/Y H:i'),
            'author' => $user->getPseudo() ?? $user->getNom() ?? 'Vous',
        ]);
    }

    #[Route('/fil/events/{id}/participate', name: 'fil_event_participate', methods: ['POST'])]
    public function participate(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager,
        EventParticipantRepository $participantRepository
    ): JsonResponse
    {
        if (!$this->isCsrfTokenValid('event_participate', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'CSRF token invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Vous devez être connecté pour participer.'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$post->isEvent()) {
            return new JsonResponse(['error' => 'Ce post n\'est pas un événement.'], Response::HTTP_BAD_REQUEST);
        }

        $existing = $participantRepository->findOneBy(['post' => $post, 'user' => $user]);
        $joined = false;

        if ($existing) {
            $entityManager->remove($existing);
        } else {
            $currentCount = $participantRepository->count(['post' => $post]);
            if ($post->getMaxParticipants() !== null && $currentCount >= $post->getMaxParticipants()) {
                return new JsonResponse(['error' => 'Événement complet.'], Response::HTTP_BAD_REQUEST);
            }
            $participant = new EventParticipant();
            $participant->setPost($post);
            $participant->setUser($user);
            $participant->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($participant);
            $joined = true;
        }

        $entityManager->flush();

        return new JsonResponse([
            'joined' => $joined,
            'participantsCount' => $participantRepository->count(['post' => $post]),
            'maxParticipants' => $post->getMaxParticipants(),
        ]);
    }

    #[Route('/fil/posts/{id}/save', name: 'fil_post_save', methods: ['POST'])]
    public function save(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        if (!$this->isCsrfTokenValid('post_save', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'CSRF token invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return new JsonResponse(['error' => 'Vous devez être connecté pour enregistrer.'], Response::HTTP_UNAUTHORIZED);
        }

        $saved = false;
        if ($user->hasSavedPost($post)) {
            $user->removeSavedPost($post);
        } else {
            $user->addSavedPost($post);
            $saved = true;
        }

        $entityManager->flush();

        return new JsonResponse(['saved' => $saved]);
    }

    #[Route('/enregistrements', name: 'saved_posts')]
    public function savedPosts(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('home/saved.html.twig', [
            'posts' => $user->getSavedPosts(),
        ]);
    }
}
