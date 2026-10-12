<?php

namespace App\EventListener;

use App\Exception\LessonNotFoundException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * The API must never return Symfony's default HTML error page. This listener turns
 * every exception raised while handling a /api/* request into a consistent JSON body:
 * { "error": { "message": "...", "details": [...] } }.
 */
#[AsEventListener(event: 'kernel.exception')]
class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();
        [$status, $message, $details] = $this->resolve($exception);

        $event->setResponse(new JsonResponse([
            'error' => array_filter([
                'message' => $message,
                'details' => $details,
            ], static fn ($value) => null !== $value),
        ], $status));
    }

    /**
     * @return array{0: int, 1: string, 2: array|null}
     */
    private function resolve(\Throwable $exception): array
    {
        if ($exception instanceof LessonNotFoundException) {
            return [404, $exception->getMessage(), null];
        }

        if ($exception instanceof ValidationFailedException) {
            $details = [];
            foreach ($exception->getViolations() as $violation) {
                $details[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }

            return [422, 'Validation failed.', $details];
        }

        if ($exception instanceof AuthenticationException) {
            return [401, 'Authentication required.', null];
        }

        if ($exception instanceof HttpExceptionInterface) {
            return [$exception->getStatusCode(), $exception->getMessage() ?: 'Request failed.', null];
        }

        return [500, 'Internal server error.', null];
    }
}
