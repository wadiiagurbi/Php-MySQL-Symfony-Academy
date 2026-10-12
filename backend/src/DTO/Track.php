<?php

namespace App\DTO;

final class Track implements \JsonSerializable
{
    /**
     * @param Chapter[] $chapters
     */
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly int $order,
        public readonly array $chapters,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'title' => $this->title,
            'chapters' => $this->chapters,
        ];
    }
}
