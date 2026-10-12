<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Shape of the JSON body accepted by POST /api/tasks and PUT /api/tasks/{id}.
 * Validating this DTO (instead of the Task entity directly) keeps invalid
 * input from ever reaching Doctrine.
 */
final class TaskRequest
{
    #[Assert\NotBlank(message: 'Title is required.')]
    #[Assert\Length(max: 140, maxMessage: 'Title cannot be longer than {{ limit }} characters.')]
    public string $title = '';

    public ?string $description = null;

    #[Assert\Choice(choices: ['TODO', 'IN_PROGRESS', 'DONE'], message: 'Status must be TODO, IN_PROGRESS or DONE.')]
    public string $status = 'TODO';

    #[Assert\Choice(choices: ['LOW', 'MEDIUM', 'HIGH'], message: 'Priority must be LOW, MEDIUM or HIGH.')]
    public string $priority = 'MEDIUM';

    #[Assert\Date(message: 'Due date must be a valid date (YYYY-MM-DD).')]
    public ?string $dueDate = null;

    #[Assert\Length(max: 80)]
    public ?string $category = null;
}
