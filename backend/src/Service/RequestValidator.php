<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Decodes a request's JSON body straight into a typed DTO and validates it.
 * Controllers call this once instead of repeating decode+validate boilerplate,
 * and any violation is turned into a clean 422 JSON response by ApiExceptionListener.
 */
class RequestValidator
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $dtoClass
     *
     * @return T
     */
    public function fromJson(Request $request, string $dtoClass): object
    {
        $dto = $this->serializer->deserialize($request->getContent() ?: '{}', $dtoClass, 'json');

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            throw new ValidationFailedException($dto, $violations);
        }

        return $dto;
    }
}
