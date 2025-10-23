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
        } elseif (method_exists($this->object, 'refresh')) {
            $this->object->refresh();
        }

        return $this;
    }

    /**
     * Check if the object is properly loaded
     */
    public function isLoaded(): bool
    {
        return $this->object !== null && is_array($this->object);
    }

    /**
     * Check if a property exists
     */
    public function hasProperty(string $key): bool
    {
        return $this->isLoaded() && isset($this->object[$key]);
    }

    /**
     * Get property with default value
     */
    public function getProperty(string $key, $default = null)
    {
        return $this->hasProperty($key) ? $this->object[$key] : $default;
    }

    /**
     * Validate required properties exist
     */
    public function validateProperties(array $requiredProperties): bool
    {
        if (! $this->isLoaded()) {
            return false;
        }

        foreach ($requiredProperties as $property) {
            if (! $this->hasProperty($property)) {
                return false;
            }
        }

        return true;
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
