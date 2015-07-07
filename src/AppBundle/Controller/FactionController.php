<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Decklist;
use AppBundle\Entity\Faction;

class FactionController extends Controller
{
    public function factionAction($faction_code, Request $request)
    {
    	$response = new Response();
    	$response->setPublic();
    	$response->setMaxAge($this->container->getParameter('cache_expiration'));
    	
    	$em = $this->getDoctrine()->getManager();
        
        /* @var $faction Faction */
        $faction = $em->getRepository('AppBundle:Faction')->findOneBy(array('code' => $faction_code));
        
        if(!$faction) {
            throw new NotFoundHttpException("Faction $faction_code not found.");
        }

        // build the list of identites for the faction
        
        /* @var $qb \Doctrine\ORM\QueryBuilder */
        $qb = $em->createQueryBuilder();
        $qb->select('c')
        ->from('AppBundle:Card', 'c')
        ->join('c.pack', 'p')
        ->where('c.faction=:faction')
        ->setParameter('faction', $faction)
        ->andWhere('c.type=:type')
        ->andWhere('p.dateRelease is not null')
        ->setParameter('type', $em->getRepository('AppBundle:Type')->findOneBy(array('name' => 'Identity')));
        
        $identities = $qb->getQuery()->getResult();
        
        $nb_decklists_per_id = 3;
        
        // build the list of the top $nb_decklists_per_id decklists per id
        // also, compute the total points of those decks per id
        
        $decklists = [];
        foreach($identities as $identity) {
        	
        	$qb = $em->createQueryBuilder();
        	$qb->select('d, (d.nb_votes/(1+DATE_DIFF(CURRENT_TIMESTAMP(),d.date_creation)/10)) as points')
        	->from('AppBundle:Decklist', 'd')
        	->where('d.identity=:identity')
        	->setParameter('identity', $identity)
        	->orderBy('points', 'DESC')
        	->setMaxResults($nb_decklists_per_id);
        	$results = $qb->getQuery()->getResult();

        	$points = 0;
        	$list = [];
        	foreach($results as $row) {
        		$list[] = $row[0];
        		$points += intval($row['points']);
        	}

        	$decklists[] = array(
        			'identity' => $identity,
        			'points' => $points,
        			'decklists' => $list
        	);
        }
        
        // sort the identities from most points to least
        usort($decklists, function ($a, $b) {
        	return $b['points'] - $a['points'];
        });
        
        return $this->render('AppBundle:Faction:faction.html.twig', array(
                "pagetitle" => $faction->getName(),
                "faction" => $faction,
                "decklists" => $decklists
        ), $response);
    }
}