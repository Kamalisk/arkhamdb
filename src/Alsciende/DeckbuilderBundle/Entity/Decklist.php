<?php

namespace Alsciende\DeckbuilderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Alsciende\DeckbuilderBundle\Model\DecklistInterface;

/**
 * Decklist
 *
 * @ORM\MappedSuperclass
 */
abstract class Decklist implements DecklistInterface
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
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="name_canonical", type="string", length=255)
     */
    private $nameCanonical;

    /**
     * @var string
     *
     * @ORM\Column(name="description_md", type="text", nullable=true)
     */
    private $descriptionMd;

    /**
     * @var string
     *
     * @ORM\Column(name="description_html", type="text", nullable=true)
     */
    private $descriptionHtml;

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
     * @var string
     *
     * @ORM\Column(name="signature", type="string", length=255)
     */
    private $signature;

    /**
     * @var integer
     *
     * @ORM\Column(name="nb_votes", type="smallint")
     */
    private $nbVotes;

    /**
     * @var integer
     *
     * @ORM\Column(name="nb_favorites", type="smallint")
     */
    private $nbFavorites;

    /**
     * @var integer
     *
     * @ORM\Column(name="nb_comments", type="smallint")
     */
    private $nbComments;


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
     * @return Decklist
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
     * Set nameCanonical
     *
     * @param string $nameCanonical
     * @return Decklist
     */
    public function setNameCanonical($nameCanonical)
    {
        $this->nameCanonical = $nameCanonical;

        return $this;
    }

    /**
     * Get nameCanonical
     *
     * @return string 
     */
    public function getNameCanonical()
    {
        return $this->nameCanonical;
    }

    /**
     * Set descriptionMd
     *
     * @param string $descriptionMd
     * @return Decklist
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
     * Set descriptionHtml
     *
     * @param string $descriptionHtml
     * @return Decklist
     */
    public function setDescriptionHtml($descriptionHtml)
    {
        $this->descriptionHtml = $descriptionHtml;

        return $this;
    }

    /**
     * Get descriptionHtml
     *
     * @return string 
     */
    public function getDescriptionHtml()
    {
        return $this->descriptionHtml;
    }

    /**
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     * @return Decklist
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
     * @return Decklist
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
     * Set signature
     *
     * @param string $signature
     * @return Decklist
     */
    public function setSignature($signature)
    {
        $this->signature = $signature;

        return $this;
    }

    /**
     * Get signature
     *
     * @return string 
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * Set nbVotes
     *
     * @param integer $nbVotes
     * @return Decklist
     */
    public function setNbVotes($nbVotes)
    {
        $this->nbVotes = $nbVotes;

        return $this;
    }

    /**
     * Get nbVotes
     *
     * @return integer 
     */
    public function getNbVotes()
    {
        return $this->nbVotes;
    }

    /**
     * Set nbFavorites
     *
     * @param integer $nbFavorites
     * @return Decklist
     */
    public function setNbFavorites($nbFavorites)
    {
        $this->nbFavorites = $nbFavorites;

        return $this;
    }

    /**
     * Get nbFavorites
     *
     * @return integer 
     */
    public function getNbFavorites()
    {
        return $this->nbFavorites;
    }

    /**
     * Set nbComments
     *
     * @param integer $nbComments
     * @return Decklist
     */
    public function setNbComments($nbComments)
    {
        $this->nbComments = $nbComments;

        return $this;
    }

    /**
     * Get nbComments
     *
     * @return integer 
     */
    public function getNbComments()
    {
        return $this->nbComments;
    }

    /**
     * @ORM\ManyToOne(targetEntity="Alsciende\DeckbuilderBundle\Model\UserInterface")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     **/
    private $user;
    
    /**
     * Set user
     *
     * @param \Alsciende\DeckbuilderBundle\Model\UserInterface $user
     * @return Card
     */
    public function setUser(\Alsciende\DeckbuilderBundle\Model\UserInterface $user = null)
    {
    	$this->user = $user;
    
    	return $this;
    }
    
    /**
     * Get user
     *
     * @return \Alsciende\DeckbuilderBundle\Model\UserInterface
     */
    public function getUser()
    {
    	return $this->user;
    }

    /**
     * @ORM\ManyToOne(targetEntity="Alsciende\DeckbuilderBundle\Model\DecklistInterface")
     * @ORM\JoinColumn(name="precedent_decklist_id", referencedColumnName="id")
     **/
    private $precedentDecklist;
    
    /**
     * Set precedentDecklist
     *
     * @param \Alsciende\DeckbuilderBundle\Model\DecklistInterface $precedentDecklist
     * @return Deck
     */
    public function setPrecedentDecklist(\Alsciende\DeckbuilderBundle\Model\DecklistInterface $precedentDecklist = null)
    {
    	$this->precedentDecklist = $precedentDecklist;
    
    	return $this;
    }
    
    /**
     * Get precedentDecklist
     *
     * @return \Alsciende\DeckbuilderBundle\Model\DecklistInterface
     */
    public function getPrecedentDecklist()
    {
    	return $this->precedentDecklist;
    }

    /**
     * @ORM\ManyToOne(targetEntity="Alsciende\DeckbuilderBundle\Model\DeckInterface")
     * @ORM\JoinColumn(name="parent_deck_id", referencedColumnName="id")
     **/
    private $parentDeck;
    
    /**
     * Set parentDeck
     *
     * @param \Alsciende\DeckbuilderBundle\Model\DeckInterface $parentDeck
     * @return Deck
     */
    public function setParentDeck(\Alsciende\DeckbuilderBundle\Model\DeckInterface $parentDeck = null)
    {
    	$this->parentDeck = $parentDeck;
    
    	return $this;
    }
    
    /**
     * Get parentDeck
     *
     * @return \Alsciende\DeckbuilderBundle\Model\DeckInterface
     */
    public function getParentDeck()
    {
    	return $this->parentDeck;
    }
}
