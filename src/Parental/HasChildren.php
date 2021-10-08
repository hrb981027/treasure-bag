<?php

declare(strict_types=1);

namespace Hrb981027\TreasureBag\Parental;

use Hyperf\Utils\Str;

trait HasChildren
{
    protected static array $parentBootMethods;

    protected bool $hasChildren = true;

    protected static function registerModelEvent($event, $callback)
    {
        parent::registerModelEvent($event, $callback);

        if (static::class === self::class && property_exists(self::class, 'childTypes')) {
            if (!self::parentIsBooting()) {
                foreach ((new self)->childTypes as $childClass) {
                    if ($childClass !== self::class) {
                        $childClass::registerModelEvent($event, $callback);
                    }
                }
            }
        }
    }

    protected static function parentIsBooting(): bool
    {
        if (!isset(self::$parentBootMethods)) {
            self::$parentBootMethods[] = 'boot';

            foreach (class_uses_recursive(self::class) as $trait) {
                self::$parentBootMethods[] = 'boot' . class_basename($trait);
            }

            self::$parentBootMethods = array_flip(self::$parentBootMethods);
        }

        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $trace) {
            $class = $trace['class'] ?? null;
            $function = $trace['function'] ?? '';

            if ($class === self::class && isset(self::$parentBootMethods[$function])) {
                return true;
            }
        }

        return false;
    }

    public function newInstance($attributes = [], $exists = false)
    {
        $model = isset($attributes[$this->getInheritanceColumn()])
            ? $this->getChildModel($attributes)
            : new static(((array)$attributes));

        $model->exists = $exists;

        $model->setConnection(
            $this->getConnectionName()
        );

        return $model;
    }

    public function newFromBuilder($attributes = [], $connection = null)
    {
        $attributes = (array)$attributes;

        $inheritanceAttributes = [];
        $inheritanceColumn = $this->getInheritanceColumn();

        if (isset($attributes[$inheritanceColumn])) {
            $inheritanceAttributes[$inheritanceColumn] = $attributes[$inheritanceColumn];
        }

        $model = $this->newInstance($inheritanceAttributes, true);

        $model->setRawAttributes($attributes, true);

        $model->setConnection($connection ?: $this->getConnectionName());

        $model->fireModelEvent('retrieved', false);

        return $model;
    }

    public function belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null)
    {
        $instance = $this->newRelatedInstance($related);

        if (is_null($foreignKey) && $instance->hasParent) {
            $foreignKey = Str::snake($instance->getClassNameForRelationships()) . '_' . $instance->getKeyName();
        }

        if (is_null($relation)) {
            $relation = $this->guessBelongsToRelation();
        }

        return parent::belongsTo($related, $foreignKey, $ownerKey, $relation);
    }

    public function hasMany($related, $foreignKey = null, $localKey = null)
    {
        return parent::hasMany($related, $foreignKey, $localKey);
    }

    public function belongsToMany($related, $table = null, $foreignPivotKey = null, $relatedPivotKey = null, $parentKey = null, $relatedKey = null, $relation = null)
    {
        $instance = $this->newRelatedInstance($related);

        if (is_null($table) && $instance->hasParent) {
            $table = $this->joiningTable($instance->getClassNameForRelationships());
        }

        return parent::belongsToMany($related, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relation);
    }

    public function getClassNameForRelationships(): string
    {
        return class_basename($this);
    }

    public function getInheritanceColumn(): string
    {
        return property_exists($this, 'childColumn') ? $this->childColumn : 'type';
    }

    protected function getChildModel(array $attributes)
    {
        $className = $this->classFromAlias(
            $attributes[$this->getInheritanceColumn()]
        );

        return new $className($attributes);
    }

    public function classFromAlias($aliasOrClass): string
    {
        if (property_exists($this, 'childTypes')) {
            if (isset($this->childTypes[$aliasOrClass])) {
                return $this->childTypes[$aliasOrClass];
            }
        }

        return $aliasOrClass;
    }

    public function classToAlias($className): string
    {
        if (property_exists($this, 'childTypes')) {
            if (in_array($className, $this->childTypes)) {
                return array_search($className, $this->childTypes);
            }
        }

        return $className;
    }

    public function getChildTypes(): array
    {
        return property_exists($this, 'childTypes') ? $this->childTypes : [];
    }
}
