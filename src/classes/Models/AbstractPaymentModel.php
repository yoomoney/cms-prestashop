<?php
/**
 * @author Yandex.Money <cms@yamoney.ru>
 * @copyright © 2015-2017 NBCO Yandex.Money LLC
 * @license  https://money.yandex.ru/doc.xml?id=527052
 */

namespace YandexMoneyModule\Models;

abstract class AbstractPaymentModel extends AbstractModel
{
    /**
     * @var bool Флаг разрешения оплаты с помощью модели
     */
    protected $enabled;

    /**
     * @var string Имя контроллера, используемого для проведения платежа
     */
    protected $paymentActionController;

    /**
     * Разрешена ли оплата с помощью текущего метода оплаты
     * @return bool True если оплата разрешена, false если нет
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Возвращает имя контроллера, для проведения платежа
     * @return string Имя контороллера
     */
    public function getPaymentActionController()
    {
        return $this->paymentActionController;
    }

    /**
     * @param \Smarty $smarty
     * @return string
     */
    abstract public function assignVariables($smarty);
}
