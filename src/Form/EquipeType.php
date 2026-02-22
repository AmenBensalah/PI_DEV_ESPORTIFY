<?php

namespace App\Form;

use App\Entity\Equipe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EquipeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomEquipe')
            ->add('logo')
            ->add('description')
            ->add('tag')
            ->add('region', ChoiceType::class, [
                'choices' => [
                    'Asia' => 'Asia',
                    'Europe' => 'Europe',
                    'Middle East' => 'Middle East',
                    'North America' => 'North America',
                    'South America' => 'South America',
                    'Oceania' => 'Oceania',
                    'Africa' => 'Africa',
                ],
                'required' => false,
                'placeholder' => 'Selectionner une region',
            ])
            ->add('discordInviteUrl', UrlType::class, [
                'required' => false,
            ])
            ->add('classement')
            ->add('maxMembers')
            ->add('isPrivate');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Equipe::class,
        ]);
    }
}
