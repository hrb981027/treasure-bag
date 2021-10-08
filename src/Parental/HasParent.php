<?php

declare(strict_types=1);

namespace Hrb981027\TreasureBag\Parental;

use Hyperf\Utils\Str;
use ReflectionClass;

trait HasParent
{
    public bool $hasParent = true;

    public function parentHasHasChildrenTrait(): bool
    {
        return $this->hasChildren ?? false;
    }

    public function getTable()
    {
        if (!isset($this->table)) {
            return str_replace('\\', '', Str::snake(Str::plural(class_basename($this->getParentClass()))));
        }

        return $this->table;
    }

    public function getForeignKey()
    {
        return Str::snake(class_basename($this->getParentClass())) . '_' . $this->primaryKey;
    }

    public function joiningTable($related, $instance = null)
    {
        $relatedClassName = method_exists((new $related), 'getClassNameForRelationships')
            ? (new $related)->getClassNameForRelationships()
            : class_basename($related);

        $models = [
            Str::snake($relatedClassName),
            Str::snake($this->getClassNameForRelationships()),
        ];

        sort($models);

        return strtolower(implode('_', $models));
    }

    public function getClassNameForRelationships(): string
    {
        return class_basename($this->getParentClass());
    }

    public function getMorphClass()
    {
        if ($this->parentHasHasChildrenTrait()) {
            $parentClass = $this->getParentClass();
            return (new $parentClass)->getMorphClass();
        }

        return parent::getMorphClass();
    }

    protected function getParentClass()
    {
        static $parentClassName;

        return $parentClassName ?: $parentClassName = (new ReflectionClass($this))->getParentClass()->getName();
    }
}
