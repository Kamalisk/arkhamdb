<?php

namespace AppBundle\Repository;

class CardRepository extends TranslatableRepository
{
	function __construct($entityManager)
	{
		parent::__construct($entityManager, $entityManager->getClassMetadata('AppBundle\Entity\Card'));
	}


	public function findAll()
	{
		$qb = $this->createQueryBuilder('c')
			->select('c', 'p', 'y', 't', 'b', 'f', 'e')
			->leftJoin('c.pack', 'p')
			->leftJoin('p.cycle', 'y')
			->leftJoin('c.type', 't')
			->leftJoin('c.subtype', 'b')
			->leftJoin('c.faction', 'f')
			->leftJoin('c.encounter', 'e');
		return $this->getResult($qb);
	}

	public function findAllWithoutEncounter()
	{
		$qb = $this->createQueryBuilder('c')
			->select('c', 'p', 'y', 't', 'b', 'f', 'e')
			->leftJoin('c.pack', 'p')
			->leftJoin('p.cycle', 'y')
			->leftJoin('c.type', 't')
			->leftJoin('c.subtype', 'b')
			->leftJoin('c.faction', 'f')
			->leftJoin('c.encounter', 'e')
			->andWhere('c.encounter IS NULL');
		return $this->getResult($qb);
	}

	public function findByType($type)
	{
		$qb = $this->createQueryBuilder('c')
			->select('c, p')
			->join('c.pack', 'p')
			->join('c.type', 't')
			->andWhere('t.code = ?1')
			->orderBY('c.code', 'ASC');

		$qb->setParameter(1, $type);

		return $this->getResult($qb);
	}

	public function findByCode($code)
	{
		$qb = $this->createQueryBuilder('c')
			->select('c')
			->andWhere('c.code = ?1');

		$qb->setParameter(1, $code);

		return $this->getOneOrNullResult($qb);
	}

	public function findByDuplicateOf()
	{
		$qb = $this->createQueryBuilder('c')
			->select('c')
			->andWhere('c.duplicate_of is not null');
		return $this->getResult($qb);
	}

	public function findAllByCode($code)
	{
		$qb = $this->createQueryBuilder('c')
			->select('c', 'p', 'y', 't', 'b', 'f', 'e')
			->leftJoin('c.pack', 'p')
			->leftJoin('p.cycle', 'y')
			->leftJoin('c.type', 't')
			->leftJoin('c.subtype', 'b')
			->leftJoin('c.faction', 'f')
			->leftJoin('c.encounter', 'e')
			->andWhere('c.code in (?1)')
			->orderBY('c.code', 'ASC');

		$qb->setParameter(1, $code);

		return $this->getOneOrNullResult($qb);
	}

	public function findAllByCodes($codes)
	{
		$qb = $this->createQueryBuilder('c')
			->select('c', 'p', 'y', 't', 'b', 'f', 'e')
			->leftJoin('c.pack', 'p')
			->leftJoin('p.cycle', 'y')
			->leftJoin('c.type', 't')
			->leftJoin('c.subtype', 'b')
			->leftJoin('c.faction', 'f')
			->leftJoin('c.encounter', 'e')
			->andWhere('c.code in (?1)')
			->orderBY('c.code', 'ASC');

		$qb->setParameter(1, $codes);

		return $this->getResult($qb);
	}

	public function findByRelativePosition($card, $position)
	{
		$qb = $this->createQueryBuilder('c')
			->select('c')
			->join('c.pack', 'p')
			->andWhere('p.code = ?1')
			->andWhere('c.hidden = false');

		// select the card next in the sequence based on position or set position (since sometimes cards have the same position)
		if ($position > 0) {
			$exp1 = $qb->expr()->andX('c.position > ?2');
			$exp2 = $qb->expr()->andX('c.encounterPosition > ?3', 'c.position >= ?2');
			$exp3 = $qb->expr()->orX($exp1, $exp2);

			$qb->andWhere($exp3)
			->orderBy('c.position', 'ASC')
			->addOrderBy('c.encounterPosition', 'ASC');
		} else {
			$exp1 = $qb->expr()->andX('c.position < ?2');
			$exp2 = $qb->expr()->andX('c.encounterPosition < ?3', 'c.position <= ?2');
			$exp3 = $qb->expr()->orX($exp1, $exp2);

			$qb->andWhere($exp3)
			->orderBy('c.position', 'DESC')
			->addOrderBy('c.encounterPosition', 'DESC');
		}

		$qb->setParameter(1, $card->getPack()->getCode());
		$qb->setParameter(2, $card->getPosition());
		$qb->setParameter(3, $card->getEncounterPosition());
		$qb->setMaxResults(1);

		return $this->getOneOrNullResult($qb);
	}

	public function findPreviousCard($card)
	{
		return $this->findByRelativePosition($card, -1);
	}

	public function findNextCard($card)
	{
		return $this->findByRelativePosition($card, 1);
	}

	public function findTraits()
	{
		$qb = $this->createQueryBuilder('c')
			->select('DISTINCT c.traits')
			->andWhere("c.traits != ''");
		return $this->getResult($qb);
	}

	public function findSlots()
	{
		$qb = $this->createQueryBuilder('c')
			->select('DISTINCT c.slot')
			->andWhere("c.slot != ''");
		return $this->getResult($qb);
	}

	public function findInvestigators()
	{
		$qb = $this->createQueryBuilder('c')
			->select('c', 'p', 'y', 't', 'b', 'f', 'e')
			->leftJoin('c.pack', 'p')
			->leftJoin('p.cycle', 'y')
			->leftJoin('c.type', 't')
			->leftJoin('c.subtype', 'b')
			->leftJoin('c.faction', 'f')
			->leftJoin('c.encounter', 'e')
			->andWhere('t.code = ?1')
			->orderBY('c.name', 'ASC');

		$qb->setParameter(1, "investigator");

		return $this->getResult($qb);
	}
}