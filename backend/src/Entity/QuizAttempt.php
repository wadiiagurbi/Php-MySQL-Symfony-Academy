<?php

namespace App\Entity;

use App\Repository\QuizAttemptRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizAttemptRepository::class)]
#[ORM\Table(name: 'quiz_attempt')]
class QuizAttempt
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
    private int $score;

    #[ORM\Column]
    private int $totalQuestions;

    /** @var array<int, array{question: string, selected: int, correct: int, isCorrect: bool}> */
    #[ORM\Column(type: Types::JSON)]
    private array $answers;

    #[ORM\Column]
    private \DateTimeImmutable $attemptedAt;

    public function __construct(User $user, string $lessonSlug, int $score, int $totalQuestions, array $answers)
    {
        $this->user = $user;
        $this->lessonSlug = $lessonSlug;
        $this->score = $score;
        $this->totalQuestions = $totalQuestions;
        $this->answers = $answers;
        $this->attemptedAt = new \DateTimeImmutable();
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

    public function getScore(): int
    {
        return $this->score;
    }

    public function getTotalQuestions(): int
    {
        return $this->totalQuestions;
    }

    public function getAnswers(): array
    {
        return $this->answers;
    }

    public function getAttemptedAt(): \DateTimeImmutable
    {
        return $this->attemptedAt;
    }

    public function getPercentage(): int
    {
        if (0 === $this->totalQuestions) {
            return 0;
        }

        return (int) round(($this->score / $this->totalQuestions) * 100);
    }
}
