<?php

namespace App\DTO;

final class Chapter implements \JsonSerializable
{
    /**
     * @param LessonSummary[] $lessons
     */
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly int $order,
        public readonly array $lessons,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'title' => $this->title,
            'lessons' => $this->lessons,
        ];
    }
}
