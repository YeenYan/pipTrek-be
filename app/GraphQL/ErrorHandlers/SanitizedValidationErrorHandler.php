<?php

namespace App\GraphQL\ErrorHandlers;

use Closure;
use GraphQL\Error\ClientAware;
use GraphQL\Error\Error;
use Nuwave\Lighthouse\Exceptions\ValidationException;
use Nuwave\Lighthouse\Execution\ErrorHandler;

/**
 * Cleans up GraphQL error responses for frontend consumption.
 *
 * Handles:
 * - Lighthouse ValidationException: strips 'input.' prefix, returns field-level errors
 * - ClientAware exceptions: returns the message with the exception's category
 * - Returns clean responses without file paths, line numbers, or stack traces
 * - Short-circuits the error pipeline (bypasses the debug formatter)
 */
class SanitizedValidationErrorHandler implements ErrorHandler
{
    public function __invoke(?Error $error, Closure $next): ?array
    {
        if ($error === null) {
            return $next(null);
        }

        $previous = $error->getPrevious();

        // Handle Lighthouse validation errors (field-level)
        if ($previous instanceof ValidationException) {
            $extensions = $previous->getExtensions();
            $validation = $extensions['validation'] ?? [];

            $cleaned = [];
            foreach ($validation as $field => $messages) {
                $cleanField = preg_replace('/^input\./', '', $field);
                $cleanMessages = array_map(
                    fn (string $msg) => str_replace('input.', '', $msg),
                    $messages,
                );
                $cleaned[$cleanField] = $cleanMessages;
            }

            return [
                'message' => 'Validation failed.',
                'extensions' => [
                    'category' => 'validation',
                    'validation' => $cleaned,
                ],
            ];
        }

        // Handle ClientAware exceptions (authentication, business logic)
        if ($previous instanceof ClientAware && $previous->isClientSafe()) {

            $category = method_exists($previous, 'getCategory')
                ? $previous->getCategory()
                : 'authentication';

            return [
                'message' => $previous->getMessage(),
                'extensions' => [
                    'category' => $category,
                ],
        ];
}

        return $next($error);
    }
}
