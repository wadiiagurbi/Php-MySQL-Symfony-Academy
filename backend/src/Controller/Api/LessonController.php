<?php

namespace App\Controller\Api;

use App\Service\CourseContentLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class LessonController extends AbstractController
{
    #[Route('/api/lessons/{slug}', name: 'api_lesson_show', methods: ['GET'])]
    public function __invoke(string $slug, CourseContentLoader $loader): JsonResponse
    {
        // LessonNotFoundException bubbles up to ApiExceptionListener, which turns it
        // into a clean JSON 404 instead of Symfony's default HTML error page.
        $lesson = $loader->getLesson($slug);

        return $this->json($lesson);
    }
}
