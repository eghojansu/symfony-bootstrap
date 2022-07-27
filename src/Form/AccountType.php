<?php

namespace App\Form;

use App\Entity\Csuser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, array(
                'row_attr' => array(
                    // 'grid' => array('width' => 6),
                ),
            ))
            ->add('email')
            ->add('currentPassword')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'data_class' => Csuser::class,
            'validation_groups' => array('profile', 'current_user'),
        ));
    }
}
