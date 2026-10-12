<?php

namespace App\Service;

use App\DTO\TaskListQuery;
use App\DTO\TaskRequest;
use App\Entity\Task;
use App\Entity\TaskPriority;
use App\Entity\TaskStatus;
use App\Entity\User;
use App\Repository\TaskCategoryRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Business logic for tasks lives here, not in the controller. The controller's job is
 * only to translate HTTP <-> DTOs; TaskService decides how a task is actually created,
 * updated, and looked up, and is the piece unit tests target directly.
 */
class TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly TaskCategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{items: Task[], total: int, page: int, perPage: int}
     */
    public function list(User $owner, TaskListQuery $query): array
    {
        $result = $this->taskRepository->findByQuery($owner, $query);

        return [
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $query->page,
            'perPage' => $query->perPage,
        ];
    }

    public function create(User $owner, TaskRequest $request): Task
    {
        $task = new Task($owner, $request->title);
        $this->applyRequest($task, $request, $owner);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }

    public function update(Task $task, TaskRequest $request): Task
    {
        $task->setTitle($request->title);
        $this->applyRequest($task, $request, $task->getOwner());

        $this->entityManager->flush();

        return $task;
    }

    public function delete(Task $task): void
    {
        $this->entityManager->remove($task);
        $this->entityManager->flush();
    }

    /**
     * @return array<string, int>
     */
    public function statsByStatus(User $owner): array
    {
        $counts = $this->taskRepository->countByStatusForOwner($owner);

        $stats = [];
        foreach (TaskStatus::cases() as $status) {
            $stats[$status->value] = $counts[$status->value] ?? 0;
        }

        return $stats;
    }

    private function applyRequest(Task $task, TaskRequest $request, User $owner): void
    {
        $task->setDescription($request->description);
        $task->setStatus(TaskStatus::from($request->status));
        $task->setPriority(TaskPriority::from($request->priority));
        $task->setDueDate(null !== $request->dueDate ? new \DateTimeImmutable($request->dueDate) : null);

        if (null === $request->category || '' === trim($request->category)) {
            $task->setCategory(null);

            return;
        }

        $category = $this->categoryRepository->findOneForOwnerAndName($owner, $request->category);
        if (null === $category) {
            $category = new \App\Entity\TaskCategory($owner, $request->category);
            $this->entityManager->persist($category);
        }

        $task->setCategory($category);
    }
}
