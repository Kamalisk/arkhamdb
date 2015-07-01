<?php

namespace Alsciende\DeckbuilderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Alsciende\DeckbuilderBundle\Model\DeckInterface;

/**
 * Deck
 *
 * @ORM\MappedSuperclass
 */
abstract class Deck implements DeckInterface
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
     * @ORM\Column(name="name", type="string", length=1024)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description_md", type="text", nullable=true)
     */
    private $descriptionMd;

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
     * @ORM\Column(name="problem", type="string", length=255, nullable=true)
     */
    private $problem;

    /**
     * @var string
     *
     * @ORM\Column(name="tags", type="string", length=255, nullable=true)
     */
    private $tags;


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
     * Set descriptionMd
     *
     * @param string $descriptionMd
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
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
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
     * Set problem
     *
     * @param string $problem
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
     * @ORM\ManyToOne(targetEntity="Alsciende\DeckbuilderBundle\Model\UserInterface")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     **/
    private $user;

    /**
     * Set user
     *
     * @param \Alsciende\DeckbuilderBundle\Model\UserInterface $user
     * @return Deck
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
     * @ORM\JoinColumn(name="parent_decklist_id", referencedColumnName="id")
     **/
    private $parentDecklist;
    
    /**
     * Set parentDecklist
     *
     * @param \Alsciende\DeckbuilderBundle\Model\DecklistInterface $parentDecklist
     * @return Deck
     */
    public function setParentDecklist(\Alsciende\DeckbuilderBundle\Model\DecklistInterface $parentDecklist = null)
    {
    	$this->parentDecklist = $parentDecklist;
    
    	return $this;
    }
    
    /**
     * Get parentDecklist
     *
     * @return \Alsciende\DeckbuilderBundle\Model\DecklistInterface
     */
    public function getParentDecklist()
    {
    	return $this->parentDecklist;
    }
    
}
