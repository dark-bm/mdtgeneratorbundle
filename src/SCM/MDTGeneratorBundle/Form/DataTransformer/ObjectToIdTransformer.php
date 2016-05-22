<?php

namespace SCM\MDTGeneratorBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ObjectToIdTransformer implements DataTransformerInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var string Name spaced entity
     */
    private $class;

    /**
     * @param EntityManager $em
     * @param string        $class
     */
    public function __construct(EntityManager $em, $class)
    {
        $this->em = $em;
        $this->class = $class;
    }

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public function transform($data)
    {
        return $data;
    }

    /**
     * Transforms a string or array into an id.
     */
    public function reverseTransform($data)
    {

        if (!$data) {
            return null;
        }

        if (is_scalar($data)) {

            $identifier = current(array_values($this->em->getClassMetadata($this->class)->getIdentifier()));

            $object = $this->em
                ->getRepository($this->class)
                ->findOneBy(array($identifier => $data))
            ;

            if (null === $object) {
                throw new TransformationFailedException(sprintf(
                    'An object with identifier key "%s" and value "%s" does not exist!',
                    $identifier, $data
                ));
            }
            return $object;
        }

        if(is_array($data)) {
            $identifier = current(array_values($this->em->getClassMetadata($this->class)->getIdentifier()));
            $object = $this->em
                ->getRepository($this->class)
                ->findBy(array($identifier => $data))
            ;
            if (null === $object) {
                throw new TransformationFailedException(sprintf(
                    'An object with identifier key "%s" and value "%s" does not exist!',
                    $identifier, $data
                ));
            }
            return $object;
        }

        return null;

    }

    /**
     * @return EntityManager
     */
    public function getEm()
    {
        return $this->em;
    }

    /**
     * @param EntityManager $em
     */
    public function setEm($em)
    {
        $this->em = $em;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param string $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }
}
