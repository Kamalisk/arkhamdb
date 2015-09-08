<?php 

namespace AppBundle\Model;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Router;
use AppBundle\Services\DeckInterface;
use Psr\Log\LoggerInterface;
use AppBundle\Entity\User;
use AppBundle\Entity\Pack;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\QueryBuilder;
use AppBundle\Entity\Faction;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * The job of this class is to find and return decklists
 * @author alsciende
 * @property integer $maxcount Number of found rows for last request
 * 
 */
class DecklistManager
{
	protected $faction;
	protected $page = 1;
	protected $start = 0;
	protected $limit = 30;
	protected $maxcount = 0;
	
	public function __construct(EntityManager $doctrine, RequestStack $request_stack, Router $router, LoggerInterface $logger)
	{
		$this->doctrine = $doctrine;
		$this->request_stack = $request_stack;
		$this->router = $router;
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
		$this->maxcount = $paginator->count();
		return $paginator;
	}
	
	public function getEmptyList()
	{
		$this->maxcount = 0;
		return new ArrayCollection([]);
	}
	
	public function findDecklistsByPopularity()
	{
		$qb = $this->getQueryBuilder();
		$qb->addSelect('(1+d.nbVotes)/(1+POWER(DATE_DIFF(CURRENT_TIMESTAMP(), d.dateCreation), 2)) AS HIDDEN popularity');
		$qb->orderBy('popularity', 'DESC');
		return $this->getPaginator($qb->getQuery());
	}
	
	public function findDecklistsByAge($ignoreEmptyDescriptions = FALSE)
	{
		$qb = $this->getQueryBuilder();
		$qb->orderBy('d.dateCreation', 'DESC');
		return $this->getPaginator($qb->getQuery());
	}
	
	public function findDecklistsByFavorite(User $user)
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
	
	public function findDecklistsWithComplexSearch()
	{
		$request = $this->request_stack->getCurrentRequest();

		$cards_code = $request->query->get('cards');
		if(!is_array($cards_code)) {
			$cards_code = [];
		}
		
		$faction_code = filter_var($request->query->get('faction'), FILTER_SANITIZE_STRING);
		if($faction_code) {
			$faction = $this->doctrine->getRepository('AppBundle:Faction')->findOneBy(['code' => $faction_code]);
		}
		
		$author_name = filter_var($request->query->get('author'), FILTER_SANITIZE_STRING);
		
		$decklist_name = filter_var($request->query->get('name'), FILTER_SANITIZE_STRING);
		
		$sort = $request->query->get('sort');
		
		$packs = $request->query->get('packs');
		
		$qb = $this->getQueryBuilder();
		if(!empty($faction)) {
			$qb->andWhere('d.faction = :faction');
			$qb->setParameter('faction', $faction);
		}
		if(!empty($author_name)) {
			$qb->innerJoin('d.user', 'u');
			$qb->andWhere('u.username = :username');
			$qb->setParameter('username', $author_name);
		}
		if(! empty($decklist_name)) {
			$qb->andWhere('d.name like :deckname');
			$qb->setParameter('deckname', "%$decklist_name%");
		}
		if(!empty($card_code) && !empty($packs)) {
			$qb->innerJoin('d.slots', 's');
			if (!empty($cards_code) ) {
				foreach ($cards_code as $i => $card_code) {
					/* @var $card \AppBundle\Entity\Card */
					$card = $this->doctrine->getRepository('AppBundle:Card')->findOneBy(array('code' => $card_code));
					if(!$card) continue;
			
					$qb->andWhere('s.card = :card'.$i);
					$qb->setParameter('card'.$i, $card);
						
					$packs[] = $card->getPack()->getId();
				}
			}
			if (!empty($packs)) {
				$sub = $this->doctrine->createQueryBuilder();
				$sub->select("c");
				$sub->from("AppBundle:Card","c");
				$sub->where('c = s.card');
				$sub->andWhere($sub->expr()->notIn('c.pack', $packs));
					
				$qb->andWhere($qb->expr()->not($qb->expr()->exists($sub->getDQL())));
			}
		}

		return $this->getPaginator($qb->getQuery());
	}
	
	public function getNumberOfPages()
	{
		return intval(ceil($this->maxcount / $this->limit));
	}
	
	public function getAllPages()
	{
		$request = $this->request_stack->getCurrentRequest();
		$route = $request->get('_route');
		$route_params = $request->get('_route_params');
		$query = $request->query->all();
		
		$params = $query + $route_params;
		
		$number_of_pages = $this->getNumberOfPages();
		$pages = [];
		for ($page = 1; $page <= $number_of_pages; $page ++) {
			$pages[] = array(
				"numero" => $page,
				"url" => $this->router->generate($route, ["page" => $page] + $params),
				"current" => $page == $this->page
			);
		}
		return $pages;
	}

	public function getClosePages()
	{
		$allPages = $this->getAllPages();
		$numero_courant = $this->page - 1;
		$pages = [];
		foreach($allPages as $numero => $page) {
			if($numero === 0
					|| $numero === count($allPages) - 1
					|| abs($numero - $numero_courant) <= 2) 
			{
				$pages[] = $page;
			}
		}
		return $pages;
	}
	
	public function getPreviousUrl()
	{
		if($this->page === 1) return null;
		
		$request = $this->request_stack->getCurrentRequest();
		$route = $request->get('_route');
		$params = $request->query->all();
		$previous_page = max(1, $this->page - 1);
		
		return $this->router->generate($route, $params + [ "page" => $previous_page ]);
	}

	public function getNextUrl()
	{
		if($this->page === $this->getNumberOfPages()) return null;
	
		$request = $this->request_stack->getCurrentRequest();
		$route = $request->get('_route');
		$params = $request->query->all();
		$next_page = min($this->getNumberOfPages(), $this->page + 1);
	
		return $this->router->generate($route, $params + [ "page" => $next_page ]);
	}
	
}