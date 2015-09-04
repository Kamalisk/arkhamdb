<?php 

namespace AppBundle\Model;

use Psr\Log\LoggerInterface;
use AppBundle\Services\DeckInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;
use AppBundle\Entity\User;
use AppBundle\Entity\Pack;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\QueryBuilder;
use AppBundle\Entity\Faction;

/**
 * The job of this class is to find and return decklists
 * @author alsciende
 * @property integer $maxcount Number of found rows for last request
 */
class DecklistManager
{
	protected $faction;
	protected $page = 1;
	protected $start = 0;
	protected $limit = 30;
	protected $maxcount = 0;
	
	public function __construct(EntityManager $doctrine, RequestStack $request_stack, DeckInterface $deck_interface, LoggerInterface $logger)
	{
		$this->doctrine = $doctrine;
		$this->request_stack = $request_stack;
		$this->deck_interface = $deck_interface;
		$this->logger = $logger;
	}
	
	public function setFaction(Faction $faction = null)
	{
		$this->faction = $faction;
	}
	
	public function setLimit($limit)
	{
		$this->limit = $limit;
	}
	
	public function setPage($page)
	{
		$this->page = max($page, 1);
		$this->start = ($this->page - 1) * $this->limit;
	}
	
	public function getMaxCount()
	{
		return $this->maxcount;
	}
	
	/**
	 * creates the basic query builder and initializes it
	 */
	private function getQueryBuilder()
	{
		$qb = $this->doctrine->createQueryBuilder();
		$qb->select('d');
		$qb->from('AppBundle:Decklist', 'd');
		if($this->faction) {
			$qb->where('d.faction = :faction');
			$qb->setParameter('faction', $this->faction);
		}
		$qb->setFirstResult($this->start);
		$qb->setMaxResults($this->limit);
		return $qb;
	}
	
	/**
	 * creates the paginator around the query
	 * @param Query $query
	 */
	private function getPaginator(Query $query)
	{
		$paginator = new Paginator($query, $fetchJoinCollection = FALSE);
		return $paginator;
	}
	
	public function findPopularDecklists()
	{
		$qb = $this->getQueryBuilder();
		$qb->addSelect('(1+d.nbVotes)/(1+POWER(DATE_DIFF(CURRENT_TIMESTAMP(), d.dateCreation), 2)) AS HIDDEN popularity');
		$qb->orderBy('popularity', 'DESC');
		return $this->getPaginator($qb->getQuery());
	}
	
	public function findRecentDecklists($ignoreEmptyDescriptions = FALSE)
	{
		$qb = $this->getQueryBuilder();
		$qb->orderBy('d.dateCreation', 'DESC');
		return $this->getPaginator($qb->getQuery());
	}
	
	public function findFavoriteDecklists(User $user)
	{
		$qb = $this->getQueryBuilder();
		$qb->leftJoin('d.favorites', 'u');
		$qb->andWhere('u = :user');
		$qb->setParameter('user', $user);
		$qb->orderBy('d.dateCreation', 'DESC');
		return $this->getPaginator($qb->getQuery());
	}
	
	public function findDecklistsByAuthor(User $user)
	{
		$qb = $this->getQueryBuilder();
		$qb->andWhere('d.user = :user');
		$qb->setParameter('user', $user);
		$qb->orderBy('d.dateCreation', 'DESC');
		return $this->getPaginator($qb->getQuery());
	}
	
	public function findDecklistsInHallOfFame()
	{
		$qb = $this->getQueryBuilder();
		$qb->andWhere('d.nbVotes > 10');
		$qb->orderBy('d.nbVotes', 'DESC');
		return $this->getPaginator($qb->getQuery());
	}
	
	public function findDecklistsInHotTopic()
	{
		$qb = $this->getQueryBuilder();
		$qb->addSelect('(SELECT count(c) FROM AppBundle:Comment c WHERE c.decklist=d AND DATE_DIFF(CURRENT_TIMESTAMP(), c.dateCreation)<1) AS HIDDEN nbRecentComments');
		$qb->orderBy('nbRecentComments', 'DESC');
		$qb->orderBy('d.nbComments', 'DESC');
		return $this->getPaginator($qb->getQuery());
	}
	
	public function findDecklistsInTournaments()
	{
		$qb = $this->getQueryBuilder();
		$qb->andWhere('d.tournament is not null');
		$qb->orderBy('d.dateCreation', 'DESC');
		return $this->getPaginator($qb->getQuery());
	}
	
	public function findDecklistsWithComplexSearch($params)
	{
		$qb = $this->getQueryBuilder();
		return $this->getPaginator($qb->getQuery());
	}
	
}