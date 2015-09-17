<?php

namespace AppBundle\Entity;

/**
 * Deckchange
 */
class Deckchange
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $dateCreation;

    /**
     * @var string
     */
    private $variation;

    /**
     * @var boolean
     */
    private $isSaved;

    /**
     * @var \AppBundle\Entity\Deck
     */
    private $deck;


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
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     *
     * @return Deckchange
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
     * Set variation
     *
     * @param string $variation
     *
     * @return Deckchange
     */
    public function setVariation($variation)
    {
        $this->variation = $variation;

        return $this;
    }

    /**
     * Get variation
     *
     * @return string
     */
    public function getVariation()
    {
        return $this->variation;
    }

    /**
     * Set isSaved
     *
     * @param boolean $isSaved
     *
     * @return Deckchange
     */
    public function setIsSaved($isSaved)
    {
        $this->isSaved = $isSaved;

        return $this;
    }

    /**
     * Get isSaved
     *
     * @return boolean
     */
    public function getIsSaved()
    {
        return $this->isSaved;
    }

    /**
     * Set deck
     *
     * @param \AppBundle\Entity\Deck $deck
     *
     * @return Deckchange
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
}

