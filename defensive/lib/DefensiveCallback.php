<?php

namespace Ascio\Defensive;

use Ascio\Core\AbstractCallback;
use Ascio\Core\ObjectType;

/**
 * Callback handler for Defensive Registration (DPML) status updates.
 */
class DefensiveCallback extends AbstractCallback
{
    /**
     * {@inheritdoc}
     */
    public function getTableName(): string
    {
        return 'mod_ascio_defensive';
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectType(): string
    {
        return ObjectType::DEFENSIVE;
    }

    /**
     * {@inheritdoc}
     */
    protected function getModuleName(): string
    {
        return 'asciodefensive';
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
        if (method_exists($orderRequest, 'getDefensive')) {
            return $orderRequest->getDefensive();
        }

        return null;
    }

    /**
     * Process completed status.
     */
    protected function processCompleted(): void
    {
        $defensive = $this->getObjectFromOrder();

        if ($defensive) {
            $handle = $defensive->getHandle();
            $this->setData('handle', $handle);

            // Get full info from API
            try {
                $response = $this->client->getDefensive($handle);
                $result = $response->GetDefensiveResult;

                if ($result->getResultCode() === 200 && $result->getDefensiveInfo()) {
                    $info = $result->getDefensiveInfo();
                    $this->setData('expire_date', $info->getExpDate());

                    if ($info->getAuthInfo()) {
                        $this->setData('auth_info', $info->getAuthInfo());
                    }
                }
            } catch (\Exception $e) {
                $this->responseHandler->logCall('GetDefensive', $handle, null, $e->getMessage());
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
        if ($this->message) {
            $messageText = is_string($this->message) ? $this->message : '';
            if (is_object($this->message) && method_exists($this->message, 'getMessage')) {
                $messageText = $this->message->getMessage();
            }
            $this->setData('message', $messageText);
        }
    }
}
