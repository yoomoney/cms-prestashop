<?php

namespace YaMoney\Model;

use YaMoney\Common\AbstractObject;
use YaMoney\Common\Exceptions\EmptyPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueException;
use YaMoney\Common\Exceptions\InvalidPropertyValueTypeException;
use YaMoney\Helpers\TypeCast;

/**
 * Класс объекта с информацией о возврате платежа
 *
 * @property string $id Идентификатор возврата платежа
 * @property string $paymentId Идентификатор платежа
 * @property string $status Статус возврата
 * @property RefundErrorInterface $error Описание ошибки или null если ошибок нет
 * @property \DateTime $createdAt Время создания возврата
 * @property \DateTime $authorizedAt Время проведения возврата
 * @property AmountInterface $amount Сумма возврата
 * @property string $receiptRegistration Статус регистрации чека
 * @property string $comment Комментарий, основание для возврата средств покупателю
 */
class Refund extends AbstractObject implements RefundInterface
{
    /**
     * @var string Идентификатор возврата платежа
     */
    private $_id;

    /**
     * @var string Идентификатор платежа
     */
    private $_paymentId;

    /**
     * @var string Статус возврата
     */
    private $_status;

    /**
     * @var RefundErrorInterface Описание ошибки или null если ошибок нет
     */
    private $_error;

    /**
     * @var \DateTime Время создания возврата
     */
    private $_createdAt;

    /**
     * @var \DateTime Время проведения возврата
     */
    private $_authorizedAt;

    /**
     * @var MonetaryAmount Сумма возврата
     */
    private $_amount;

    /**
     * @var string Статус регистрации чека
     */
    private $_receiptRegistration;

    /**
     * @var string Комментарий, основание для возврата средств покупателю
     */
    private $_comment;

    /**
     * Возвращает идентификатор возврата платежа
     * @return string Идентификатор возврата
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Устанавливает идентификатор возврата
     * @param string $value Идентификатор возврата
     *
     * @throws EmptyPropertyValueException Выбрасывается если был передан пустой аргумент
     * @throws InvalidPropertyValueException Выбрасывается если було передано невалидное значение
     * @throws InvalidPropertyValueTypeException Выбрасывается если аргумент не является строкой
     */
    public function setId($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty refund id', 0, 'Refund.id');
        } elseif (TypeCast::canCastToString($value)) {
            $castedValue = (string)$value;
            $length = mb_strlen($castedValue, 'utf-8');
            if ($length === 36) {
                $this->_id = $castedValue;
            } else {
                throw new InvalidPropertyValueException('Invalid refund id value', 0, 'Refund.id', $value);
            }
        } else {
            throw new InvalidPropertyValueTypeException('Invalid refund id value type', 0, 'Refund.id', $value);
        }
    }

    /**
     * Возвращает идентификатор платежа
     * @return string Идентификатор платежа
     */
    public function getPaymentId()
    {
        return $this->_paymentId;
    }

    /**
     * Устанавливает идентификатор платежа
     * @param string $value Идентификатор платежа
     *
     * @throws EmptyPropertyValueException Выбрасывается если был передан пустой аргумент
     * @throws InvalidPropertyValueException Выбрасывается если було передано невалидное значение
     * @throws InvalidPropertyValueTypeException Выбрасывается если аргумент не является строкой
     */
    public function setPaymentId($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty refund paymentId', 0, 'Refund.paymentId');
        } elseif (TypeCast::canCastToString($value)) {
            $castedValue = (string)$value;
            $length = mb_strlen($castedValue, 'utf-8');
            if ($length === 36) {
                $this->_paymentId = $castedValue;
            } else {
                throw new InvalidPropertyValueException(
                    'Invalid refund paymentId value', 0, 'Refund.paymentId', $value
                );
            }
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid refund paymentId value type', 0, 'Refund.paymentId', $value
            );
        }
    }

    /**
     * Возвращает статус текущего возврата
     * @return string Статус возврата
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * Усианавливает стутус возврата платежа
     * @param string $value Статус возврата платежа
     *
     * @throws EmptyPropertyValueException Выбрасывается если был передан пустой аргумент
     * @throws InvalidPropertyValueException Выбрасывается если було передано невалидное значение
     * @throws InvalidPropertyValueTypeException Выбрасывается если аргумент не является строкой
     */
    public function setStatus($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty refund status', 0, 'Refund.status');
        } elseif (TypeCast::canCastToEnumString($value)) {
            $castedValue = (string)$value;
            if (RefundStatus::valueExists($castedValue)) {
                $this->_status = $castedValue;
            } else {
                throw new InvalidPropertyValueException(
                    'Invalid refund status value', 0, 'Refund.status', $value
                );
            }
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid refund status value type', 0, 'Refund.status', $value
            );
        }
    }

    /**
     * Возвращает описание ошибки, если она есть, либо null
     * @return RefundErrorInterface Инстанс объекта с описанием ошибки или null
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * Устанавливает информацию о ошибке проведения возврата
     * @param RefundErrorInterface $value Инстанс объекта с описанием ошибки
     */
    public function setError(RefundErrorInterface $value)
    {
        $this->_error = $value;
    }

    /**
     * Возвращает дату создания возврата
     * @return \DateTime Время создания возврата
     */
    public function getCreatedAt()
    {
        return $this->_createdAt;
    }

    /**
     * Устанавливает вермя создания возврата
     * @param \DateTime $value Время создания возврата
     *
     * @throws EmptyPropertyValueException Выбрасывается если быо передано пустое значение
     * @throws InvalidPropertyValueException Выбрасывается если переданную строку или число не удалось интерпретировать
     * как дату и время
     * @throws InvalidPropertyValueTypeException Выбрасывается если было передано значение невалидного типа
     */
    public function setCreatedAt($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty refund created_at value', 0, 'Refund.createdAt');
        } elseif (TypeCast::canCastToDateTime($value)) {
            $dateTime = TypeCast::castToDateTime($value);
            if ($dateTime === null) {
                throw new InvalidPropertyValueException('Invalid created_at value', 0, 'Refund.createdAt', $value);
            }
            $this->_createdAt = $dateTime;
        } else {
            throw new InvalidPropertyValueTypeException('Invalid created_at value', 0, 'Refund.createdAt', $value);
        }
    }

    /**
     * Возвращает дату проведения возврата
     * @return \DateTime|null Время проведения возврата
     *
     * @throws InvalidPropertyValueException Выбрасывается если переданную строку или число не удалось интерпретировать
     * как дату и время
     * @throws InvalidPropertyValueTypeException Выбрасывается если было передано значение невалидного типа
     */
    public function getAuthorizedAt()
    {
        return $this->_authorizedAt;
    }

    /**
     * Устанавливает время проведения возврата
     * @param \DateTime|null $value Время проведения возврата
     *
     *
     */
    public function setAuthorizedAt($value)
    {
        if ($value === null || $value === '') {
            $this->_authorizedAt = null;
        } elseif (TypeCast::canCastToDateTime($value)) {
            $dateTime = TypeCast::castToDateTime($value);
            if ($dateTime === null) {
                throw new InvalidPropertyValueException('Invalid authorizedAt value', 0, 'Refund.authorizedAt', $value);
            }
            $this->_authorizedAt = $dateTime;
        } else {
            throw new InvalidPropertyValueTypeException('Invalid authorizedAt value', 0, 'Refund.authorizedAt', $value);
        }
    }

    /**
     * Возвращает сумму возврата
     * @return AmountInterface Сумма возврата
     */
    public function getAmount()
    {
        return $this->_amount;
    }

    /**
     * Устанавливает сумму возврата
     * @param AmountInterface $value Сумма возврата
     *
     * @throws InvalidPropertyValueException Выбрасывается если переданная сумма меньше или равна нулю
     */
    public function setAmount(AmountInterface $value)
    {
        if ($value->getIntegerValue() <= 0) {
            throw new InvalidPropertyValueException('Invalid refund amount', 0, 'Refund.amount', $value->getValue());
        }
        $this->_amount = $value;
    }

    /**
     * Возвращает статус регистрации чека
     * @return string Статус регистрации чека
     */
    public function getReceiptRegistration()
    {
        return $this->_receiptRegistration;
    }

    /**
     * Устанавливает статус регистрации чека
     * @param string $value Статус регистрации чека
     *
     * @throws EmptyPropertyValueException Выбрасывается если был передан пустой аргумент
     * @throws InvalidPropertyValueException Выбрасывается если було передано невалидное значение
     * @throws InvalidPropertyValueTypeException Выбрасывается если аргумент не является строкой
     */
    public function setReceiptRegistration($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty refund receiptRegistration', 0, 'Refund.receiptRegistration');
        } elseif (TypeCast::canCastToEnumString($value)) {
            $castedValue = (string)$value;
            if (ReceiptRegistrationStatus::valueExists($castedValue)) {
                $this->_receiptRegistration = $castedValue;
            } else {
                throw new InvalidPropertyValueException(
                    'Invalid refund receiptRegistration value', 0, 'Refund.receiptRegistration', $value
                );
            }
        } else {
            throw new InvalidPropertyValueTypeException(
                'Invalid refund receiptRegistration value type', 0, 'Refund.receiptRegistration', $value
            );
        }
    }

    /**
     * Возвращает комментарий к возврату
     * @return string Комментарий, основание для возврата средств покупателю
     */
    public function getComment()
    {
        return $this->_comment;
    }

    /**
     * Устанавливает комментарий к возврату
     * @param string $value Комментарий, основание для возврата средств покупателю
     *
     * @throws EmptyPropertyValueException Выбрасывается если был передан пустой аргумент
     * @throws InvalidPropertyValueException Выбрасывается если було передано невалидное значение
     * @throws InvalidPropertyValueTypeException Выбрасывается если аргумент не является строкой
     */
    public function setComment($value)
    {
        if ($value === null || $value === '') {
            throw new EmptyPropertyValueException('Empty refund comment', 0, 'Refund.comment');
        } elseif (TypeCast::canCastToEnumString($value)) {
            $length = mb_strlen((string)$value, 'utf-8');
            if ($length > 250) {
                throw new InvalidPropertyValueException('Empty refund comment', 0, 'Refund.comment', $value);
            }
            $this->_comment = (string)$value;
        } else {
            throw new InvalidPropertyValueTypeException('Empty refund comment', 0, 'Refund.comment', $value);
        }
    }
}
