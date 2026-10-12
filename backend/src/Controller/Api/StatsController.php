<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\TaskService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class StatsController extends AbstractController
{
    #[Route('/api/stats', name: 'api_stats', methods: ['GET'])]
    public function __invoke(TaskService $taskService): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json(['byStatus' => $taskService->statsByStatus($user)]);
    }
}
