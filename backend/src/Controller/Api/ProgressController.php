<?php

namespace App\Controller\Api;

use App\Entity\LessonCompletion;
use App\Entity\User;
use App\Repository\LessonCompletionRepository;
use App\Repository\QuizAttemptRepository;
use App\Service\CourseContentLoader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ProgressController extends AbstractController
{
    #[Route('/api/progress', name: 'api_progress', methods: ['GET'])]
    public function show(
        LessonCompletionRepository $completionRepository,
        QuizAttemptRepository $quizAttemptRepository,
        CourseContentLoader $loader,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $completedSlugs = $completionRepository->findCompletedSlugsForUser($user);
        $totalLessons = 0;
        foreach ($loader->getCourseTree() as $track) {
            foreach ($track->chapters as $chapter) {
                $totalLessons += count($chapter->lessons);
            }
        }

        $attempts = $quizAttemptRepository->findAllForUser($user);
        $averageScore = 0;
        if ([] !== $attempts) {
            $averageScore = (int) round(
                array_sum(array_map(static fn ($a) => $a->getPercentage(), $attempts)) / count($attempts)
            );
        }

        return $this->json([
            'completedLessonSlugs' => $completedSlugs,
            'totalLessons' => $totalLessons,
            'completedCount' => count($completedSlugs),
            'completionPercentage' => $totalLessons > 0 ? (int) round((count($completedSlugs) / $totalLessons) * 100) : 0,
            'lastLessonSlug' => $user->getLastLessonSlug(),
            'quizAttemptsCount' => count($attempts),
            'quizAverageScore' => $averageScore,
        ]);
    }

    #[Route('/api/lessons/{slug}/complete', name: 'api_lesson_complete', methods: ['POST'])]
    public function complete(
        string $slug,
        CourseContentLoader $loader,
        LessonCompletionRepository $completionRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        // Throws LessonNotFoundException -> JSON 404 if the slug doesn't exist.
        $loader->getLesson($slug);

        /** @var User $user */
        $user = $this->getUser();

        if (null === $completionRepository->findOneForUserAndLesson($user, $slug)) {
            $entityManager->persist(new LessonCompletion($user, $slug));
        }

        $user->setLastLessonSlug($slug);
        $entityManager->flush();

        return $this->json(['slug' => $slug, 'completed' => true]);
    }

    #[Route('/api/lessons/{slug}/complete', name: 'api_lesson_uncomplete', methods: ['DELETE'])]
    public function uncomplete(
        string $slug,
        LessonCompletionRepository $completionRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $completion = $completionRepository->findOneForUserAndLesson($user, $slug);
        if (null !== $completion) {
            $entityManager->remove($completion);
            $entityManager->flush();
        }

        return $this->json(['slug' => $slug, 'completed' => false]);
    }
}
