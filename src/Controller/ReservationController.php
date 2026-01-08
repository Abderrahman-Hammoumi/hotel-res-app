<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use App\Repository\CustomerRepository;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\Attribute\Autowire;

#[Route('/reservation')]
final class ReservationController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.audit')] private LoggerInterface $auditLogger
    ) {
    }

    #[Route(name: 'app_reservation_index', methods: ['GET'])]
    public function index(Request $request, ReservationRepository $reservationRepository): Response
    {
        $query = $request->query->get('q', '');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;

        $result = $reservationRepository->searchPaginated($query, $page, $limit);
        $totalPages = (int) ceil(max(1, $result['total']) / $limit);

        return $this->render('reservation/index.html.twig', [
            'reservations' => $result['data'],
            'query' => $query,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CustomerRepository $customerRepository, ReservationRepository $reservationRepository): Response
    {
        $reservation = new Reservation();

        $customerId = $request->query->getInt('customer');
        if ($customerId > 0) {
            $customer = $customerRepository->find($customerId);
            if ($customer) {
                $reservation->setCustomer($customer);
            }
        }

        $form = $this->createForm(ReservationType::class, $reservation);
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
                    $this->addFlash('danger', 'This room is not available for the selected dates.');

                    return $this->render('reservation/new.html.twig', [
                        'reservation' => $reservation,
                        'form' => $form,
                    ]);
                }
            }

            $entityManager->persist($reservation);
            $entityManager->flush();
            $this->auditLogger->info('Reservation created', [
                'reservationId' => $reservation->getId(),
                'roomId' => $reservation->getRoom()?->getId(),
                'customerId' => $reservation->getCustomer()?->getId(),
                'checkIn' => $reservation->getCheckIn()?->format('Y-m-d'),
                'checkOut' => $reservation->getCheckOut()?->format('Y-m-d'),
                'status' => $reservation->getStatus(),
                'user' => $this->getUser()?->getUserIdentifier(),
            ]);

            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager, ReservationRepository $reservationRepository): Response
    {
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($reservation->getRoom() && $reservation->getCheckIn() && $reservation->getCheckOut()) {
                $hasOverlap = $reservationRepository->hasOverlap(
                    $reservation->getRoom(),
                    $reservation->getCheckIn(),
                    $reservation->getCheckOut(),
                    $reservation->getId()
                );

                if ($hasOverlap) {
                    $this->addFlash('danger', 'This room is not available for the selected dates.');

                    return $this->render('reservation/edit.html.twig', [
                        'reservation' => $reservation,
                        'form' => $form,
                    ]);
                }
            }

            $entityManager->flush();
            $this->auditLogger->info('Reservation updated', [
                'reservationId' => $reservation->getId(),
                'roomId' => $reservation->getRoom()?->getId(),
                'customerId' => $reservation->getCustomer()?->getId(),
                'checkIn' => $reservation->getCheckIn()?->format('Y-m-d'),
                'checkOut' => $reservation->getCheckOut()?->format('Y-m-d'),
                'status' => $reservation->getStatus(),
                'user' => $this->getUser()?->getUserIdentifier(),
            ]);

            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->getPayload()->getString('_token'))) {
            $reservationId = $reservation->getId();
            $roomId = $reservation->getRoom()?->getId();
            $customerId = $reservation->getCustomer()?->getId();
            $entityManager->remove($reservation);
            $entityManager->flush();
            $this->auditLogger->info('Reservation deleted', [
                'reservationId' => $reservationId,
                'roomId' => $roomId,
                'customerId' => $customerId,
                'user' => $this->getUser()?->getUserIdentifier(),
            ]);
        }

        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }
}
