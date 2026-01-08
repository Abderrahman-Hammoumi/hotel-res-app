<?php

namespace App\Controller;

use App\Entity\Room;
use App\Form\RoomType;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\Attribute\Autowire;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/room')]
final class RoomController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.audit')] private LoggerInterface $auditLogger
    ) {
    }

    #[Route(name: 'app_room_index', methods: ['GET'])]
    public function index(RoomRepository $roomRepository): Response
    {
        return $this->render('room/index.html.twig', [
            'availableRooms' => $roomRepository->findBy(['isAvailable' => true], ['number' => 'ASC']),
            'unavailableRooms' => $roomRepository->findBy(['isAvailable' => false], ['number' => 'ASC']),
            'totalRooms' => $roomRepository->count([]),
        ]);
    }

    #[Route('/new', name: 'app_room_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $room = new Room();
        $form = $this->createForm(RoomType::class, $room);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($room);
            $entityManager->flush();
            $this->auditLogger->info('Room created', [
                'roomId' => $room->getId(),
                'number' => $room->getNumber(),
                'user' => $this->getUser()?->getUserIdentifier(),
            ]);

            return $this->redirectToRoute('app_room_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('room/new.html.twig', [
            'room' => $room,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_room_show', methods: ['GET'])]
    public function show(Room $room): Response
    {
        return $this->render('room/show.html.twig', [
            'room' => $room,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_room_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Room $room, EntityManagerInterface $entityManager): Response
    {
        $availabilityOnly = $this->isGranted('ROLE_RECEPTIONIST') && !$this->isGranted('ROLE_ADMIN');
        $form = $this->createForm(RoomType::class, $room, [
            'availability_only' => $availabilityOnly,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->auditLogger->info('Room updated', [
                'roomId' => $room->getId(),
                'number' => $room->getNumber(),
                'user' => $this->getUser()?->getUserIdentifier(),
            ]);

            return $this->redirectToRoute('app_room_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('room/edit.html.twig', [
            'room' => $room,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_room_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Room $room, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$room->getId(), $request->getPayload()->getString('_token'))) {
            $roomId = $room->getId();
            $number = $room->getNumber();
            $entityManager->remove($room);
            $entityManager->flush();
            $this->auditLogger->info('Room deleted', [
                'roomId' => $roomId,
                'number' => $number,
                'user' => $this->getUser()?->getUserIdentifier(),
            ]);
        }

        return $this->redirectToRoute('app_room_index', [], Response::HTTP_SEE_OTHER);
    }
}
