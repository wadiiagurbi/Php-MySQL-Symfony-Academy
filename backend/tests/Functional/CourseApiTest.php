<?php

namespace App\Tests\Functional;

use App\Tests\Support\ApiTestCase;

class CourseApiTest extends ApiTestCase
{
    public function testCoursesEndpointReturnsTree(): void
    {
        $this->client->request('GET', '/api/courses');

        self::assertResponseIsSuccessful();
        $data = $this->jsonBody();

        self::assertArrayHasKey('tracks', $data);
        self::assertSame('php', $data['tracks'][0]['key']);
        self::assertSame('fixture-valid-lesson', $data['tracks'][0]['chapters'][0]['lessons'][0]['slug']);
    }

    public function testLessonEndpointReturnsStructuredContent(): void
    {
        $this->client->request('GET', '/api/lessons/fixture-valid-lesson');

        self::assertResponseIsSuccessful();
        $data = $this->jsonBody();

        self::assertSame('Fixture Valid Lesson', $data['title']);
        self::assertArrayHasKey('sections', $data);
        self::assertArrayHasKey('explanation', $data['sections']);
        self::assertCount(1, $data['quiz']);
        // The correct answer index must never be exposed by the API.
        self::assertArrayNotHasKey('answer', $data['quiz'][0]);
    }

    public function testMissingLessonReturnsJson404(): void
    {
        $this->client->request('GET', '/api/lessons/does-not-exist');

        self::assertResponseStatusCodeSame(404);
        self::assertSame('application/json', $this->client->getResponse()->headers->get('Content-Type'));

        $data = $this->jsonBody();
        self::assertArrayHasKey('error', $data);
    }

    public function testMalformedLessonAlsoReturnsJson404InsteadOfCrashing(): void
    {
        $this->client->request('GET', '/api/lessons/fixture-malformed-lesson');

        self::assertResponseStatusCodeSame(404);
    }

    public function testSearchEndpointFindsLessonByContent(): void
    {
        $this->client->request('GET', '/api/search?q=hello+two');

        self::assertResponseIsSuccessful();
        $data = $this->jsonBody();

        self::assertNotEmpty($data['results']);
        self::assertSame('fixture-valid-lesson-two', $data['results'][0]['lesson']['slug']);
    }
}
