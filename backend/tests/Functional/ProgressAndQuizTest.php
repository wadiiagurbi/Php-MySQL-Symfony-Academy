<?php

namespace App\Tests\Functional;

use App\Tests\Support\ApiTestCase;

class ProgressAndQuizTest extends ApiTestCase
{
    public function testCompleteLessonMarksProgress(): void
    {
        $token = $this->registerAndLogin();

        $this->client->request('POST', '/api/lessons/fixture-valid-lesson/complete', server: $this->authHeader($token));
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/api/progress', server: $this->authHeader($token));
        self::assertResponseIsSuccessful();

        $data = $this->jsonBody();
        self::assertSame(['fixture-valid-lesson'], $data['completedLessonSlugs']);
        self::assertSame(1, $data['completedCount']);
        self::assertSame(2, $data['totalLessons']);
        self::assertSame(50, $data['completionPercentage']);
    }

    public function testUncompleteLessonRemovesProgress(): void
    {
        $token = $this->registerAndLogin();

        $this->client->request('POST', '/api/lessons/fixture-valid-lesson/complete', server: $this->authHeader($token));
        $this->client->request('DELETE', '/api/lessons/fixture-valid-lesson/complete', server: $this->authHeader($token));
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/api/progress', server: $this->authHeader($token));
        $data = $this->jsonBody();
        self::assertSame([], $data['completedLessonSlugs']);
    }

    public function testCompletingLessonRequiresAuthentication(): void
    {
        $this->client->request('POST', '/api/lessons/fixture-valid-lesson/complete');

        self::assertResponseStatusCodeSame(401);
    }

    public function testQuizAttemptIsGradedAndStored(): void
    {
        $token = $this->registerAndLogin();

        // fixture-valid-lesson has exactly one question with correct answer index 1.
        $this->client->request('POST', '/api/quizzes/fixture-valid-lesson/attempt', server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']), content: json_encode(['answers' => [1]]));

        self::assertResponseStatusCodeSame(201);
        $data = $this->jsonBody();
        self::assertSame(1, $data['score']);
        self::assertSame(100, $data['percentage']);

        $this->client->request('GET', '/api/progress', server: $this->authHeader($token));
        $progress = $this->jsonBody();
        self::assertSame(1, $progress['quizAttemptsCount']);
        self::assertSame(100, $progress['quizAverageScore']);
    }

    public function testQuizAttemptWithWrongAnswerScoresZero(): void
    {
        $token = $this->registerAndLogin();

        $this->client->request('POST', '/api/quizzes/fixture-valid-lesson/attempt', server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']), content: json_encode(['answers' => [0]]));

        self::assertResponseStatusCodeSame(201);
        $data = $this->jsonBody();
        self::assertSame(0, $data['score']);
        self::assertFalse($data['answers'][0]['isCorrect']);
    }
}
