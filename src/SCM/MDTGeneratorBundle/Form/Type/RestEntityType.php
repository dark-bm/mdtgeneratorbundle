<?php


namespace SCM\MDTGeneratorBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Form\Transformer\EntityToIdObjectTransformer;
use MDTGeneratorBundle\Form\DataTransformer\EntityArrayToIdArrayTransformer;
use MDTGeneratorBundle\Form\DataTransformer\ObjectToIdTransformer;
use Symfony\Bridge\Doctrine\Form\DataTransformer\CollectionToArrayTransformer;
use Symfony\Bridge\Doctrine\Form\EventListener\MergeDoctrineCollectionListener;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use MDTGeneratorBundle\Form\DataTransformer\ArrayToIdTransformer;

/**
 * Class RestEntityType
 * @package MDTGeneratorBundle\Form\Type
 */
class RestEntityType extends EntityType
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param EntityManager $em
     */

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->resetViewTransformers();
        $builder->addViewTransformer(new ObjectToIdTransformer($this->em,$options['class']));
        if ($options['multiple']) {
            $builder->addViewTransformer(new EntityArrayToIdArrayTransformer($this->em, null));
        } else {
            $builder->addViewTransformer(new ArrayToIdTransformer($this->em, null));

        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'invalid_message' => 'The selected entity does not exist',
            )
        );
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'entity';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'rest_entity';
    }
}
