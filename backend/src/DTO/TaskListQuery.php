<?php

namespace App\DTO;

use App\Entity\TaskPriority;
use App\Entity\TaskStatus;
use Symfony\Component\HttpFoundation\Request;

/**
 * Groups every filter/search/sort/pagination option accepted by GET /api/tasks
 * into one typed object instead of passing five loose scalar arguments around.
 */
final class TaskListQuery
{
    public function __construct(
        public readonly ?TaskStatus $status = null,
        public readonly ?TaskPriority $priority = null,
        public readonly ?int $categoryId = null,
        public readonly ?string $search = null,
        public readonly string $sortBy = 'createdAt',
        public readonly string $sortDir = 'DESC',
        public readonly int $page = 1,
        public readonly int $perPage = 10,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $status = $request->query->get('status');
        $priority = $request->query->get('priority');

        return new self(
            status: $status ? TaskStatus::tryFrom($status) : null,
            priority: $priority ? TaskPriority::tryFrom($priority) : null,
            categoryId: $request->query->has('categoryId') ? (int) $request->query->get('categoryId') : null,
            search: $request->query->get('search') ?: null,
            sortBy: $request->query->get('sortBy', 'createdAt'),
            sortDir: $request->query->get('sortDir', 'DESC'),
            page: max(1, (int) $request->query->get('page', 1)),
            perPage: min(50, max(1, (int) $request->query->get('perPage', 10))),
        );
    }
}
