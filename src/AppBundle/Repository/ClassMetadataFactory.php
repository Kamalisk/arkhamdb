<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;

use Gedmo\Translatable\TranslatableListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

class ClassMetadataFactory extends \Doctrine\ORM\Mapping\ClassMetadataFactory
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * Workaround for private EntityManager field in \Doctrine\ORM\Mapping\ClassMetadataFactory::newClassMetadataInstance()
     * @inheritdoc
     */
    public function setEntityManager(EntityManagerInterface $em)
    {
        $this->em = $em;
        parent::setEntityManager($em);
    }

    /**
     * @inheritDoc
     */
    protected function newClassMetadataInstance($className)
    {
        return new \AppBundle\Repository\ClassMetadata($className, $this->em->getConfiguration()->getNamingStrategy());
    }
}

