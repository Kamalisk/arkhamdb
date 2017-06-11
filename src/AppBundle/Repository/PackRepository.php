<?php 

namespace AppBundle\Repository;

class PackRepository extends TranslatableRepository
{
	function __construct($entityManager)
	{
		parent::__construct($entityManager, $entityManager->getClassMetadata('AppBundle\Entity\Pack'));
	}

	public function findAll()
	{
		$qb = $this->createQueryBuilder('p')
				->select('p, y')
				->join('p.cycle', 'y')
				->orderBy('p.dateRelease', 'ASC')
				->addOrderBy('p.position', 'ASC');

		return $this->getResult($qb);
	}

	public function findByCode($code)
	{
		$qb = $this->createQueryBuilder('p')
			->select('p')
			->andWhere('p.code = ?1');

		$qb->setParameter(1, $code);

		return $this->getOneOrNullResult($qb);
	}
}