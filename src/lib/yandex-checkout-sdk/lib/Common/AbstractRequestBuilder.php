<?php

namespace YaMoney\Common;

use YaMoney\Common\Exceptions\InvalidPropertyException;
use YaMoney\Common\Exceptions\InvalidRequestException;

/**
 * Базовый класс билдера запросов
 *
 * @package YaMoney\Common
 */
abstract class AbstractRequestBuilder
{
    /**
     * @var AbstractRequest Инстанс собираемого запроса
     */
    protected $currentObject;

    /**
     * Конструктор, инициализирует пустой запрос, который в будущем начнём собирать
     */
    public function __construct()
    {
        $this->currentObject = $this->initCurrentObject();
    }

    /**
     * Инициализирует пустой запрос
     * @return AbstractRequest Инстанс запроса который будем собирать
     */
    abstract protected function initCurrentObject();

    /**
     * Строит запрос, валидирует его и возвращает, если все прошло нормально
     * @param array $options Массив свойств запроса, если нужно их установить перед сборкой
     * @return AbstractRequest Инстанс собранного запроса
     *
     * @throws InvalidRequestException Выбрасывается если при валидации запроса произошла ошибка
     * @throws InvalidPropertyException Выбрасывается если не удалось установить один из параметров, переданныч в
     * массиве настроек
     */
    public function build(array $options = null)
    {
        if (!empty($options)) {
            $this->setOptions($options);
        }
        try {
            $this->currentObject->clearValidationError();
            if (!$this->currentObject->validate()) {
                throw new InvalidRequestException($this->currentObject);
            }
        } catch (InvalidRequestException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new InvalidRequestException($this->currentObject, 0, $e);
        }
        $result = $this->currentObject;
        $this->currentObject = $this->initCurrentObject();
        return $result;
    }

    /**
     * Устанавливает свойства запроса из массива
     * @param array|\Traversable $options Массив свойств запроса
     * @return AbstractRequestBuilder Инстанс текущего билдера запросов
     *
     * @throws \InvalidArgumentException Выбрасывается если аргумент не массив и не итерируемый объект
     * @throws InvalidPropertyException Выбрасывается если не удалось установить один из параметров, переданныч
     * в массиве настроек
     */
    public function setOptions($options)
    {
        if (empty($options)) {
            return $this;
        }
        if (!is_array($options) && !($options instanceof \Traversable)) {
            throw new \InvalidArgumentException('Invalid options value in setOptions method');
        }
        foreach ($options as $property => $value) {
            $method = 'set' . ucfirst($property);
            if (method_exists($this, $method)) {
                $this->{$method} ($value);
            }
        }
        return $this;
    }
}
