<?php 

namespace AppBundle\Repository;

class TypeRepository extends TranslatableRepository
{
	function __construct($entityManager)
	{
		parent::__construct($entityManager, $entityManager->getClassMetadata('AppBundle\Entity\Type'));
	}

	public function findAll()
	{
		$qb = $this->createQueryBuilder('t')
			->select('t')
			->orderBy('t.name', 'ASC');

		return $this->getResult($qb);
	}
}