<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class UserController extends Controller
{

    /*
	 * displays details about a user and the list of decklists he published
	 */
    public function publicProfileAction ($user_id, $user_name, $page, Request $request)
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

        return $this->render('AppBundle:User:profile_public.html.twig', array(
                'user'=> $user
        ));
    }

    public function editProfileAction()
    {
    	$user = $this->getUser();

    	$factions = $this->get('doctrine')->getRepository('AppBundle:Faction')->findAll();

    	return $this->render('AppBundle:User:profile_edit.html.twig', array(
    			'user'=> $user,
                'factions' => $factions
        ));
    }

    public function saveProfileAction()
    {
    	/* @var $user \AppBundle\Entity\User */
    	$user = $this->getUser();
    	$request = $this->getRequest();
    	$em = $this->getDoctrine()->getManager();

    	$username = filter_var($request->get('username'), FILTER_SANITIZE_STRING);
    	if($username !== $user->getUsername()) {
    	    $user_existing = $em->getRepository('AppBundle:User')->findOneBy(array('username' => $username));

    	    if($user_existing) {

    	        $this->get('session')
    	        ->getFlashBag()
    	        ->set('error', "Username $username is already taken.");

    	        return $this->redirect($this->generateUrl('user_profile_edit'));
    	    }

    	    $user->setUsername($username);
    	}

    	$email = filter_var($request->get('email'), FILTER_SANITIZE_STRING);
    	if($email !== $user->getEmail()) {
    	    $user->setEmail($email);
    	}

    	$resume = filter_var($request->get('resume'), FILTER_SANITIZE_STRING);
    	$faction_code = filter_var($request->get('user_faction_code'), FILTER_SANITIZE_STRING);
    	$notifAuthor = $request->get('notif_author') ? TRUE : FALSE;
    	$notifCommenter = $request->get('notif_commenter') ? TRUE : FALSE;
    	$notifMention = $request->get('notif_mention') ? TRUE : FALSE;
    	$shareDecks = $request->get('share_decks') ? TRUE : FALSE;

    	$user->setColor($faction_code);
    	$user->setResume($resume);
    	$user->setIsNotifAuthor($notifAuthor);
    	$user->setIsNotifCommenter($notifCommenter);
    	$user->setIsNotifMention($notifMention);
    	$user->setIsShareDecks($shareDecks);

    	$this->get('doctrine')->getManager()->flush();

        $this->get('session')
            ->getFlashBag()
            ->set('notice', "Successfully saved your profile.");

    	return $this->redirect($this->generateUrl('user_profile_edit'));
    }

    public function infoAction(Request $request)
    {
        $jsonp = $request->query->get('jsonp');

        $decklist_id = $request->query->get('decklist_id');
        $card_id = $request->query->get('card_id');

        $content = null;

        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();
        if($user)
        {
            $user_id = $user->getId();

            $public_profile_url = $this->get('router')->generate('user_profile_public', array(
                    'user_id' => $user_id,
                    'user_name' => urlencode($user->getUsername())
            ));
            $content = array(
                    'public_profile_url' => $public_profile_url,
                    'id' => $user_id,
                    'name' => $user->getUsername(),
                    'faction' => $user->getColor(),
                    'donation' => $user->getDonation(),
            );

            if(isset($decklist_id)) {
                /* @var $em \Doctrine\ORM\EntityManager */
                $em = $this->get('doctrine')->getManager();
                /* @var $decklist \AppBundle\Entity\Decklist */
                $decklist = $em->getRepository('AppBundle:Decklist')->find($decklist_id);

				if ($decklist) {
    				$decklist_id = $decklist->getId();

    				$dbh = $this->get('doctrine')->getConnection();

    			    $content['is_liked'] = (boolean) $dbh->executeQuery("SELECT
        				count(*)
        				from decklist d
        				join vote v on v.decklist_id=d.id
        				where v.user_id=?
        				and d.id=?", array($user_id, $decklist_id))->fetch(\PDO::FETCH_NUM)[0];

    			    $content['is_favorite'] = (boolean) $dbh->executeQuery("SELECT
        				count(*)
        				from decklist d
        				join favorite f on f.decklist_id=d.id
        				where f.user_id=?
        				and d.id=?", array($user_id, $decklist_id))->fetch(\PDO::FETCH_NUM)[0];

    			    $content['is_author'] = ($user_id == $decklist->getUser()->getId());

    			    $content['can_delete'] = ($decklist->getNbcomments() == 0) && ($decklist->getNbfavorites() == 0) && ($decklist->getnbVotes() == 0);
				}
            }

            if(isset($card_id)) {
                /* @var $em \Doctrine\ORM\EntityManager */
                $em = $this->get('doctrine')->getManager();
                /* @var $card \AppBundle\Entity\Card */
                $card = $em->getRepository('AppBundle:Card')->find($card_id);

                if($card) {
                    $reviews = $card->getReviews();
                    /* @var $review \AppBundle\Entity\Review */
                    foreach($reviews as $review) {
                        if($review->getUser()->getId() === $user->getId()) {
                            $content['review_id'] = $review->getId();
                            $content['review_text'] = $review->getRawtext();
                        }
                    }
                }
            }
        }
        $content = json_encode($content);

        $response = new Response();
        $response->setPrivate();
        if(isset($jsonp))
        {
            $content = "$jsonp($content)";
            $response->headers->set('Content-Type', 'application/javascript');
        } else
        {
            $response->headers->set('Content-Type', 'application/json');
        }
        $response->setContent($content);

        return $response;

    }

    public function remindAction($username)
    {
    	$user = $this->get('fos_user.user_manager')->findUserByUsername($username);
    	if(!$user) {
    		throw new NotFoundHttpException("Cannot find user from username [$username]");
    	}
    	if(!$user->getConfirmationToken()) {
    		return $this->render('AppBundle:User:remind-no-token.html.twig');
    	}

    	$this->get('fos_user.mailer')->sendConfirmationEmailMessage($user);

    	$this->get('session')->set('fos_user_send_confirmation_email/email', $user->getEmail());

    	$url = $this->get('router')->generate('fos_user_registration_check_email');
    	return $this->redirect($url);
    }

}
