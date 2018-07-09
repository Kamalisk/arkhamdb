<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use AppBundle\Entity\Deck;
use AppBundle\Entity\Deckslot;
use Symfony\Component\HttpFoundation\JsonResponse;

class Oauth2Controller extends Controller
{
	/**
	 * Get the description of all the Decks of the authenticated user
	 *
	 * @ApiDoc(
	 *  section="Deck",
	 *  resource=true,
	 *  description="All the Decks",
	 * )
	 * @param Request $request
	 */
	public function listDecksAction(Request $request)
	{
		$response = new Response();
		$response->headers->add(array('Access-Control-Allow-Origin' => '*'));

		/* @var $decks \AppBundle\Entity\Deck[] */
		$decks = $this->getDoctrine()->getRepository('AppBundle:Deck')->findBy(['user' => $this->getUser()]);

		$dateUpdates = array_map(function ($deck) {
			return $deck->getDateUpdate();
		}, $decks);

		$response->setLastModified(max($dateUpdates));
		if ($response->isNotModified($request)) {
			return $response;
		}

		$content = json_encode($decks);

		$response->headers->set('Content-Type', 'application/json');
		$response->setContent($content);
		return $response;
	}


	/**
	 * Get the description of one Deck of the authenticated user
	 *
	 * @ApiDoc(
	 *  section="Deck",
	 *  resource=true,
	 *  description="Load One Deck",
	 *  requirements={
	 *      {
	 *          "name"="id",
	 *          "dataType"="integer",
	 *          "requirement"="\d+",
	 *          "description"="The numeric identifier of the Deck to load"
	 *      },
	 *  },
	 * )
	 * @param Request $request
	 */
	public function loadDeckAction($id)
	{
		$response = new Response();
		$response->headers->add(array('Access-Control-Allow-Origin' => '*'));

		/* @var $deck \AppBundle\Entity\Deck */
		$deck = $this->getDoctrine()->getRepository('AppBundle:Deck')->find($id);

		if($deck->getUser()->getId() !== $this->getUser()->getId() &&
			!$deck->getUser()->getIsShareDecks()) {
			throw $this->createAccessDeniedException("Access denied to this object.");
		}


		$content = json_encode($deck);

		$response->headers->set('Content-Type', 'application/json');
		$response->setContent($content);
		return $response;
	}

	/**
	 * Create a new deck for the authenticated user.
	 * An investigator is required, and the deck will be created empty with only
	 * the 'required' cards for that investigator.
	 * If successful, id of new Deck is in the msg.
	 *
	 * @ApiDoc(
	 *  section="Deck",
	 *  resource=true,
	 *  description="Create a New Deck",
 	 *  requirements={},
	 *  parameters={
	 *      {"name"="investigator", "dataType"="string", "required"=true, "description"="Code of the investigator card."},
	 *      {"name"="name", "dataType"="string", "required"=false, "description"="Name of the Deck. A default name will be generated if it is not specified."},
	 *  },
	 * )
	 * @param Request $request
	 */
	public function newDeckAction(Request $request)
	{
		/* @var $em \Doctrine\ORM\EntityManager */
		$em = $this->getDoctrine()->getManager();

		$investigator = false;
		$investigator_code = filter_var($request->get('investigator'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
		if ($investigator_code && $card = $em->getRepository('AppBundle:Card')->findOneBy(["code" => $investigator_code])){
			$investigator = $card = $em->getRepository('AppBundle:Card')->findOneBy(["code" => $investigator_code]);
		}

		if (!$investigator) {
			return new JsonResponse([
				'success' => FALSE,
				'msg' => "investigator is required to build a new deck."
			]);
		}

		$tags = [ $investigator->getFaction()->getCode() ];
		$cards_to_add = [];

		// Parse deck requirements and pre-fill deck with needed cards
		if ($investigator->getDeckRequirements()){
			$deck_requirements = $this->get('DeckValidationHelper')->parseReqString($investigator->getDeckRequirements());
			if (isset($deck_requirements['card']) && $deck_requirements['card']){
				foreach($deck_requirements['card'] as $card_code => $alternates){
					if ($card_code){
						$card_to_add = $em->getRepository('AppBundle:Card')->findOneBy(array("code" => $card_code));
						if ($card_to_add){
							$cards_to_add[] = $card_to_add;
						}
					}
				}
			}

			// add random deck requirements here
			// should add a flag so the user can choose to add these or not
			if (isset($deck_requirements['random']) && $deck_requirements['random']){
				foreach($deck_requirements['random'] as $random){
					if (isset($random['target']) && $random['target']){
						if ($random['target'] === "subtype"){
							$subtype = $em->getRepository('AppBundle:Subtype')->findOneBy(array("code" => $random['value']));
							//$valid_targets = $em->getRepository('AppBundle:Card')->findBy(array("subtype" => $subtype->getId() ));
							$valid_targets = $em->getRepository('AppBundle:Card')->findBy(array("name" => "Random Basic Weakness" ));
							//print_r($subtype->getId());
							if ($valid_targets){
								$key = array_rand($valid_targets);
								// should disable adding random weakness
								$cards_to_add[] = $valid_targets[$key];
							}
						}
					}
				}
			}
		}

		$pack = $investigator->getPack();
		$name = filter_var($request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
		if(!$name) {
			// Set a default name if one was not provided.
			$name = sprintf("%s", $investigator->getName());
			if ($investigator->getFaction()->getCode() == "guardian"){
				$name = sprintf("The Adventures of %s", $investigator->getName());
			} else if ($investigator->getFaction()->getCode() == "seeker"){
				$name = sprintf("%s Investigates", $investigator->getName());
			} else if ($investigator->getFaction()->getCode() == "mystic"){
				$name = sprintf("The %s Mysteries", $investigator->getName());
			} else if ($investigator->getFaction()->getCode() == "rogue"){
				$name = sprintf("The %s Job", $investigator->getName());
			} else if ($investigator->getFaction()->getCode() == "survivor"){
				$name = sprintf("%s on the Road", $investigator->getName());
			}
		}

		$deck = new Deck();
		// Make most of these fields empty by default, they can be set later.
		$deck->setDescriptionMd("");
		$deck->setCharacter($investigator);
		$deck->setLastPack($pack);
		$deck->setName($name);
		$deck->setProblem('too_few_cards');
		$deck->setTags(join(' ', array_unique($tags)));
		$deck->setUser($this->getUser());

		foreach ( $cards_to_add as $card) {
			$slot = new Deckslot ();
			$slot->setQuantity ( $card->getDeckLimit() );
			$slot->setCard ( $card );
			$slot->setDeck ( $deck );
			//$slot->setIgnoreDeckRestrictions ( true );
			$deck->addSlot ( $slot );
		}
		$em->persist($deck);
		$em->flush();

		// Return a successful deck with just the required cards.
		return new JsonResponse([
				'success' => TRUE,
				'msg' => $deck->getId()
		]);
	}

	/**
	 * Upgrade a deck of the authenticated user. Takes a parameter for the amount of XP earned + a list of exiled cards.
	 * This also serves to lock changes so that future card swaps are properly accounted for in the campaign XP.
	 * If successful, the id of the new Deck is in the msg.
	 *
	 * @ApiDoc(
	 *  section="Deck",
	 *  resource=true,
	 *  description="Upgrade One Deck",
	 *  requirements={
	 *      {
	 *          "name"="id",
	 *          "dataType"="integer",
	 *          "requirement"="\d+",
	 *          "description"="The numeric identifier of the Deck to update.",
	 *      },
	 *  },
	 *  parameters={
	 *      {"name"="xp", "dataType"="integer", "required"=true, "description"="Number of XP earned to apply to the upgrade"},
	 *      {"name"="exiles", "dataType"="string", "required"=false, "description"="Optional comma separated list of card codes to 'exile'. All passed codes must be present in the existing deck and must be exilable cards."},
	 *  },
	 * )
	 * @param Request $request
	 */
	public function upgradeDeckAction($id, Request $request) {
		/* @var $em \Doctrine\ORM\EntityManager */
		$em = $this->getDoctrine()->getManager();

		if(!$id) {
			return new JsonResponse([
				'success' => FALSE,
				'msg' => 'id of deck is required.'
			]);
		}

		/* @var $deck \AppBundle\Entity\Deck */
		$deck = $em->getRepository('AppBundle:Deck')->find($id);
		if (!$deck){
			return false;
		}
		if ($deck->getNextDeck()) {
			return new JsonResponse([
				'success' => FALSE,
				'msg' => 'Deck is locked.'
			]);
		}
		$is_owner = $this->getUser() && $this->getUser()->getId() == $deck->getUser()->getId();
		if(!$is_owner) {
			return new JsonResponse([
				'success' => FALSE,
				'msg' => 'You are not allowed to upgrade this deck, you are not the owner.'
			]);
		}


		$slots = [];
		foreach ($deck->getSlots() as $slot) {
			$slots[$slot->getCard()->getCode()] = $slot->getQuantity();
		}

		if ($request->get('exiles')){
			$exiles = filter_var_array(explode(',', $request->get('exiles')), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
		} else {
			$exiles = false;
		}
		$filtered_exiles = [];
		$filtered_exile_cards = [];
		$exile_counts=[];
		if ($exiles) {
			foreach ($exiles as $exile) {
				$exile_card = $em->getRepository('AppBundle:Card')->findOneBy(array("code" => $exile));
				// Validate that the exile card code is valid and is exilable.
				if ($exile_card && $exile_card->getExile()) {
					$filtered_exile_cards[] = $exile_card;
					$filtered_exiles[] = $exile_card->getCode();

					// Keep track of total count of each exile card.
					if (!isset($exile_counts[$exile_card->getCode()])) {
						$exile_counts[$exile_card->getCode()] = 0;
					}
					$exile_counts[$exile_card->getCode()] = $exile_counts[$exile_card->getCode()] + 1;

					// Check if the deck contains enough cards to exile, so we don't 'over' exile.
					$slot_count = isset($slots[$exile_card->getCode()]) ? $slots[$exile_card->getCode()] : 0;
					if ($slot_count < $exile_counts[$exile_card->getCode()]) {
						return new JsonResponse([
							'success' => FALSE,
							'msg' => 'Not enough count to exile: '.$exile
						]);
					}
				} else {
					return new JsonResponse([
						'success' => FALSE,
						'msg' => 'Invalid exile card: '.$exile
					]);
				}
			}
		}

		// Read the XP for the upgrade.
		$xp = filter_var($request->get('xp'), FILTER_SANITIZE_NUMBER_INT);

		// Account for any carryover XP from the previous deck (old - spent).
		if ($deck->getXp()){
			$xp += $deck->getXp();
		}
		if ($deck->getXpSpent()){
			$xp -= $deck->getXpSpent();
		}

		// No decklist_id parameter, used when cloning/copying decks.
		$decklist_id = null;

		// Create and save a new deck.
		$newDeck = new Deck();
		$this->get('decks')->saveDeck(
			$this->getUser(),
			$newDeck,
			$decklist_id,
			$deck->getName(),
			$deck->getCharacter(),
			$deck->getDescriptionMd(),
			$deck->getTags(),
			$slots,
			null, // no source deck, we want it to be new.
			$deck->getProblem()
		);

		if ($filtered_exiles) {
			// Set exiled cards if there were any sent in the request.
			$newDeck->setExiles(implode(",",$filtered_exiles));
		}

		// Upgrade action takes care of setting XP, removing exiles, and linking
		// the deck to the previous incarnation (and managing upgrades).
		$this->get('decks')->upgradeDeck(
			$newDeck,
			$xp,
			$deck,
			$deck->getUpgrades(),
			$filtered_exile_cards
		);

		// Send changes back to the database.
		$em->flush();

		// Return the new deck id.
		return new JsonResponse([
			'success' => TRUE,
			'msg' => $newDeck->getId(),
		]);
	}

	/**
	 * Save one Deck of the authenticated user. The parameters are the same as in the response to the load method, but only a few are writable.
	 * So you can parse the result from the load, change a few values, then send the object as the param of an ajax request.
	 * If successful, id of Deck is in the msg
	 *
	 * @ApiDoc(
	 *  section="Deck",
	 *  resource=true,
	 *  description="Save One Deck",
	 *  requirements={
	 *      {
	 *          "name"="id",
	 *          "dataType"="integer",
	 *          "requirement"="\d+",
	 *          "description"="The numeric identifier of the Deck to update.",
	 *      },
	 *  },
	 *  parameters={
	 *      {"name"="name", "dataType"="string", "required"=false, "description"="Name of the Deck if a change is needed."},
	 *      {"name"="decklist_id", "dataType"="integer", "required"=false, "description"="Identifier of the Decklist from which the Deck is copied"},
	 *      {"name"="description_md", "dataType"="string", "required"=false, "description"="Description of the Decklist in Markdown"},
	 *      {"name"="tags", "dataType"="string", "required"=false, "description"="Space-separated list of tags"},
	 *      {"name"="slots", "dataType"="string", "required"=true, "description"="Content of the Decklist as a JSON object"},
	 *      {"name"="problem", "dataType"="string", "required"=true, "description"="A short code description of the problem with the provided slots, if one exists. Must be one of: too_few_cards,too_many_cards,too_many_copies,invalid_cards,deck_options_limit,investigator"},
	 *  },
	 * )
	 * @param Request $request
	 */
	public function saveDeckAction($id, Request $request)
	{
		/* @var $deck \AppBundle\Entity\Deck */
		$em = $this->getDoctrine()->getManager();

		if(!$id) {
			return new JsonResponse([
				'success' => FALSE,
				'msg' => 'id of deck is required.'
			]);
		}

		// A deck ID was provided, so we lookup the deck that is being modified.
		$deck = $this->getDoctrine()->getRepository('AppBundle:Deck')->find($id);
		if($deck->getUser()->getId() !== $this->getUser()->getId()) {
			return new JsonResponse([
				'success' => FALSE,
				'msg' => 'You are not allowed to edit this deck, you are not the owner.'
			]);
		}
		if ($deck->getNextDeck()) {
			return new JsonResponse([
				'success' => FALSE,
				'msg' => 'Deck is locked.'
			]);
		}

		// Don't allow investigator to be changed when 'editing' a deck.
		// Seems unnecessary and is bound to break something.
		$investigator = $deck->getCharacter();
		if (!$investigator) {
			return new JsonResponse([
				'success' => FALSE,
				'msg' => "Investigator code invalid"
			]);
		}

		// Slots is the one required parameter.
		$slots = (array) json_decode($request->get('slots'));
		if (!count($slots)) {
			return new JsonResponse([
					'success' => FALSE,
					'msg' => "Slots missing"
			]);
		}

		foreach($slots as $card_code => $qty) {
			if(!is_string($card_code) || !is_integer($qty)) {
				return new JsonResponse([
						'success' => FALSE,
						'msg' => "Slots invalid"
				]);
			}
		}
		// We expect all requests to include problem.
		$problem = filter_var($request->get('problem'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
		if (!empty($problem) && !in_array($problem, [
			'too_few_cards',
			'too_many_cards',
			'too_many_copies',
			'invalid_cards',
			'deck_options_limit',
			'investigator'], true)) {
			return new JsonResponse([
					'success' => FALSE,
					'msg' => "Invalid problem"
			]);
		}
		$name = filter_var($request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
		if(!$name) {
			if ($deck->getName()) {
				$name = $deck->getName();
			} else {
				return new JsonResponse([
						'success' => FALSE,
						'msg' => "Name missing"
				]);
			}
		}

		$decklist_id = filter_var($request->get('decklist_id'), FILTER_SANITIZE_NUMBER_INT);
		if (!$decklist_id && $deck->getParent()) {
			// Don't override the parent if this deck was copied and a param was not specified.
			$decklist_id = $deck->getParent();
		}

		$description = trim($request->get('description_md'));
		if (!$description && $deck->getDescriptionMd()) {
			// Leave description alone if it was not specified (or was blank?).
			$description = $deck->getDescriptionMd();
		}

		$tags = filter_var($request->get('tags'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
		if (!$tags && $deck->getTags()) {
			// Leave tags alone if they were blank.
			$tags = $deck->getTags();
		}

		// Save the deck.
		$this->get('decks')->saveDeck($this->getUser(), $deck, $decklist_id, $name, $investigator, $description, $tags, $slots, $deck , $problem);

		// xp_spent is only read/set if there was a previousDeck.
		if ($deck->getPreviousDeck() && $request->get('xp_spent') !== null) {
			$xp_spent = filter_var($request->get('xp_spent'), FILTER_SANITIZE_NUMBER_INT);
			$deck->setXpSpent($xp_spent);
		}

		// Actually flush the database edits.
		$em->flush();

		return new JsonResponse([
				'success' => TRUE,
				'msg' => $deck->getId()
		]);
	}

	/**
	 * Try to publish one Deck of the authenticated user
	 * If publication is successful, update the version of the deck and return the id of the decklist
	 *
	 * @ApiDoc(
	 *  section="Deck",
	 *  resource=true,
	 *  description="Publish One Deck",
	 *  requirements={
	 *      {
	 *          "name"="id",
	 *          "dataType"="integer",
	 *          "requirement"="\d+",
	 *          "description"="The numeric identifier of the Deck to publish"
	 *      },
	 *  },
	 *  parameters={
	 *      {"name"="description_md", "dataType"="string", "required"=false, "description"="Description of the Decklist in Markdown"},
	 *      {"name"="tournament_id", "dataType"="integer", "required"=false, "description"="Identifier of the Tournament type of the Decklist"},
	 *      {"name"="precedent_id", "dataType"="integer", "required"=false, "description"="Identifier of the Predecessor of the Decklist"},
	 *  },
	 * )
	 * @param Request $request
	 */
	public function publishDeckAction($id, Request $request)
	{
		/* @var $deck \AppBundle\Entity\Deck */
		$deck = $this->getDoctrine()->getRepository('AppBundle:Deck')->find($id);
		if ($this->getUser()->getId() !== $deck->getUser()->getId()) {
			throw $this->createAccessDeniedException("Access denied to this object.");
		}

		$name = filter_var($request->request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
		$descriptionMd = trim($request->request->get('description_md'));

		$tournament_id = intval(filter_var($request->request->get('tournament_id'), FILTER_SANITIZE_NUMBER_INT));
		$tournament = $this->getDoctrine()->getManager()->getRepository('AppBundle:Tournament')->find($tournament_id);

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
        	return new JsonResponse([
        			'success' => FALSE,
        			'msg' => $e->getMessage()
        	]);
        }

        $decklist->setTournament($tournament);
        $decklist->setPrecedent($precedent);
        $this->getDoctrine()->getManager()->persist($decklist);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse([
        		'success' => TRUE,
        		'msg' => $decklist->getId()
        ]);
    }
}
