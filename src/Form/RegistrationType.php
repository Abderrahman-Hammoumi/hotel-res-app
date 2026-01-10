<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'auth.full_name',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 3, max: 100),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'auth.email',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                    new Assert\Length(max: 180),
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'auth.phone_optional',
                'required' => false,
                'constraints' => [
                    new Assert\Length(max: 20),
                    new Assert\Regex(pattern: '/^$|^[0-9 +()\\-]{6,20}$/', message: 'auth.phone_invalid'),
                ],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'auth.password',
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 6, max: 4096),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
