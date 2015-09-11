<?php

namespace TRD\CoreBundle\Component\Form\Type;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use TRD\CoreBundle\Component\Form\DataTransformer\EntityIdTransformer;

class EntityIdType extends AbstractType
{
    private $em;
    
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new EntityIdTransformer($this->em, $options['repo_class']);
        
        $builder->addModelTransformer($transformer);
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('repo_class'));
    }

    public function getParent()
    {
        return 'integer';
    }
    
    public function getName()
    {
        return 'entity_id';
    }
}
