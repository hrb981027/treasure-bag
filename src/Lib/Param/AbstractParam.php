<?php

declare(strict_types=1);

namespace Hrb981027\TreasureBag\Lib\Param;

use Hrb981027\TreasureBag\Annotation\Param;
use Hrb981027\TreasureBag\Annotation\ParamProperty;
use Hrb981027\TreasureBag\Exception\InvalidParamException;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Utils\Contracts\Arrayable;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionException;
use ReflectionProperty;

abstract class AbstractParam implements Arrayable
{
    public function __construct(array $data = [])
    {
        if (!$this->hasParamAnnotation()) {
            return;
        }

        $paramAnnotation = $this->getParamAnnotation();

        $propertyList = $this->getHasParamPropertyAnnotationPropertyList();

        foreach ($data as $key => $value) {
            if (!array_key_exists($key, $propertyList)) {
                if (function_exists($paramAnnotation->inHandle)) {
                    $key = call_user_func($paramAnnotation->inHandle, $key);
                }
            }

            if (!array_key_exists($key, $propertyList)) {
                continue;
            }

            if (!$propertyList[$key]['annotation']->allowIn) {
                continue;
            }

            $propertyName = $propertyList[$key]['property_name'];

            $type = $propertyList[$key]['type'];

            if (!class_exists($type) && gettype($value) != $type) {
                throw new InvalidParamException("$key 参数类型必须为 $type");
            }

            if ($type == 'array') {
                $arrayType = $propertyList[$key]['array_type'];

                if (class_exists($arrayType)) {
                    if (get_parent_class($arrayType) != AbstractParam::class) {
                        continue;
                    }

                    $array = [];
                    foreach ($value as $item) {
                        $array[] = new $arrayType($item);
                    }
                    $this->$propertyName = $array;
                } else {
                    if ($arrayType != 'mixed') {
                        foreach ($value as $item) {
                            if (gettype($item) != $arrayType) {
                                throw new InvalidParamException("$key 参数必须为 $arrayType 型数组");
                            }
                        }
                    }

                    $this->$propertyName = $value;
                }
            } elseif (class_exists($type)) {
                if (get_parent_class($type) != AbstractParam::class) {
                    continue;
                }

                $this->$propertyName = new $type($value);
            } else {
                $this->$propertyName = $value;
            }
        }

        $this->verifyParams();
    }

    public function toArray(): array
    {
        if (!$this->hasParamAnnotation()) {
            return [];
        }

        $paramAnnotation = $this->getParamAnnotation();

        $propertyList = $this->getHasParamPropertyAnnotationPropertyList();

        $result = [];

        foreach ($propertyList as $property) {
            $propertyName = $property['property_name'];

            if (!isset($this->$propertyName)) {
                continue;
            }

            if (!$property['annotation']->allowOut) {
                continue;
            }

            if (empty($property['annotation']->out)) {
                if (function_exists($paramAnnotation->outHandle)) {
                    $key = call_user_func($paramAnnotation->outHandle, $propertyName);
                } else {
                    $key = $propertyName;
                }
            } else {
                $key = $property['annotation']->out;
            }

            if (is_object($this->$propertyName)) {
                $result[$key] = $this->$propertyName->toArray();

                continue;
            }

            if (is_array($this->$propertyName)) {
                foreach ($this->$propertyName as &$item) {
                    if (is_object($item)) {
                        $item = $item->toArray();
                    }
                }
                unset($item);
            }

            $result[$key] = $this->$propertyName;
        }

        return $result;
    }

    public function verifyParams(): void
    {
        $propertyList = $this->getHasParamPropertyAnnotationPropertyList();

        foreach ($propertyList as $property) {
            $propertyName = $property['property_name'];

            if ($property['annotation']->required) {
                if (!isset($this->$propertyName)) {
                    throw new InvalidParamException("$propertyName 参数必须存在");
                }
            }

            if ($property['annotation']->filled) {
                if (!isset($this->$propertyName) || empty($this->$propertyName)) {
                    throw new InvalidParamException("$propertyName 参数必须存在且不能为空");
                }
            }
        }
    }

    private function getParamAnnotation(): ?Param
    {
        return AnnotationCollector::getClassAnnotation(static::class, Param::class);
    }

    private function hasParamAnnotation(): bool
    {
        return AnnotationCollector::getClassAnnotation(static::class, Param::class) != null;
    }

    private function getHasParamPropertyAnnotationPropertyList(): array
    {
        $propertyList = AnnotationCollector::getPropertiesByAnnotation(ParamProperty::class);

        $result = [];

        foreach ($propertyList as $item) {
            if ($item['class'] != static::class) {
                continue;
            }

            try {
                $key = empty($item['annotation']->in) ? $item['property'] : $item['annotation']->in;

                $result[$key] = array_merge(
                    $this->getPropertyType($item['property']),
                    ['property_name' => $item['property'], 'annotation' => $item['annotation']]
                );
            } catch (ReflectionException) {
            }
        }

        return $result;
    }

    /**
     * @throws ReflectionException
     */
    private function getPropertyType(string $property): array
    {
        $result = [];

        $reflectionProperty = new ReflectionProperty(static::class, $property);

        $reflectionType = $reflectionProperty->getType();
        if (is_null($reflectionType)) {
            $result['type'] = 'mixed';
        } else {
            $type = $reflectionType->getName();

            $type = match ($type) {
                'bool' => 'boolean',
                'int' => 'integer',
                'float' => 'double',
                default => $type,
            };

            $result['type'] = $type;
        }

        if ($result['type'] == 'array') {
            $factory = DocBlockFactory::createInstance();

            $docblock = $factory->create($reflectionProperty->getDocComment());

            $tags = $docblock->getTagsByName('var');

            if (isset($tags[0])) {
                $tag = str_replace('[]', '', (string)$tags[0]);

                $tag = match ($tag) {
                    'bool' => 'boolean',
                    'int' => 'integer',
                    'float' => 'double',
                    default => $tag,
                };

                $result['array_type'] = $tag;
            } else {
                $result['array_type'] = 'mixed';
            }
        }

        return $result;
    }
}
