<?php

namespace Soap\LaravelOmise\Omise;

use Illuminate\Support\Str;

#[\AllowDynamicProperties]
class BaseObject
{
    protected $object;

    /**
     * @param  mixed  $object  of \Soap\LaravelOmise\Omise\BaseObject.
     * @return $this
     */
    protected function refresh($object = null)
    {
        if ($this->object == null && $object == null) {
            return $this;
        }

        if ($object != null) {
            $this->object = $object;
        } elseif (method_exists($this->object, 'reload')) {
            $this->object->reload();
        }

        return $this;
    }

    /**
     * Get array values from object by the key like $object->$key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return isset($this->object[$key]) ? $this->object[$key] : null;
    }

    /**
     * Call the method from object by the key like $object->$key().
     *
     * @param  string  $method
     * @param  array  $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        $key = Str::snake($method);

        return $this->$key;
    }
}
