<?php 

namespace AppBundle\Entity;

class Encounter implements \Gedmo\Translatable\Translatable, \Serializable
{
	public function serialize() {
		return [
				'code' => $this->code,
				'name' => $this->name
		];
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
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $cards;
    
     /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $scenarios;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cards = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set code
     *
     * @param string $code
     *
     * @return Type
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
     * @return Type
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
     * Add card
     *
     * @param \AppBundle\Entity\Card $card
     *
     * @return Type
     */
    public function addCard(\AppBundle\Entity\Card $card)
    {
        $this->cards[] = $card;

        return $this;
    }

    /**
     * Remove card
     *
     * @param \AppBundle\Entity\Card $card
     */
    public function removeCard(\AppBundle\Entity\Card $card)
    {
        $this->cards->removeElement($card);
    }

    /**
     * Get cards
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCards()
    {
        return $this->cards;
    }

    /**
     * Add scenario.
     *
     * @param \AppBundle\Entity\Scenario $scenario
     *
     * @return Encounter
     */
    public function addScenario(\AppBundle\Entity\Scenario $scenario)
    {
        $this->scenarios[] = $scenario;

        return $this;
    }

    /**
     * Remove scenario.
     *
     * @param \AppBundle\Entity\Scenario $scenario
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeScenario(\AppBundle\Entity\Scenario $scenario)
    {
        return $this->scenarios->removeElement($scenario);
    }

    /**
     * Get scenarios.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getScenarios()
    {
        return $this->scenarios;
    }

    /*
     * I18N vars
     */
    private $locale = 'en';

    public function setTranslatableLocale($locale)
    {
       $this->locale = $locale;
    }
}
