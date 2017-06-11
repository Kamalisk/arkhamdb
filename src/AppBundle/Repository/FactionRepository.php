<?php 

namespace AppBundle\Repository;

class FactionRepository extends TranslatableRepository
{
	function __construct($entityManager)
	{
		parent::__construct($entityManager, $entityManager->getClassMetadata('AppBundle\Entity\Faction'));
	}
	
	public function findAllAndOrderByName()
	{
		$qb = $this->createQueryBuilder('f')->orderBy('f.name', 'ASC');
		return $this->getResult($qb);
	}


	public function findPrimaries()
	{
		$qb = $this->createQueryBuilder('f')->andWhere('f.isPrimary = 1');
		return $this->getResult($qb);
	}

	public function findByCode($code)
	{
		$qb = $this->createQueryBuilder('f')->andWhere('f.code = ?1');
		$qb->setParameter(1, $code);
		return $this->getOneOrNullResult($qb);
	}
}