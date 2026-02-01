<?php

namespace Ascio\Core;

/**
 * Shared response and error handling for v3 API responses.
 * All v3 API responses follow the same pattern.
 */
class ResponseHandler
{
    /** @var string Module name for logging */
    protected string $moduleName;

    /**
     * @param string $moduleName Module name for log entries
     */
    public function __construct(string $moduleName = 'ascio')
    {
        $this->moduleName = $moduleName;
    }

    /**
     * Handle API response and extract result.
     *
     * @param object $response API response object
     * @param string $operation Operation name (e.g., 'CreateOrder', 'GetOrder')
     * @return object Result object
     * @throws AscioApiException If result code indicates error
     */
    public function handleResponse($response, string $operation)
    {
        $resultProperty = $operation . 'Result';

        if (!property_exists($response, $resultProperty)) {
            throw new AscioApiException(
                "Invalid response: missing {$resultProperty}",
                500
            );
        }

        $result = $response->$resultProperty;

        if ($result->getResultCode() !== 200) {
            throw AscioApiException::fromResult($result);
        }

        return $result;
    }

    /**
     * Extract error details from API result.
     *
     * @param object $result API result object
     * @return array Array of error details
     */
    public function extractErrors($result): array
    {
        $errors = [];

        if (!method_exists($result, 'getErrors') || !$result->getErrors()) {
            return $errors;
        }

        $errorContainer = $result->getErrors();

        // Handle ErrorCode objects
        if (method_exists($errorContainer, 'getErrorCode')) {
            foreach ($errorContainer->getErrorCode() as $error) {
                $errors[] = [
                    'code' => method_exists($error, 'getCode') ? $error->getCode() : null,
                    'message' => method_exists($error, 'getMessage') ? $error->getMessage() : null,
                    'value' => method_exists($error, 'getValue') ? $error->getValue() : null,
                    'field' => method_exists($error, 'getFieldName') ? $error->getFieldName() : null,
                ];
            }
        }

        // Handle string errors
        if (method_exists($errorContainer, 'getString')) {
            foreach ($errorContainer->getString() as $errorString) {
                $errors[] = ['message' => $errorString];
            }
        }

        return $errors;
    }

    /**
     * Format errors for display.
     *
     * @param array $errors Error array
     * @return string Formatted error string
     */
    public function formatErrors(array $errors): string
    {
        $messages = array_map(function ($error) {
            $field = $error['field'] ?? '';
            $msg = $error['message'] ?? '';
            return $field ? "{$field}: {$msg}" : $msg;
        }, $errors);

        return implode('; ', array_filter($messages));
    }

    /**
     * Log API call using WHMCS module logging.
     *
     * @param string $action Action name
     * @param mixed $request Request data
     * @param mixed $response Response data
     * @param mixed $errors Error data (optional)
     * @return void
     */
    public function logCall(string $action, $request, $response, $errors = null): void
    {
        if (function_exists('logModuleCall')) {
            logModuleCall(
                $this->moduleName,
                $action,
                $request,
                $response,
                $errors ? json_encode($errors) : null
            );
        }
    }

    /**
     * Create a standardized result array for WHMCS.
     *
     * @param object $result API result
     * @param string|null $orderId Order ID if available
     * @return array
     */
    public function toWhmcsResult($result, ?string $orderId = null): array
    {
        $errors = $this->extractErrors($result);

        $data = [
            'code' => $result->getResultCode(),
            'message' => $result->getResultMessage(),
            'status' => $result->getResultMessage(),
            'errors' => !empty($errors) ? json_encode($errors) : null,
        ];

        if ($orderId !== null) {
            $data['order_id'] = $orderId;
        }

        // Extract order info if available
        if (method_exists($result, 'getOrderInfo') && $result->getOrderInfo()) {
            $orderInfo = $result->getOrderInfo();
            $data['order_id'] = $orderInfo->getOrderId();
            $data['status'] = $orderInfo->getStatus();
        }

        return $data;
    }

    /**
     * Check if response indicates success.
     *
     * @param object $response Response object
     * @param string $operation Operation name
     * @return bool
     */
    public function isSuccess($response, string $operation): bool
    {
        $resultProperty = $operation . 'Result';

        if (!property_exists($response, $resultProperty)) {
            return false;
        }

        return $response->$resultProperty->getResultCode() === 200;
    }
}
