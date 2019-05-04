<?php 

namespace AppBundle\Entity;

class Deck extends \AppBundle\Model\ExportableDeck implements \JsonSerializable
{
	
	/**
	 * @return array
	 */
	public function isOctgnable()
	{
		// run through all cards and see if they have an ocgtn ID
		$slots = $this->getSlots();
		$cards = $slots->getContent();
		foreach($slots as $slot){
			if (!$slot->getCard()->getOctgnID()){
				return false;
			}
		}
		return true;
	}
	
	public function __clone() {
		if ($this->id) {
			$this->id = null;
		}
	}
	/**
	 * @return array
	 */
	public function getHistory()
	{
		$slots = $this->getSlots();
		$cards = $slots->getContent();
			
		$snapshots = [];
			
		/**
		 * All changes, with the newest at position 0
		*/
		$changes = $this->getChanges();
			
		/**
		 * Saved changes, with the newest at position 0
		 * @var $savedChanges Deckchange[]
		*/
		$savedChanges =[];
			
		/**
		 * Unsaved changes, with the oldest at position 0
		 * @var $unsavedChanges Deckchange[]
		*/
		$unsavedChanges =[];
			
		foreach($changes as $change) {
			if($change->getIsSaved()) {
				array_push($savedChanges, $change);
			}
			else {
				array_unshift($unsavedChanges, $change);
			}
		}
		$array['unsaved'] = count($unsavedChanges);
			
		// recreating the versions with the variation info, starting from $preversion
		$preversion = $cards;
		foreach ( $savedChanges as $change ) {
			$variation = json_decode ( $change->getVariation(), TRUE );
			$row = [
					'variation' => $variation,
					'is_saved' => $change->getIsSaved(),
					'version' => $change->getVersion(),
					'content' => $preversion,
					'date_creation' => $change->getDateCreation()->format('c'),
			];
			array_unshift ( $snapshots, $row );
				
			// applying variation to create 'next' (older) preversion
			foreach ( $variation[0] as $code => $qty ) {
				if (isset($preversion[$code])){
					$preversion[$code] = $preversion[$code] - $qty;
					if ($preversion[$code] == 0) unset ( $preversion[$code] );
				}
			}
			foreach ( $variation[1] as $code => $qty ) {
				if (! isset ( $preversion[$code] )) $preversion[$code] = 0;
				$preversion[$code] = $preversion[$code] + $qty;
			}
			ksort ( $preversion );
		}
			
		// add last know version with empty diff
		$row = [
				'variation' => null,
				'is_saved' => true,
				'version' => "0.0",
				'content' => $preversion,
				'date_creation' => $this->getDateCreation()->format('c')
		];
		array_unshift ( $snapshots, $row );
			
		// recreating the snapshots with the variation info, starting from $postversion
		$postversion = $cards;
		foreach ( $unsavedChanges as $change ) {
			$variation = json_decode ( $change->getVariation(), TRUE );
			$row = [
					'variation' => $variation,
					'is_saved' => $change->getIsSaved(),
					'version' => $change->getVersion(),
					'date_creation' => $change->getDateCreation()->format('c'),
			];
				
			// applying variation to postversion
			foreach ( $variation[0] as $code => $qty ) {
				if (! isset ( $postversion[$code] )) $postversion[$code] = 0;
				$postversion[$code] = $postversion[$code] + $qty;
			}
			foreach ( $variation[1] as $code => $qty ) {
				if (! isset ( $postversion[$code] )) $postversion[$code] = 0;
				$postversion[$code] = $postversion[$code] - $qty;
				if ($postversion[$code] == 0) unset ( $postversion[$code] );
			}
			ksort ( $postversion );
				
			// add postversion with variation that lead to it
			$row['content'] = $postversion;
			array_push ( $snapshots, $row );
		}
		
		return $snapshots;
	}
	
	public function getUpgradePath()
	{
		$pointer = $this;
		$decks = [];
		while ($pointer = $pointer->getPreviousDeck()){
			$decks[] = $pointer;
		}
		return $pointer;
	}
	
	public function jsonSerialize()
	{
		$array = parent::getArrayExport();
		$array['problem'] = $this->getProblem();
		$array['tags'] = $this->getTags();
		
		return $array;
	}
	
	public function getIsUnsaved()
	{
		$changes = $this->getChanges();

		foreach($changes as $change) {
			if(!$change->getIsSaved()) {
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	/**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \DateTime
     */
    private $dateCreation;

    /**
     * @var \DateTime
     */
    private $dateUpdate;

    /**
     * @var string
     */
    private $descriptionMd;

    /**
     * @var string
     */
    private $problem;

    /**
     * @var string
     */
    private $exiles;


    /**
     * @var string
     */
    private $tags;
    
    /**
     * @var integer
     */
    private $majorVersion;
    
    /**
     * @var integer
     */
    private $minorVersion;
    
    /**
     * @var integer
     */
    private $xp;
    
     /**
     * @var integer
     */
    private $xpSpent;
    
    /**
     * @var integer
     */
    private $xpAdjustment;
    
    /**
     * @var \AppBundle\Entity\Deck
     */
    private $previousDeck;
    /**
     * @var \AppBundle\Entity\Deck
     */
    private $nextDeck;
    
    /**
     * @var integer
     */
    private $upgrades;
    
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $slots;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $children;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $changes;

    /**
     * @var \AppBundle\Entity\User
     */
    private $user;
    
    /**
     * @var \AppBundle\Entity\Usercampaign
     */
    private $usercampaign;

    /**
     * @var \AppBundle\Entity\Faction
     */
    private $character;

    /**
     * @var \AppBundle\Entity\Taboo
     */
    private $taboo;

    /**
     * @var \AppBundle\Entity\Pack
     */
    private $lastPack;

    /**
     * @var \AppBundle\Entity\Decklist
     */
    private $parent;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->slots = new \Doctrine\Common\Collections\ArrayCollection();
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->changes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->minorVersion = 0;
        $this->majorVersion = 0;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Deck
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     *
     * @return Deck
     */
    public function setDateCreation($dateCreation)
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Get dateCreation
     *
     * @return \DateTime
     */
    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    /**
     * Set dateUpdate
     *
     * @param \DateTime $dateUpdate
     *
     * @return Deck
     */
    public function setDateUpdate($dateUpdate)
    {
        $this->dateUpdate = $dateUpdate;

        return $this;
    }

    /**
     * Get dateUpdate
     *
     * @return \DateTime
     */
    public function getDateUpdate()
    {
        return $this->dateUpdate;
    }

    /**
     * Set descriptionMd
     *
     * @param string $descriptionMd
     *
     * @return Deck
     */
    public function setDescriptionMd($descriptionMd)
    {
        $this->descriptionMd = $descriptionMd;

        return $this;
    }

    /**
     * Get descriptionMd
     *
     * @return string
     */
    public function getDescriptionMd()
    {
        return $this->descriptionMd;
    }

    /**
     * Set exiles
     *
     * @param string $exiles
     *
     * @return Deck
     */
    public function setExiles($exiles)
    {
        $this->exiles = $exiles;

        return $this;
    }

    /**
     * Get exiles
     *
     * @return string
     */
    public function getExiles()
    {
        return $this->exiles;
    }


    /**
     * Set problem
     *
     * @param string $problem
     *
     * @return Deck
     */
    public function setProblem($problem)
    {
        $this->problem = $problem;

        return $this;
    }

    /**
     * Get problem
     *
     * @return string
     */
    public function getProblem()
    {
        return $this->problem;
    }


    /**
     * Set tags
     *
     * @param string $tags
     *
     * @return Deck
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Get tags
     *
     * @return string
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Add slot
     *
     * @param \AppBundle\Entity\Deckslot $slot
     *
     * @return Deck
     */
    public function addSlot(\AppBundle\Entity\Deckslot $slot)
    {
        $this->slots[] = $slot;

        return $this;
    }

    /**
     * Remove slot
     *
     * @param \AppBundle\Entity\Deckslot $slot
     */
    public function removeSlot(\AppBundle\Entity\Deckslot $slot)
    {
        $this->slots->removeElement($slot);
    }

    /**
     * Get slots
     *
     * @return \AppBundle\Model\SlotCollectionInterface
     */
    public function getSlots()
    {
        return new \AppBundle\Model\SlotCollectionDecorator($this->slots);
    }

    /**
     * Add child
     *
     * @param \AppBundle\Entity\Decklist $child
     *
     * @return Deck
     */
    public function addChild(\AppBundle\Entity\Decklist $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param \AppBundle\Entity\Decklist $child
     */
    public function removeChild(\AppBundle\Entity\Decklist $child)
    {
        $this->children->removeElement($child);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Add change
     *
     * @param \AppBundle\Entity\Deckchange $change
     *
     * @return Deck
     */
    public function addChange(\AppBundle\Entity\Deckchange $change)
    {
        $this->changes[] = $change;

        return $this;
    }

    /**
     * Remove change
     *
     * @param \AppBundle\Entity\Deckchange $change
     */
    public function removeChange(\AppBundle\Entity\Deckchange $change)
    {
        $this->changes->removeElement($change);
    }

    /**
     * Get changes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * Set user
     *
     * @param \AppBundle\Entity\User $user
     *
     * @return Deck
     */
    public function setUser(\AppBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \AppBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set user
     *
     * @param \AppBundle\Entity\Taboo $taboo
     *
     * @return Deck
     */
    public function setTaboo(\AppBundle\Entity\Taboo $taboo = null)
    {
        $this->taboo = $taboo;

        return $this;
    }

    /**
     * Get Taboo
     *
     * @return \AppBundle\Entity\Taboo
     */
    public function getTaboo()
    {
        return $this->taboo;
    }

    /**
     * Set usercampaign
     *
     * @param \AppBundle\Entity\Usercampaign $usercampaign
     *
     * @return Deck
     */
    public function setUsercampaign(\AppBundle\Entity\Usercampaign $usercampaign = null)
    {
        $this->usercampaign = $usercampaign;

        return $this;
    }

    /**
     * Get usercampaign
     *
     * @return \AppBundle\Entity\Usercampaign
     */
    public function getUsercampaign()
    {
        return $this->usercampaign;
    }



    /**
     * Set character
     *
     * @param \AppBundle\Entity\Card $character
     *
     * @return Deck
     */
    public function setCharacter(\AppBundle\Entity\Card $character = null)
    {
        $this->character = $character;

        return $this;
    }

    /**
     * Get character
     *
     * @return \AppBundle\Entity\Card
     */
    public function getCharacter()
    {
        return $this->character;
    }

    /**
     * Get faction
     *
     * @return \AppBundle\Entity\Faction
     */
    public function getFaction()
    {
        return $this->character->getFaction();
    }

    /**
     * Set lastPack
     *
     * @param \AppBundle\Entity\Pack $lastPack
     *
     * @return Deck
     */
    public function setLastPack(\AppBundle\Entity\Pack $lastPack = null)
    {
        $this->lastPack = $lastPack;

        return $this;
    }

    /**
     * Get lastPack
     *
     * @return \AppBundle\Entity\Pack
     */
    public function getLastPack()
    {
        return $this->lastPack;
    }

    /**
     * Set parent
     *
     * @param \AppBundle\Entity\Decklist $parent
     *
     * @return Deck
     */
    public function setParent(\AppBundle\Entity\Decklist $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \AppBundle\Entity\Decklist
     */
    public function getParent()
    {
        return $this->parent;
    }
    
    /**
     * Set previousDeck
     *
     * @param \AppBundle\Entity\Deck $previousDeck
     *
     * @return Deck
     */
    public function setPreviousDeck(\AppBundle\Entity\Deck $previousDeck = null)
    {
        $this->previousDeck = $previousDeck;

        return $this;
    }

    /**
     * Get previousDeck
     *
     * @return \AppBundle\Entity\Deck
     */
    public function getPreviousDeck()
    {
        return $this->previousDeck;
    }
    
    /**
     * Set nextDeck
     *
     * @param \AppBundle\Entity\Deck $nextDeck
     *
     * @return Deck
     */
    public function setNextDeck(\AppBundle\Entity\Deck $nextDeck = null)
    {
        $this->nextDeck = $nextDeck;

        return $this;
    }

    /**
     * Get nextDeck
     *
     * @return \AppBundle\Entity\Deck
     */
    public function getNextDeck()
    {
        return $this->nextDeck;
    }

    /**
     * Set majorVersion
     *
     * @param integer $majorVersion
     *
     * @return Deck
     */
    public function setMajorVersion($majorVersion)
    {
        $this->majorVersion = $majorVersion;

        return $this;
    }

    /**
     * Get majorVersion
     *
     * @return integer
     */
    public function getMajorVersion()
    {
        return $this->majorVersion;
    }

    /**
     * Set minorVersion
     *
     * @param integer $minorVersion
     *
     * @return Deck
     */
    public function setMinorVersion($minorVersion)
    {
        $this->minorVersion = $minorVersion;

        return $this;
    }

    /**
     * Get xp
     *
     * @return integer
     */
    public function getXp()
    {
        return $this->xp;
    }
    
    
    /**
     * Set xp
     *
     * @param integer $xp
     *
     * @return Deck
     */
    public function setXp($xp)
    {
        $this->xp = $xp;

        return $this;
    }
    
    
        /**
     * Get xpSpent
     *
     * @return integer
     */
    public function getXpSpent()
    {
        return $this->xpSpent;
    }
    
    
    /**
     * Set xpSpent
     *
     * @param integer $xpSpent
     *
     * @return Deck
     */
    public function setXpSpent($xpSpent)
    {
        $this->xpSpent = $xpSpent;

        return $this;
    }
    
    
    
    /**
     * Get xpSpent
     *
     * @return integer
     */
    public function getXpAdjustment()
    {
        return $this->xpAdjustment;
    }
    
    
    /**
     * Set xpSpent
     *
     * @param integer $xpSpent
     *
     * @return Deck
     */
    public function setXpAdjustment($xpAdjustment)
    {
        $this->xpAdjustment = $xpAdjustment;

        return $this;
    }
    
    /**
     * Get upgrades
     *
     * @return integer
     */
    public function getUpgrades()
    {
        return $this->upgrades;
    }
    
    
    
    
    
    /**
     * Set upgrades
     *
     * @param integer $upgrades
     *
     * @return Deck
     */
    public function setUpgrades($upgrades)
    {
        $this->upgrades = $upgrades;

        return $this;
    }

    /**
     * Get minorVersion
     *
     * @return integer
     */
    public function getMinorVersion()
    {
        return $this->minorVersion;
    }
    
    public function getVersion()
    {
    	return $this->majorVersion . "." . $this->minorVersion;
    }
}
