<?php

namespace App\Form;

use App\Entity\Room;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoomType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['availability_only']) {
            $builder->add('isAvailable', CheckboxType::class, [
                'label' => 'Available',
                'required' => false,
            ]);

            return;
        }

        $builder
            ->add('number')
            ->add('type')
            ->add('capacity')
            ->add('price')
            ->add('isAvailable', CheckboxType::class, [
                'label' => 'Available',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Room::class,
            'availability_only' => false,
        ]);
        $resolver->setAllowedTypes('availability_only', 'bool');
    }
}
