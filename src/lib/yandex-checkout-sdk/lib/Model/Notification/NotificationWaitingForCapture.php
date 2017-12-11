<?php

namespace YaMoney\Model\Notification;

use YaMoney\Common\Exceptions\EmptyPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueException;
use YaMoney\Model\NotificationEventType;
use YaMoney\Model\NotificationType;
use YaMoney\Model\Payment;
use YaMoney\Model\PaymentInterface;
use YaMoney\Request\Payments\PaymentResponse;

class NotificationWaitingForCapture extends AbstractNotification
{
    /**
     * @var Payment
     */
    private $_object;

    /**
     * NotificationWaitingForCapture constructor.
     *
     * @param array $source
     */
    public function __construct(array $source)
    {
        $this->_setType(NotificationType::NOTIFICATION);
        $this->_setEvent(NotificationEventType::PAYMENT_WAITING_FOR_CAPTURE);
        if (!empty($source['type'])) {
            if ($this->getType() !== $source['type']) {
                throw new InvalidPropertyValueException(
                    'Invalid value for "type" parameter in Notification', 0, 'notification.type', $source['type']
                );
            }
        }
        if (!empty($source['event'])) {
            if ($this->getEvent() !== $source['event']) {
                throw new InvalidPropertyValueException(
                    'Invalid value for "event" parameter in Notification', 0, 'notification.event', $source['event']
                );
            }
        }
        if (empty($source['object'])) {
            throw new EmptyPropertyValueException('Parameter object in NotificationWaitingForCapture is empty');
        }
        $this->_object = new PaymentResponse($source['object']);
    }

    /**
     * @return PaymentInterface
     */
    public function getObject()
    {
        return $this->_object;
    }
}