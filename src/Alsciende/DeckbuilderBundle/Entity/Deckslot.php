<?php

namespace Alsciende\DeckbuilderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Alsciende\DeckbuilderBundle\Model\DeckInterface;
use Alsciende\DeckbuilderBundle\Model\CardInterface;

/**
 * Deckslot
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Deckslot implements DeckInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

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
     * @var integer
     *
     * @ORM\Column(name="number", type="smallint")
     */
    private $quantity;
    
    /**
     * Set quantity
     *
     * @param integer $quantity
     * @return Cycle
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
     * @ORM\ManyToOne(targetEntity="Alsciende\DeckbuilderBundle\Model\DeckInterface")
     * @ORM\JoinColumn(name="deck_id", referencedColumnName="id")
     **/
    private $deck;
    
    /**
     * Set deck
     *
     * @param \Alsciende\DeckbuilderBundle\Model\DeckInterface $deck
     * @return DeckInterface
     */
    public function setDeck(\Alsciende\DeckbuilderBundle\Model\DeckInterface $deck = null)
    {
    	$this->deck = $deck;
    
    	return $this;
    }
    
    /**
     * Get deck
     *
     * @return \Alsciende\DeckbuilderBundle\Model\DeckInterface
     */
    public function getDeck()
    {
    	return $this->deck;
    }

    /**
     * @ORM\ManyToOne(targetEntity="Alsciende\DeckbuilderBundle\Model\CardInterface")
     * @ORM\JoinColumn(name="card_id", referencedColumnName="id")
     **/
    private $card;
    
    /**
     * Set card
     *
     * @param \Alsciende\DeckbuilderBundle\Model\CardInterface $card
     * @return CardInterface
     */
    public function setCard(\Alsciende\DeckbuilderBundle\Model\CardInterface $card = null)
    {
    	$this->card = $card;
    
    	return $this;
    }
    
    /**
     * Get card
     *
     * @return \Alsciende\DeckbuilderBundle\Model\CardInterface
     */
    public function getCard()
    {
    	return $this->card;
    }
    
}
