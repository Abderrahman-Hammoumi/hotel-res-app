<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\User;
use App\Form\RegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(RegistrationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $email = $data['email'];

            $existing = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existing) {
                $this->addFlash('danger', 'An account with this email already exists. Please log in.');
            } else {
                $user = new User();
                $user->setEmail($email);
                $user->setRoles(['ROLE_CLIENT']);
                $user->setPassword(
                    $passwordHasher->hashPassword($user, $form->get('password')->getData())
                );

                $customer = new Customer();
                $customer->setFullName($data['fullName']);
                $customer->setEmail($email);
                $customer->setPhone($data['phone']);

                $entityManager->persist($user);
                $entityManager->persist($customer);
                $entityManager->flush();

                $this->addFlash('success', 'Account created. You can now log in.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/register.html.twig', [
            'form' => $form,
        ]);
    }
}
