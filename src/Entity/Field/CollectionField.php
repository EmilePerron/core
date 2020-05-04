<?php

declare(strict_types=1);

namespace Bolt\Entity\Field;

use ArrayIterator;
use Bolt\Configuration\Content\ContentType;
use Bolt\Entity\Field;
use Bolt\Entity\FieldInterface;
use Bolt\Entity\FieldParentInterface;
use Bolt\Entity\FieldParentTrait;
use Bolt\Repository\FieldRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Tightenco\Collect\Support\Collection;

/**
 * @ORM\Entity
 */
class CollectionField extends Field implements FieldInterface, FieldParentInterface
{
    use FieldParentTrait;

    public const TYPE = 'collection';

    public function getTemplates(): array
    {
        $fieldDefinitions = $this->getDefinition()->get('fields', []);
        $result = [];

        foreach ($fieldDefinitions as $fieldName => $fieldDefinition) {
            $templateField = FieldRepository::factory($fieldDefinition, '', $fieldName);
            $templateField->setDefinition($fieldName, $this->getDefinition()->get('fields')[$fieldName]);
            $templateField->setName($fieldName);
            $result[$fieldName] = $templateField;
        }

        return $result;
    }

    public function getApiValue()
    {
        $fields = $this->getValue();
        $result = [];

        foreach ($fields as $field) {
            $result[] = [
                'name' => $field->getName(),
                'type' => $field->getType(),
                'value' => $field->getApiValue(),
            ];
        }

        return $result;
    }

    public function setValue($fields): Field
    {
        if (! is_iterable($fields)) {
            return $this;
        }

        $order = 1;
        /** @var Field $field */
        foreach($fields as $field) {
            $field->setParent($this);
            $field->setSortorder($order);
            $order += 5;
        }

        parent::setValue($fields);

        return $this;
    }

    public function getDefaultValue()
    {
        $default = parent::getDefaultValue();

        if ($default === null) {
            return [];
        }

        $result = [];

        /** @var ContentType $type */
        foreach ($default as $i => $type) {
            $value = $type->toArray()['default'];
            $name = $type->toArray()['field'];
            $definition = $this->getDefinition()->get('fields')[$name];
            $field = FieldRepository::factory($definition, $name);
            $field->setValue($value);
            $result[] = $field;
        }

        return $result;
    }
}
