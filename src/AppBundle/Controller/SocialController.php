<?php
namespace AppBundle\Controller;

use \DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use AppBundle\Entity\Deck;
use AppBundle\Entity\Deckslot;
use AppBundle\Entity\Decklist;
use AppBundle\Entity\Decklistslot;
use AppBundle\Entity\Comment;
use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use AppBundle\Model\DecklistManager;
use AppBundle\Services\Pagination\Pagination;

class SocialController extends Controller
{
    /*
	 * checks to see if a deck can be published in its current saved state
	 */
    public function publishAction ($deck_id, Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();

        $deck = $em->getRepository('AppBundle:Deck')->find($deck_id);
        if(!$deck)
        	throw new NotFoundHttpException("Deck not found: ".$deck_id);

        if ($this->getUser()->getId() != $deck->getUser()->getId()) {
            throw new UnauthorizedHttpException("You don't have access to this deck.");
        }

        $problem = $this->get('deck_validation_helper')->findProblem($deck);
        if ($problem) {
            return new Response($this->get('deck_validation_helper')->getProblemLabel($problem));
        }

        $new_content = json_encode($deck->getSlots()->getContent());
        $new_signature = md5($new_content);
        $old_decklists = $this->getDoctrine()
            ->getRepository('AppBundle:Decklist')
            ->findBy(array(
                'signature' => $new_signature
        ));

        /* @var $decklist \AppBundle\Entity\Decklist */
        foreach ($old_decklists as $decklist) {
            if (json_encode($decklist->getSlots()->getContent()) == $new_content) {
                $url = $this->generateUrl('decklist_detail', array(
                        'decklist_id' => $decklist->getId(),
                        'decklist_name' => $decklist->getNameCanonical()
                ));
                return new Response(json_encode($url));
            }
        }

        return new Response(json_encode($deck->getArrayExport(false)));

    }

    /*
	 * creates a new decklist from a deck (publish action)
	 */
    public function newAction (Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();

        $deck_id = filter_var($request->request->get('deck_id'), FILTER_SANITIZE_NUMBER_INT);

        /* @var $deck \AppBundle\Entity\Deck */
        $deck = $this->getDoctrine()->getRepository('AppBundle:Deck')->find($deck_id);
        if ($this->getUser()->getId() != $deck->getUser()->getId()) {
            throw new UnauthorizedHttpException("You don't have access to this deck.");
        }

        $problem = $this->get('deck_validation_helper')->findProblem($deck);
        if($problem) {
            return $this->render('AppBundle:Default:error.html.twig', [
				'pagetitle' => "Error",
				'error' => 'You cannot publish this deck because it is invalid: "'.$this->get('deck_validation_helper')->getProblemLabel($problem).'".'
			]);
        }

        $new_content = json_encode($deck->getSlots()->getContent());
        $new_signature = md5($new_content);
        $old_decklists = $this->getDoctrine()->getRepository('AppBundle:Decklist')->findBy(['signature' => $new_signature]);
        foreach ($old_decklists as $decklist) {
            if (json_encode($decklist->getSlots()->getContent()) == $new_content) {
                throw new AccessDeniedHttpException('That decklist already exists.');
            }
        }

        $name = filter_var($request->request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $name = substr($name, 0, 60);
        if (empty($name))
            $name = "Untitled";
        $rawdescription = trim($request->request->get('description'));
        $description = $this->get('texts')->markdown($rawdescription);

        $tournament_id = filter_var($request->request->get('tournament'), FILTER_SANITIZE_NUMBER_INT);
        $tournament = $em->getRepository('AppBundle:Tournament')->find($tournament_id);

        $decklist = new Decklist();
        $decklist->setName($name);
        $decklist->setNameCanonical(preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($name)));
        $decklist->setDescriptionMd($rawdescription);
        $decklist->setDescriptionHtml($description);
        $decklist->setUser($this->getUser());
        $decklist->setDateCreation(new \DateTime());
        $decklist->setDateUpdate(new \DateTime());
        $decklist->setSignature($new_signature);
        $decklist->setFaction($deck->getFaction());
        $decklist->setLastPack($deck->getLastPack());
        $decklist->setnbVotes(0);
        $decklist->setNbfavorites(0);
        $decklist->setNbcomments(0);
        $decklist->setTournament($tournament);
        foreach ($deck->getSlots() as $slot) {
            $decklistslot = new Decklistslot();
            $decklistslot->setQuantity($slot->getQuantity());
            $decklistslot->setCard($slot->getCard());
            $decklistslot->setDecklist($decklist);
            $decklist->getSlots()->add($decklistslot);
        }
        if (count($deck->getChildren())) {
            $decklist->setPrecedent($deck->getChildren()[0]);
        } else {
            if ($deck->getParent()) {
                $decklist->setPrecedent($deck->getParent());
            }
        }
        $decklist->setParent($deck);

        $em->persist($decklist);
        $em->flush();

        return $this->redirect($this->generateUrl('decklist_detail', array(
                'decklist_id' => $decklist->getId(),
                'decklist_name' => $decklist->getNameCanonical()
        )));

    }

    private function searchForm(Request $request)
    {
        $dbh = $this->get('doctrine')->getConnection();

        $cards_code = $request->query->get('cards');
        $faction_code = filter_var($request->query->get('faction'), FILTER_SANITIZE_STRING);
        $author_name = filter_var($request->query->get('author'), FILTER_SANITIZE_STRING);
        $decklist_name = filter_var($request->query->get('name'), FILTER_SANITIZE_STRING);
        $sort = $request->query->get('sort');
        $packs = $request->query->get('packs');

        if(!is_array($packs)) {
            $packs = $dbh->executeQuery("select id from pack")->fetchAll(\PDO::FETCH_COLUMN);
        }

        $categories = [];
        $on = 0; $off = 0;
        $categories[] = array("label" => "Core / Deluxe", "packs" => []);
        $list_cycles = $this->get('doctrine')->getRepository('AppBundle:Cycle')->findBy([], array("position" => "ASC"));
        foreach($list_cycles as $cycle) {
            $size = count($cycle->getPacks());
            if($cycle->getPosition() == 0 || $size == 0) continue;
            $first_pack = $cycle->getPacks()[0];
            if($size === 1 && $first_pack->getName() == $cycle->getName()) {
                $checked = count($packs) ? in_array($first_pack->getId(), $packs) : true;
                if($checked) $on++;
                else $off++;
                $categories[0]["packs"][] = array("id" => $first_pack->getId(), "label" => $first_pack->getName(), "checked" => $checked, "future" => $first_pack->getDateRelease() === NULL);
            } else {
                $category = array("label" => $cycle->getName(), "packs" => []);
                foreach($cycle->getPacks() as $pack) {
                    $checked = count($packs) ? in_array($pack->getId(), $packs) : true;
                    if($checked) $on++;
                    else $off++;
                    $category['packs'][] = array("id" => $pack->getId(), "label" => $pack->getName(), "checked" => $checked, "future" => $pack->getDateRelease() === NULL);
                }
                $categories[] = $category;
            }
        }

        $params = array(
                'allowed' => $categories,
                'on' => $on,
                'off' => $off,
                'author' => $author_name,
                'name' => $decklist_name
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
                        'url' => $this->getRequest()->getRequestUri(),
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
        
        $tournaments = $this->getDoctrine()->getManager()->getRepository('AppBundle:Tournament')->findAll();
        
        $commenters = array_map(function ($comment) {
        	return $comment->getUser()->getUsername();
        }, $decklist->getComments()->getValues());
        
        return $this->render('AppBundle:Decklist:decklist.html.twig',
                array(
                        'pagetitle' => $decklist->getName(),
                        'decklist' => $decklist,
                		'arrayexport' => $decklist->getArrayExport(false),
                		'commenters' => $commenters,
                		'tournaments' => $tournaments,
                ), $response);

    }

    /*
	 * adds a decklist to a user's list of favorites
	 */
    public function favoriteAction (Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();

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

        $dbh = $this->get('doctrine')->getConnection();
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
        $this->get('doctrine')->getManager()->flush();

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

            $this->get('doctrine')
                ->getManager()
                ->persist($comment);
            $decklist->setDateUpdate($now);
            $decklist->setNbcomments($decklist->getNbcomments() + 1);

            $this->get('doctrine')
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
                'url' => $this->generateUrl('decklist_detail', array('decklist_id' => $decklist->getId(), 'decklist_name' => $decklist->getNameCanonical()), TRUE) . '#' . $comment->getId(),
                'comment' => $comment_html,
                'profile' => $this->generateUrl('user_profile_edit', [], TRUE)
            );
            foreach($spool as $email => $view) {
                $message = \Swift_Message::newInstance()
                ->setSubject("[thronesdb] New comment")
                ->setFrom(array("alsciende@thronesdb.com" => $user->getUsername()))
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
        $em = $this->get('doctrine')->getManager();

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
        $em = $this->get('doctrine')->getManager();

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
                $this->get('doctrine')->getManager()->flush();
            }
        }
        return new Response($decklist->getNbVotes());

    }

    /*
	 * (unused) returns an ordered list of decklists similar to the one given
	 */
    public function findSimilarDecklists ($decklist_id, $number)
    {

        $dbh = $this->get('doctrine')->getConnection();

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

            $dbh = $this->get('doctrine')->getConnection();
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
        $em = $this->get('doctrine')->getManager();

        /* @var $decklist \AppBundle\Entity\Decklist */
        $decklist = $em->getRepository('AppBundle:Decklist')->find($decklist_id);
        if (! $decklist)
            throw new NotFoundHttpException("Unable to find decklist.");

        $content = $this->renderView('AppBundle:Export:plain.txt.twig', [
        	"deck" => $decklist->getTextExport()
      	]);

        $response = new Response();

        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
        		ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        		str_replace(['/','\\'], ['-','_'], $decklist->getName() . '.txt')
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
        $em = $this->get('doctrine')->getManager();

        /* @var $decklist \AppBundle\Entity\Decklist */
        $decklist = $em->getRepository('AppBundle:Decklist')->find($decklist_id);
        if (! $decklist)
            throw new NotFoundHttpException("Unable to find decklist.");

        $content = $this->renderView('AppBundle:Export:octgn.xml.twig', [
        	"deck" => $decklist->getTextExport()
      	]);

        $response = new Response();

        $response->headers->set('Content-Type', 'application/octgn');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
        		ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        		str_replace(['/','\\'], ['-','_'], $decklist->getName() . '.o8d')
        ));

        $response->setContent($content);
        return $response;
    }

    /*
	 * edits name and description of a decklist by its publisher
	 */
    public function editAction ($decklist_id, Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();

        $user = $this->getUser();
        if (! $user)
            throw new UnauthorizedHttpException("You must be logged in for this operation.");

        $decklist = $em->getRepository('AppBundle:Decklist')->find($decklist_id);
        if (! $decklist || $decklist->getUser()->getId() != $user->getId())
            throw new UnauthorizedHttpException("You don't have access to this decklist.");

        $name = trim(filter_var($request->request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $name = substr($name, 0, 60);
        if (empty($name))
            $name = "Untitled";
        $rawdescription = trim($request->request->get('description'));
        $description = $this->get('texts')->markdown($rawdescription);

        $tournament_id = filter_var($request->request->get('tournament'), FILTER_SANITIZE_NUMBER_INT);
        $tournament = $em->getRepository('AppBundle:Tournament')->find($tournament_id);

        $derived_from = $request->request->get('derived');
        $matches = [];
        if(preg_match('/^(\d+)$/', $derived_from, $matches)) {

        } else if(preg_match('/decklist\/(\d+)\//', $derived_from, $matches)) {
            $derived_from = $matches[1];
        } else {
            $derived_from = null;
        }

        if(!$derived_from) {
            $precedent_decklist = null;
        }
        else {
            /* @var $precedent_decklist Decklist */
            $precedent_decklist = $em->getRepository('AppBundle:Decklist')->find($derived_from);
            if(!$precedent_decklist || $precedent_decklist->getDateCreation() > $decklist->getDateCreation()) {
                $precedent_decklist = $decklist->getPrecedent();
            }
        }

        $decklist->setName($name);
        $decklist->setNameCanonical(preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($name)));
        $decklist->setDescriptionMd($rawdescription);
        $decklist->setDescriptionHtml($description);
        $decklist->setPrecedent($precedent_decklist);
        $decklist->setTournament($tournament);
        $decklist->setDateUpdate(new \DateTime());
        $em->flush();

        return $this->redirect($this->generateUrl('decklist_detail', array(
                'decklist_id' => $decklist_id,
                'decklist_name' => $decklist->getNameCanonical()
        )));

    }

    /*
	 * deletes a decklist if it has no comment, no vote, no favorite
	*/
    public function deleteAction ($decklist_id, Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();

        $user = $this->getUser();
        if (! $user)
            throw new UnauthorizedHttpException("You must be logged in for this operation.");

        $decklist = $em->getRepository('AppBundle:Decklist')->find($decklist_id);
        if (! $decklist || $decklist->getUser()->getId() != $user->getId())
            throw new UnauthorizedHttpException("You don't have access to this decklist.");

        if ($decklist->getnbVotes() || $decklist->getNbfavorites() || $decklist->getNbcomments())
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
        $dbh = $this->get('doctrine')->getConnection();

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

        $route = $this->getRequest()->get('_route');

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
                        'url' => $this->getRequest()
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
        $dbh = $this->get('doctrine')->getConnection();

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

        $route = $this->getRequest()->get('_route');

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
                        'url' => $this->getRequest()
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

        $dbh = $this->get('doctrine')->getConnection();
        $factions = $dbh->executeQuery(
                "SELECT
				f.name,
				f.code
				from faction f
				order by f.name asc")
            ->fetchAll();

        $categories = []; $on = 0; $off = 0;
        $categories[] = array("label" => "Core / Deluxe", "packs" => []);
        $list_cycles = $this->get('doctrine')->getRepository('AppBundle:Cycle')->findBy([], array("position" => "ASC"));
        foreach($list_cycles as $cycle) {
            $size = count($cycle->getPacks());
            if($cycle->getPosition() == 0 || $size == 0) continue;
            $first_pack = $cycle->getPacks()[0];
            if($size === 1 && $first_pack->getName() == $cycle->getName()) {
                $checked = $first_pack->getDateRelease() !== NULL;
                if($checked) $on++;
                else $off++;
                $categories[0]["packs"][] = array("id" => $first_pack->getId(), "label" => $first_pack->getName(), "checked" => $checked, "future" => $first_pack->getDateRelease() === NULL);
            } else {
                $category = array("label" => $cycle->getName(), "packs" => []);
                foreach($cycle->getPacks() as $pack) {
                    $checked = $pack->getDateRelease() !== NULL;
                    if($checked) $on++;
                    else $off++;
                    $category['packs'][] = array("id" => $pack->getId(), "label" => $pack->getName(), "checked" => $checked, "future" => $pack->getDateRelease() === NULL);
                }
                $categories[] = $category;
            }
        }

        return $this->render('AppBundle:Search:search.html.twig',
                array(
                        'pagetitle' => 'Decklist Search',
                        'url' => $this->getRequest()->getRequestUri(),
                        'form' => $this->renderView('AppBundle:Search:form.html.twig',
                            array(
                                'factions' => $factions,
                                'allowed' => $categories,
                                'on' => $on,
                                'off' => $off,
                                'author' => '',
                                'name' => '',
                            )
                        ),
                ), $response);

    }

    public function donatorsAction (Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));

        /* @var $dbh \Doctrine\DBAL\Driver\PDOConnection */
        $dbh = $this->get('doctrine')->getConnection();

        $users = $dbh->executeQuery("SELECT * FROM user WHERE donation>0 ORDER BY donation DESC, username", [])->fetchAll(\PDO::FETCH_ASSOC);

        return $this->render('AppBundle:Default:donators.html.twig',
                array(
                        'pagetitle' => 'The Gracious Donators',
                        'donators' => $users
                ), $response);
    }

}
