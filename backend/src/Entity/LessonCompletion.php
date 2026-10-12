<?php

namespace App\Entity;

use App\Repository\LessonCompletionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LessonCompletionRepository::class)]
#[ORM\Table(name: 'lesson_completion')]
#[ORM\UniqueConstraint(name: 'uniq_user_lesson', columns: ['user_id', 'lesson_slug'])]
class LessonCompletion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 180)]
    private string $lessonSlug;

    #[ORM\Column]
    private \DateTimeImmutable $completedAt;

    public function __construct(User $user, string $lessonSlug)
    {
        $this->user = $user;
        $this->lessonSlug = $lessonSlug;
        $this->completedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getLessonSlug(): string
    {
        return $this->lessonSlug;
    }

    public function getCompletedAt(): \DateTimeImmutable
    {
        return $this->completedAt;
    }
}
