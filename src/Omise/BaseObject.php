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
        return $this->object !== null;
    }

    /**
     * Check if a property exists
     */
    public function hasProperty(string $key): bool
    {
        if (! $this->object) {
            return false;
        }

        // Check if it's an array
        if (is_array($this->object)) {
            return isset($this->object[$key]);
        }

        // Check if it's an object with the property
        if (is_object($this->object)) {
            return property_exists($this->object, $key) || isset($this->object->$key);
        }

        return false;
    }

    /**
     * Get property with default value
     */
    public function getProperty(string $key, $default = null)
    {
        if (! $this->object) {
            return $default;
        }

        // Handle array access
        if (is_array($this->object)) {
            return $this->object[$key] ?? $default;
        }

        // Handle object access
        if (is_object($this->object)) {
            return $this->object->$key ?? $default;
        }

        return $default;
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
