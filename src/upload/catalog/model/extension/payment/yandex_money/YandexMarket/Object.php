<?php

namespace YandexMoneyModule\YandexMarket;

class Object
{
    private $properties = array();

    public function __get($property)
    {
        $method = 'get' . ucfirst($property);
        if (method_exists($this, $method)) {
            return $this->{$method} ();
        }
        if (array_key_exists($property, $this->properties)) {
            return $this->properties[$property];
        }
        throw new \OutOfBoundsException();
    }

    public function __set($property, $value)
    {
        $method = 'set' . ucfirst($property);
        if (method_exists($this, $method)) {
            $this->{$method} ($value);
        }
        if ($value !== null) {
            $this->properties[$property] = $value;
        } else {
            unset($this->properties[$property]);
        }
    }

    public function __isset($property)
    {
        $method = 'get' . ucfirst($property);
        if (method_exists($this, $method)) {
            $value = $this->{$method} ();
            return $value !== null;
        }
        return array_key_exists($property, $this->properties);
    }

    public function __unset($property)
    {
        if (array_key_exists($property, $this->properties)) {
            unset($this->properties[$property]);
        }
    }

    public function __call($method, $arguments)
    {
        if (strncmp('get', $method, 3) === 0) {
            $property = lcfirst(substr($method, 3));
            if (array_key_exists($property, $this->properties)) {
                return $this->properties[$property];
            }
            return null;
        } elseif (strncmp('set', $method, 3) === 0) {
            $property = lcfirst(substr($method, 3));
            $this->properties[$property] = $arguments[0];
            return $this;
        }
        throw new \OutOfBoundsException();
    }
}
