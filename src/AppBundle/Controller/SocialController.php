<?php
namespace AppBundle\Controller;
use \DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
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

        if ($this->getUser()->getId() != $deck->getUser()->getId())
            throw new UnauthorizedHttpException("You don't have access to this deck.");

        /*
        $judge = $this->get('judge');
        $analyse = $judge->analyse($deck->getCards());

        if (is_string($analyse))
            throw new AccessDeniedHttpException($judge->problem($analyse));
        */

        $decklists = $this->get('decklists');

        $new_content = json_encode($this->get('decks')->getDeckContent($deck));
        $new_signature = md5($new_content);
        $old_decklists = $this->getDoctrine()
            ->getRepository('AppBundle:Decklist')
            ->findBy(array(
                'signature' => $new_signature
        ));

        /* @var $decklist \AppBundle\Entity\Decklist */
        foreach ($old_decklists as $decklist) {
            if (json_encode($decklists->getContent($decklist)) == $new_content) {
                $url = $this->generateUrl('decklist_detail', array(
                        'decklist_id' => $decklist->getId(),
                        'decklist_name' => $decklist->getNameCanonical()
                ));
                return new Response(json_encode($url));
            }
        }

        return new Response(json_encode($this->get('decks')->getDeckInfo($deck_id)));

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

/*
        $judge = $this->get('judge');
        $analyse = $judge->analyse($deck->getCards());
        if (is_string($analyse)) {
            throw new AccessDeniedHttpException($judge->problem($analyse));
        }
*/

        $new_content = json_encode($this->get('decks')->getDeckContent($deck));
        $new_signature = md5($new_content);
        $old_decklists = $this->getDoctrine()->getRepository('AppBundle:Decklist')->findBy(['signature' => $new_signature]);
        foreach ($old_decklists as $decklist) {
            if (json_encode($this->get('decklists')->getContent($decklist)) == $new_content) {
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
        $params['faction_'.substr($faction_code, 0, 1)] = ' selected="selected"';

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
    public function listAction ($type, $code = null, $page = 1, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));

        $limit = 30;
        if ($page < 1)
            $page = 1;
        $start = ($page - 1) * $limit;

        $pagetitle = "Decklists";
        $header = '';

        switch ($type) {
            case 'find':
                $result = $this->get('decklists')->find($start, $limit, $request);
                $pagetitle = "Decklist search results";
                $header = $this->searchForm($request);
                break;
            case 'favorites':
                $response->setPrivate();
                $user = $this->getUser();
                if (! $user) {
                    $result = array('decklists' => [], 'count' => 0);
                } else {
                    $result = $this->get('decklists')->favorites($user->getId(), $start, $limit);
                }
                $pagetitle = "Favorite Decklists";
                break;
            case 'mine':
                $response->setPrivate();
                $user = $this->getUser();
                if (! $user) {
                    $result = array('decklists' => [], 'count' => 0);
                } else {
                    $result = $this->get('decklists')->by_author($user->getId(), $start, $limit);
                }
                $pagetitle = "My Decklists";
                break;
            case 'recent':
                $result = $this->get('decklists')->recent($start, $limit);
                $pagetitle = "Recent Decklists";
                break;
            case 'halloffame':
                $result = $this->get('decklists')->halloffame($start, $limit);
                $pagetitle = "Hall of Fame";
                break;
            case 'hottopics':
                $result = $this->get('decklists')->hottopics($start, $limit);
                $pagetitle = "Hot Topics";
                break;
            case 'tournament':
                $result = $this->get('decklists')->tournaments($start, $limit);
                $pagetitle = "Tournaments";
                break;
            case 'popular':
            default:
                $result = $this->get('decklists')->popular($start, $limit);
                $pagetitle = "Popular Decklists";
                break;
        }

        $decklists = $result['decklists'];
        $maxcount = $result['count'];

        $dbh = $this->get('doctrine')->getConnection();
        $factions = $dbh->executeQuery(
                "SELECT
				f.name,
				f.code
				from faction f
				order by f.name asc")
            ->fetchAll();

        $packs = $dbh->executeQuery(
                "SELECT
				p.name,
				p.code
				from pack p
				where p.date_release is not null
				order by p.date_release desc
				limit 0,5")
            ->fetchAll();

        // pagination : calcul de nbpages // currpage // prevpage // nextpage
        // à partir de $start, $limit, $count, $maxcount, $page

        $currpage = $page;
        $prevpage = max(1, $currpage - 1);
        $nbpages = min(10, ceil($maxcount / $limit));
        $nextpage = min($nbpages, $currpage + 1);

        $route = $request->get('_route');

        $params = $request->query->all();
        $params['type'] = $type;

        $pages = [];
        for ($page = 1; $page <= $nbpages; $page ++) {
            $pages[] = array(
                    "numero" => $page,
                    "url" => $this->generateUrl($route, $params + array(
                            "page" => $page
                    )),
                    "current" => $page == $currpage
            );
        }

        return $this->render('AppBundle:Decklist:decklists.html.twig',
                array(
                        'pagetitle' => $pagetitle,
                        'pagedescription' => "Browse the collection of thousands of premade decks.",
                        'decklists' => $decklists,
                        'packs' => $packs,
                        'factions' => $factions,
                        'url' => $this->getRequest()->getRequestUri(),
                        'header' => $header,
                        'route' => $route,
                        'pages' => $pages,
                        'type' => $type,
                        'prevurl' => $currpage == 1 ? null : $this->generateUrl($route, $params + array(
                                "page" => $prevpage
                        )),
                        'nexturl' => $currpage == $nbpages ? null : $this->generateUrl($route, $params + array(
                                "page" => $nextpage
                        ))
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

        $dbh = $this->get('doctrine')->getConnection();
        $rows = $dbh->executeQuery(
                "SELECT
				d.id,
				d.date_update,
				d.name,
				d.name_canonical,
				d.date_creation,
				d.description_md,
				d.description_html,
				d.precedent_decklist_id precedent,
                d.tournament_id,
                t.description tournament,
				u.id user_id,
				u.username,
				u.color usercolor,
				u.reputation,
				u.donation,
				f.code faction_code,
				d.nb_votes,
				d.nb_favorites,
				d.nb_comments
				from decklist d
				join user u on d.user_id=u.id
				join faction f on d.faction_id=f.id
                left join tournament t on d.tournament_id=t.id
				where d.id=?
				", array(
                        $decklist_id
                ))->fetchAll();

        if (empty($rows)) {
            throw new NotFoundHttpException('Wrong id');
        }

        $decklist = $rows[0];

        $comments = $dbh->executeQuery(
                "SELECT
				c.id,
				c.date_creation,
				c.user_id,
				u.username author,
				u.color authorcolor,
                u.donation,
				c.text,
                c.is_hidden
				from comment c
				join user u on c.user_id=u.id
				where c.decklist_id=?
				order by date_creation asc", array(
                        $decklist_id
                ))->fetchAll();

		$commenters = array_values(array_unique(array_merge(array($decklist['username']), array_map(function ($item) { return $item['author']; }, $comments))));

        $decklist['comments'] = $comments;

        $precedent_decklists = $dbh->executeQuery(
                "SELECT
					d.id,
					d.name,
					d.name_canonical,
					d.nb_votes,
					d.nb_favorites,
					d.nb_comments
					from decklist d
					where d.id=?
					order by d.date_creation asc", array(
                        $decklist['precedent']
                ))->fetchAll();

        $successor_decklists = $dbh->executeQuery(
                "SELECT
					d.id,
					d.name,
					d.name_canonical,
					d.nb_votes,
					d.nb_favorites,
					d.nb_comments
					from decklist d
					where d.precedent_decklist_id=?
					order by d.date_creation asc", array(
                        $decklist_id
                ))->fetchAll();

        $tournaments = $dbh->executeQuery(
                "SELECT
					t.id,
					t.description
                FROM tournament t
                ORDER BY t.description desc")->fetchAll();

        $deck = $this->get('decklists')->getDeckInfo($decklist_id);

        return $this->render('AppBundle:Decklist:decklist.html.twig',
                array(
                        'pagetitle' => $decklist['name'],
                        'decklist' => $decklist,
                        'commenters' => $commenters,
                        'precedent_decklists' => $precedent_decklists,
                        'successor_decklists' => $successor_decklists,
                        'tournaments' => $tournaments,
                        'deck' => $deck
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
            $decklist->setNbfavorites($decklist->getNbfavorites() - 1);
            $user->removeFavorite($decklist);
            if ($author->getId() != $user->getId())
                $author->setReputation($author->getReputation() - 5);
        } else {
            $decklist->setNbfavorites($decklist->getNbfavorites() + 1);
            $user->addFavorite($decklist);
            $decklist->setTs(new \DateTime());
            if ($author->getId() != $user->getId())
                $author->setReputation($author->getReputation() + 5);
        }
        $this->get('doctrine')
            ->getManager()
            ->flush();

        return new Response(count($decklist->getFavorites()));

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
            $comment->setCreation($now);
            $comment->setAuthor($user);
            $comment->setDecklist($decklist);
            $comment->setHidden(FALSE);

            $this->get('doctrine')
                ->getManager()
                ->persist($comment);
            $decklist->setTs($now);
            $decklist->setNbcomments($decklist->getNbcomments() + 1);

            $this->get('doctrine')
            ->getManager()
            ->flush();

            // send emails
            $spool = [];
            if($decklist->getUser()->getNotifAuthor()) {
                if(!isset($spool[$decklist->getUser()->getEmail()])) {
                    $spool[$decklist->getUser()->getEmail()] = 'AppBundle:Emails:newcomment_author.html.twig';
                }
            }
            foreach($decklist->getComments() as $comment) {
                /* @var $comment Comment */
                $commenter = $comment->getAuthor();
                if($commenter && $commenter->getNotifCommenter()) {
                    if(!isset($spool[$commenter->getEmail()])) {
                        $spool[$commenter->getEmail()] = 'AppBundle:Emails:newcomment_commenter.html.twig';
                    }
                }
            }
            foreach($mentionned_usernames as $mentionned_username) {
                /* @var $mentionned_user User */
                $mentionned_user = $this->getDoctrine()->getRepository('AppBundle:User')->findOneBy(array('username' => $mentionned_username));
                if($mentionned_user && $mentionned_user->getNotifMention()) {
                    if(!isset($spool[$mentionned_user->getEmail()])) {
                        $spool[$mentionned_user->getEmail()] = 'AppBundle:Emails:newcomment_mentionned.html.twig';
                    }
                }
            }
            unset($spool[$user->getEmail()]);

            $email_data = array(
                'username' => $user->getUsername(),
                'decklist_name' => $decklist->getName(),
                'url' => $this->generateUrl('decklist_detail', array('decklist_id' => $decklist->getId(), 'decklist_name' => $decklist->getPrettyname()), TRUE) . '#' . $comment->getId(),
                'comment' => $comment_html,
                'profile' => $this->generateUrl('user_profile', [], TRUE)
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
                'decklist_name' => $decklist->getPrettyName()
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

        $comment->setHidden((boolean) $hidden);
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
                $decklist->setTs(new \DateTime());
                $decklist->setnbVotes($decklist->getnbVotes() + 1);
                $this->get('doctrine')
                ->getManager()
                ->flush();
            }
        }
        return new Response(count($decklist->getVotes()));

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

            /* @var $judge \AppBundle\Services\Judge */
        $judge = $this->get('judge');
        $classement = $judge->classe($decklist->getCards(), $decklist->getIdentity());

        $lines = [];
        $types = array(
                "Event",
                "Hardware",
                "Resource",
                "Icebreaker",
                "Program",
                "Agenda",
                "Asset",
                "Upgrade",
                "Operation",
                "Barrier",
                "Code Gate",
                "Sentry",
                "ICE"
        );

        $lines[] = $decklist->getIdentity()->getName() . " (" . $decklist->getIdentity()
            ->getPack()
            ->getName() . ")";
        foreach ($types as $type) {
            if (isset($classement[$type]) && $classement[$type]['qty']) {
                $lines[] = "";
                $lines[] = $type . " (" . $classement[$type]['qty'] . ")";
                foreach ($classement[$type]['slots'] as $slot) {
                    $inf = "";
                    for ($i = 0; $i < $slot['influence']; $i ++) {
                        if ($i % 5 == 0)
                            $inf .= " ";
                        $inf .= "•";
                    }
                    $lines[] = $slot['qty'] . "x " . $slot['card']->getName() . " (" . $slot['card']->getPack()->getName() . ") " . $inf;
                }
            }
        }
        $content = implode("\r\n", $lines);

        $name = mb_strtolower($decklist->getName());
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '-', $name);
        $name = preg_replace('/--+/', '-', $name);

        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . $name . ".txt");

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

        $rd = [];
        $identity = null;
        /** @var $slot Decklistslot */
        foreach ($decklist->getSlots() as $slot) {
            if ($slot->getCard()
                ->getType()
                ->getName() == "Identity") {
                $identity = array(
                        "index" => $slot->getCard()->getCode(),
                        "name" => $slot->getCard()->getName()
                );
            } else {
                $rd[] = array(
                        "index" => $slot->getCard()->getCode(),
                        "name" => $slot->getCard()->getName(),
                        "qty" => $slot->getQuantity()
                );
            }
        }
        $name = mb_strtolower($decklist->getName());
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '-', $name);
        $name = preg_replace('/--+/', '-', $name);
        if (empty($identity)) {
            return new Response('no identity found');
        }
        return $this->octgnexport("$name.o8d", $identity, $rd, $decklist->getRawdescription(), $response);

    }

    /*
	 * does the "downloadable file" part of the export
	 */
    public function octgnexport ($filename, $identity, $rd, $description, $response)
    {

        $content = $this->renderView('AppBundle::octgn.xml.twig', array(
                "identity" => $identity,
                "rd" => $rd,
                "description" => strip_tags($description)
        ));

        $response->headers->set('Content-Type', 'application/octgn');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . $filename);

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
            if(!$precedent_decklist || $precedent_decklist->getCreation() > $decklist->getCreation()) {
                $precedent_decklist = $decklist->getPrecedent();
            }
        }

        $decklist->setName($name);
        $decklist->setPrettyname(preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($name)));
        $decklist->setRawdescription($rawdescription);
        $decklist->setDescription($description);
        $decklist->setPrecedent($precedent_decklist);
        $decklist->setTournament($tournament);
        $decklist->setTs(new \DateTime());
        $em->flush();

        return $this->redirect($this->generateUrl('decklist_detail', array(
                'decklist_id' => $decklist_id,
                'decklist_name' => $decklist->getPrettyName()
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

    /*
	 * displays details about a user and the list of decklists he published
	 */
    public function profileAction ($user_id, $user_name, $page, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();

        /* @var $user \AppBundle\Entity\User */
        $user = $em->getRepository('AppBundle:User')->find($user_id);
        if (! $user)
            throw new NotFoundHttpException("No such user.");

        $decklists = $em->getRepository('AppBundle:Decklist')->findBy(array('user' => $user));
        $nbdecklists = count($decklists);

        $reviews = $em->getRepository('AppBundle:Review')->findBy(array('user' => $user));
        $nbreviews = count($reviews);


        return $this->render('AppBundle:Default:profile.html.twig',
                array(
                        'pagetitle' => $user->getUsername(),
                        'user' => $user,
                        'nbdecklists' => $nbdecklists,
                        'nbreviews' => $nbreviews
                ), $response);

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
				c.creation,
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
				c.creation,
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
				order by f.side_id asc, f.name asc")
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
                        'url' => $this->getRequest()
                            ->getRequestUri(),
                        'factions' => $factions,
                        'form' => $this->renderView('AppBundle:Search:form.html.twig',
                            array(
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
