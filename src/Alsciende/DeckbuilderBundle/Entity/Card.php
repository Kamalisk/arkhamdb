<?php

namespace Alsciende\DeckbuilderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Alsciende\DeckbuilderBundle\Model\CardInterface;

/**
 * Card
 *
 * @ORM\MappedSuperclass
 */
abstract class Card implements CardInterface
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
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=1024)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="text", nullable=true)
     */
    private $text;

    /**
     * @var integer
     *
     * @ORM\Column(name="cost", type="smallint", nullable=true)
     */
    private $cost;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_creation", type="datetime")
     */
    private $dateCreation;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_update", type="datetime")
     */
    private $dateUpdate;

    /**
     * @var integer
     *
     * @ORM\Column(name="position", type="smallint")
     */
    private $position;


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
     * Set text
     *
     * @param string $text
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
     * Set cost
     *
     * @param integer $cost
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
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
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
     * Set position
     *
     * @param integer $position
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
     * @ORM\ManyToOne(targetEntity="Alsciende\DeckbuilderBundle\Model\PackInterface")
     * @ORM\JoinColumn(name="pack_id", referencedColumnName="id")
     **/
    private $pack;
    
    /**
     * Set pack
     *
     * @param \Alsciende\DeckbuilderBundle\Model\PackInterface $pack
     * @return Card
     */
    public function setPack(\Alsciende\DeckbuilderBundle\Model\PackInterface $pack = null)
    {
        $this->pack = $pack;

        return $this;
    }

    /**
     * Get pack
     *
     * @return \Alsciende\DeckbuilderBundle\Model\PackInterface
     */
    public function getPack()
    {
        return $this->pack;
    }

    /**
     * @ORM\ManyToOne(targetEntity="Alsciende\DeckbuilderBundle\Model\TypeInterface")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="id")
     **/
    private $type;

    /**
     * Set type
     *
     * @param \Alsciende\DeckbuilderBundle\Model\TypeInterface $type
     * @return Card
     */
    public function setType(\Alsciende\DeckbuilderBundle\Model\TypeInterface $type = null)
    {
    	$this->type = $type;
    
    	return $this;
    }
    
    /**
     * Get type
     *
     * @return \Alsciende\DeckbuilderBundle\Model\TypeInterface
     */
    public function getType()
    {
    	return $this->type;
    }

    /**
     * @ORM\ManyToOne(targetEntity="Alsciende\DeckbuilderBundle\Model\FactionInterface")
     * @ORM\JoinColumn(name="faction_id", referencedColumnName="id")
     **/
    private $faction;
    
    /**
     * Set faction
     *
     * @param \Alsciende\DeckbuilderBundle\Model\FactionInterface $faction
     * @return Card
     */
    public function setFaction(\Alsciende\DeckbuilderBundle\Model\FactionInterface $faction = null)
    {
    	$this->faction = $faction;
    
    	return $this;
    }
    
    /**
     * Get faction
     *
     * @return \Alsciende\DeckbuilderBundle\Model\FactionInterface
     */
    public function getFaction()
    {
    	return $this->faction;
    }
    
}
