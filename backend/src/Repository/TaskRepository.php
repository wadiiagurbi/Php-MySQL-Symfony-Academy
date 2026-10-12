<?php

namespace App\Repository;

use App\DTO\TaskListQuery;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    private const SORTABLE_FIELDS = ['createdAt', 'updatedAt', 'dueDate', 'priority', 'title'];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * @return array{items: Task[], total: int}
     */
    public function findByQuery(User $owner, TaskListQuery $query): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.owner = :owner')
            ->setParameter('owner', $owner);

        if (null !== $query->status) {
            $qb->andWhere('t.status = :status')->setParameter('status', $query->status);
        }

        if (null !== $query->priority) {
            $qb->andWhere('t.priority = :priority')->setParameter('priority', $query->priority);
        }

        if (null !== $query->categoryId) {
            $qb->andWhere('t.category = :categoryId')->setParameter('categoryId', $query->categoryId);
        }

        if (null !== $query->search && '' !== $query->search) {
            $qb->andWhere('LOWER(t.title) LIKE :search OR LOWER(t.description) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower($query->search).'%');
        }

        $total = (int) (clone $qb)
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $sortField = in_array($query->sortBy, self::SORTABLE_FIELDS, true) ? $query->sortBy : 'createdAt';
        $sortDir = 'ASC' === strtoupper($query->sortDir) ? 'ASC' : 'DESC';

        $qb->orderBy('t.'.$sortField, $sortDir)
            ->setFirstResult(($query->page - 1) * $query->perPage)
            ->setMaxResults($query->perPage);

        return [
            'items' => $qb->getQuery()->getResult(),
            'total' => $total,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function countByStatusForOwner(User $owner): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select('t.status AS status, COUNT(t.id) AS total')
            ->andWhere('t.owner = :owner')
            ->setParameter('owner', $owner)
            ->groupBy('t.status')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $status = $row['status'] instanceof \BackedEnum ? $row['status']->value : $row['status'];
            $counts[$status] = (int) $row['total'];
        }

        return $counts;
    }
}
