<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public const OPTION_PRICING = [
        'breakfast' => [
            'label' => 'Breakfast',
            'price' => 80.0,
        ],
        'airport_pickup' => [
            'label' => 'Airport pickup',
            'price' => 150.0,
        ],
        'late_checkout' => [
            'label' => 'Late checkout',
            'price' => 50.0,
        ],
    ];

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'Check-in date is required.')]
    private ?\DateTime $CheckIn = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'Check-out date is required.')]
    #[Assert\GreaterThan(propertyPath: 'CheckIn', message: 'Check-out date must be after check-in date.')]
    private ?\DateTime $CheckOut = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?float $totalPrice = 0;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Assert\Type('array')]
    private ?array $options = [];

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?float $optionsTotal = 0;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['pending', 'confirmed', 'canceled'])]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'A room is required.')]
    private ?Room $room = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'A customer is required.')]
    private ?Customer $customer = null;

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTotalPrice(): void
    {
        $this->optionsTotal = $this->calculateOptionsTotal();
        $this->totalPrice = $this->calculateTotalPrice();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCheckIn(): ?\DateTime
    {
        return $this->CheckIn;
    }

    public function setCheckIn(\DateTime $CheckIn): static
    {
        $this->CheckIn = $CheckIn;

        return $this;
    }

    public function getCheckOut(): ?\DateTime
    {
        return $this->CheckOut;
    }

    public function setCheckOut(\DateTime $CheckOut): static
    {
        $this->CheckOut = $CheckOut;

        return $this;
    }

    public function getTotalPrice(): ?float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(float $totalPrice): static
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options ?? [];
    }

    public function setOptions(?array $options): static
    {
        $this->options = $options ?? [];
        $this->optionsTotal = $this->calculateOptionsTotal();

        return $this;
    }

    public function getOptionsTotal(): float
    {
        return $this->optionsTotal ?? 0;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): static
    {
        $this->room = $room;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function calculateTotalPrice(): float
    {
        if (!$this->room || !$this->CheckIn || !$this->CheckOut) {
            return 0;
        }

        $days = $this->getNights();
        $optionsTotal = $this->calculateOptionsTotal();

        return ($days * $this->room->getPrice()) + $optionsTotal;
    }

    public function getNights(): int
    {
        $days = 0;

        if ($this->CheckIn && $this->CheckOut) {
            $days = $this->CheckIn->diff($this->CheckOut)->days;
        }

        return $days > 0 ? $days : 1;
    }

    public function getReadableOptions(): array
    {
        $labels = [];

        foreach ($this->getOptions() as $option) {
            if (isset(self::OPTION_PRICING[$option])) {
                $data = self::OPTION_PRICING[$option];
                $labels[] = sprintf('%s (+%s MAD)', $data['label'], $data['price']);
            }
        }

        return $labels;
    }

    private function calculateOptionsTotal(): float
    {
        $total = 0;

        foreach ($this->getOptions() as $option) {
            if (isset(self::OPTION_PRICING[$option])) {
                $total += self::OPTION_PRICING[$option]['price'];
            }
        }

        return $total;
    }

}
