<?php

namespace App\Form;
use App\Enum\Role;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('roles')
            ->add('password')
            ->add('nom')
            ->add('role', ChoiceType::class, [
                'choices' => [
                'Admin' => Role::ADMIN,
                'Joueur' => Role::JOUEUR,
                'Manager' => Role::MANAGER,
                'Organisateur' => Role::ORGANISATEUR,
            ],
    'choice_label' => fn (Role $role) => $role->value,
])
            ->add('pseudo')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
