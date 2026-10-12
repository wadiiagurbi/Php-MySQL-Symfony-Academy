<?php

namespace App\Controller\Api;

use App\DTO\TaskListQuery;
use App\DTO\TaskRequest;
use App\DTO\TaskView;
use App\Entity\Task;
use App\Entity\User;
use App\Repository\TaskRepository;
use App\Service\RequestValidator;
use App\Service\TaskService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class TaskController extends AbstractController
{
    #[Route('/api/tasks', name: 'api_tasks_list', methods: ['GET'])]
    public function list(Request $request, TaskService $taskService): JsonResponse
    {
        $result = $taskService->list($this->currentUser(), TaskListQuery::fromRequest($request));

        return $this->json([
            'items' => array_map(TaskView::fromEntity(...), $result['items']),
            'total' => $result['total'],
            'page' => $result['page'],
            'perPage' => $result['perPage'],
        ]);
    }

    #[Route('/api/tasks', name: 'api_tasks_create', methods: ['POST'])]
    public function create(Request $request, RequestValidator $requestValidator, TaskService $taskService): JsonResponse
    {
        $data = $requestValidator->fromJson($request, TaskRequest::class);
        $task = $taskService->create($this->currentUser(), $data);

        return $this->json(TaskView::fromEntity($task), 201);
    }

    #[Route('/api/tasks/{id}', name: 'api_tasks_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, TaskRepository $taskRepository): JsonResponse
    {
        return $this->json(TaskView::fromEntity($this->findOwnedTask($id, $taskRepository)));
    }

    #[Route('/api/tasks/{id}', name: 'api_tasks_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(
        int $id,
        Request $request,
        TaskRepository $taskRepository,
        RequestValidator $requestValidator,
        TaskService $taskService,
    ): JsonResponse {
        $task = $this->findOwnedTask($id, $taskRepository);
        $data = $requestValidator->fromJson($request, TaskRequest::class);

        return $this->json(TaskView::fromEntity($taskService->update($task, $data)));
    }

    #[Route('/api/tasks/{id}', name: 'api_tasks_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id, TaskRepository $taskRepository, TaskService $taskService): JsonResponse
    {
        $taskService->delete($this->findOwnedTask($id, $taskRepository));

        return $this->json(null, 204);
    }

    private function findOwnedTask(int $id, TaskRepository $taskRepository): Task
    {
        $task = $taskRepository->find($id);

        if (null === $task || $task->getOwner()->getId() !== $this->currentUser()->getId()) {
            throw new NotFoundHttpException('Task not found.');
        }

        return $task;
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = $this->getUser();

        return $user;
    }
}
