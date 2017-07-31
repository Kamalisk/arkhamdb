<?php 

namespace AppBundle\Repository;

class CycleRepository extends TranslatableRepository
{
	function __construct($entityManager)
	{
		parent::__construct($entityManager, $entityManager->getClassMetadata('AppBundle\Entity\Cycle'));
	}

	public function findAll()
	{
		$qb = $this->createQueryBuilder('y')
			->select('y, p')
			->leftJoin('y.packs', 'p')
			->orderBy('y.position', 'ASC');

		return $this->getResult($qb);
	}

	public function findByCode($code)
	{
		$qb = $this->createQueryBuilder('y')
			->select('y')
			->andWhere('y.code = ?1');

		$qb->setParameter(1, $code);

		return $this->getOneOrNullResult($qb);
	}
}