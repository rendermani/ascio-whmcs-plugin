<?php

namespace Ascio\Monitoring;

use Ascio\Core\AbstractCallback;
use Ascio\Core\ObjectType;

/**
 * Callback handler for Domain Monitoring (NameWatch) status updates.
 */
class MonitoringCallback extends AbstractCallback
{
    /**
     * {@inheritdoc}
     */
    public function getTableName(): string
    {
        return 'mod_ascio_monitoring';
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectType(): string
    {
        return ObjectType::NAME_WATCH;
    }

    /**
     * {@inheritdoc}
     */
    protected function getModuleName(): string
    {
        return 'asciomonitoring';
    }

    /**
     * {@inheritdoc}
     */
    protected function processStatus(): void
    {
        if ($this->isCompleted()) {
            $this->processCompleted();
        } elseif ($this->isFailed()) {
            $this->processFailed();
        } elseif ($this->isPendingUserAction()) {
            $this->processPendingUserAction();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getObjectFromOrder()
    {
        if (!$this->order) {
            return null;
        }

        $orderRequest = $this->order->getOrderRequest();
        if (method_exists($orderRequest, 'getNameWatch')) {
            return $orderRequest->getNameWatch();
        }

        return null;
    }

    /**
     * Process completed status.
     */
    protected function processCompleted(): void
    {
        $nameWatch = $this->getObjectFromOrder();

        if ($nameWatch) {
            $handle = $nameWatch->getHandle();
            $this->setData('handle', $handle);

            // Get full info from API
            try {
                $response = $this->client->getNameWatch($handle);
                $result = $response->GetNameWatchResult;

                if ($result->getResultCode() === 200 && $result->getNameWatchInfo()) {
                    $info = $result->getNameWatchInfo();
                    $this->setData('expire_date', $info->getExpDate());
                }
            } catch (\Exception $e) {
                $this->responseHandler->logCall('GetNameWatch', $handle, null, $e->getMessage());
            }
        }
    }

    /**
     * Process failed status.
     */
    protected function processFailed(): void
    {
        if ($this->message) {
            $messageText = is_string($this->message) ? $this->message : '';
            if (is_object($this->message) && method_exists($this->message, 'getMessage')) {
                $messageText = $this->message->getMessage();
            }
            $this->setData('message', $messageText);
        }
    }

    /**
     * Process pending user action status.
     */
    protected function processPendingUserAction(): void
    {
        // Domain monitoring typically doesn't require user action
        // But log the message for reference
        if ($this->message) {
            $messageText = is_string($this->message) ? $this->message : '';
            if (is_object($this->message) && method_exists($this->message, 'getMessage')) {
                $messageText = $this->message->getMessage();
            }
            $this->setData('message', $messageText);
        }
    }
}
