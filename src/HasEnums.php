<?php

namespace Spatie\Enum\Laravel;

use Spatie\Enum\Enumerable;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;
use Spatie\Enum\Laravel\Exceptions\InvalidEnumError;

/**
 * @mixin Model
 */
trait HasEnums
{
    public function setAttribute($key, $value)
    {
        return $this->isEnumAttribute($key)
            ? $this->setEnumAttribute($key, $value)
            : parent::setAttribute($key, $value);
    }

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        return $this->isEnumAttribute($key)
            ? $this->getEnumAttribute($key, $value)
            : $value;
    }

    /**
     * @param string $key
     * @param int|string|Enumerable $value
     *
     * @return $this
     */
    protected function setEnumAttribute(string $key, $value)
    {
        $enumClass = $this->getEnumClass($key);

        if (is_string($value) || is_int($value)) {
            $value = $this->asEnum($enumClass, $value);
        }

        if (! is_a($value, $enumClass)) {
            throw InvalidEnumError::make(static::class, $key, $enumClass, get_class($value));
        }

        $this->attributes[$key] = $this->hasCast($key, ['int', 'integer'])
            ? $value->getIndex()
            : $value->getValue();

        return $this;
    }

    /**
     * @param string $key
     * @param int|string $value
     * @return Enumerable
     */
    protected function getEnumAttribute(string $key, $value): Enumerable
    {
        return $this->asEnum($this->getEnumClass($key), $value);
    }

    protected function isEnumAttribute(string $key): bool
    {
        return isset($this->enums[$key]);
    }

    protected function getEnumClass(string $key): string
    {
        $enumClass = $this->enums[$key];
        $enumInterface = Enumerable::class;
        $classImplementsEnumerable = class_implements($enumClass)[$enumInterface] ?? false;

        if (! $classImplementsEnumerable) {
            throw new InvalidArgumentException("Expected {$enumClass} to implement {$enumInterface}");
        }

        return $enumClass;
    }

    /**
     * @param string $class
     * @param int|string $value
     *
     * @return Enumerable
     */
    protected function asEnum(string $class, $value): Enumerable
    {
        return forward_static_call(
            $class.'::make',
            $value
        );
    }
}
