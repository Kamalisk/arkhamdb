<?php

namespace AppBundle\Entity;

class Decklist extends \AppBundle\Model\ExportableDeck implements \AppBundle\Model\ExportableDeckInterface
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $nameCanonical;

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
    private $descriptionHtml;

    /**
     * @var string
     */
    private $signature;

    /**
     * @var integer
     */
    private $nbVotes;

    /**
     * @var integer
     */
    private $nbFavorites;

    /**
     * @var integer
     */
    private $nbComments;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $slots;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $comments;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $successors;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $children;

    /**
     * @var \AppBundle\Entity\User
     */
    private $user;

    /**
     * @var \AppBundle\Entity\Faction
     */
    private $faction;

    /**
     * @var \AppBundle\Entity\Pack
     */
    private $lastPack;

    /**
     * @var \AppBundle\Entity\Deck
     */
    private $parent;

    /**
     * @var \AppBundle\Entity\Decklist
     */
    private $precedent;

    /**
     * @var \AppBundle\Entity\Tournament
     */
    private $tournament;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $favorites;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $votes;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->slots = new \Doctrine\Common\Collections\ArrayCollection();
        $this->comments = new \Doctrine\Common\Collections\ArrayCollection();
        $this->successors = new \Doctrine\Common\Collections\ArrayCollection();
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->favorites = new \Doctrine\Common\Collections\ArrayCollection();
        $this->votes = new \Doctrine\Common\Collections\ArrayCollection();
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
     *
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
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     *
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
     *
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
     * Set descriptionMd
     *
     * @param string $descriptionMd
     *
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
     *
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
     * Set signature
     *
     * @param string $signature
     *
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
     *
     * @return Decklist
     */
    public function setnbVotes($nbVotes)
    {
        $this->nbVotes = $nbVotes;

        return $this;
    }

    /**
     * Get nbVotes
     *
     * @return integer
     */
    public function getnbVotes()
    {
        return $this->nbVotes;
    }

    /**
     * Set nbFavorites
     *
     * @param integer $nbFavorites
     *
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
     *
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
     * Add slot
     *
     * @param \AppBundle\Entity\Decklistslot $slot
     *
     * @return Decklist
     */
    public function addSlot(\AppBundle\Entity\Decklistslot $slot)
    {
        $this->slots[] = $slot;

        return $this;
    }

    /**
     * Remove slot
     *
     * @param \AppBundle\Entity\Decklistslot $slot
     */
    public function removeSlot(\AppBundle\Entity\Decklistslot $slot)
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
     * Add comment
     *
     * @param \AppBundle\Entity\Comment $comment
     *
     * @return Decklist
     */
    public function addComment(\AppBundle\Entity\Comment $comment)
    {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove comment
     *
     * @param \AppBundle\Entity\Comment $comment
     */
    public function removeComment(\AppBundle\Entity\Comment $comment)
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Add successor
     *
     * @param \AppBundle\Entity\Decklist $successor
     *
     * @return Decklist
     */
    public function addSuccessor(\AppBundle\Entity\Decklist $successor)
    {
        $this->successors[] = $successor;

        return $this;
    }

    /**
     * Remove successor
     *
     * @param \AppBundle\Entity\Decklist $successor
     */
    public function removeSuccessor(\AppBundle\Entity\Decklist $successor)
    {
        $this->successors->removeElement($successor);
    }

    /**
     * Get successors
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSuccessors()
    {
        return $this->successors;
    }

    /**
     * Add child
     *
     * @param \AppBundle\Entity\Deck $child
     *
     * @return Decklist
     */
    public function addChild(\AppBundle\Entity\Deck $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param \AppBundle\Entity\Deck $child
     */
    public function removeChild(\AppBundle\Entity\Deck $child)
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
     * Set user
     *
     * @param \AppBundle\Entity\User $user
     *
     * @return Decklist
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
     * Set faction
     *
     * @param \AppBundle\Entity\Faction $faction
     *
     * @return Decklist
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

    /**
     * Set lastPack
     *
     * @param \AppBundle\Entity\Pack $lastPack
     *
     * @return Decklist
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
     * @param \AppBundle\Entity\Deck $parent
     *
     * @return Decklist
     */
    public function setParent(\AppBundle\Entity\Deck $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \AppBundle\Entity\Deck
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set precedent
     *
     * @param \AppBundle\Entity\Decklist $precedent
     *
     * @return Decklist
     */
    public function setPrecedent(\AppBundle\Entity\Decklist $precedent = null)
    {
        $this->precedent = $precedent;

        return $this;
    }

    /**
     * Get precedent
     *
     * @return \AppBundle\Entity\Decklist
     */
    public function getPrecedent()
    {
        return $this->precedent;
    }

    /**
     * Set tournament
     *
     * @param \AppBundle\Entity\Tournament $tournament
     *
     * @return Decklist
     */
    public function setTournament(\AppBundle\Entity\Tournament $tournament = null)
    {
        $this->tournament = $tournament;

        return $this;
    }

    /**
     * Get tournament
     *
     * @return \AppBundle\Entity\Tournament
     */
    public function getTournament()
    {
        return $this->tournament;
    }

    /**
     * Add favorite
     *
     * @param \AppBundle\Entity\User $favorite
     *
     * @return Decklist
     */
    public function addFavorite(\AppBundle\Entity\User $favorite)
    {
        $this->favorites[] = $favorite;

        return $this;
    }

    /**
     * Remove favorite
     *
     * @param \AppBundle\Entity\User $favorite
     */
    public function removeFavorite(\AppBundle\Entity\User $favorite)
    {
        $this->favorites->removeElement($favorite);
    }

    /**
     * Get favorites
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFavorites()
    {
        return $this->favorites;
    }

    /**
     * Add vote
     *
     * @param \AppBundle\Entity\User $vote
     *
     * @return Decklist
     */
    public function addVote(\AppBundle\Entity\User $vote)
    {
        $this->votes[] = $vote;

        return $this;
    }

    /**
     * Remove vote
     *
     * @param \AppBundle\Entity\User $vote
     */
    public function removeVote(\AppBundle\Entity\User $vote)
    {
        $this->votes->removeElement($vote);
    }

    /**
     * Get votes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVotes()
    {
        return $this->votes;
    }
}
