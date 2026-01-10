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
                'label' => 'reservation.form.check_in',
            ])
            ->add('CheckOut', DateType::class, [
                'widget' => 'single_text',
                'label' => 'reservation.form.check_out',
            ])
            ->add('options', ChoiceType::class, [
                'choices' => $this->getOptionChoices(),
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'label' => 'reservation.form.extras',
                'help' => 'reservation.form.extras_help',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'reservation.form.status',
                'choices' => [
                    'reservation.status.pending' => 'pending',
                    'reservation.status.confirmed' => 'confirmed',
                    'reservation.status.canceled' => 'canceled',
                ],
                'choice_translation_domain' => 'messages',
            ])
            ->add('room', EntityType::class, [
                'class' => Room::class,
                'choice_label' => function (Room $room) {
                    return 'Room #' . $room->getNumber() . ' - ' . $room->getType() . ' - ' . $room->getPrice() . ' MAD';
                },
                'label' => 'reservation.form.select_room',
            ])
            ->add('customer', EntityType::class, [
                'class' => Customer::class,
                'choice_label' => function (Customer $customer) {
                    return $customer->getFullName() . ' (' . $customer->getEmail() . ')';
                },
                'label' => 'reservation.form.select_customer',
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
