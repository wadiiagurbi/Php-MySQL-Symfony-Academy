<?php

namespace App\Controller\Api;

use App\DTO\RegisterRequest;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\RequestValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController
{
    /**
     * Login itself is handled declaratively by the "api_login" json_login firewall
     * (see config/packages/security.yaml) — Lexik JWT issues the token, so there is
     * no controller action for it here.
     */
    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(
        Request $request,
        RequestValidator $requestValidator,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $data = $requestValidator->fromJson($request, RegisterRequest::class);

        if (null !== $userRepository->findOneByEmail($data->email)) {
            return $this->json(['error' => ['message' => 'An account with this email already exists.']], 409);
        }

        $user = new User($data->email, $data->displayName);
        $user->setPassword($passwordHasher->hashPassword($user, $data->password));

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'displayName' => $user->getDisplayName(),
        ], 201);
    }
}
