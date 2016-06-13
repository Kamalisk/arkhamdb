<?php

namespace AppBundle\Entity;

class Card implements \Serializable
{
	private function snakeToCamel($snake) {
		$parts = explode('_', $snake);
		return implode('', array_map('ucfirst', $parts));
	}
	
	public function serialize() {
		$serialized = [];
		if(empty($this->code)) return $serialized;
	
		$mandatoryFields = [
				'code',
				'deck_limit',
				'position',
				'quantity',
				'name',
				'is_loyal',
				'is_unique'
		];
	
		$optionalFields = [
				'illustrator',
				'flavor',
				'traits',
				'text',
				'cost',
				'octgn_id'
		];
	
		$externalFields = [
				'faction',
				'pack',
				'type'
		];
	
		switch($this->type->getCode()) {
			case 'agenda':
			case 'title':
				break;
			case 'attachment':
			case 'event':
			case 'location':
				$mandatoryFields[] = 'cost';
				break;
			case 'character':
				$mandatoryFields[] = 'cost';
				$mandatoryFields[] = 'strength';
				$mandatoryFields[] = 'is_military';
				$mandatoryFields[] = 'is_intrigue';
				$mandatoryFields[] = 'is_power';
				break;
			case 'plot':
				$mandatoryFields[] = 'claim';
				$mandatoryFields[] = 'income';
				$mandatoryFields[] = 'initiative';
				$mandatoryFields[] = 'reserve';
				break;
		}
	
		foreach($optionalFields as $optionalField) {
			$getter = 'get' . $this->snakeToCamel($optionalField);
			$serialized[$optionalField] = $this->$getter();
			if(!isset($serialized[$optionalField]) || $serialized[$optionalField] === '') unset($serialized[$optionalField]);
		}
	
		foreach($mandatoryFields as $mandatoryField) {
			$getter = 'get' . $this->snakeToCamel($mandatoryField);
			$serialized[$mandatoryField] = $this->$getter();
		}
	
		foreach($externalFields as $externalField) {
			$getter = 'get' . $this->snakeToCamel($externalField);
			$serialized[$externalField.'_code'] = $this->$getter()->getCode();
		}
	
		ksort($serialized);
		return $serialized;
	}

	public function unserialize($serialized) {
		throw new \Exception("unserialize() method unsupported");
	}
	
    public function toString() {
		return $this->name;
	}
	
	/**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $position;

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $name;

    /**
     * @var integer
     */
    private $cost;

    /**
     * @var string
     */
    private $text;

    /**
     * @var \DateTime
     */
    private $dateCreation;

    /**
     * @var \DateTime
     */
    private $dateUpdate;

    /**
     * @var integer
     */
    private $quantity;

    /**
     * @var integer
     */
    private $income;

    /**
     * @var integer
     */
    private $initiative;

    /**
     * @var integer
     */
    private $claim;

    /**
     * @var integer
     */
    private $reserve;

    /**
     * @var integer
     */
    private $deckLimit;

    /**
     * @var integer
     */
    private $strength;

    /**
     * @var string
     */
    private $traits;

    /**
     * @var string
     */
    private $flavor;

    /**
     * @var string
     */
    private $illustrator;

    /**
     * @var boolean
     */
    private $isUnique;

    /**
     * @var boolean
     */
    private $isLoyal;

    /**
     * @var boolean
     */
    private $isMilitary;

    /**
     * @var boolean
     */
    private $isIntrigue;

    /**
     * @var boolean
     */
    private $isPower;

    /**
     * @var string
     */
    private $octgnId;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $reviews;

    /**
     * @var \AppBundle\Entity\Pack
     */
    private $pack;

    /**
     * @var \AppBundle\Entity\Type
     */
    private $type;

    /**
     * @var \AppBundle\Entity\Faction
     */
    private $faction;

    /**
     * Constructor
     */
    public function __construct()
    {
    	$this->isMilitary = false;
    	$this->isIntrigue = false;
    	$this->isPower = false;
    	 
        $this->reviews = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set position
     *
     * @param integer $position
     *
     * @return Card
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return Card
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Card
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
     * Set cost
     *
     * @param integer $cost
     *
     * @return Card
     */
    public function setCost($cost)
    {
        $this->cost = $cost;

        return $this;
    }

    /**
     * Get cost
     *
     * @return integer
     */
    public function getCost()
    {
        return $this->cost;
    }

    /**
     * Set text
     *
     * @param string $text
     *
     * @return Card
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     *
     * @return Card
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
     * @return Card
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
     * Set quantity
     *
     * @param integer $quantity
     *
     * @return Card
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return integer
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set income
     *
     * @param integer $income
     *
     * @return Card
     */
    public function setIncome($income)
    {
        $this->income = $income;

        return $this;
    }

    /**
     * Get income
     *
     * @return integer
     */
    public function getIncome()
    {
        return $this->income;
    }

    /**
     * Set initiative
     *
     * @param integer $initiative
     *
     * @return Card
     */
    public function setInitiative($initiative)
    {
        $this->initiative = $initiative;

        return $this;
    }

    /**
     * Get initiative
     *
     * @return integer
     */
    public function getInitiative()
    {
        return $this->initiative;
    }

    /**
     * Set claim
     *
     * @param integer $claim
     *
     * @return Card
     */
    public function setClaim($claim)
    {
        $this->claim = $claim;

        return $this;
    }

    /**
     * Get claim
     *
     * @return integer
     */
    public function getClaim()
    {
        return $this->claim;
    }

    /**
     * Set reserve
     *
     * @param integer $reserve
     *
     * @return Card
     */
    public function setReserve($reserve)
    {
        $this->reserve = $reserve;

        return $this;
    }

    /**
     * Get reserve
     *
     * @return integer
     */
    public function getReserve()
    {
        return $this->reserve;
    }

    /**
     * Set deckLimit
     *
     * @param integer $deckLimit
     *
     * @return Card
     */
    public function setDeckLimit($deckLimit)
    {
        $this->deckLimit = $deckLimit;

        return $this;
    }

    /**
     * Get deckLimit
     *
     * @return integer
     */
    public function getDeckLimit()
    {
        return $this->deckLimit;
    }

    /**
     * Set strength
     *
     * @param integer $strength
     *
     * @return Card
     */
    public function setStrength($strength)
    {
        $this->strength = $strength;

        return $this;
    }

    /**
     * Get strength
     *
     * @return integer
     */
    public function getStrength()
    {
        return $this->strength;
    }

    /**
     * Set traits
     *
     * @param string $traits
     *
     * @return Card
     */
    public function setTraits($traits)
    {
        $this->traits = $traits;

        return $this;
    }

    /**
     * Get traits
     *
     * @return string
     */
    public function getTraits()
    {
        return $this->traits;
    }

    /**
     * Set flavor
     *
     * @param string $flavor
     *
     * @return Card
     */
    public function setFlavor($flavor)
    {
        $this->flavor = $flavor;

        return $this;
    }

    /**
     * Get flavor
     *
     * @return string
     */
    public function getFlavor()
    {
        return $this->flavor;
    }

    /**
     * Set illustrator
     *
     * @param string $illustrator
     *
     * @return Card
     */
    public function setIllustrator($illustrator)
    {
        $this->illustrator = $illustrator;

        return $this;
    }

    /**
     * Get illustrator
     *
     * @return string
     */
    public function getIllustrator()
    {
        return $this->illustrator;
    }

    /**
     * Set isUnique
     *
     * @param boolean $isUnique
     *
     * @return Card
     */
    public function setIsUnique($isUnique)
    {
        $this->isUnique = $isUnique;

        return $this;
    }

    /**
     * Get isUnique
     *
     * @return boolean
     */
    public function getIsUnique()
    {
        return $this->isUnique;
    }

    /**
     * Set isLoyal
     *
     * @param boolean $isLoyal
     *
     * @return Card
     */
    public function setIsLoyal($isLoyal)
    {
        $this->isLoyal = $isLoyal;

        return $this;
    }

    /**
     * Get isLoyal
     *
     * @return boolean
     */
    public function getIsLoyal()
    {
        return $this->isLoyal;
    }

    /**
     * Set isMilitary
     *
     * @param boolean $isMilitary
     *
     * @return Card
     */
    public function setIsMilitary($isMilitary)
    {
        $this->isMilitary = $isMilitary;

        return $this;
    }

    /**
     * Get isMilitary
     *
     * @return boolean
     */
    public function getIsMilitary()
    {
        return $this->isMilitary;
    }

    /**
     * Set isIntrigue
     *
     * @param boolean $isIntrigue
     *
     * @return Card
     */
    public function setIsIntrigue($isIntrigue)
    {
        $this->isIntrigue = $isIntrigue;

        return $this;
    }

    /**
     * Get isIntrigue
     *
     * @return boolean
     */
    public function getIsIntrigue()
    {
        return $this->isIntrigue;
    }

    /**
     * Set isPower
     *
     * @param boolean $isPower
     *
     * @return Card
     */
    public function setIsPower($isPower)
    {
        $this->isPower = $isPower;

        return $this;
    }

    /**
     * Get isPower
     *
     * @return boolean
     */
    public function getIsPower()
    {
        return $this->isPower;
    }

    /**
     * Set octgnId
     *
     * @param boolean $octgnId
     *
     * @return Card
     */
    public function setOctgnId($octgnId)
    {
        $this->octgnId = $octgnId;

        return $this;
    }

    /**
     * Get octgnId
     *
     * @return boolean
     */
    public function getOctgnId()
    {
        return $this->octgnId;
    }

    /**
     * Add review
     *
     * @param \AppBundle\Entity\Review $review
     *
     * @return Card
     */
    public function addReview(\AppBundle\Entity\Review $review)
    {
        $this->reviews[] = $review;

        return $this;
    }

    /**
     * Remove review
     *
     * @param \AppBundle\Entity\Review $review
     */
    public function removeReview(\AppBundle\Entity\Review $review)
    {
        $this->reviews->removeElement($review);
    }

    /**
     * Get reviews
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReviews()
    {
        return $this->reviews;
    }

    /**
     * Set pack
     *
     * @param \AppBundle\Entity\Pack $pack
     *
     * @return Card
     */
    public function setPack(\AppBundle\Entity\Pack $pack = null)
    {
        $this->pack = $pack;

        return $this;
    }

    /**
     * Get pack
     *
     * @return \AppBundle\Entity\Pack
     */
    public function getPack()
    {
        return $this->pack;
    }

    /**
     * Set type
     *
     * @param \AppBundle\Entity\Type $type
     *
     * @return Card
     */
    public function setType(\AppBundle\Entity\Type $type = null)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return \AppBundle\Entity\Type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set faction
     *
     * @param \AppBundle\Entity\Faction $faction
     *
     * @return Card
     */
    public function setFaction(\AppBundle\Entity\Faction $faction = null)
    {
        $this->faction = $faction;

        return $this;
    }

    /**
     * Get faction
     *
     * @return \AppBundle\Entity\Faction
     */
    public function getFaction()
    {
        return $this->faction;
    }
}
