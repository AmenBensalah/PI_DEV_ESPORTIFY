<?php

namespace App\Form;

use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
                'label' => 'Media (URL ou fichier)',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}
