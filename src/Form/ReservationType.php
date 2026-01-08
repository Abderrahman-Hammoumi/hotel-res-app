<?php

namespace App\Form;

use App\Entity\Customer;
use App\Entity\Reservation;
use App\Entity\Room;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('CheckIn', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('CheckOut', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('options', ChoiceType::class, [
                'choices' => $this->getOptionChoices(),
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'label' => 'Additional options',
                'help' => 'Optional services are billed once per stay.',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Pending' => 'pending',
                    'Confirmed' => 'confirmed',
                    'Canceled' => 'canceled',
                ],
            ])
            ->add('room', EntityType::class, [
                'class' => Room::class,
                'choice_label' => function (Room $room) {
                    return 'Room #' . $room->getNumber() . ' - ' . $room->getType() . ' - ' . $room->getPrice() . ' MAD';
                },
            ])
            ->add('customer', EntityType::class, [
                'class' => Customer::class,
                'choice_label' => function (Customer $customer) {
                    return $customer->getFullName() . ' (' . $customer->getEmail() . ')';
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function getOptionChoices(): array
    {
        $choices = [];

        foreach (Reservation::OPTION_PRICING as $key => $option) {
            $label = sprintf('%s (+%s MAD)', $option['label'], $option['price']);
            $choices[$label] = $key;
        }

        return $choices;
    }
}
