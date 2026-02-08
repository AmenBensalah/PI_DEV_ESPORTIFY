<?php

namespace App\Controller\Admin;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
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
    public function index(PostRepository $postRepository): Response
    {
        return $this->render('admin/post/index.html.twig', [
            'posts' => $postRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'fil_admin_post_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$post->getCreatedAt()) {
                $post->setCreatedAt(new \DateTimeImmutable());
            }
            if (!$post->getMediaType()) {
                $post->setMediaType('text');
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

                try {
                    $uploadedFile->move($uploadDir, $filename);
                    $post->setMediaFilename($filename);

                    if (in_array($post->getMediaType(), ['text', 'link'], true)) {
                        $mimeType = $uploadedFile->getMimeType() ?? '';
                        $post->setMediaType(str_starts_with($mimeType, 'video/') ? 'video' : 'image');
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
    public function edit(Post $post, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PostType::class, $post);
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

                try {
                    $uploadedFile->move($uploadDir, $filename);
                    $post->setMediaFilename($filename);

                    if (in_array($post->getMediaType(), ['text', 'link'], true)) {
                        $mimeType = $uploadedFile->getMimeType() ?? '';
                        $post->setMediaType(str_starts_with($mimeType, 'video/') ? 'video' : 'image');
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
