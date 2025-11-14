<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait SecureErrorHandling
{
    /**
     * Handle exceptions securely - log full details, return safe message to user
     *
     * @param \Exception $exception
     * @param string $userMessage User-friendly message
     * @param string $context Additional context for logging
     * @param int $statusCode HTTP status code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleSecureException(\Exception $exception, string $userMessage, string $context = '', int $statusCode = 500)
    {
        // Log full exception details for debugging
        Log::error($userMessage . ($context ? " - {$context}" : ''), [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => config('app.debug') ? $exception->getTraceAsString() : 'See logs',
            'user_id' => auth()->id(),
        ]);

        // Return generic message in production, detailed in development
        $message = config('app.debug')
            ? $userMessage . ': ' . $exception->getMessage()
            : $userMessage;

        return $this->errorResponse($message, $statusCode);
    }
}
