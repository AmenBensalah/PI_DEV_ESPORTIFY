<?php

namespace App\Form;

use App\Entity\Announcement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnnouncementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('tag', TextType::class, [
                'label' => 'Tag',
            ])
            ->add('content', TextareaType::class, [
                'required' => false,
                'attr' => ['rows' => 4],
                'label' => 'Description',
            ])
            ->add('link', TextType::class, [
                'required' => false,
                'label' => 'Lien',
            ])
            ->add('mediaType', ChoiceType::class, [
                'choices' => [
                    'Texte' => 'text',
                    'Image' => 'image',
                    'Video' => 'video',
                    'Lien' => 'link',
                ],
                'label' => 'Type media',
            ])
            ->add('mediaFilename', TextType::class, [
                'required' => false,
                'label' => 'Media (URL)',
            ])
            ->add('mediaFile', FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Joindre un fichier (image ou vidéo)',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Announcement::class,
        ]);
    }
}
