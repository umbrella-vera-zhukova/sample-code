<?php

namespace TRD\CoreBundle\Component\Form\DataTransformer;

use Exception;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class EntityIdTransformer implements DataTransformerInterface
{
    /**
     * @var EntityManager
     */
    private $em;
    
    /**
     * @var string
     */
    private $repoClass;

    /**
     * @param EntityManager $om
     */
    public function __construct(EntityManager $em, $repoClass)
    {
        $this->em = $em;
        $this->repoClass = $repoClass;
    }
    
    /**
     * Transforms an object to a string (number).
     * 
     * @param Object|null $entity
     * @return string|null
     */
    public function transform($entity)
    {
        if (!$entity)
        {
            return null;
        }
        
        return $entity->getId();
    }
    
    /**
     * Transforms a string (number) to an object
     * 
     * @param string $id
     * @return Object|null
     */
    public function reverseTransform($id)
    {
        if (!$id)
        {
            return null;
        }

        $id = intval($id);
        
        $repo = $this->em->getRepository($this->repoClass);
        
        try
        {
            $obj = $repo->findOneById($id);
        }
        catch (Exception $ex)
        {
            throw new TransformationFailedException('Something wrong with reverseTransform.');
        }
        
        if (!$obj)
        {
            throw new TransformationFailedException(sprintf(
                'An object with ID "%s" does not exist!',
                $id
            ));
        }
        
        return $obj;
    }
}
