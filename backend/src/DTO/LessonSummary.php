<?php

namespace App\DTO;

final class LessonSummary implements \JsonSerializable
{
    public function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly string $track,
        public readonly string $chapter,
        public readonly string $chapterTitle,
        public readonly string $difficulty,
        public readonly int $order,
        public readonly int $globalOrder,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'track' => $this->track,
            'chapter' => $this->chapter,
            'chapterTitle' => $this->chapterTitle,
            'difficulty' => $this->difficulty,
            'order' => $this->order,
        ];
    }
}
