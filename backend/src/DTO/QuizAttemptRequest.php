<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class QuizAttemptRequest
{
    /**
     * One entry per question, in order: the index of the option the learner picked.
     *
     * @var int[]
     */
    #[Assert\NotNull(message: 'answers is required.')]
    #[Assert\All([
        new Assert\Type(type: 'integer', message: 'Each answer must be an option index.'),
    ])]
    public array $answers = [];
}
