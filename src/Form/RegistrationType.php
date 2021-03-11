<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email')
            ->add('nomComplet')
            ->add('image',FileType::class,[
                'mapped'=>false
            ])
            ->add('telephone')
            ->add('password',PasswordType::class)
            ->add('confirm_password',PasswordType::class)
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Responsable de maintenance' => 'ROLE_RESPO',
                    'Administrateur' => 'ROLE_ADMIN' ,
                    'Technicien' => 'ROLE_TECH'
                ],
                
                'multiple' => true,
                'label' => 'Rôles' 
            ])
            /*->add('type_user',ChoiceType::class,[
                'choices'=>[
                    'Client'=> "client",
                    'Chef de projet'=> "chefProjet",
                    'Technicien'=>"technicien"
                ]
            ])*/
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
