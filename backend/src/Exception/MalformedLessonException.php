<?php

namespace App\Exception;

final class MalformedLessonException extends \RuntimeException
{
    public function __construct(string $path, string $reason)
    {
        parent::__construct(sprintf('Lesson file "%s" is malformed: %s', $path, $reason));
    }
}
