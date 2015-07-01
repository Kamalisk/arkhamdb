<?php

namespace Alsciende\DeckbuilderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Alsciende\DeckbuilderBundle\Model\DeckInterface;
use Alsciende\DeckbuilderBundle\Model\CardInterface;

/**
 * Decklistslot
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Decklistslot implements DeckInterface
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
     * @ORM\ManyToOne(targetEntity="Alsciende\DeckbuilderBundle\Model\DecklistInterface")
     * @ORM\JoinColumn(name="decklist_id", referencedColumnName="id")
     **/
    private $decklist;
    
    /**
     * Set decklist
     *
     * @param \Alsciende\DeckbuilderBundle\Model\DecklistInterface $deck
     * @return DecklistInterface
     */
    public function setDecklist(\Alsciende\DeckbuilderBundle\Model\DecklistInterface $decklist = null)
    {
    	$this->decklist = $decklist;
    
    	return $this;
    }
    
    /**
     * Get decklist
     *
     * @return \Alsciende\DeckbuilderBundle\Model\DecklistInterface
     */
    public function getDecklist()
    {
    	return $this->decklist;
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
