<?php

namespace App\Controller\Api;

use App\Repository\LessonCompletionRepository;
use App\Service\CourseContentLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;

class CourseController extends AbstractController
{
    #[Route('/api/courses', name: 'api_courses', methods: ['GET'])]
    public function __invoke(
        CourseContentLoader $loader,
        LessonCompletionRepository $completionRepository,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        $completedSlugs = $user ? $completionRepository->findCompletedSlugsForUser($user) : [];

        $tracks = $loader->getCourseTree();

        return $this->json([
            'tracks' => $tracks,
            'completedLessonSlugs' => $completedSlugs,
        ]);
    }
}
