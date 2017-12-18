<?php

/**
 * The MIT License
 *
 * Copyright (c) 2017 NBCO Yandex.Money LLC
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:

 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace YandexCheckout\Model\Notification;

use YandexCheckout\Common\Exceptions\EmptyPropertyValueException;
use YandexCheckout\Common\Exceptions\InvalidPropertyValueException;
use YandexCheckout\Common\Exceptions\InvalidPropertyValueTypeException;
use YandexCheckout\Helpers\TypeCast;
use YandexCheckout\Model\NotificationEventType;
use YandexCheckout\Model\NotificationType;

/**
 *
 *
 * @package YandexCheckout\Model\Notification
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