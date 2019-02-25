<?php

namespace AppBundle\Entity;

class Deckslot implements \AppBundle\Model\SlotInterface
{
	
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $quantity;

    /**
     * @var integer
     */
    private $ignoreDeckLimit;


    /**
     * @var \AppBundle\Entity\Deck
     */
    private $deck;

    /**
     * @var \AppBundle\Entity\Card
     */
    private $card;


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
     * Set quantity
     *
     * @param integer $quantity
     *
     * @return Deckslot
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
     * Set ignoreDeckLimit
     *
     * @param integer $ignoreDeckLimit
     *
     * @return Deckslot
     */
    public function setIgnoreDeckLimit($ignoreDeckLimit)
    {
        $this->ignoreDeckLimit = $ignoreDeckLimit;

        return $this;
    }

    /**
     * Get ignoreDeckLimit
     *
     * @return integer
     */
    public function getIgnoreDeckLimit()
    {
        return $this->ignoreDeckLimit;
    }
    
    
    /**
     * Set deck
     *
     * @param \AppBundle\Entity\Deck $deck
     *
     * @return Deckslot
     */
    public function setDeck(\AppBundle\Entity\Deck $deck = null)
    {
        $this->deck = $deck;

        return $this;
    }

    /**
     * Get deck
     *
     * @return \AppBundle\Entity\Deck
     */
    public function getDeck()
    {
        return $this->deck;
    }

    /**
     * Set card
     *
     * @param \AppBundle\Entity\Card $card
     *
     * @return Deckslot
     */
    public function setCard(\AppBundle\Entity\Card $card = null)
    {
        $this->card = $card;

        return $this;
    }

    /**
     * Get card
     *
     * @return \AppBundle\Entity\Card
     */
    public function getCard()
    {
        return $this->card;
    }
}
