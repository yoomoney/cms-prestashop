<?php

namespace YaMoney\Model\Notification;

use YaMoney\Common\Exceptions\EmptyPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueTypeException;
use YaMoney\Helpers\TypeCast;
use YaMoney\Model\NotificationEventType;
use YaMoney\Model\NotificationType;

/**
 *
 *
 * @package YaMoney\Model\Notification
 */
abstract class AbstractNotification
{
    /**
     * @var string
     */
    private $_type;

    /**
     * @var string
     */
    private $_event;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * @param string $value
     */
    protected function _setType($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty parameter "type" in Notification', 0, 'notification.type');
        } elseif (TypeCast::canCastToEnumString($value)) {
            if (NotificationType::valueExists($value)) {
                $this->_type = (string)$value;
            } else {
                throw new InvalidPropertyValueException(
                    'Invalid value for "type" parameter in Notification', 0, 'notification.type', $value
                );
            }
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid value type for "type" parameter in Notification', 0, 'notification.type', $value
            );
        }
    }

    /**
     * @return string
     */
    public function getEvent()
    {
        return $this->_event;
    }

    /**
     * @param string $value
     */
    protected function _setEvent($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty parameter "event" in Notification', 0, 'notification.event');
        } elseif (TypeCast::canCastToEnumString($value)) {
            if (NotificationEventType::valueExists($value)) {
                $this->_event = (string)$value;
            } else {
                throw new InvalidPropertyValueException(
                    'Invalid value for "event" parameter in Notification', 0, 'notification.event', $value
                );
            }
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid value type for "event" parameter in Notification', 0, 'notification.event', $value
            );
        }
    }
}