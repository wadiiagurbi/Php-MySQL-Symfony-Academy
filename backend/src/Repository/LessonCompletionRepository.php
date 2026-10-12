<?php

namespace App\Repository;

use App\Entity\LessonCompletion;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LessonCompletion>
 */
class LessonCompletionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LessonCompletion::class);
    }

    public function findOneForUserAndLesson(User $user, string $lessonSlug): ?LessonCompletion
    {
        return $this->findOneBy(['user' => $user, 'lessonSlug' => $lessonSlug]);
    }

    /**
     * @return string[]
     */
    public function findCompletedSlugsForUser(User $user): array
    {
        $rows = $this->createQueryBuilder('lc')
            ->select('lc.lessonSlug')
            ->andWhere('lc.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'lessonSlug');
    }
}
