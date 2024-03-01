<?php
namespace AppBundle\Controller;

use \DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use AppBundle\Entity\Deck;
use AppBundle\Entity\Decklist;
use AppBundle\Entity\Decklistslot;
use AppBundle\Entity\Comment;
use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use AppBundle\Model\DecklistManager;
use AppBundle\Services\Pagination;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use AppBundle\Entity\Pack;

class SocialController extends Controller
{
	/**
	* Checks to see if a deck can be published in its current saved state
	* If it is, displays the decklist edit form for initial publication of a deck
	*/
	public function publishFormAction($deck_id, Request $request)
	{
		/* @var $em \Doctrine\ORM\EntityManager */
		$em = $this->getDoctrine()->getManager();

		/* @var $user \AppBundle\Entity\User */
		$user = $this->getUser();
		if (! $user) {
			throw $this->createAccessDeniedException("You must be logged in for this operation.");
		}

		$deck = $em->getRepository('AppBundle:Deck')->find($deck_id);
		if (! $deck || $deck->getUser()->getId() != $user->getId()) {
			throw $this->createAccessDeniedException("You don't have access to this decklist.");
		}

		$yesterday = (new \DateTime())->modify('-24 hours');
		if($user->getDateCreation() > $yesterday) {
			$this->get('session')->getFlashBag()->set('error', "To prevent spam, newly created accounts must wait 24 hours before being allowed to publish a decklist.");
			return $this->redirect($this->generateUrl('deck_view', [ 'deck_id' => $deck->getId() ]));
		}

		$query = $em->createQuery("SELECT COUNT(d) FROM AppBundle:Decklist d WHERE d.dateCreation>:date AND d.user=:user AND d.nextDeck IS NULL");
		$query->setParameter('date', $yesterday);
		$query->setParameter('user', $user);
		$decklistsSinceYesterday = $query->getSingleScalarResult();

		if($decklistsSinceYesterday > $user->getReputation() + 3) {
			$this->get('session')->getFlashBag()->set('error', "To prevent spam, accounts cannot publish more decklists than their reputation per 24 hours.");
			return $this->redirect($this->generateUrl('deck_view', [ 'deck_id' => $deck->getId() ]));
		}

		$lastPack = $deck->getLastPack();
		if(!$lastPack->getDateRelease() || $lastPack->getDateRelease() > new \DateTime()) {
			$this->get('session')->getFlashBag()->set('error', "You cannot publish this deck yet, because it has unreleased cards.");
			return $this->redirect($this->generateUrl('deck_view', [ 'deck_id' => $deck->getId() ]));
		}

		$problem = $this->get('deck_validation_helper')->findProblem($deck);
		if ($problem) {
			$this->get('session')->getFlashBag()->set('error', "This deck cannot be published because it is invalid.");
			return $this->redirect($this->generateUrl('deck_view', [ 'deck_id' => $deck->getId() ]));
		}

		$new_content = json_encode($deck->getSlots()->getContent());
		$new_signature = md5($new_content);
		$old_decklists = $this->getDoctrine()->getRepository('AppBundle:Decklist')->findBy([ 'signature' => $new_signature ]);

		/* @var $decklist \AppBundle\Entity\Decklist */
		foreach ($old_decklists as $decklist) {
			if (json_encode($decklist->getSlots()->getContent()) == $new_content) {
				$url = $this->generateUrl('decklist_detail', array(
				'decklist_id' => $decklist->getId(),
				'decklist_name' => $decklist->getNameCanonical()
				));
				$this->get('session')->getFlashBag()->set('warning', "This deck <a href=\"$url\">has already been published</a> before. You are going to create a duplicate.");
			}
		}

		// decklist for the form ; won't be persisted
		$decklist = $this->get('decklist_factory')->createDecklistFromDeck($deck, $deck->getName(), $deck->getDescriptionMd());

		//$tournaments = $this->getDoctrine()->getManager()->getRepository('AppBundle:Tournament')->findAll();

		return $this->render('AppBundle:Decklist:decklist_edit.html.twig', [
		'url' => $this->generateUrl('decklist_create'),
		'deck' => $deck,
		'decklist' => $decklist
		]);
	}

	/**
	* creates a new decklist from a deck (publish action)
	*/
	public function createAction (Request $request)
	{
		/* @var $em \Doctrine\ORM\EntityManager */
		$em = $this->getDoctrine()->getManager();
		/* @var $user \AppBundle\Entity\User */
		$user = $this->getUser();

		$yesterday = (new \DateTime())->modify('-24 hours');
		if($user->getDateCreation() > $yesterday) {
			return $this->render('AppBundle:Default:error.html.twig', [
			'pagetitle' => "Spam prevention",
			'error' => "To prevent spam, newly created accounts must wait 24 hours before being allowed to publish a decklist.",
			]);
		}

		$query = $em->createQuery("SELECT COUNT(d) FROM AppBundle:Decklist d WHERE d.dateCreation>:date AND d.user=:user");
		$query->setParameter('date', $yesterday);
		$query->setParameter('user', $user);
		$decklistsSinceYesterday = $query->getSingleScalarResult();

		if($decklistsSinceYesterday > $user->getReputation() + 3) {
			return $this->render('AppBundle:Default:error.html.twig', [
			'pagetitle' => "Spam prevention",
			'error' => "To prevent spam, accounts cannot publish more decklists than their reputation per 24 hours.",
			]);
		}

		$deck_id = intval(filter_var($request->request->get('deck_id'), FILTER_SANITIZE_NUMBER_INT));

		/* @var $deck \AppBundle\Entity\Deck */
		$deck = $this->getDoctrine()->getRepository('AppBundle:Deck')->find($deck_id);
		if ($user->getId() !== $deck->getUser()->getId()) {
			throw $this->createAccessDeniedException("Access denied to this object.");
		}


		$lastPack = $deck->getLastPack();
		if(!$lastPack->getDateRelease() || $lastPack->getDateRelease() > new \DateTime()) {
			$this->get('session')->getFlashBag()->set('error', "You cannot publish this deck yet, because it has unreleased cards.");
			return $this->redirect($this->generateUrl('deck_view', [ 'deck_id' => $deck->getId() ]));
		}

		$name = filter_var($request->request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
		$descriptionMd = trim($request->request->get('descriptionMd'));
		if (!$descriptionMd || strlen($descriptionMd) < 20){
			return $this->render('AppBundle:Default:error.html.twig', [
			'pagetitle' => "Description too short. ",
			'error' => "Please provide at least a quick write up of your deck or ask what you need help with. If you just want to share decks, you can do show by enabling sharing in your options and then share the regular deck link.",
			]);
		}
		// $tournament_id = filter_var($request->request->get('tournament'), FILTER_SANITIZE_NUMBER_INT);
		// $tournament = $em->getRepository('AppBundle:Tournament')->find($tournament_id);

		$validated_tags = [];
		if ($request->request->get('tags')){
			$tags = $request->request->get('tags');
			if(is_array($tags)){
				foreach($tags as $tag){
					if (filter_var($tag, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)){
						if ($tag == "multiplayer" || $tag == "solo" || $tag == "beginner" || $tag == "theme"){
							$validated_tags[] = $tag;
						}
					}
				}
			}
		}
		$tag_string = implode(",", $validated_tags);

		$precedent_id = trim($request->request->get('precedent'));
		if(!preg_match('/^\d+$/', $precedent_id))
		{
			// route decklist_detail hard-coded
			if(preg_match('/view\/(\d+)/', $precedent_id, $matches))
			{
				$precedent_id = $matches[1];
			}
			else
			{
				$precedent_id = null;
			}
		}
		$precedent = $precedent_id ? $em->getRepository('AppBundle:Decklist')->find($precedent_id) : null;

		try
		{
			$decklist = $this->get('decklist_factory')->createDecklistFromDeck($deck, $name, $descriptionMd);
		}
		catch(\Exception $e)
		{
			return $this->render('AppBundle:Default:error.html.twig', [
			'pagetitle' => "Error",
			'error' => $e
			]);
		}

		//$decklist->setTournament($tournament);
		$decklist->setPrecedent($precedent);
		if ($tag_string){
			$decklist->setTags($tag_string);
		}

		$em->persist($decklist);

		$em->flush();

		return $this->redirect($this->generateUrl('decklist_detail', array(
			'decklist_id' => $decklist->getId(),
			'decklist_name' => $decklist->getNameCanonical()
		)));
	}

	/**
	* Displays the decklist edit form
	*/
	public function editFormAction($decklist_id, Request $request)
	{
		/* @var $em \Doctrine\ORM\EntityManager */
		$em = $this->getDoctrine()->getManager();

		/* @var $user \AppBundle\Entity\User */
		$user = $this->getUser();
		if (! $user) {
			throw $this->createAccessDeniedException("Anonymous access denied");
		}

		$decklist = $em->getRepository('AppBundle:Decklist')->find($decklist_id);
		if (! $decklist ) {
			throw $this->createNotFoundException("Decklist not found");
		}

		if ( !$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN') && $user->getId() !== $decklist->getUser()->getId() ) {
			throw $this->createAccessDeniedException("Access denied");
		}

		$tournaments = $this->getDoctrine()->getManager()->getRepository('AppBundle:Tournament')->findAll();

		return $this->render('AppBundle:Decklist:decklist_edit.html.twig', [
		'url' => $this->generateUrl('decklist_save', [ 'decklist_id' => $decklist->getId() ]),
		'deck' => null,
		'decklist' => $decklist,
		'tournaments' => $tournaments,
		]);
	}

	/*
	* save the name and description of a decklist by its publisher
	*/
	public function saveAction ($decklist_id, Request $request)
	{
		/* @var $em \Doctrine\ORM\EntityManager */
		$em = $this->getDoctrine()->getManager();

		$user = $this->getUser();
		if (! $user) {
			throw $this->createAccessDeniedException("Anonymous access denied");
		}

		$decklist = $em->getRepository('AppBundle:Decklist')->find($decklist_id);
		if (! $decklist ) {
			throw $this->createNotFoundException("Decklist not found");
		}

		if ( !$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN') && $user->getId() !== $decklist->getUser()->getId() ) {
			throw $this->createAccessDeniedException("Access denied");
		}

		$name = trim(filter_var($request->request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
		$name = substr($name, 0, 60);
		if(empty($name)) $name = "Untitled";
		$descriptionMd = trim($request->request->get('descriptionMd'));
		$descriptionHtml = $this->get('texts')->markdown($descriptionMd);

		$tournament_id = intval(filter_var($request->request->get('tournament'), FILTER_SANITIZE_NUMBER_INT));
		$tournament = $em->getRepository('AppBundle:Tournament')->find($tournament_id);

		$precedent_id = trim($request->request->get('precedent'));
		if(!preg_match('/^\d+$/', $precedent_id))
		{
			// route decklist_detail hard-coded
			if(preg_match('/view\/(\d+)/', $precedent_id, $matches))
			{
				$precedent_id = $matches[1];
			}
			else
			{
				$precedent_id = null;
			}
		}
		$precedent = ($precedent_id && $precedent_id != $decklist_id) ? $em->getRepository('AppBundle:Decklist')->find($precedent_id) : null;

		$decklist->setName($name);
		$decklist->setNameCanonical($this->get('texts')->slugify($name) . '-' . $decklist->getVersion());
		$decklist->setDescriptionMd($descriptionMd);
		$decklist->setDescriptionHtml($descriptionHtml);
		$decklist->setPrecedent($precedent);
		$decklist->setTournament($tournament);
		$decklist->setDateUpdate(new \DateTime());
		$em->flush();

		return $this->redirect($this->generateUrl('decklist_detail', array(
		'decklist_id' => $decklist_id,
		'decklist_name' => $decklist->getNameCanonical()
		)));

	}

	/**
	* deletes a decklist if it has no comment, no vote, no favorite
	*/
	public function deleteAction ($decklist_id, Request $request)
	{
		/* @var $em \Doctrine\ORM\EntityManager */
		$em = $this->getDoctrine()->getManager();

		$user = $this->getUser();
		if (! $user)
		throw new UnauthorizedHttpException("You must be logged in for this operation.");

		$decklist = $em->getRepository('AppBundle:Decklist')->find($decklist_id);
		if (! $decklist || $decklist->getUser()->getId() != $user->getId())
		throw new UnauthorizedHttpException("You don't have access to this decklist.");

		if ($decklist->getnbVotes() || $decklist->getNbfavorites() || $decklist->getNbcomments())
		throw new UnauthorizedHttpException("Cannot delete this decklist.");

		if ($decklist->getNextDeck())
		throw new UnauthorizedHttpException("Cannot delete this decklist.");

		$precedent = $decklist->getPrecedent();

		$children_decks = $decklist->getChildren();
		/* @var $children_deck Deck */
		foreach ($children_decks as $children_deck) {
			$children_deck->setParent($precedent);
		}

		$successor_decklists = $decklist->getSuccessors();
		/* @var $successor_decklist Decklist */
		foreach ($successor_decklists as $successor_decklist) {
			$successor_decklist->setPrecedent($precedent);
		}

		$em->remove($decklist);
		$em->flush();

		return $this->redirect($this->generateUrl('decklists_list', array(
		'type' => 'mine'
		)));

	}

	private function searchForm(Request $request)
	{
		$dbh = $this->getDoctrine()->getConnection();

		$cards_code = $request->query->get('cards');
		$faction_code = filter_var($request->query->get('faction'), FILTER_SANITIZE_STRING);
		$author_name = filter_var($request->query->get('author'), FILTER_SANITIZE_STRING);
		$decklist_name = filter_var($request->query->get('name'), FILTER_SANITIZE_STRING);
		$sort = $request->query->get('sort');
		$packs = $request->query->get('packs');
		$date_from = $request->query->get('date_from');
		$date_to = $request->query->get('date_to');

		if(!is_array($packs)) {
			$packs = $dbh->executeQuery("select id from pack")->fetchAll(\PDO::FETCH_COLUMN);
		}

		$categories = [];
		$on = 0; $off = 0;
		$categories = array();
		$list_cycles = $this->getDoctrine()->getRepository('AppBundle:Cycle')->findBy([], array("position" => "ASC"));
		foreach($list_cycles as $cycle) {
			$size = count($cycle->getPacks());
			if($cycle->getPosition() == 0 || $size == 0) continue;
			$first_pack = $cycle->getPacks()[0];

			$category = array("label" => $cycle->getName(), "packs" => []);
			foreach($cycle->getPacks() as $pack) {
				$checked = count($packs) ? in_array($pack->getId(), $packs) : true;
				if($checked) $on++;
				else $off++;
				$category['packs'][] = array("id" => $pack->getId(), "label" => $pack->getName(), "checked" => $checked, "future" => $pack->getDateRelease() === NULL);
				if ($pack->getCode() == "core"){
					$checked = count($packs) ? in_array("1-2", $packs) : true;
					if($checked) {
						$on++;
					} else {
						$off++;
					}
					$category['packs'][] = array("id" => "1-2", "label" => "Second Core Set", "checked" => $checked, "future" => $pack->getDateRelease() === NULL);
				}
			}
			$categories[] = $category;
		}

		$params = array(
		'allowed' => $categories,
		'on' => $on,
		'off' => $off,
		'author' => $author_name,
		'name' => $decklist_name,
		'date_from' => $date_from,
		'date_to' => $date_to
		);
		$params['sort_'.$sort] = ' selected="selected"';
		$params['factions'] = $dbh->executeQuery(
		"SELECT
		f.name,
		f.code
		from faction f
		order by f.name asc")
		->fetchAll();
		$params['faction_selected'] = $faction_code;

		if (! empty($cards_code) && is_array($cards_code)) {
			$cards = $dbh->executeQuery(
			"SELECT
			c.name,
			c.code,
			f.code faction_code
			from card c
			join faction f on f.id=c.faction_id
			where c.code in (?)
			order by c.code desc", array($cards_code), array(\Doctrine\DBAL\Connection::PARAM_INT_ARRAY))
			->fetchAll();

			$params['cards'] = '';
			foreach($cards as $card) {
				$params['cards'] .= $this->renderView('AppBundle:Search:card.html.twig', $card);
			}

		}

		return $this->renderView('AppBundle:Search:form.html.twig', $params);
	}

	/*
	* displays the lists of decklists
	*/
	public function listAction ($type, $faction = null, $page = 1, Request $request)
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));

		/**
		* @var $decklist_manager DecklistManager
		*/
		$decklist_manager = $this->get('decklist_manager');
		$decklist_manager->setLimit(30);
		$decklist_manager->setPage($page);

		$request_attributes = $request->attributes->all();

		$pagetitle = "Decklists";
		$header = '';

		switch ($type) {
			case 'find':
			$pagetitle = "Decklist search results";
			$header = $this->searchForm($request);
			$paginator = $decklist_manager->findDecklistsWithComplexSearch();
			break;
			case 'favorites':
			$response->setPrivate();
			$user = $this->getUser();
			if($user)
			{
				$paginator = $decklist_manager->findDecklistsByFavorite($user);
			}
			else
			{
				$paginator = $decklist_manager->getEmptyList();
			}
			$pagetitle = "Favorite Decklists";
			break;
			case 'mine':
			$response->setPrivate();
			$user = $this->getUser();
			if($user)
			{
				$paginator = $decklist_manager->findDecklistsByAuthor($user);
			}
			else
			{
				$paginator = $decklist_manager->getEmptyList();
			}
			$pagetitle = "My Decklists";
			break;
			case 'recent':
			$paginator = $decklist_manager->findDecklistsByAge(false);
			$pagetitle = "Recent Decklists";
			break;
			case 'halloffame':
			$paginator = $decklist_manager->findDecklistsInHallOfFame();
			$pagetitle = "Hall of Fame";
			break;
			case 'hottopics':
			$paginator = $decklist_manager->findDecklistsInHotTopic();
			$pagetitle = "Hot Topics";
			break;
			case 'tournament':
			$paginator = $decklist_manager->findDecklistsInTournaments();
			$pagetitle = "Tournaments";
			break;
			case 'solo':
			$paginator = $decklist_manager->findDecklistsInSolo();
			$pagetitle = "Solo";
			break;
			case 'multiplayer':
			$paginator = $decklist_manager->findDecklistsInMultiplayer();
			$pagetitle = "Multiplayer";
			break;
			case 'beginner':
			$paginator = $decklist_manager->findDecklistsInBeginner();
			$pagetitle = "Beginner";
			break;
			case 'theme':
			$paginator = $decklist_manager->findDecklistsInTheme();
			$pagetitle = "Theme";
			break;
			case 'popular':
			default:
			$paginator = $decklist_manager->findDecklistsByPopularity();
			$pagetitle = "Popular Decklists";
			break;
		}

		return $this->render('AppBundle:Decklist:decklists.html.twig',
		array(
		'pagetitle' => $pagetitle,
		'pagedescription' => "Browse the collection of thousands of premade decks.",
		'decklists' => $paginator,
		'url' => $request->getRequestUri(),
		'header' => $header,
		'type' => $type,
		'pages' => $decklist_manager->getClosePages(),
		'prevurl' => $decklist_manager->getPreviousUrl(),
		'nexturl' => $decklist_manager->getNextUrl(),
		), $response);

	}

	/*
	* displays the content of a decklist along with comments, siblings, similar, etc.
	*/
	public function viewAction ($decklist_id, $decklist_name, Request $request)
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));

		$decklist = $this->getDoctrine()->getManager()->getRepository('AppBundle:Decklist')->find($decklist_id);
		if(!$decklist) {
			throw $this->createNotFoundException("Decklist not found.");
		}

		$duplicate = $this->getDoctrine()->getManager()->getRepository('AppBundle:Decklist')->findOneBy(['signature' => $decklist->getSignature()], ['dateCreation' => 'ASC']);
		if($duplicate->getDateCreation() >= $decklist->getDateCreation() || $duplicate->getId() === $decklist->getId()) {
			$duplicate = null;
		}

		$commenters = array_map(function ($comment) {
			return $comment->getUser()->getUsername();
		}, $decklist->getComments()->getValues());

		$octgnable = false;
		if ($decklist->isOctgnable()){
			$octgnable = true;
		}

		//$versions = $this->getDoctrine()->getManager()->getRepository('AppBundle:Decklist')->findBy([ 'parent' => $decklist->getParent() ], [ 'version' => 'DESC' ]);
		$versions = [];
		return $this->render('AppBundle:Decklist:decklist.html.twig',
		array(
		'pagetitle' => $decklist->getName(),
		'decklist' => $decklist,
		'duplicate' => $duplicate,
		'commenters' => $commenters,
		'versions' => $versions,
		'octgnable' => $octgnable
		), $response);

	}

	/*
	* adds a decklist to a user's list of favorites
	*/
	public function favoriteAction (Request $request)
	{
		/* @var $em \Doctrine\ORM\EntityManager */
		$em = $this->getDoctrine()->getManager();

		$user = $this->getUser();
		if(!$user) {
			throw new UnauthorizedHttpException('You must be logged in to comment.');
		}

		$decklist_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);

		/* @var $decklist \AppBundle\Entity\Decklist */
		$decklist = $em->getRepository('AppBundle:Decklist')->find($decklist_id);
		if (! $decklist)
		throw new NotFoundHttpException('Wrong id');

		$author = $decklist->getUser();

		$dbh = $this->getDoctrine()->getConnection();
		$is_favorite = $dbh->executeQuery("SELECT
		count(*)
		from decklist d
		join favorite f on f.decklist_id=d.id
		where f.user_id=?
		and d.id=?", array(
		$user->getId(),
		$decklist_id
		))
		->fetch(\PDO::FETCH_NUM)[0];

		if ($is_favorite) {
			$decklist->setNbfavorites($decklist->getNbFavorites() - 1);
			$user->removeFavorite($decklist);
			if ($author->getId() != $user->getId())
			$author->setReputation($author->getReputation() - 5);
		} else {
			$decklist->setNbfavorites($decklist->getNbFavorites() + 1);
			$user->addFavorite($decklist);
			$decklist->setDateUpdate(new \DateTime());
			if ($author->getId() != $user->getId())
			$author->setReputation($author->getReputation() + 5);
		}
		$this->getDoctrine()->getManager()->flush();

		return new Response($decklist->getNbFavorites());

	}

	/*
	* records a user's comment
	*/
	public function commentAction (Request $request)
	{
		/* @var $user User */
		$user = $this->getUser();
		if(!$user) {
			throw new UnauthorizedHttpException('You must be logged in to comment.');
		}

		$decklist_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
		$decklist = $this->getDoctrine()
		->getRepository('AppBundle:Decklist')
		->find($decklist_id);

		$comment_text = trim($request->get('comment'));
		if ($decklist && ! empty($comment_text)) {
			$comment_text = preg_replace(
			'%(?<!\()\b(?:(?:https?|ftp)://)(?:((?:(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)(?:\.(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)*(?:\.[a-z\x{00a1}-\x{ffff}]{2,6}))(?::\d+)?)(?:[^\s]*)?%iu',
			'[$1]($0)', $comment_text);

			$mentionned_usernames = [];
			$matches = [];
			if(preg_match_all('/`@([\w_]+)`/', $comment_text, $matches, PREG_PATTERN_ORDER)) {
				$mentionned_usernames = array_unique($matches[1]);
			}

			$comment_html = $this->get('texts')->markdown($comment_text);

			$now = new DateTime();

			$comment = new Comment();
			$comment->setText($comment_html);
			$comment->setDateCreation($now);
			$comment->setUser($user);
			$comment->setDecklist($decklist);
			$comment->setIsHidden(FALSE);

			$this->getDoctrine()
			->getManager()
			->persist($comment);
			$decklist->setDateUpdate($now);
			$decklist->setNbcomments($decklist->getNbcomments() + 1);

			$this->getDoctrine()
			->getManager()
			->flush();

			// send emails
			$spool = [];
			if($decklist->getUser()->getIsNotifAuthor()) {
				if(!isset($spool[$decklist->getUser()->getEmail()])) {
					$spool[$decklist->getUser()->getEmail()] = 'AppBundle:Emails:newcomment_author.html.twig';
				}
			}
			foreach($decklist->getComments() as $comment) {
				/* @var $comment Comment */
				$commenter = $comment->getUser();
				if($commenter && $commenter->getIsNotifCommenter()) {
					if(!isset($spool[$commenter->getEmail()])) {
						$spool[$commenter->getEmail()] = 'AppBundle:Emails:newcomment_commenter.html.twig';
					}
				}
			}
			foreach($mentionned_usernames as $mentionned_username) {
				/* @var $mentionned_user User */
				$mentionned_user = $this->getDoctrine()->getRepository('AppBundle:User')->findOneBy(array('username' => $mentionned_username));
				if($mentionned_user && $mentionned_user->getIsNotifMention()) {
					if(!isset($spool[$mentionned_user->getEmail()])) {
						$spool[$mentionned_user->getEmail()] = 'AppBundle:Emails:newcomment_mentionned.html.twig';
					}
				}
			}
			unset($spool[$user->getEmail()]);

			$email_data = array(
			'username' => $user->getUsername(),
			'decklist_name' => $decklist->getName(),
			'url' => $this->generateUrl('decklist_detail', array('decklist_id' => $decklist->getId(), 'decklist_name' => $decklist->getNameCanonical()), UrlGeneratorInterface::ABSOLUTE_URL) . '#' . $comment->getId(),
			'comment' => $comment_html,
			'profile' => $this->generateUrl('user_profile_edit', [], UrlGeneratorInterface::ABSOLUTE_URL)
			);
			foreach($spool as $email => $view) {
				$message = \Swift_Message::newInstance()
				->setSubject("[arkhamdb] New comment")
				->setFrom(array("noreply@arkhamdb.com" => "ArkhamDB"))
				->setTo($email)
				->setBody($this->renderView($view, $email_data), 'text/html');
				$this->get('mailer')->send($message);
			}

		}

		return $this->redirect($this->generateUrl('decklist_detail', array(
		'decklist_id' => $decklist_id,
		'decklist_name' => $decklist->getNameCanonical()
		)));

	}

	/*
	* hides a comment, or if $hidden is false, unhide a comment
	*/
	public function hidecommentAction($comment_id, $hidden, Request $request)
	{
		/* @var $user User */
		$user = $this->getUser();
		if(!$user) {
			throw new UnauthorizedHttpException('You must be logged in to comment.');
		}

		/* @var $em \Doctrine\ORM\EntityManager */
		$em = $this->getDoctrine()->getManager();

		$comment = $em->getRepository('AppBundle:Comment')->find($comment_id);
		if(!$comment) {
			throw new BadRequestHttpException('Unable to find comment');
		}

		if($comment->getDecklist()->getUser()->getId() !== $user->getId()) {
			return new Response(json_encode("You don't have permission to edit this comment."));
		}

		$comment->setIsHidden((boolean) $hidden);
		$em->flush();

		return new Response(json_encode(TRUE));
	}

	/*
	* records a user's vote
	*/
	public function voteAction (Request $request)
	{
		/* @var $em \Doctrine\ORM\EntityManager */
		$em = $this->getDoctrine()->getManager();

		$user = $this->getUser();
		if(!$user) {
			throw new UnauthorizedHttpException('You must be logged in to comment.');
		}

		$decklist_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);

		$decklist = $em->getRepository('AppBundle:Decklist')->find($decklist_id);

		if($decklist->getUser()->getId() != $user->getId())
		{
			$query = $em->getRepository('AppBundle:Decklist')
			->createQueryBuilder('d')
			->innerJoin('d.votes', 'u')
			->where('d.id = :decklist_id')
			->andWhere('u.id = :user_id')
			->setParameter('decklist_id', $decklist_id)
			->setParameter('user_id', $user->getId())
			->getQuery();

			$result = $query->getResult();
			if (empty($result)) {
				$user->addVote($decklist);
				$author = $decklist->getUser();
				$author->setReputation($author->getReputation() + 1);
				$decklist->setDateUpdate(new \DateTime());
				$decklist->setNbVotes($decklist->getNbVotes() + 1);
				$this->getDoctrine()->getManager()->flush();
			}
		}
		return new Response($decklist->getNbVotes());

	}

	/*
	* (unused) returns an ordered list of decklists similar to the one given
	*/
	public function findSimilarDecklists ($decklist_id, $number)
	{

		$dbh = $this->getDoctrine()->getConnection();

		$list = $dbh->executeQuery(
		"SELECT
		l.id,
		(
		SELECT COUNT(s.id)
		FROM decklistslot s
		WHERE (
		s.decklist_id=l.id
		AND s.card_id NOT IN (
		SELECT t.card_id
		FROM decklistslot t
		WHERE t.decklist_id=?
		)
		)
		OR
		(
		s.decklist_id=?
		AND s.card_id NOT IN (
		SELECT t.card_id
		FROM decklistslot t
		WHERE t.decklist_id=l.id
		)
		)
		) difference
		FROM decklist l
		WHERE l.id!=?
		ORDER BY difference ASC
		LIMIT 0,$number", array(
		$decklist_id,
		$decklist_id,
		$decklist_id
		))->fetchAll();

		$arr = [];
		foreach ($list as $item) {

			$dbh = $this->getDoctrine()->getConnection();
			$rows = $dbh->executeQuery("SELECT
			d.id,
			d.name,
			d.name_canonical,
			d.nb_votes,
			d.nb_favorites,
			d.nb_comments
			from decklist d
			where d.id=?
			", array(
			$item["id"]
			))->fetchAll();

			$decklist = $rows[0];
			$arr[] = $decklist;
		}
		return $arr;

	}

	/*
	* returns a text file with the content of a decklist
	*/
	public function textexportAction ($decklist_id, Request $request)
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));

		/* @var $em \Doctrine\ORM\EntityManager */
		$em = $this->getDoctrine()->getManager();

		/* @var $decklist \AppBundle\Entity\Decklist */
		$decklist = $em->getRepository('AppBundle:Decklist')->find($decklist_id);
		if (! $decklist)
		throw new NotFoundHttpException("Unable to find decklist.");

		$content = $this->renderView('AppBundle:Export:plain.txt.twig', [
		"deck" => $decklist->getTextExport()
		]);
		$content = str_replace("\n", "\r\n", $content);

		$response = new Response();

		$response->headers->set('Content-Type', 'text/plain');
		$response->headers->set('Content-Disposition', $response->headers->makeDisposition(
		ResponseHeaderBag::DISPOSITION_ATTACHMENT,
		$decklist->getNameCanonical() . '.txt'
		));

		$response->setContent($content);
		return $response;
	}

	/*
	* returns a octgn file with the content of a decklist
	*/
	public function octgnexportAction ($decklist_id, Request $request)
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));

		/* @var $em \Doctrine\ORM\EntityManager */
		$em = $this->getDoctrine()->getManager();

		/* @var $decklist \AppBundle\Entity\Decklist */
		$decklist = $em->getRepository('AppBundle:Decklist')->find($decklist_id);
		if (! $decklist)
		throw new NotFoundHttpException("Unable to find decklist.");

		$content = $this->renderView('AppBundle:Export:octgn.xml.twig', [
		"deck" => $decklist->getOctgnExport()
		]);

		$response = new Response();

		$response->headers->set('Content-Type', 'application/octgn');
		$response->headers->set('Content-Disposition', $response->headers->makeDisposition(
		ResponseHeaderBag::DISPOSITION_ATTACHMENT,
		$decklist->getNameCanonical() . '.o8d'
		));

		$response->setContent($content);
		return $response;
	}

	public function usercommentsAction ($page, Request $request)
	{
		$response = new Response();
		$response->setPrivate();

		/* @var $user \AppBundle\Entity\User */
		$user = $this->getUser();

		$limit = 100;
		if ($page < 1)
		$page = 1;
		$start = ($page - 1) * $limit;

		/* @var $dbh \Doctrine\DBAL\Driver\PDOConnection */
		$dbh = $this->getDoctrine()->getConnection();

		$comments = $dbh->executeQuery(
		"SELECT SQL_CALC_FOUND_ROWS
		c.id,
		c.text,
		c.date_creation,
		d.id decklist_id,
		d.name decklist_name,
		d.name_canonical decklist_name_canonical
		from comment c
		join decklist d on c.decklist_id=d.id
		where c.user_id=?
		order by date_creation desc
		limit $start, $limit", array(
		$user->getId()
		))
		->fetchAll(\PDO::FETCH_ASSOC);

		$maxcount = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(\PDO::FETCH_NUM)[0];

		// pagination : calcul de nbpages // currpage // prevpage // nextpage
		// à partir de $start, $limit, $count, $maxcount, $page

		$currpage = $page;
		$prevpage = max(1, $currpage - 1);
		$nbpages = min(10, ceil($maxcount / $limit));
		$nextpage = min($nbpages, $currpage + 1);

		$route = $request->get('_route');

		$pages = [];
		for ($page = 1; $page <= $nbpages; $page ++) {
			$pages[] = array(
			"numero" => $page,
			"url" => $this->generateUrl($route, array(
			"page" => $page
			)),
			"current" => $page == $currpage
			);
		}

		return $this->render('AppBundle:Default:usercomments.html.twig',
		array(
		'user' => $user,
		'comments' => $comments,
		'url' => $request
		->getRequestUri(),
		'route' => $route,
		'pages' => $pages,
		'prevurl' => $currpage == 1 ? null : $this->generateUrl($route, array(
		"page" => $prevpage
		)),
		'nexturl' => $currpage == $nbpages ? null : $this->generateUrl($route, array(
		"page" => $nextpage
		))
		), $response);

	}

	public function commentsAction ($page, Request $request)
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));

		$limit = 100;
		if ($page < 1)
		$page = 1;
		$start = ($page - 1) * $limit;

		/* @var $dbh \Doctrine\DBAL\Driver\PDOConnection */
		$dbh = $this->getDoctrine()->getConnection();

		$comments = $dbh->executeQuery(
		"SELECT SQL_CALC_FOUND_ROWS
		c.id,
		c.text,
		c.date_creation,
		d.id decklist_id,
		d.name decklist_name,
		d.name_canonical decklist_name_canonical,
		u.id user_id,
		u.username author
		from comment c
		join decklist d on c.decklist_id=d.id
		join user u on c.user_id=u.id
		order by date_creation desc
		limit $start, $limit", [])->fetchAll(\PDO::FETCH_ASSOC);

		$maxcount = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(\PDO::FETCH_NUM)[0];

		// pagination : calcul de nbpages // currpage // prevpage // nextpage
		// à partir de $start, $limit, $count, $maxcount, $page

		$currpage = $page;
		$prevpage = max(1, $currpage - 1);
		$nbpages = min(10, ceil($maxcount / $limit));
		$nextpage = min($nbpages, $currpage + 1);

		$route = $request->get('_route');

		$pages = [];
		for ($page = 1; $page <= $nbpages; $page ++) {
			$pages[] = array(
			"numero" => $page,
			"url" => $this->generateUrl($route, array(
			"page" => $page
			)),
			"current" => $page == $currpage
			);
		}

		return $this->render('AppBundle:Default:allcomments.html.twig',
		array(
		'comments' => $comments,
		'url' => $request
		->getRequestUri(),
		'route' => $route,
		'pages' => $pages,
		'prevurl' => $currpage == 1 ? null : $this->generateUrl($route, array(
		"page" => $prevpage
		)),
		'nexturl' => $currpage == $nbpages ? null : $this->generateUrl($route, array(
		"page" => $nextpage
		))
		), $response);

	}

	public function searchAction (Request $request)
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));

		$dbh = $this->getDoctrine()->getConnection();
		$factions = $dbh->executeQuery(
		"SELECT
		f.name,
		f.code
		from faction f
		order by f.name asc")
		->fetchAll();


		$categories = []; $on = 0; $off = 0;
		$list_cycles = $this->getDoctrine()->getRepository('AppBundle:Cycle')->findBy([], array("position" => "ASC"));
		foreach($list_cycles as $cycle) {
			$size = count($cycle->getPacks());
			if($cycle->getPosition() == 0 || $size == 0) continue;

			$category = array("label" => $cycle->getName(), "packs" => []);
			foreach($cycle->getPacks() as $pack) {
				$checked = $pack->getDateRelease() !== NULL;
				if($checked) {
					$on++;
				} else {
					$off++;
				}
				$category['packs'][] = array("id" => $pack->getId(), "label" => $pack->getName(), "checked" => $checked, "future" => $pack->getDateRelease() === NULL);
				if ($pack->getCode() == "core"){
					if($checked) {
						$on++;
					} else {
						$off++;
					}
					$category['packs'][] = array("id" => "1-2", "label" => "Second Core Set", "checked" => $checked, "future" => $pack->getDateRelease() === NULL);
				}
			}
			$categories[] = $category;

		}

		$searchForm = $this->renderView('AppBundle:Search:form.html.twig',
		array(
		'factions' => $factions,
		'allowed' => $categories,
		'on' => $on,
		'off' => $off,
		'author' => '',
		'name' => '',
		)
		);

		return $this->render('AppBundle:Decklist:decklists.html.twig',
		array(
		'pagetitle' => 'Decklist Search',
		'decklists' => null,
		'url' => $request->getRequestUri(),
		'header' => $searchForm,
		'type' => 'find',
		'pages' => null,
		'prevurl' => null,
		'nexturl' => null,
		), $response);

	}

	public function donatorsAction (Request $request)
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));

		/* @var $dbh \Doctrine\DBAL\Driver\PDOConnection */
		$dbh = $this->getDoctrine()->getConnection();

		$users = $dbh->executeQuery("SELECT * FROM user WHERE donation>0 ORDER BY donation DESC, username", [])->fetchAll(\PDO::FETCH_ASSOC);

		return $this->render('AppBundle:Default:donators.html.twig',
		array(
		'pagetitle' => 'The Gracious Donators',
		'donators' => $users
		), $response);
	}

}
