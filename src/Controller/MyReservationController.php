<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\CustomerRepository;
use App\Repository\ReservationRepository;
use App\Form\ClientReservationType;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\Attribute\Autowire;

final class MyReservationController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.audit')] private LoggerInterface $auditLogger
    ) {
    }

    #[Route('/my/reservations', name: 'app_my_reservations', methods: ['GET'])]
    public function index(
        ReservationRepository $reservationRepository,
        CustomerRepository $customerRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $customer = $customerRepository->findOneBy(['email' => $user->getEmail()]);
        $reservations = [];

        if ($customer) {
            $reservations = $reservationRepository->findBy(
                ['customer' => $customer],
                ['CheckIn' => 'DESC']
            );
        }

        return $this->render('my_reservation/index.html.twig', [
            'reservations' => $reservations,
            'customer' => $customer,
        ]);
    }

    #[Route('/my/reservations/new', name: 'app_my_reservation_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        CustomerRepository $customerRepository,
        ReservationRepository $reservationRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $customer = $customerRepository->findOneBy(['email' => $user->getEmail()]);

        if (!$customer) {
            $this->addFlash('danger', 'flash.customer_profile_missing');
            return $this->redirectToRoute('app_my_reservations');
        }

        $reservation = new Reservation();
        $reservation->setCustomer($customer);
        $reservation->setStatus('pending');

        $form = $this->createForm(ClientReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($reservation->getRoom() && $reservation->getCheckIn() && $reservation->getCheckOut()) {
                $hasOverlap = $reservationRepository->hasOverlap(
                    $reservation->getRoom(),
                    $reservation->getCheckIn(),
                    $reservation->getCheckOut(),
                    null
                );

                if ($hasOverlap) {
                    $this->addFlash('danger', 'flash.room_not_available_dates');

                    return $this->render('my_reservation/new.html.twig', [
                        'form' => $form,
                    ]);
                }
            }

            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'flash.reservation_submitted');
            $this->auditLogger->info('Client reservation created', [
                'reservationId' => $reservation->getId(),
                'roomId' => $reservation->getRoom()?->getId(),
                'customerId' => $reservation->getCustomer()?->getId(),
                'checkIn' => $reservation->getCheckIn()?->format('Y-m-d'),
                'checkOut' => $reservation->getCheckOut()?->format('Y-m-d'),
                'status' => $reservation->getStatus(),
                'user' => $this->getUser()?->getUserIdentifier(),
            ]);

            return $this->redirectToRoute('app_my_reservations');
        }

        return $this->render('my_reservation/new.html.twig', [
            'form' => $form,
        ]);
    }
}
