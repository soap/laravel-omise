<?php

namespace Soap\LaravelOmise\Omise;

use ArrayAccess;
use Illuminate\Support\Str;
use Soap\LaravelOmise\Exceptions\OmiseRequestException;

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
            // The SDK reuses one instance per resource class, keep our own copy of it.
            $this->object = is_object($object) ? clone $object : $object;
        } elseif (method_exists($this->object, 'refresh')) {
            $this->object->refresh();
        }

        return $this;
    }

    /**
     * Merge the attributes of an API response into the loaded object.
     *
     * @param  array<string, mixed>  $values
     * @return $this
     */
    protected function fill(array $values)
    {
        if (is_object($this->object) && method_exists($this->object, 'refresh')) {
            $this->object->refresh($values);
        } else {
            $this->object = array_merge(is_array($this->object) ? $this->object : [], $values);
        }

        return $this;
    }

    /**
     * Whether the API call failed, see Error.
     */
    public function isError(): bool
    {
        return false;
    }

    /**
     * Throw an exception if the API call failed, otherwise return the object.
     *
     * @return $this
     *
     * @throws OmiseRequestException
     */
    public function throw()
    {
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

        // Check if it's an array, or an SDK object (they are accessed as arrays)
        if (is_array($this->object) || $this->object instanceof ArrayAccess) {
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

        // Handle array access (arrays and SDK objects)
        if (is_array($this->object) || $this->object instanceof ArrayAccess) {
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
