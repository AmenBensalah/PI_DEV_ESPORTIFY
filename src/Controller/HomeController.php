<?php

namespace App\Controller;

use App\Entity\EventParticipant;
use App\Entity\Like;
use App\Entity\Commentaire;
use App\Entity\Post;
use App\Entity\PostMedia;
use App\Repository\AnnouncementRepository;
use App\Repository\CommentaireRepository;
use App\Repository\EventParticipantRepository;
use App\Repository\LikeRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use App\Service\FeedIntelligenceService;
use App\Service\NotificationService;
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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class HomeController extends AbstractController
{
    #[Route('/fil', name: 'fil_home')]
    public function index(
        Request $request,
        PostRepository $postRepository,
        AnnouncementRepository $announcementRepository,
        FeedIntelligenceService $feedIntelligenceService
    ): Response
    {
        $savedIds = [];
        $likedIds = [];
        $user = $this->getUser();
        if ($user instanceof \App\Entity\User) {
            $savedIds = $user->getSavedPosts()->map(fn ($post) => $post->getId())->toArray();
            $likedIds = $user->getLikes()->map(fn ($like) => $like->getPost()?->getId())->filter(fn ($id) => $id !== null)->toArray();
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = max(5, min(30, (int) $request->query->get('per_page', 10)));

        $allPosts = $postRepository->findAllWithAuthor();
        $allPosts = $feedIntelligenceService->sortPostsForUser(
            $allPosts,
            $user instanceof \App\Entity\User ? $user : null,
            []
        );
        $totalPosts = count($allPosts);
        $totalPages = max(1, (int) ceil($totalPosts / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;
        $posts = array_slice($allPosts, $offset, $perPage);
        $analysisByPost = $feedIntelligenceService->ensurePostAnalyses($posts, 30);

        $yesterday = (new \DateTimeImmutable('now'))->modify('-1 day');
        $postsLastDay = array_values(array_filter($allPosts, static function (Post $post) use ($yesterday): bool {
            return $post->getCreatedAt() !== null && $post->getCreatedAt() >= $yesterday;
        }));
        if ($postsLastDay === []) {
            $postsLastDay = array_slice($allPosts, 0, 30);
        }

        $bestTime = $feedIntelligenceService->suggestBestTimeToPost(
            $user instanceof \App\Entity\User ? $user : null,
            array_slice($allPosts, 0, 150)
        );
        $dailyHighlights = $feedIntelligenceService->buildDailyHighlights($postsLastDay, 5);
        $trendingTopics = $feedIntelligenceService->detectTrendingTopics($postsLastDay, 5);

        return $this->render('home/index.html.twig', [
            'posts' => $posts,
            'announcements' => $announcementRepository->findBy([], ['createdAt' => 'DESC'], 50),
            'savedIds' => $savedIds,
            'likedIds' => $likedIds,
            'analysisByPost' => $analysisByPost,
            'bestTime' => $bestTime,
            'dailyHighlights' => $dailyHighlights,
            'trendingTopics' => $trendingTopics,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'totalPosts' => $totalPosts,
        ]);
    }

    #[Route('/fil/posts/create', name: 'fil_post_create', methods: ['POST'])]
    public function createPost(
        Request $request,
        EntityManagerInterface $entityManager,
        Packages $packages,
        FileUploader $fileUploader,
        MailerInterface $mailer,
        PostRepository $postRepository,
        UserRepository $userRepository,
        NotificationService $notificationService,
        FeedIntelligenceService $feedIntelligenceService
    ): JsonResponse
    {
        $contentLength = (int) $request->server->get('CONTENT_LENGTH', 0);
        $postMaxBytes = $this->parseIniSizeToBytes((string) ini_get('post_max_size'));
        if ($contentLength > 0 && $postMaxBytes > 0 && $contentLength > $postMaxBytes) {
            return new JsonResponse([
                'error' => sprintf(
                    'Le total des fichiers est trop volumineux (max serveur: %s). RÃ©duisez la taille des vidÃ©os ou envoyez-les sÃ©parÃ©ment.',
                    (string) ini_get('post_max_size')
                ),
            ], Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }

        if (!$this->isCsrfTokenValid('fil_post_create', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'CSRF token invalide.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->getUser()) {
            return new JsonResponse(['error' => 'Vous devez Ãªtre connectÃ© pour publier.'], Response::HTTP_UNAUTHORIZED);
        }

        $content = $this->normalizeFeedText(trim((string) $request->request->get('content', '')));
        $videoUrl = trim((string) $request->request->get('videoUrl', ''));
        $imagePath = trim((string) $request->request->get('imagePath', ''));
        // Never persist temporary browser URLs from previews.
        if (str_starts_with($imagePath, 'blob:') || str_starts_with($imagePath, 'data:')) {
            $imagePath = '';
        }
        $isEvent = filter_var($request->request->get('isEvent', false), FILTER_VALIDATE_BOOLEAN);

        $eventTitle = $this->normalizeFeedText(trim((string) $request->request->get('eventTitle', '')));
        $eventDateRaw = trim((string) $request->request->get('eventDate', ''));
        $eventLocation = $this->normalizeFeedText(trim((string) $request->request->get('eventLocation', '')));
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
                return new JsonResponse(['error' => 'Veuillez complÃ©ter toutes les informations de l\'Ã©vÃ©nement.'], Response::HTTP_BAD_REQUEST);
            }
        } else {
            $uploadedFilesRaw = $request->files->all()['mediaFiles'] ?? [];
            $hasUploadedFiles = is_array($uploadedFilesRaw) ? count($uploadedFilesRaw) > 0 : false;
            if ($content === '' && $videoUrl === '' && $imagePath === '' && !$hasUploadedFiles) {
                return new JsonResponse(['error' => 'Veuillez ajouter un contenu ou un mÃ©dia.'], Response::HTTP_BAD_REQUEST);
            }
        }

        $author = $this->getUser();
        $recentTexts = [];
        if ($author instanceof \App\Entity\User) {
            $recentTexts = $postRepository->findRecentTextsByAuthor($author, 20);
        }
        $incomingMediaHints = [$imagePath, $videoUrl];
        $incomingFiles = $request->files->all()['mediaFiles'] ?? [];
        if (is_array($incomingFiles)) {
            foreach ($incomingFiles as $incomingFile) {
                if ($incomingFile instanceof UploadedFile) {
                    $incomingMediaHints[] = $incomingFile->getClientOriginalName();
                }
            }
        }
        $sourceForModeration = trim(implode(' ', array_filter([
            $content,
            $eventTitle,
            $eventLocation,
            $videoUrl,
        ], static fn ($value) => (string) $value !== '')));
        $precheck = $feedIntelligenceService->analyzeRawContent($sourceForModeration, [
            'existing_texts' => $recentTexts,
            'media_paths' => $incomingMediaHints,
        ]);
        if (($precheck['auto_action'] ?? 'allow') === 'block') {
            $reason = trim((string) ($precheck['block_reason'] ?? ''));
            $tip = trim((string) ($precheck['blocking_tip'] ?? ''));
            $errorMessage = 'Publication bloquee automatiquement.';
            if ($reason !== '') {
                $errorMessage .= ' ' . $reason;
            }
            if ($tip !== '') {
                $errorMessage .= ' Conseil: ' . $tip;
            }
            return new JsonResponse([
                'error' => $errorMessage,
                'moderation' => $precheck,
            ], Response::HTTP_BAD_REQUEST);
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

        /** @var array<int, UploadedFile> $uploadedFiles */
        $uploadedFiles = $request->files->all()['mediaFiles'] ?? [];
        if (!is_array($uploadedFiles)) {
            $uploadedFiles = [];
        }
        $position = 0;
        foreach ($uploadedFiles as $uploadedFile) {
            if (!$uploadedFile instanceof UploadedFile) {
                continue;
            }

            $mime = strtolower((string) ($uploadedFile->getMimeType() ?: $uploadedFile->getClientMimeType()));
            $isVideo = str_starts_with($mime, 'video/');

            if ($isVideo) {
                try {
                    $upload = $fileUploader->upload($uploadedFile);
                    $media = (new PostMedia())
                        ->setPost($post)
                        ->setType('video')
                        ->setPath($upload['filename'])
                        ->setPosition($position++);
                    $post->addMedia($media);

                    // Backward compatibility for legacy fields.
                    if ($post->getImagePath() === null && $post->getVideoUrl() === null) {
                        $post->setVideoUrl($packages->getUrl('uploads/' . $upload['filename']));
                    }
                } catch (FileException $e) {
                    return new JsonResponse(['error' => 'Impossible d\'uploader le fichier.'], Response::HTTP_BAD_REQUEST);
                }
                continue;
            }

            // Frontoffice feed images use VichUploaderBundle.
            $media = (new PostMedia())
                ->setPost($post)
                ->setType('image')
                ->setPosition($position++)
                ->setImageFile($uploadedFile);
            $post->addMedia($media);
        }

        $entityManager->persist($post);
        $entityManager->flush();
        $analysisRecord = $feedIntelligenceService->analyzeAndPersistPost($post, $recentTexts, true);

        if ($analysisRecord->getAutoAction() === 'review') {
            $this->notifyAdminsAboutRisk(
                $notificationService,
                $userRepository,
                'Alerte moderation publication',
                sprintf(
                    'Post #%d potentiellement sensible (toxicite: %d, spam: %d, hate: %d).',
                    (int) $post->getId(),
                    $analysisRecord->getToxicityScore(),
                    $analysisRecord->getSpamScore(),
                    $analysisRecord->getHateSpeechScore()
                ),
                $this->generateUrl('fil_admin_post_index')
            );
        }

        if ($post->isEvent()) {
            $recipients = array_filter(
                $userRepository->findAll(),
                fn ($user) => $user !== $post->getAuthor()
            );

            if ($recipients) {
                $email = (new Email())
                    ->from('noreply@esportify.local')
                    ->subject('Nouvel Ã©vÃ©nement dans le fil d\'actualitÃ©')
                    ->text(sprintf(
                        "%s vient de crÃ©er un Ã©vÃ©nement : %s (%s)",
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

        $authorName = $post->getAuthor()?->getPseudo() ?: $post->getAuthor()?->getNom() ?: 'Un membre';
        $notificationService->notifyUsers(
            $userRepository->findAll(),
            $post->isEvent() ? 'Nouvel Ã©vÃ©nement' : 'Nouvelle publication',
            $post->isEvent()
                ? sprintf('%s a crÃ©Ã© un Ã©vÃ©nement: %s', $authorName, (string) $post->getEventTitle())
                : sprintf('%s a publiÃ© dans le fil d\'actualitÃ©.', $authorName),
            $this->generateUrl('fil_home') . '#post-' . $post->getId(),
            $post->isEvent() ? 'event' : 'post',
            $post->getAuthor()
        );

        $imageUrl = null;
        if ($post->getImagePath()) {
            if (str_starts_with($post->getImagePath(), 'http')) {
                $imageUrl = $post->getImagePath();
            } else {
                $imageUrl = $packages->getUrl('uploads/' . $post->getImagePath());
            }
        }
        $medias = [];
        foreach ($post->getMedias() as $media) {
            $path = $media->getPath();
            $url = str_starts_with($path, 'http') ? $path : $packages->getUrl('uploads/' . $path);
            $medias[] = [
                'type' => $media->getType(),
                'url' => $url,
            ];
        }

        return new JsonResponse([
            'id' => $post->getId(),
            'content' => $this->normalizeFeedText((string) $post->getContent()),
            'imagePath' => $post->getImagePath(),
            'imageUrl' => $imageUrl,
            'videoUrl' => $post->getVideoUrl(),
            'medias' => $medias,
            'createdAt' => $post->getCreatedAt()?->format('d/m/Y H:i'),
            'author' => $post->getAuthor()?->getPseudo() ?? $post->getAuthor()?->getNom() ?? 'Vous',
            'authorAvatar' => $post->getAuthor()?->getAvatar()
                ? $packages->getUrl('uploads/avatars/' . $post->getAuthor()->getAvatar())
                : null,
            'authorInitial' => mb_strtoupper(mb_substr((string) ($post->getAuthor()?->getPseudo() ?? $post->getAuthor()?->getNom() ?? 'V'), 0, 1)),
            'isEvent' => $post->isEvent(),
            'eventTitle' => $this->normalizeFeedText((string) $post->getEventTitle()),
            'eventDate' => $post->getEventDate()?->format('d/m/Y H:i'),
            'eventLocation' => $this->normalizeFeedText((string) $post->getEventLocation()),
            'maxParticipants' => $post->getMaxParticipants(),
            'participantsCount' => $post->getParticipantsCount(),
            'participateUrl' => $post->isEvent() ? $this->generateUrl('fil_event_participate', ['id' => $post->getId()]) : null,
            'likeUrl' => $this->generateUrl('fil_post_like', ['id' => $post->getId()]),
            'likeListUrl' => $this->generateUrl('fil_post_likes', ['id' => $post->getId()]),
            'commentUrl' => $this->generateUrl('fil_post_comment', ['id' => $post->getId()]),
            'saveUrl' => $this->generateUrl('fil_post_save', ['id' => $post->getId()]),
            'liked' => false,
            'canManage' => true,
            'editUrl' => $this->generateUrl('fil_post_edit', ['id' => $post->getId()]),
            'deleteUrl' => $this->generateUrl('fil_post_delete', ['id' => $post->getId()]),
            'aiSummary' => $analysisRecord->getSummaryShort(),
            'aiCategory' => $analysisRecord->getCategory(),
            'aiHashtags' => $analysisRecord->getHashtags() ?? [],
            'aiAction' => $analysisRecord->getAutoAction(),
        ]);
    }

    #[Route('/fil/posts/{id}/like', name: 'fil_post_like', methods: ['POST'])]
    public function like(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager,
        LikeRepository $likeRepository,
        NotificationService $notificationService
    ): JsonResponse
    {
        if (!$this->isCsrfTokenValid('post_like', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'CSRF token invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Vous devez Ãªtre connectÃ© pour aimer.'], Response::HTTP_UNAUTHORIZED);
        }

        $existing = $likeRepository->findOneBy(['post' => $post, 'user' => $user]);
        $createdLike = false;
        if ($existing) {
            $entityManager->remove($existing);
        } else {
            $like = new Like();
            $like->setPost($post);
            $like->setUser($user);
            $like->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($like);
            $createdLike = true;
        }

        $entityManager->flush();

        if ($createdLike && $post->getAuthor() && $post->getAuthor()->getId() !== $user->getId()) {
            $notificationService->notifyUser(
                $post->getAuthor(),
                'Nouveau like',
                sprintf('%s a aimÃ© votre publication.', $user->getPseudo() ?: $user->getNom() ?: 'Un utilisateur'),
                $this->generateUrl('fil_home') . '#post-' . $post->getId(),
                'like'
            );
        }

        return new JsonResponse([
            'count' => $likeRepository->count(['post' => $post]),
            'liked' => $createdLike,
        ]);
    }

    #[Route('/fil/posts/{id}/edit', name: 'fil_post_edit', methods: ['GET', 'POST'])]
    public function editPost(Post $post, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        if ($post->getAuthor()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Action non autorisee.');
        }

        $form = $this->createFormBuilder(['content' => (string) ($post->getContent() ?? '')])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'required' => false,
                'attr' => ['rows' => 8],
            ])
            ->add('save', SubmitType::class, ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = (array) $form->getData();
            $content = $this->normalizeFeedText(trim((string) ($data['content'] ?? '')));
            $hasMedia = $post->getImagePath() || $post->getVideoUrl() || $post->getMedias()->count() > 0;
            if ($content === '' && !$hasMedia && !$post->isEvent()) {
                $this->addFlash('error', 'Le contenu ne peut pas etre vide.');
            } else {
                $post->setContent($content !== '' ? $content : null);
                $entityManager->flush();

                $targetUrl = $this->generateUrl('fil_home', [
                    'page' => max(1, (int) $request->query->get('page', 1)),
                ]) . '#post-' . $post->getId();

                return $this->redirect($targetUrl, Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('home/edit_post.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }
    #[Route('/fil/posts/{id}/delete', name: 'fil_post_delete', methods: ['POST'])]
    public function deletePost(Post $post, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isCsrfTokenValid('post_manage', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'CSRF token invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Vous devez Ãªtre connectÃ©.'], Response::HTTP_UNAUTHORIZED);
        }
        if ($post->getAuthor()?->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Action non autorisÃ©e.'], Response::HTTP_FORBIDDEN);
        }

        $entityManager->remove($post);
        $entityManager->flush();

        return new JsonResponse(['deleted' => true]);
    }

    #[Route('/fil/posts/{id}/likes', name: 'fil_post_likes', methods: ['GET'])]
    public function likesList(Post $post, LikeRepository $likeRepository, Packages $packages): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse(['error' => 'Vous devez Ãªtre connectÃ©.'], Response::HTTP_UNAUTHORIZED);
        }

        $likes = $likeRepository->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')
            ->addSelect('u')
            ->andWhere('l.post = :post')
            ->setParameter('post', $post)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults(200)
            ->getQuery()
            ->getResult();

        $users = array_map(static function (Like $like) use ($packages): array {
            $user = $like->getUser();
            $display = $user?->getPseudo() ?: $user?->getNom() ?: $user?->getEmail() ?: 'Utilisateur';
            return [
                'id' => $user?->getId(),
                'name' => $display,
                'avatar' => $user?->getAvatar() ? $packages->getUrl('uploads/avatars/' . $user->getAvatar()) : null,
                'initial' => mb_strtoupper(mb_substr((string) $display, 0, 1)),
                'likedAt' => $like->getCreatedAt()->format('d/m/Y H:i'),
            ];
        }, $likes);

        return new JsonResponse([
            'count' => count($users),
            'users' => $users,
        ]);
    }

    #[Route('/fil/posts/{id}/comments', name: 'fil_post_comment', methods: ['POST'])]
    public function comment(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager,
        NotificationService $notificationService,
        Packages $packages,
        FeedIntelligenceService $feedIntelligenceService,
        UserRepository $userRepository,
        CommentaireRepository $commentaireRepository
    ): JsonResponse
    {
        if (!$this->isCsrfTokenValid('post_comment', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'CSRF token invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return new JsonResponse(['error' => 'Vous devez Ãªtre connectÃ© pour commenter.'], Response::HTTP_UNAUTHORIZED);
        }

        $content = trim((string) $request->request->get('content', ''));
        if ($content === '') {
            return new JsonResponse(['error' => 'Commentaire vide.'], Response::HTTP_BAD_REQUEST);
        }
        $recentComments = $commentaireRepository->findRecentTextsByAuthor($user, 30);
        $commentPrecheck = $feedIntelligenceService->analyzeRawContent($content, [
            'existing_texts' => $recentComments,
            'media_paths' => [],
        ]);
        if (($commentPrecheck['auto_action'] ?? 'allow') === 'block') {
            $reason = trim((string) ($commentPrecheck['block_reason'] ?? ''));
            $tip = trim((string) ($commentPrecheck['blocking_tip'] ?? ''));
            $errorMessage = 'Commentaire bloque automatiquement.';
            if ($reason !== '') {
                $errorMessage .= ' ' . $reason;
            }
            if ($tip !== '') {
                $errorMessage .= ' Conseil: ' . $tip;
            }
            return new JsonResponse([
                'error' => $errorMessage,
                'moderation' => $commentPrecheck,
            ], Response::HTTP_BAD_REQUEST);
        }

        $comment = new Commentaire();
        $comment->setPost($post);
        $comment->setAuthor($user);
        $comment->setContent($content);
        $comment->setCreatedAt(new \DateTimeImmutable());
        $entityManager->persist($comment);
        $entityManager->flush();
        $analysisRecord = $feedIntelligenceService->analyzeAndPersistComment($comment, $recentComments, true);

        if ($post->getAuthor() && $post->getAuthor()->getId() !== $user->getId()) {
            $notificationService->notifyUser(
                $post->getAuthor(),
                'Nouveau commentaire',
                sprintf('%s a commentÃ© votre publication.', $user->getPseudo() ?: $user->getNom() ?: 'Un utilisateur'),
                $this->generateUrl('fil_home') . '#post-' . $post->getId(),
                'comment'
            );
        }
        if ($analysisRecord->getAutoAction() === 'review') {
            $this->notifyAdminsAboutRisk(
                $notificationService,
                $userRepository,
                'Alerte moderation commentaire',
                sprintf(
                    'Commentaire #%d potentiellement sensible (toxicite: %d, spam: %d, hate: %d).',
                    (int) $comment->getId(),
                    $analysisRecord->getToxicityScore(),
                    $analysisRecord->getSpamScore(),
                    $analysisRecord->getHateSpeechScore()
                ),
                $this->generateUrl('fil_admin_comment_index')
            );
        }

        return new JsonResponse([
            'id' => $comment->getId(),
            'content' => $this->normalizeFeedText((string) $comment->getContent()),
            'createdAt' => $comment->getCreatedAt()->format('d/m/Y H:i'),
            'author' => $user->getPseudo() ?? $user->getNom() ?? 'Vous',
            'authorAvatar' => $user->getAvatar() ? $packages->getUrl('uploads/avatars/' . $user->getAvatar()) : null,
            'authorInitial' => mb_strtoupper(mb_substr((string) ($user->getPseudo() ?? $user->getNom() ?? 'U'), 0, 1)),
            'canManage' => true,
            'editUrl' => $this->generateUrl('fil_comment_edit', ['id' => $comment->getId()]),
            'deleteUrl' => $this->generateUrl('fil_comment_delete', ['id' => $comment->getId()]),
            'aiAction' => $analysisRecord->getAutoAction(),
        ]);
    }

    #[Route('/fil/comments/{id}/edit', name: 'fil_comment_edit', methods: ['GET', 'POST'])]
    public function editComment(Commentaire $comment, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        if ($comment->getAuthor()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Action non autorisee.');
        }

        $form = $this->createFormBuilder(['content' => (string) $comment->getContent()])
            ->add('content', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => true,
                'attr' => ['rows' => 5],
            ])
            ->add('save', SubmitType::class, ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = (array) $form->getData();
            $content = $this->normalizeFeedText(trim((string) ($data['content'] ?? '')));
            if ($content === '') {
                $this->addFlash('error', 'Le commentaire ne peut pas etre vide.');
            } else {
                $comment->setContent($content);
                $entityManager->flush();

                $targetUrl = $this->generateUrl('fil_home', [
                    'page' => max(1, (int) $request->query->get('page', 1)),
                ]) . '#post-' . $comment->getPost()?->getId();

                return $this->redirect($targetUrl, Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('home/edit_comment.html.twig', [
            'comment' => $comment,
            'form' => $form->createView(),
        ]);
    }
    #[Route('/fil/comments/{id}/delete', name: 'fil_comment_delete', methods: ['POST'])]
    public function deleteComment(Commentaire $comment, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isCsrfTokenValid('comment_manage', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'CSRF token invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Vous devez Ãªtre connectÃ©.'], Response::HTTP_UNAUTHORIZED);
        }
        if ($comment->getAuthor()?->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Action non autorisÃ©e.'], Response::HTTP_FORBIDDEN);
        }

        $entityManager->remove($comment);
        $entityManager->flush();

        return new JsonResponse(['deleted' => true]);
    }

    #[Route('/fil/events/{id}/participate', name: 'fil_event_participate', methods: ['POST'])]
    public function participate(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager,
        EventParticipantRepository $participantRepository,
        NotificationService $notificationService
    ): JsonResponse
    {
        if (!$this->isCsrfTokenValid('event_participate', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'CSRF token invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Vous devez Ãªtre connectÃ© pour participer.'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$post->isEvent()) {
            return new JsonResponse(['error' => 'Ce post n\'est pas un Ã©vÃ©nement.'], Response::HTTP_BAD_REQUEST);
        }

        $existing = $participantRepository->findOneBy(['post' => $post, 'user' => $user]);
        $joined = false;

        if ($existing) {
            $entityManager->remove($existing);
        } else {
            $currentCount = $participantRepository->count(['post' => $post]);
            if ($post->getMaxParticipants() !== null && $currentCount >= $post->getMaxParticipants()) {
                return new JsonResponse(['error' => 'Ã‰vÃ©nement complet.'], Response::HTTP_BAD_REQUEST);
            }
            $participant = new EventParticipant();
            $participant->setPost($post);
            $participant->setUser($user);
            $participant->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($participant);
            $joined = true;
        }

        $entityManager->flush();

        if ($joined && $post->getAuthor() && $post->getAuthor()->getId() !== $user->getId()) {
            $notificationService->notifyUser(
                $post->getAuthor(),
                'Nouvelle participation',
                sprintf('%s participe Ã  votre Ã©vÃ©nement.', $user->getPseudo() ?: $user->getNom() ?: 'Un utilisateur'),
                $this->generateUrl('fil_home') . '#post-' . $post->getId(),
                'participation'
            );
        }

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
            return new JsonResponse(['error' => 'Vous devez Ãªtre connectÃ© pour enregistrer.'], Response::HTTP_UNAUTHORIZED);
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

    private function notifyAdminsAboutRisk(
        NotificationService $notificationService,
        UserRepository $userRepository,
        string $title,
        string $message,
        string $link
    ): void {
        $admins = $userRepository->findAdmins();
        if ($admins === []) {
            return;
        }

        $notificationService->notifyUsers(
            $admins,
            $title,
            $message,
            $link,
            'moderation'
        );
    }

    private function normalizeFeedText(?string $text): string
    {
        $value = trim((string) $text);
        if ($value === '') {
            return '';
        }
        if (preg_match('~https?://~i', $value)) {
            return $value;
        }

        $value = strtr($value, [
            'ÃƒÂ©' => 'Ã©',
            'ÃƒÂ¨' => 'Ã¨',
            'ÃƒÂª' => 'Ãª',
            'Ãƒ ' => 'Ã ',
            'ÃƒÂ¢' => 'Ã¢',
            'ÃƒÂ®' => 'Ã®',
            'ÃƒÂ´' => 'Ã´',
            'ÃƒÂ»' => 'Ã»',
            'ÃƒÂ§' => 'Ã§',
            'Ã¢â‚¬â„¢' => '\'',
            'Ã¢â‚¬â€œ' => '-',
        ]);

        return (string) preg_replace('/(?<=\p{L})\?{1,3}(?=\p{L})/u', 'e', $value);
    }

    private function parseIniSizeToBytes(string $value): int
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return 0;
        }

        $unit = strtolower(substr($trimmed, -1));
        $number = (float) $trimmed;

        return match ($unit) {
            'g' => (int) ($number * 1024 * 1024 * 1024),
            'm' => (int) ($number * 1024 * 1024),
            'k' => (int) ($number * 1024),
            default => (int) $number,
        };
    }
}

