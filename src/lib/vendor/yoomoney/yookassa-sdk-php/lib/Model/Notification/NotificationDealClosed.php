<?php
/**
 * The MIT License
 *
 * Copyright (c) 2020 "YooMoney", NBСO LLC
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace YooKassa\Model\Notification;


use YooKassa\Common\Exceptions\EmptyPropertyValueException;
use YooKassa\Common\Exceptions\InvalidPropertyValueException;
use YooKassa\Model\NotificationEventType;
use YooKassa\Model\NotificationType;
use YooKassa\Model\SafeDeal;
use YooKassa\Model\DealInterface;
use YooKassa\Request\Deals\DealResponse;

/**
 * Класс объекта, присылаемого API при изменении статуса сделки на "closed"
 *
 * @example 03-notification.php 3 Пример скрипта обработки уведомления
 *
 * @package YooKassa
 *
 * @property-read DealInterface $object Объект с информацией о сделке
 */
class NotificationDealClosed extends AbstractNotification
{
    /**
     * Объект сделки, для которого пришла нотификация. Так как нотификация может быть сгенерирована и поставлена в
     * очередь на отправку гораздо раньше, чем она будет получена на сайте, то опираться на статус пришедшей
     * сделки не стоит, лучше запросить текущую информацию о сделке у API.
     *
     * @var SafeDeal Объект платежа
     */
    private $_object;

    /**
     * Конструктор объекта нотификации
     *
     * Инициализирует текущий объект из ассоциативного массива, который просто путём JSON десериализации получен из
     * тела пришедшего запроса. При конструировании проверяется валидность типа передаваемого уведомления, если
     * передать уведомление не того типа, будет сгенерировано исключение типа {@link InvalidPropertyValueException}
     *
     * @param array $source Ассоциативный массив с информацией об уведомлении
     *
     * @throws InvalidPropertyValueException|\Exception Генерируется если значение типа нотификации или события не равны
     * "notification" и "deal.closed" соответственно, что может говорить о том, что переданные в
     * конструктор данные не являются уведомлением нужного типа.
     */
    public function __construct(array $source)
    {
        $this->_setType(NotificationType::NOTIFICATION);
        $this->_setEvent(NotificationEventType::DEAL_CLOSED);
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
            throw new EmptyPropertyValueException('Parameter object in NotificationDealClosed is empty');
        }
        $this->_object = new DealResponse($source['object']);
    }

    /**
     * Возвращает объект с информацией о сделке, уведомление о которой хранится в текущем объекте
     *
     * Так как нотификация может быть сгенерирована и поставлена в очередь на отправку гораздо раньше, чем она будет
     * получена на сайте, то опираться на статус пришедшей сделки не стоит, лучше запросить текущую информацию о
     * сделке у API.
     *
     * @return DealInterface Объект с информацией о сделке
     */
    public function getObject()
    {
        return $this->_object;
    }
}