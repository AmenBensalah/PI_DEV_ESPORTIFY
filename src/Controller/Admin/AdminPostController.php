<?php

namespace App\Controller\Admin;

use App\Entity\Post;
use App\Entity\PostMedia;
use App\Form\PostType;
use App\Repository\PostRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/fil/admin/posts')]
class AdminPostController extends AbstractController
{
    #[Route('/', name: 'fil_admin_post_index')]
    public function index(Request $request, PostRepository $postRepository): Response
    {
        $filters = [
            'q' => trim((string) $request->query->get('q', '')),
            'media' => trim((string) $request->query->get('media', '')),
            'date_from' => trim((string) $request->query->get('date_from', '')),
            'date_to' => trim((string) $request->query->get('date_to', '')),
            'sort' => trim((string) $request->query->get('sort', 'date')),
            'direction' => strtoupper(trim((string) $request->query->get('direction', 'DESC'))) === 'ASC' ? 'ASC' : 'DESC',
        ];

        $posts = $postRepository->searchAdmin($filters);

        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/post/_results.html.twig', [
                'posts' => $posts,
            ]);
        }

        return $this->render('admin/post/index.html.twig', [
            'posts' => $posts,
            'filters' => $filters,
        ]);
    }

    #[Route('/new', name: 'fil_admin_post_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$post->getCreatedAt()) {
                $post->setCreatedAt(new \DateTimeImmutable());
            }
            if (!$post->getAuthor()) {
                $post->setAuthor($this->getUser());
            }

            /** @var array<int, UploadedFile> $uploadedFiles */
            $uploadedFiles = $form->get('mediaFile')->getData() ?? [];
            $position = 0;
            foreach ($uploadedFiles as $uploadedFile) {
                if (!$uploadedFile instanceof UploadedFile) {
                    continue;
                }
                try {
                    $upload = $fileUploader->upload($uploadedFile);
                    $mime = strtolower((string) ($upload['mime'] ?? ''));
                    $type = str_starts_with($mime, 'video/') ? 'video' : 'image';

                    $media = (new PostMedia())
                        ->setPost($post)
                        ->setType($type)
                        ->setPath($upload['filename'])
                        ->setPosition($position++);
                    $post->addMedia($media);

                    if ($post->getImagePath() === null && $post->getVideoUrl() === null) {
                        if ($type === 'video') {
                            $post->setVideoUrl('/uploads/' . $upload['filename']);
                        } else {
                            $post->setImagePath($upload['filename']);
                        }
                    }
                } catch (FileException $e) {
                    $this->addFlash('error', 'Impossible d\'uploader le fichier.');
                }
            }

            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('fil_admin_post_index');
        }

        return $this->render('admin/post/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'fil_admin_post_edit')]
    public function edit(Post $post, Request $request, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<int, UploadedFile> $uploadedFiles */
            $uploadedFiles = $form->get('mediaFile')->getData() ?? [];
            if (!empty($uploadedFiles)) {
                foreach ($post->getMedias()->toArray() as $existingMedia) {
                    $post->removeMedia($existingMedia);
                }
                $post->setImagePath(null);
                $post->setVideoUrl(null);
            }
            $position = 0;
            foreach ($uploadedFiles as $uploadedFile) {
                if (!$uploadedFile instanceof UploadedFile) {
                    continue;
                }
                try {
                    $upload = $fileUploader->upload($uploadedFile);
                    $mime = strtolower((string) ($upload['mime'] ?? ''));
                    $type = str_starts_with($mime, 'video/') ? 'video' : 'image';
                    $media = (new PostMedia())
                        ->setPost($post)
                        ->setType($type)
                        ->setPath($upload['filename'])
                        ->setPosition($position++);
                    $post->addMedia($media);
                    if ($post->getImagePath() === null && $post->getVideoUrl() === null) {
                        if ($type === 'video') {
                            $post->setVideoUrl('/uploads/' . $upload['filename']);
                        } else {
                            $post->setImagePath($upload['filename']);
                        }
                    }
                } catch (FileException $e) {
                    $this->addFlash('error', 'Impossible d\'uploader le fichier.');
                }
            }

            $entityManager->flush();

            return $this->redirectToRoute('fil_admin_post_index');
        }

        return $this->render('admin/post/edit.html.twig', [
            'form' => $form,
            'post' => $post,
        ]);
    }

    #[Route('/{id}/delete', name: 'fil_admin_post_delete', methods: ['POST'])]
    public function delete(Post $post, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_post_' . $post->getId(), $request->request->get('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();
        }

        return $this->redirectToRoute('fil_admin_post_index');
    }
}
