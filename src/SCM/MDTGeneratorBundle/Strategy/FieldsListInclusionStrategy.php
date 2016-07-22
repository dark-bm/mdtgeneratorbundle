<?php

namespace SCM\MDTGeneratorBundle\Strategy;

use JMS\Serializer\Context;
use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;

class FieldsListInclusionStrategy extends FieldsListStrategy
{

    /**
     * {@inheritDoc}
     */
    public function shouldSkipProperty(PropertyMetadata $property, Context $navigatorContext)
    {
        return !$this->isListedPropertyCheck($property, $navigatorContext);
    }
}