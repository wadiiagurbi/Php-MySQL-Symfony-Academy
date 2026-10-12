<?php

namespace App\DTO;

final class LessonContent implements \JsonSerializable
{
    /**
     * @param array<string, string> $sections keyed by: learningObjective, explanation, whyItMatters,
     *                                         example, code, codeLanguage, codeExplanation, commonMistakes,
     *                                         bestPractices, exercise, solution
     * @param QuizQuestion[]         $quiz
     * @param string[]               $tags
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $track,
        public readonly string $trackTitle,
        public readonly string $chapter,
        public readonly string $chapterTitle,
        public readonly string $title,
        public readonly string $difficulty,
        public readonly int $order,
        public readonly int $estimatedMinutes,
        public readonly array $tags,
        public readonly array $sections,
        public readonly array $quiz,
        public ?LessonSummary $previous = null,
        public ?LessonSummary $next = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'slug' => $this->slug,
            'track' => $this->track,
            'trackTitle' => $this->trackTitle,
            'chapter' => $this->chapter,
            'chapterTitle' => $this->chapterTitle,
            'title' => $this->title,
            'difficulty' => $this->difficulty,
            'order' => $this->order,
            'estimatedMinutes' => $this->estimatedMinutes,
            'tags' => $this->tags,
            'sections' => $this->sections,
            'quiz' => $this->quiz,
            'navigation' => [
                'previous' => $this->previous,
                'next' => $this->next,
            ],
        ];
    }
}
