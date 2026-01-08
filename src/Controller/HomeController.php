<?php

namespace App\Controller;

use App\Repository\RoomRepository;
use App\Repository\ReservationRepository;
use App\Repository\CustomerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        RoomRepository $roomRepository,
        ReservationRepository $reservationRepository,
        CustomerRepository $customerRepository
    ): Response
    {
        if ($this->isGranted('ROLE_CLIENT') && !$this->isGranted('ROLE_RECEPTIONIST') && !$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_my_reservations');
        }

        if ($this->isGranted('ROLE_RECEPTIONIST') && !$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_reservation_index');
        }

        $totalRooms = $roomRepository->count([]);
        $availableRooms = $roomRepository->count(['isAvailable' => true]);
        $totalReservations = $reservationRepository->count([]);
        $totalCustomers = $customerRepository->count([]);

        return $this->render('home/index.html.twig', [
            'totalRooms' => $totalRooms,
            'availableRooms' => $availableRooms,
            'totalReservations' => $totalReservations,
            'totalCustomers' => $totalCustomers,
        ]);
    }
}
