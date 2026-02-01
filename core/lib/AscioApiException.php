<?php

namespace Ascio\Core;

/**
 * Exception for Ascio API errors.
 * Contains structured error information from API responses.
 */
class AscioApiException extends \Exception
{
    /** @var array Detailed error list from API */
    protected array $errors;

    /** @var mixed The request that caused the error */
    protected $request;

    /**
     * @param string $message Error message
     * @param int $code Result code from API
     * @param array $errors Detailed error array
     * @param mixed $request Original request for debugging
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message,
        int $code = 0,
        array $errors = [],
        $request = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
        $this->request = $request;
    }

    /**
     * Get detailed errors from API response.
     *
     * @return array Array of error details
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get the original request that caused the error.
     *
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Format errors for display to users.
     *
     * @return string Formatted error message
     */
    public function getFormattedErrors(): string
    {
        if (empty($this->errors)) {
            return $this->getMessage();
        }

        $messages = array_map(function ($error) {
            $field = $error['field'] ?? '';
            $msg = $error['message'] ?? '';
            return $field ? "{$field}: {$msg}" : $msg;
        }, $this->errors);

        return implode('; ', array_filter($messages));
    }

    /**
     * Get errors as array suitable for JSON encoding.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'code' => $this->getCode(),
            'message' => $this->getMessage(),
            'errors' => $this->errors,
        ];
    }

    /**
     * Create exception from API result object.
     *
     * @param object $result API result object
     * @param mixed $request Original request
     * @return self
     */
    public static function fromResult($result, $request = null): self
    {
        $errors = [];
        if (method_exists($result, 'getErrors') && $result->getErrors()) {
            $errorCodes = $result->getErrors();
            if (method_exists($errorCodes, 'getErrorCode')) {
                foreach ($errorCodes->getErrorCode() as $error) {
                    $errors[] = [
                        'code' => method_exists($error, 'getCode') ? $error->getCode() : null,
                        'message' => method_exists($error, 'getMessage') ? $error->getMessage() : null,
                        'value' => method_exists($error, 'getValue') ? $error->getValue() : null,
                        'field' => method_exists($error, 'getFieldName') ? $error->getFieldName() : null,
                    ];
                }
            } elseif (method_exists($errorCodes, 'getString')) {
                // Alternative error format
                foreach ($errorCodes->getString() as $errorString) {
                    $errors[] = ['message' => $errorString];
                }
            }
        }

        return new self(
            method_exists($result, 'getResultMessage') ? $result->getResultMessage() : 'API Error',
            method_exists($result, 'getResultCode') ? $result->getResultCode() : 0,
            $errors,
            $request
        );
    }
}
