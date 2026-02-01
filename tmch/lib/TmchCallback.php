<?php

namespace Ascio\Tmch;

use Ascio\Core\AbstractCallback;
use Ascio\Core\ObjectType;

/**
 * Callback handler for TMCH (Trademark Clearinghouse) status updates.
 */
class TmchCallback extends AbstractCallback
{
    /**
     * {@inheritdoc}
     */
    public function getTableName(): string
    {
        return 'mod_ascio_tmch';
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectType(): string
    {
        return ObjectType::MARK;
    }

    /**
     * {@inheritdoc}
     */
    protected function getModuleName(): string
    {
        return 'asciotmch';
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
        if (method_exists($orderRequest, 'getMark')) {
            return $orderRequest->getMark();
        }

        return null;
    }

    /**
     * Process completed status.
     */
    protected function processCompleted(): void
    {
        $mark = $this->getObjectFromOrder();

        if ($mark) {
            $handle = $mark->getHandle();
            $this->setData('handle', $handle);

            // Get mark ID if available
            if (method_exists($mark, 'getMarkId')) {
                $this->setData('mark_id', $mark->getMarkId());
            }

            // Get full info from API
            try {
                $response = $this->client->getMark($handle);
                $result = $response->GetMarkResult;

                if ($result->getResultCode() === 200 && $result->getMarkInfo()) {
                    $info = $result->getMarkInfo();
                    $this->setData('expire_date', $info->getExpDate());

                    if (method_exists($info, 'getMarkId')) {
                        $this->setData('mark_id', $info->getMarkId());
                    }

                    if (method_exists($info, 'getAuthInfo') && $info->getAuthInfo()) {
                        $this->setData('auth_info', $info->getAuthInfo());
                    }
                }
            } catch (\Exception $e) {
                $this->responseHandler->logCall('GetMark', $handle, null, $e->getMessage());
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
     * TMCH may require document uploads or verification.
     */
    protected function processPendingUserAction(): void
    {
        if ($this->message) {
            $messageText = is_string($this->message) ? $this->message : '';
            if (is_object($this->message) && method_exists($this->message, 'getMessage')) {
                $messageText = $this->message->getMessage();
            }
            $this->setData('message', $messageText);

            // Check if documents are needed
            if (stripos($messageText, 'document') !== false) {
                $this->setData('documents_uploaded', 0);
            }
        }
    }
}
