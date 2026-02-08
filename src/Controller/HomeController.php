<?php

namespace App\Controller;

use App\Entity\Post;
use App\Repository\AnnouncementRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/fil', name: 'fil_home')]
    public function index(PostRepository $postRepository, AnnouncementRepository $announcementRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'posts' => $postRepository->findBy([], ['createdAt' => 'DESC']),
            'announcements' => $announcementRepository->findBy([], ['createdAt' => 'DESC'], 6),
        ]);
    }

    #[Route('/fil/posts/create', name: 'fil_post_create', methods: ['POST'])]
    public function createPost(Request $request, EntityManagerInterface $entityManager, Packages $packages): JsonResponse
    {
        if (!$this->isCsrfTokenValid('fil_post_create', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'CSRF token invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $content = trim((string) $request->request->get('content', ''));
        $mediaType = (string) $request->request->get('mediaType', 'text');
        $mediaFilename = (string) $request->request->get('mediaFilename', '');

        if ($content === '' && $mediaFilename === '' && !$request->files->get('mediaFile')) {
            return new JsonResponse(['error' => 'Veuillez ajouter un contenu ou un m\u00e9dia.'], Response::HTTP_BAD_REQUEST);
        }

        $post = new Post();
        $post->setContent($content !== '' ? $content : null);
        $post->setMediaType($mediaType !== '' ? $mediaType : 'text');
        $post->setMediaFilename($mediaFilename !== '' ? $mediaFilename : null);
        $post->setCreatedAt(new \DateTimeImmutable());

        /** @var UploadedFile|null $uploadedFile */
        $uploadedFile = $request->files->get('mediaFile');
        if ($uploadedFile) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $extension = $uploadedFile->guessExtension() ?: $uploadedFile->getClientOriginalExtension();
            $filename = bin2hex(random_bytes(12)) . ($extension ? '.' . $extension : '');

            try {
                $uploadedFile->move($uploadDir, $filename);
                $post->setMediaFilename($filename);

                $mimeType = $uploadedFile->getMimeType() ?? '';
                $post->setMediaType(str_starts_with($mimeType, 'video/') ? 'video' : 'image');
            } catch (FileException $e) {
                return new JsonResponse(['error' => 'Impossible d\'uploader le fichier.'], Response::HTTP_BAD_REQUEST);
            }
        }

        $entityManager->persist($post);
        $entityManager->flush();

        $mediaUrl = null;
        if ($post->getMediaFilename()) {
            if (str_starts_with($post->getMediaFilename(), 'http')) {
                $mediaUrl = $post->getMediaFilename();
            } elseif (in_array($post->getMediaType(), ['image', 'video'], true)) {
                $mediaUrl = $packages->getUrl('uploads/' . $post->getMediaFilename());
            }
        }

        return new JsonResponse([
            'id' => $post->getId(),
            'content' => $post->getContent(),
            'mediaType' => $post->getMediaType(),
            'mediaFilename' => $post->getMediaFilename(),
            'mediaUrl' => $mediaUrl,
            'createdAt' => $post->getCreatedAt()?->format('d/m/Y H:i'),
        ]);
    }
}
