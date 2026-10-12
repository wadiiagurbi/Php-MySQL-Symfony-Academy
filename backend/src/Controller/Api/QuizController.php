<?php

namespace App\Controller\Api;

use App\DTO\QuizAttemptRequest;
use App\Entity\QuizAttempt;
use App\Entity\User;
use App\Service\CourseContentLoader;
use App\Service\RequestValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class QuizController extends AbstractController
{
    #[Route('/api/quizzes/{slug}/attempt', name: 'api_quiz_attempt', methods: ['POST'])]
    public function attempt(
        string $slug,
        Request $request,
        CourseContentLoader $loader,
        RequestValidator $requestValidator,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $lesson = $loader->getLesson($slug);
        $data = $requestValidator->fromJson($request, QuizAttemptRequest::class);

        $questions = $lesson->quiz;
        if ([] === $questions) {
            return $this->json(['error' => ['message' => 'This lesson has no quiz.']], 404);
        }

        $score = 0;
        $answerBreakdown = [];
        foreach ($questions as $index => $question) {
            $selected = $data->answers[$index] ?? -1;
            $isCorrect = $selected === $question->answer;
            if ($isCorrect) {
                ++$score;
            }

            $answerBreakdown[] = [
                'question' => $question->question,
                'selected' => $selected,
                'correct' => $question->answer,
                'isCorrect' => $isCorrect,
                'explanation' => $question->explanation,
            ];
        }

        /** @var User $user */
        $user = $this->getUser();

        $attempt = new QuizAttempt($user, $slug, $score, count($questions), $answerBreakdown);
        $entityManager->persist($attempt);
        $entityManager->flush();

        return $this->json([
            'slug' => $slug,
            'score' => $score,
            'totalQuestions' => count($questions),
            'percentage' => $attempt->getPercentage(),
            'answers' => $answerBreakdown,
        ], 201);
    }
}
