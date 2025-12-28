<?php

namespace AppBundle\Model;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Router;
use Psr\Log\LoggerInterface;
use AppBundle\Entity\User;
use AppBundle\Entity\Pack;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use AppBundle\Entity\Faction;
use AppBundle\Entity\Card;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * The job of this class is to find and return decks
 */
class DeckManager
{
	protected $faction;
	protected $page = 1;
	protected $start = 0;
	protected $limit = 30;
	protected $maxcount = 0;
	protected $user = false;
	protected $popularityString = '(1+d.nbVotes)/(1 + POWER(DATE_DIFF(CURRENT_TIMESTAMP(), d.dateCreation), 1) )';

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

	public function setUser($user)
	{
		$this->user = $user;
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
		$qb->from('AppBundle:Deck', 'd');
		//$qb->addSelect('JSON_EXTRACT(d.meta, "aspect") as meta');
		$qb->setFirstResult($this->start);
		$qb->setMaxResults($this->limit);
		if ($this->user) {
			$qb->andWhere('d.user = :user');
			$qb->setParameter('user', $this->user);
		}
		$qb->distinct();
		$qb->andWhere('d.nextDeck IS NULL');
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

	public function getAllTags(){
		$qb = $this->doctrine->createQueryBuilder();
		$qb->select('d.tags');
		$qb->from('AppBundle:Deck', 'd');
		if ($this->user) {
			$qb->andWhere('d.user = :user');
			$qb->setParameter('user', $this->user);
		}
		$qb->andWhere('d.nextDeck IS NULL');
		$qb->andWhere('d.tags IS NOT NULL');
		$qb->andWhere('d.tags != :tags');
		$qb->setParameter('tags', '');
		$tags = [];
		foreach($qb->getQuery()->getResult() as $deck) {
			$tags = array_merge($tags, explode(" ", $deck['tags']));
		}
		return array_filter($tags);
	}

	public function findDecksWithComplexSearch($user = false)
	{
		if (!$user) {
			return;
		}
		$request = $this->request_stack->getCurrentRequest();

		$cards_code = $request->query->get('cards');
		if(!is_array($cards_code)) {
			$cards_code = [];
		}

		$aspect = false;
		$aspect_code = filter_var($request->query->get('aspect'), FILTER_SANITIZE_STRING);
		if($aspect_code) {
			$aspect = $this->doctrine->getRepository('AppBundle:Faction')->findOneBy(['code' => $aspect_code]);
		}

		$investigator = false;
		$investigator_code = filter_var($request->query->get('investigator'), FILTER_SANITIZE_STRING);
		if($investigator_code) {
			$investigator = $this->doctrine->getRepository('AppBundle:Card')->findOneBy(['code' => $investigator_code]);
		}

		$decklist_name = filter_var($request->query->get('name'), FILTER_SANITIZE_STRING);

		$sort = $request->query->get('sort');
		$packs = $request->query->get('packs');

		$collection = filter_var($request->query->get('collection'), FILTER_SANITIZE_STRING);
		if (!$packs && $collection && $user) {
			// figure out users collection and assign the packs here!
			$owned_packs = $user->getOwnedPacks();
			if ($owned_packs) {
				$packs = explode(',', $owned_packs);
			}
		}

		$qb = $this->getQueryBuilder();
		$joinTables = [];

		if($investigator) {
			$duplicates = [];
			if ($investigator->getDuplicates()) {
				foreach ($investigator->getDuplicates() as $duplicate) {
					$duplicates[] = $duplicate->getCode();
				}
			}
			if ($investigator->getDuplicateOf()) {
				$duplicates[] = $investigator->getDuplicateOf()->getCode();
			}
			$qb->innerJoin('d.character', "investigator");
			if ($duplicates && count($duplicates) > 0) {
				$qb->andWhere("investigator.code IN (:investigator)");
				$qb->setParameter("investigator", array_merge([$investigator->getCode()], $duplicates));
			} else {
				$qb->andWhere("investigator.code = :investigator");
				$qb->setParameter("investigator", $investigator->getCode());
			}
		}

		$tag = filter_var($request->query->get('tag'), FILTER_SANITIZE_STRING);
		if($tag) {
			$qb->andWhere("d.tags like :tags");
			$qb->setParameter("tags", '%'.$tag.'%');
		}

		if(! empty($decklist_name)) {
			$qb->andWhere('d.name like :deckname');
			$qb->setParameter('deckname', "%$decklist_name%");
		}
		if(!empty($cards_code) || !empty($packs)) {
			if (!empty($cards_code) ) {
				foreach ($cards_code as $i => $card_code) {
					/* @var $card \AppBundle\Entity\Card */
					$card = $this->doctrine->getRepository('AppBundle:Card')->findOneBy(array('code' => $card_code));
					if ($card->getType()->getCode() == "hero"){
						$qb->innerJoin('d.character', "s$i");
						$qb->andWhere("s$i.code = :card$i");
						$qb->setParameter("card$i", $card_code);
						$packs[] = $card->getPack()->getId();
					} else if ($card->getType()->getCode() == "alter_ego" && !empty($card->getLinkedFrom())) {
						$qb->innerJoin('d.character', "s$i");
						$qb->andWhere("s$i.code = :card$i");
						$qb->setParameter("card$i", $card->getLinkedFrom()[0]->getCode());
						$packs[] = $card->getPack()->getId();
					} else {
						$packs[] = $card->getPack()->getId();
						$cardsOr = [$card];
						$dupeToCheck = $card;
						// if the card is a duplicate of another
						if ($card->getDuplicateOf()) {
							$cardsOr[] = $card->getDuplicateOf();
							$dupeToCheck = $card->getDuplicateOf();
						}
						// look up what this card duplicates and search for those also
						if ($dupeToCheck->getDuplicates()) {
							foreach($dupeToCheck->getDuplicates() as $j => $dupe) {
								$cardsOr[] = $dupe;
							}
						}
						$qb->innerJoin('d.slots', "s$i");
						$qb->andWhere("s$i.card IN (:card$i)");
						$qb->setParameter("card$i", $cardsOr);
					}
				}
			}
			if (!empty($packs)) {
				$sub = $this->doctrine->createQueryBuilder();
				$sub->select("c");
				$sub->from("AppBundle:Card","c");
				$sub->innerJoin('AppBundle:Decklistslot', 's', 'WITH', 's.card = c');
				$sub->where('s.decklist = d');
				$sub->andWhere($sub->expr()->notIn('c.pack', $packs));
				$qb->andWhere($qb->expr()->not($qb->expr()->exists($sub->getDQL())));

				$qb->innerJoin('d.character', "heropacks");
				$qb->andWhere($sub->expr()->in('heropacks.pack', $packs));
			}
		}

		switch($sort) {
			case 'name':
				$qb->orderBy('d.name', 'ASC');
				break;
			case 'date':
				$qb->orderBy('d.dateCreation', 'DESC');
				break;
			case 'updated':
			default:
				$qb->orderBy('d.dateUpdate', 'DESC');
				break;
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
		$routeParams = $request->get('_route_params');
		$params = $request->query->all();
		$previous_page = max(1, $this->page - 1);

		return $this->router->generate($route, [ "page" => $previous_page ] + $routeParams + $params);
	}

	public function getNextUrl()
	{
		if($this->page === $this->getNumberOfPages()) return null;

		$request = $this->request_stack->getCurrentRequest();
		$route = $request->get('_route');
		$routeParams = $request->get('_route_params');
		$params = $request->query->all();
		$next_page = min($this->getNumberOfPages(), $this->page + 1);

		return $this->router->generate($route, [ "page" => $next_page ] + $routeParams + $params);
	}

}
