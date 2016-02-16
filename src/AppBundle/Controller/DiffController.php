<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Decklist;

class DiffController extends Controller
{
	public function deckDiffAction($deck1_id, $deck2_id, Request $request)
	{
		$entityManager = $this->getDoctrine()->getManager();
		
		/* @var $deck1 \AppBundle\Entity\Deck */
		$deck1 = $entityManager->getRepository('AppBundle:Deck')->find($deck1_id);
		
		/* @var $deck2 \AppBundle\Entity\Deck */
		$deck2 = $entityManager->getRepository('AppBundle:Deck')->find($deck2_id);
		
		if(!$deck1 || !$deck2) {
			return $this->render(
					'AppBundle:Default:error.html.twig',
					array(
							'pagetitle' => "Error",
							'error' => 'This deck cannot be found.'
					)
			);
		}
		
		$plotIntersection = $this->get('diff')->getSlotsDiff([$deck1->getSlots()->getPlotDeck(), $deck2->getSlots()->getPlotDeck()]);
		
		$drawIntersection = $this->get('diff')->getSlotsDiff([$deck1->getSlots()->getDrawDeck(), $deck2->getSlots()->getDrawDeck()]);
		
		
		return $this->render('AppBundle:Compare:deck_compare.html.twig', [
				'deck1' => $deck1,
				'deck2' => $deck2,
				'plot_deck' => $plotIntersection,
				'draw_deck' => $drawIntersection,
		]);
	}
}