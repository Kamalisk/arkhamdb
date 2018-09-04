<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;

use Gedmo\Translatable\TranslatableListener;

use Doctrine\ORM\EntityManager;

class ClassMetadata extends \Doctrine\ORM\Mapping\ClassMetadata
{
    /**
     * @inheritdoc
     */
    public function mapField(array $mapping)
    {
        // Fix performance issue with column types mismatch and lack of indexes optimization
        if (
             $this->name === 'Gedmo\Translatable\Entity\Translation'
             // If you have a custom translation entitny. See: https://github.com/Atlantic18/DoctrineExtensions/blob/master/doc/translatable.md#translation-entity
             || $this->name === 'AppBundle\Repository\CustomEntityTranslation'
        ) {
            if ($mapping['fieldName'] === 'foreignKey') {
                $mapping['type'] = 'integer';
                unset($mapping['length']);
            }
        }
        parent::mapField($mapping);
    }
}

