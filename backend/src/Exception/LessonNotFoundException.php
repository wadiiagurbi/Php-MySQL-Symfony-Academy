<?php

namespace App\Exception;

final class LessonNotFoundException extends \RuntimeException
{
    public function __construct(string $slug)
    {
        parent::__construct(sprintf('No lesson found for slug "%s".', $slug));
    }
}
