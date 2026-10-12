<?php

namespace App\DTO;

use App\Entity\Task;

/**
 * The Task entity is a Doctrine mapping concern; the API should never serialize it
 * directly (that would leak the owner relation and couple the wire format to the
 * database schema). TaskView is the read model a controller actually returns.
 */
final class TaskView implements \JsonSerializable
{
    public static function fromEntity(Task $task): self
    {
        return new self($task);
    }

    private function __construct(private readonly Task $task)
    {
    }

    public function jsonSerialize(): array
    {
        $task = $this->task;

        return [
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus()->value,
            'priority' => $task->getPriority()->value,
            'dueDate' => $task->getDueDate()?->format('Y-m-d'),
            'category' => $task->getCategory()?->getName(),
            'createdAt' => $task->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $task->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
