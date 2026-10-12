<?php

namespace App\Tests\Functional;

use App\Tests\Support\ApiTestCase;

class TaskApiTest extends ApiTestCase
{
    public function testCreateAndListTask(): void
    {
        $token = $this->registerAndLogin();

        $this->client->request('POST', '/api/tasks', server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']), content: json_encode([
            'title' => 'Write the migration',
            'status' => 'TODO',
            'priority' => 'HIGH',
            'category' => 'Backend',
        ]));

        self::assertResponseStatusCodeSame(201);
        $created = $this->jsonBody();
        self::assertSame('Write the migration', $created['title']);
        self::assertSame('Backend', $created['category']);

        $this->client->request('GET', '/api/tasks', server: $this->authHeader($token));
        self::assertResponseIsSuccessful();
        $list = $this->jsonBody();
        self::assertSame(1, $list['total']);
        self::assertCount(1, $list['items']);
    }

    public function testCreateTaskRejectsBlankTitle(): void
    {
        $token = $this->registerAndLogin();

        $this->client->request('POST', '/api/tasks', server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']), content: json_encode([
            'title' => '',
        ]));

        self::assertResponseStatusCodeSame(422);
    }

    public function testUpdateTask(): void
    {
        $token = $this->registerAndLogin();
        $id = $this->createTask($token, 'Original title');

        $this->client->request('PUT', "/api/tasks/{$id}", server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']), content: json_encode([
            'title' => 'Updated title',
            'status' => 'DONE',
            'priority' => 'LOW',
        ]));

        self::assertResponseIsSuccessful();
        $data = $this->jsonBody();
        self::assertSame('Updated title', $data['title']);
        self::assertSame('DONE', $data['status']);
    }

    public function testDeleteTask(): void
    {
        $token = $this->registerAndLogin();
        $id = $this->createTask($token, 'To be deleted');

        $this->client->request('DELETE', "/api/tasks/{$id}", server: $this->authHeader($token));
        self::assertResponseStatusCodeSame(204);

        $this->client->request('GET', "/api/tasks/{$id}", server: $this->authHeader($token));
        self::assertResponseStatusCodeSame(404);
    }

    public function testUserCannotAccessAnotherUsersTask(): void
    {
        $tokenA = $this->registerAndLogin('owner@example.com');
        $id = $this->createTask($tokenA, 'Owner-only task');

        $tokenB = $this->registerAndLogin('intruder@example.com');
        $this->client->request('GET', "/api/tasks/{$id}", server: $this->authHeader($tokenB));

        self::assertResponseStatusCodeSame(404);
    }

    public function testFilteringByStatus(): void
    {
        $token = $this->registerAndLogin();
        $this->createTask($token, 'Todo task', 'TODO');
        $this->createTask($token, 'Done task', 'DONE');

        $this->client->request('GET', '/api/tasks?status=DONE', server: $this->authHeader($token));
        $data = $this->jsonBody();

        self::assertSame(1, $data['total']);
        self::assertSame('Done task', $data['items'][0]['title']);
    }

    public function testSearchingByTitle(): void
    {
        $token = $this->registerAndLogin();
        $this->createTask($token, 'Deploy backend service');
        $this->createTask($token, 'Buy groceries');

        $this->client->request('GET', '/api/tasks?search=backend', server: $this->authHeader($token));
        $data = $this->jsonBody();

        self::assertSame(1, $data['total']);
        self::assertSame('Deploy backend service', $data['items'][0]['title']);
    }

    public function testPagination(): void
    {
        $token = $this->registerAndLogin();
        for ($i = 1; $i <= 15; ++$i) {
            $this->createTask($token, "Task {$i}");
        }

        $this->client->request('GET', '/api/tasks?page=2&perPage=10', server: $this->authHeader($token));
        $data = $this->jsonBody();

        self::assertSame(15, $data['total']);
        self::assertCount(5, $data['items']);
        self::assertSame(2, $data['page']);
    }

    public function testStatsEndpointCountsByStatus(): void
    {
        $token = $this->registerAndLogin();
        $this->createTask($token, 'A', 'TODO');
        $this->createTask($token, 'B', 'TODO');
        $this->createTask($token, 'C', 'DONE');

        $this->client->request('GET', '/api/stats', server: $this->authHeader($token));
        $data = $this->jsonBody();

        self::assertSame(2, $data['byStatus']['TODO']);
        self::assertSame(1, $data['byStatus']['DONE']);
        self::assertSame(0, $data['byStatus']['IN_PROGRESS']);
    }

    private function createTask(string $token, string $title, string $status = 'TODO'): int
    {
        $this->client->request('POST', '/api/tasks', server: array_merge($this->authHeader($token), ['CONTENT_TYPE' => 'application/json']), content: json_encode([
            'title' => $title,
            'status' => $status,
        ]));

        return $this->jsonBody()['id'];
    }
}
