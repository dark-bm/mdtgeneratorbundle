<?php
namespace SCM\MDTGeneratorBundle\Strategy;

use JMS\Serializer\Context;
use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;

class FieldsListStrategy implements ExclusionStrategyInterface
{

    private $fields = array();

    private $classFields = array();

    public function __construct(array $fields)
    {
        $this->fields = $this->organizeFields($fields);
    }

    private function assignArrayByPath(&$arr, $path, $value, $separator = '.')
    {
        $keys = explode($separator, $path);
        foreach ($keys as $key) {
            $arr = &$arr[$key];
        }
        $arr = $value;
    }

    private function organizeFields(array $fields)
    {
        $result = [];
        foreach ($fields as $field) {
            $this->assignArrayByPath($result, $field, true);
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function shouldSkipClass(ClassMetadata $metadata, Context $navigatorContext)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isListedPropertyCheck(PropertyMetadata $property, Context $navigatorContext)
    {
        if (empty($this->fields)) {
            return false;
        }

        $name = $property->serializedName ?: $property->name;

        switch (true){
            case $property->type["name"] == "ArrayCollection":
                $this->classFields[$property->type["params"][0]["name"]] = ($navigatorContext->getDepth() == 1) ? $name : $this->classFields[$property->class] . "." . $name;
                break;
            case strpos($property->type["name"],"\\"):
                $this->classFields[$property->type["name"]] = ($navigatorContext->getDepth() == 1) ? $name : $this->classFields[$property->class] . "." . $name;
                break;
            default:
                break;
        }
        
        $data = $this->fields;

        if ($navigatorContext->getDepth() > 1) {
            $prefix = $this->classFields[$property->class];
            $prefix_parts = explode(".", $prefix);
            foreach ($prefix_parts as $part) {
                $data = isset($data[$part])?$data[$part]:[];
            }
        }

        return isset($data[$name]);
    }

    public function shouldSkipProperty(PropertyMetadata $property, Context $navigatorContext){

    }
}