<?php

namespace App\Form;

use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'maxlength' => 2000,
                    'data-post-field' => 'content',
                ],
                'label' => 'Contenu',
                'constraints' => [
                    new Assert\Length(max: 2000, maxMessage: 'Le contenu ne doit pas depasser {{ limit }} caracteres.'),
                ],
            ])
            ->add('imagePath', TextType::class, [
                'required' => false,
                'label' => 'Image (URL)',
                'attr' => [
                    'maxlength' => 255,
                    'data-post-field' => 'imagePath',
                ],
                'constraints' => [
                    new Assert\Length(max: 255, maxMessage: 'L\'URL image ne doit pas depasser {{ limit }} caracteres.'),
                ],
            ])
            ->add('videoUrl', UrlType::class, [
                'required' => false,
                'label' => 'Lien video',
                'attr' => [
                    'maxlength' => 255,
                    'data-post-field' => 'videoUrl',
                ],
                'constraints' => [
                    new Assert\Length(max: 255, maxMessage: 'L\'URL video ne doit pas depasser {{ limit }} caracteres.'),
                    new Assert\Url(message: 'L\'URL video est invalide.'),
                ],
            ])
            ->add('isEvent', CheckboxType::class, [
                'required' => false,
                'label' => 'Publication evenement',
                'attr' => [
                    'data-post-field' => 'isEvent',
                ],
            ])
            ->add('eventTitle', TextType::class, [
                'required' => false,
                'label' => 'Titre evenement',
                'attr' => [
                    'maxlength' => 180,
                    'data-post-field' => 'eventTitle',
                ],
                'constraints' => [
                    new Assert\Length(max: 180, maxMessage: 'Le titre de l\'evenement ne doit pas depasser {{ limit }} caracteres.'),
                ],
            ])
            ->add('eventDate', DateTimeType::class, [
                'required' => false,
                'label' => 'Date et heure',
                'widget' => 'single_text',
                'attr' => [
                    'data-post-field' => 'eventDate',
                ],
            ])
            ->add('eventLocation', TextType::class, [
                'required' => false,
                'label' => 'Lieu / Lien',
                'attr' => [
                    'maxlength' => 255,
                    'data-post-field' => 'eventLocation',
                ],
                'constraints' => [
                    new Assert\Length(max: 255, maxMessage: 'Le lieu ne doit pas depasser {{ limit }} caracteres.'),
                ],
            ])
            ->add('maxParticipants', IntegerType::class, [
                'required' => false,
                'label' => 'Places disponibles',
                'attr' => [
                    'min' => 1,
                    'max' => 100000,
                    'data-post-field' => 'maxParticipants',
                ],
                'constraints' => [
                    new Assert\Positive(message: 'Le nombre de places doit etre superieur a 0.'),
                    new Assert\LessThanOrEqual(100000, message: 'Le nombre de places est trop eleve.'),
                ],
            ])
            ->add('mediaFile', FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Joindre un fichier (image ou video)',
                'multiple' => true,
                'attr' => [
                    'data-post-field' => 'mediaFile',
                ],
                'constraints' => [
                    new Assert\All([
                        'constraints' => [
                            new Assert\File(
                                maxSize: '20M',
                                maxSizeMessage: 'Le fichier ne doit pas depasser 20 Mo.',
                                mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'],
                                mimeTypesMessage: 'Le fichier doit etre une image ou une video valide.'
                            ),
                        ],
                    ]),
                ],
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $post = $event->getData();
            if (!$post instanceof Post) {
                return;
            }

            $isHttpUrl = static function (string $value): bool {
                if (!preg_match('/^https?:\/\//i', $value)) {
                    return true;
                }
                return filter_var($value, FILTER_VALIDATE_URL) !== false;
            };

            $content = trim((string) $post->getContent());
            $imagePath = trim((string) $post->getImagePath());
            $videoUrl = trim((string) $post->getVideoUrl());
            $uploadedFile = $form->get('mediaFile')->getData();

            if ($imagePath !== '' && !$isHttpUrl($imagePath)) {
                $form->get('imagePath')->addError(new FormError('URL image invalide.'));
            }

            if (!$post->isEvent() && $content === '' && $imagePath === '' && $videoUrl === '' && !$uploadedFile) {
                $form->addError(new FormError('Ajoutez au moins un contenu: texte, URL image, URL video ou fichier media.'));
            }

            if ($post->isEvent()) {
                if (trim((string) $post->getEventTitle()) === '') {
                    $form->get('eventTitle')->addError(new FormError('Le titre de l\'evenement est obligatoire.'));
                }
                if (!$post->getEventDate()) {
                    $form->get('eventDate')->addError(new FormError('La date de l\'evenement est obligatoire.'));
                }
                if (trim((string) $post->getEventLocation()) === '') {
                    $form->get('eventLocation')->addError(new FormError('Le lieu de l\'evenement est obligatoire.'));
                }
                if (!$post->getMaxParticipants() || $post->getMaxParticipants() < 1) {
                    $form->get('maxParticipants')->addError(new FormError('Le nombre de places doit etre superieur a 0.'));
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}
