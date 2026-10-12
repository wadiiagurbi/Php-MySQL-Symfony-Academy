<?php

namespace App\DTO;

final class QuizQuestion implements \JsonSerializable
{
    /**
     * @param string[] $options
     */
    public function __construct(
        public readonly string $question,
        public readonly array $options,
        public readonly int $answer,
        public readonly string $explanation = '',
    ) {
    }

    /**
     * The correct answer index is never sent to the client before an attempt is graded.
     */
    public function jsonSerialize(): array
    {
        return [
            'question' => $this->question,
            'options' => $this->options,
        ];
    }
}
