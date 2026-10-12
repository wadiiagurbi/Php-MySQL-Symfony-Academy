<?php

namespace App\Repository;

use App\Entity\QuizAttempt;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuizAttempt>
 */
class QuizAttemptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuizAttempt::class);
    }

    /**
     * @return QuizAttempt[]
     */
    public function findForUserAndLesson(User $user, string $lessonSlug): array
    {
        return $this->findBy(['user' => $user, 'lessonSlug' => $lessonSlug], ['attemptedAt' => 'DESC']);
    }

    /**
     * @return QuizAttempt[]
     */
    public function findAllForUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['attemptedAt' => 'DESC']);
    }
}
