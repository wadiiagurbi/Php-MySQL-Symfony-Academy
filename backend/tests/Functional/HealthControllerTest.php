<?php

namespace App\Tests\Functional;

use App\Tests\Support\ApiTestCase;

class HealthControllerTest extends ApiTestCase
{
    public function testHealthEndpointReturnsOk(): void
    {
        $this->client->request('GET', '/api/health');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(
            '{"status":"ok","app":"php-symfony-academy-backend"}',
            $this->client->getResponse()->getContent()
        );
    }
}
