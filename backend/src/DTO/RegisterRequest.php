<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterRequest
{
    #[Assert\NotBlank(message: 'Email is required.')]
    #[Assert\Email(message: 'Please provide a valid email address.')]
    public string $email = '';

    #[Assert\NotBlank(message: 'Password is required.')]
    #[Assert\Length(min: 8, minMessage: 'Password must be at least {{ limit }} characters long.')]
    public string $password = '';

    #[Assert\NotBlank(message: 'Display name is required.')]
    #[Assert\Length(max: 120)]
    public string $displayName = '';
}
