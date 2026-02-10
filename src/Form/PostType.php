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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'required' => false,
                'attr' => ['rows' => 4],
                'label' => 'Contenu',
            ])
            ->add('imagePath', TextType::class, [
                'required' => false,
                'label' => 'Image (URL)',
            ])
            ->add('videoUrl', TextType::class, [
                'required' => false,
                'label' => 'Lien vidéo',
            ])
            ->add('isEvent', CheckboxType::class, [
                'required' => false,
                'label' => 'Publication événement',
            ])
            ->add('eventTitle', TextType::class, [
                'required' => false,
                'label' => 'Titre de l\'événement',
            ])
            ->add('eventDate', DateTimeType::class, [
                'required' => false,
                'label' => 'Date et heure',
                'widget' => 'single_text',
            ])
            ->add('eventLocation', TextType::class, [
                'required' => false,
                'label' => 'Lieu / Lien',
            ])
            ->add('maxParticipants', IntegerType::class, [
                'required' => false,
                'label' => 'Places disponibles',
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
            'data_class' => Post::class,
        ]);
    }
}
