<?php

namespace App\Form;

use App\Entity\Announcement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AnnouncementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => [
                    'maxlength' => 180,
                    'data-ann-field' => 'title',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le titre est obligatoire.'),
                    new Assert\Length(max: 180, maxMessage: 'Le titre ne doit pas depasser {{ limit }} caracteres.'),
                ],
            ])
            ->add('tag', TextType::class, [
                'label' => 'Tag',
                'attr' => [
                    'maxlength' => 60,
                    'data-ann-field' => 'tag',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le tag est obligatoire.'),
                    new Assert\Length(max: 60, maxMessage: 'Le tag ne doit pas depasser {{ limit }} caracteres.'),
                ],
            ])
            ->add('content', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'maxlength' => 5000,
                    'data-ann-field' => 'content',
                ],
                'label' => 'Description',
                'constraints' => [
                    new Assert\Length(max: 5000, maxMessage: 'La description ne doit pas depasser {{ limit }} caracteres.'),
                ],
            ])
            ->add('link', UrlType::class, [
                'required' => false,
                'label' => 'Lien',
                'attr' => [
                    'maxlength' => 255,
                    'data-ann-field' => 'link',
                ],
                'constraints' => [
                    new Assert\Length(max: 255, maxMessage: 'Le lien ne doit pas depasser {{ limit }} caracteres.'),
                    new Assert\Url(message: 'Le lien est invalide.'),
                ],
            ])
            ->add('mediaType', ChoiceType::class, [
                'choices' => [
                    'Texte' => 'text',
                    'Image' => 'image',
                    'Video' => 'video',
                    'Lien' => 'link',
                ],
                'label' => 'Type media',
                'attr' => [
                    'data-ann-field' => 'mediaType',
                ],
            ])
            ->add('mediaFilename', TextType::class, [
                'required' => false,
                'label' => 'Media (URL)',
                'attr' => [
                    'maxlength' => 255,
                    'data-ann-field' => 'mediaFilename',
                ],
                'constraints' => [
                    new Assert\Length(max: 255, maxMessage: 'Le media URL ne doit pas depasser {{ limit }} caracteres.'),
                ],
            ])
            ->add('mediaFile', FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Joindre un fichier (image ou video)',
                'attr' => [
                    'data-ann-field' => 'mediaFile',
                ],
                'constraints' => [
                    new Assert\File(
                        maxSize: '20M',
                        maxSizeMessage: 'Le fichier ne doit pas depasser 20 Mo.',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'video/mp4', 'video/webm', 'video/ogg'],
                        mimeTypesMessage: 'Le fichier doit etre une image/video valide.'
                    ),
                ],
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $announcement = $event->getData();
            if (!$announcement instanceof Announcement) {
                return;
            }

            $isHttpUrl = static function (string $value): bool {
                return filter_var($value, FILTER_VALIDATE_URL) !== false && preg_match('/^https?:\/\//i', $value) === 1;
            };

            $mediaType = (string) $announcement->getMediaType();
            $content = trim((string) $announcement->getContent());
            $link = trim((string) $announcement->getLink());
            $mediaFilename = trim((string) $announcement->getMediaFilename());
            $uploadedFile = $form->get('mediaFile')->getData();

            if ($mediaType === 'text' && $content === '') {
                $form->get('content')->addError(new FormError('La description est obligatoire pour une annonce texte.'));
            }

            if ($mediaType === 'link' && $link === '' && $mediaFilename === '') {
                $form->get('link')->addError(new FormError('Ajoutez un lien pour une annonce de type lien.'));
            }
            if ($link !== '' && !$isHttpUrl($link)) {
                $form->get('link')->addError(new FormError('Lien invalide.'));
            }
            if ($mediaType === 'link' && $mediaFilename !== '' && !$isHttpUrl($mediaFilename)) {
                $form->get('mediaFilename')->addError(new FormError('URL media invalide pour une annonce de type lien.'));
            }
            if (in_array($mediaType, ['image', 'video'], true) && $mediaFilename !== '' && preg_match('/^https?:\/\//i', $mediaFilename) === 1 && !$isHttpUrl($mediaFilename)) {
                $form->get('mediaFilename')->addError(new FormError('URL media invalide.'));
            }

            if (in_array($mediaType, ['image', 'video'], true) && $mediaFilename === '' && !$uploadedFile) {
                $form->get('mediaFilename')->addError(new FormError('Ajoutez une URL media ou uploadez un fichier.'));
            }

            if ($uploadedFile) {
                $mimeType = (string) $uploadedFile->getMimeType();
                if ($mediaType === 'image' && !str_starts_with($mimeType, 'image/')) {
                    $form->get('mediaFile')->addError(new FormError('Le fichier doit etre une image pour le type image.'));
                }
                if ($mediaType === 'video' && !str_starts_with($mimeType, 'video/')) {
                    $form->get('mediaFile')->addError(new FormError('Le fichier doit etre une video pour le type video.'));
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Announcement::class,
        ]);
    }
}
