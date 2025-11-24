<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ErrorHandlingService - Centralized error handling untuk aplikasi E-Clean
 *
 * Usage:
 * - ErrorHandlingService::handle(callable $callback, string $context)
 * - ErrorHandlingService::handleWithFallback(callable $callback, mixed $fallback, string $context)
 */
class ErrorHandlingService
{
    /**
     * Handle exceptions dengan logging
     *
     * @param callable $callback
     * @param string $context
     * @return mixed
     * @throws Throwable
     */
    public static function handle(callable $callback, string $context = 'Unknown')
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            self::logError($e, $context);
            throw $e;
        }
    }

    /**
     * Handle exceptions dengan fallback value
     *
     * @param callable $callback
     * @param mixed $fallback
     * @param string $context
     * @return mixed
     */
    public static function handleWithFallback(callable $callback, $fallback = null, string $context = 'Unknown')
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            self::logError($e, $context);
            return $fallback;
        }
    }

    /**
     * Handle database operations dengan retry
     *
     * @param callable $callback
     * @param int $maxRetries
     * @param int $delay milliseconds
     * @param string $context
     * @return mixed
     * @throws Throwable
     */
    public static function handleWithRetry(callable $callback, int $maxRetries = 3, int $delay = 1000, string $context = 'Unknown')
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            try {
                return $callback();
            } catch (Throwable $e) {
                $attempt++;
                $lastException = $e;

                if ($attempt < $maxRetries) {
                    self::logWarning($e, $context, $attempt, $maxRetries);
                    usleep($delay * 1000); // Convert ms to microseconds
                } else {
                    self::logError($e, $context);
                }
            }
        }

        throw $lastException;
    }

    /**
     * Handle Fonnte API calls dengan proper error handling
     *
     * @param callable $callback
     * @param string $context
     * @return array
     */
    public static function handleFonnteApi(callable $callback, string $context = 'Fonnte API'): array
    {
        try {
            $response = $callback();

            // Check if response is successful
            if (isset($response['status']) && $response['status'] === false) {
                Log::warning("{$context} - API returned error", [
                    'response' => $response,
                    'context' => $context,
                ]);

                return [
                    'success' => false,
                    'message' => $response['message'] ?? 'Unknown error from Fonnte API',
                    'data' => $response,
                ];
            }

            return [
                'success' => true,
                'message' => 'Success',
                'data' => $response,
            ];
        } catch (Throwable $e) {
            self::logError($e, $context);

            return [
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Handle file operations dengan proper error handling
     *
     * @param callable $callback
     * @param string $context
     * @return array
     */
    public static function handleFileOperation(callable $callback, string $context = 'File Operation'): array
    {
        try {
            $result = $callback();

            return [
                'success' => true,
                'message' => 'File operation completed successfully',
                'data' => $result,
            ];
        } catch (\Exception $e) {
            self::logError($e, $context);

            return [
                'success' => false,
                'message' => 'File operation failed: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Log error dengan detail context
     *
     * @param Throwable $e
     * @param string $context
     * @return void
     */
    private static function logError(Throwable $e, string $context): void
    {
        Log::error("{$context} - Error occurred", [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'context' => $context,
            'user_id' => auth()->id(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Log warning untuk retry attempts
     *
     * @param Throwable $e
     * @param string $context
     * @param int $attempt
     * @param int $maxRetries
     * @return void
     */
    private static function logWarning(Throwable $e, string $context, int $attempt, int $maxRetries): void
    {
        Log::warning("{$context} - Retry attempt {$attempt}/{$maxRetries}", [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'context' => $context,
            'attempt' => $attempt,
            'max_retries' => $maxRetries,
        ]);
    }

    /**
     * Safe array access dengan default value
     *
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function safeGet(array $array, string $key, $default = null)
    {
        try {
            return $array[$key] ?? $default;
        } catch (Throwable $e) {
            self::logError($e, "Safe Array Access - {$key}");
            return $default;
        }
    }

    /**
     * Safe divide operation (prevent division by zero)
     *
     * @param float $numerator
     * @param float $denominator
     * @param float $default
     * @return float
     */
    public static function safeDivide(float $numerator, float $denominator, float $default = 0.0): float
    {
        try {
            if ($denominator == 0) {
                Log::warning('Division by zero attempted', [
                    'numerator' => $numerator,
                    'denominator' => $denominator,
                ]);
                return $default;
            }

            return $numerator / $denominator;
        } catch (Throwable $e) {
            self::logError($e, 'Safe Divide');
            return $default;
        }
    }

    /**
     * Validate and sanitize input
     *
     * @param mixed $input
     * @param string $type
     * @param mixed $default
     * @return mixed
     */
    public static function validateInput($input, string $type = 'string', $default = null)
    {
        try {
            return match ($type) {
                'int' => filter_var($input, FILTER_VALIDATE_INT) !== false ? (int)$input : $default,
                'float' => filter_var($input, FILTER_VALIDATE_FLOAT) !== false ? (float)$input : $default,
                'bool' => filter_var($input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default,
                'email' => filter_var($input, FILTER_VALIDATE_EMAIL) !== false ? $input : $default,
                'url' => filter_var($input, FILTER_VALIDATE_URL) !== false ? $input : $default,
                default => is_string($input) ? trim($input) : $default,
            };
        } catch (Throwable $e) {
            self::logError($e, "Input Validation - {$type}");
            return $default;
        }
    }
}
