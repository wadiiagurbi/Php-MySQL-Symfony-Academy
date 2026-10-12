<?php

namespace App\Tests\Functional;

use App\Tests\Support\ApiTestCase;

class AuthTest extends ApiTestCase
{
    public function testRegisterCreatesUser(): void
    {
        $this->client->request('POST', '/api/auth/register', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => 'new@example.com',
            'password' => 'password123',
            'displayName' => 'New User',
        ]));

        self::assertResponseStatusCodeSame(201);
        $data = $this->jsonBody();
        self::assertSame('new@example.com', $data['email']);
    }

    public function testRegisterRejectsDuplicateEmail(): void
    {
        $this->registerAndLogin('duplicate@example.com');

        $this->client->request('POST', '/api/auth/register', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => 'duplicate@example.com',
            'password' => 'password123',
            'displayName' => 'Another User',
        ]));

        self::assertResponseStatusCodeSame(409);
    }

    public function testRegisterRejectsInvalidPayload(): void
    {
        $this->client->request('POST', '/api/auth/register', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => 'not-an-email',
            'password' => 'short',
            'displayName' => '',
        ]));

        self::assertResponseStatusCodeSame(422);
        $data = $this->jsonBody();
        self::assertArrayHasKey('details', $data['error']);
    }

    public function testLoginReturnsJwtToken(): void
    {
        $token = $this->registerAndLogin();

        self::assertStringStartsWith('Bearer ey', $token);
    }

    public function testLoginRejectsWrongPassword(): void
    {
        $this->registerAndLogin('wrongpass@example.com', 'password123');

        $this->client->request('POST', '/api/auth/login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => 'wrongpass@example.com',
            'password' => 'incorrect',
        ]));

        self::assertResponseStatusCodeSame(401);
    }

    public function testMeRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/me');

        self::assertResponseStatusCodeSame(401);
    }

    public function testMeReturnsCurrentUserWithValidToken(): void
    {
        $token = $this->registerAndLogin('me@example.com');

        $this->client->request('GET', '/api/me', server: $this->authHeader($token));

        self::assertResponseIsSuccessful();
        $data = $this->jsonBody();
        self::assertSame('me@example.com', $data['email']);
    }
}
