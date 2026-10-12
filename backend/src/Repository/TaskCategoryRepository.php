<?php

namespace App\Repository;

use App\Entity\TaskCategory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TaskCategory>
 */
class TaskCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskCategory::class);
    }

    /**
     * @return TaskCategory[]
     */
    public function findAllForOwner(User $owner): array
    {
        return $this->findBy(['owner' => $owner], ['name' => 'ASC']);
    }

    public function findOneForOwnerAndName(User $owner, string $name): ?TaskCategory
    {
        return $this->findOneBy(['owner' => $owner, 'name' => $name]);
    }
}
