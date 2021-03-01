<?php

namespace AppBundle\Entity;

class Decklist extends \AppBundle\Model\ExportableDeck implements \JsonSerializable
{
	/**
	 * @return array
	 */
	public function isOctgnable()
	{
		// run through all cards and see if they have an ocgtn ID
		$slots = $this->getSlots();
		$cards = $slots->getContent();
		foreach($slots as $slot){
			if (!$slot->getCard()->getOctgnID()){
				return false;
			}
		}
		return true;
	}
	/**
	 * @return array
	 */
	public function getUpgrades()
	{

		$upgrades = [];
		$previousDeck = $this->getPreviousDeck();
		while ($previousDeck){
			$slots = $previousDeck->getSlots();
			$cards = $slots->getContent();
			$upgrade = [
					'content' => $cards,
					'exile_string' => $previousDeck->getExiles(),
					'xp' => $previousDeck->getNextDeck()->getXpSpent(),
					'xp_left' => $previousDeck->getNextDeck()->getXp() - $previousDeck->getNextDeck()->getXpSpent() + $previousDeck->getNextDeck()->getXpAdjustment(),
					'xp_adjustment' => $previousDeck->getNextDeck()->getXpAdjustment(),
					'date_creation' => $previousDeck->getDateCreation()->format('c')
			];
			$upgrades[] = $upgrade;
			$previousDeck = $previousDeck->getPreviousDeck();
		}

		return $upgrades;
	}

	public function jsonSerialize()
	{
		$array = parent::getArrayExport();

		return $array;
	}

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
     * @var integer
     */
    private $xp;

     /**
     * @var integer
     */
    private $xpSpent;

    /**
     * @var integer
     */
    private $xpAdjustment;


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
    private $exiles;

    /**
     * @var string
     */
    private $meta;

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
     * @var integer
     */
    private $version;

     /**
     * @var \AppBundle\Entity\Decklist
     */
    private $previousDeck;
    /**
     * @var \AppBundle\Entity\Decklist
     */
    private $nextDeck;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $slots;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $sideSlots;

    /**
     * @var string
     */
    private $tags;

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
     * @var \AppBundle\Entity\Card
     */
    private $character;

    /**
     * @var \AppBundle\Entity\Taboo
     */
    private $taboo;

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
        $this->sideSlots = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set exiles
     *
     * @param string $exiles
     *
     * @return Deck
     */
    public function setExiles($exiles)
    {
        $this->exiles = $exiles;

        return $this;
    }

    /**
     * Get exiles
     *
     * @return string
     */
    public function getExiles()
    {
        return $this->exiles;
    }

    /**
     * Set meta
     *
     * @param string $meta
     *
     * @return Deck
     */
    public function setMeta($meta)
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * Get meta
     *
     * @return string
     */
    public function getMeta()
    {
        return $this->meta;
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
     * Set tags
     *
     * @param string $tags
     *
     * @return Decklist
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
     * Add slot
     *
     * @param \AppBundle\Entity\SideDecklistSlot $sideSlot
     *
     * @return Deck
     */
    public function addSideSlot(\AppBundle\Entity\SideDecklistSlot $slot)
    {
        $this->sideSlots[] = $slot;

        return $this;
    }

    /**
     * Remove slot
     *
     * @param \AppBundle\Entity\SideDecklistSlot $slot
     */
    public function removeSideSlot(\AppBundle\Entity\SideDecklistSlot $slot)
    {
        $this->sideSlots->removeElement($slot);
    }

    /**
     * Get slots
     *
     * @return \AppBundle\Model\SlotCollectionInterface
     */
    public function getSideSlots()
    {
        return new \AppBundle\Model\SlotCollectionDecorator($this->sideSlots);
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
     * Set previousDeck
     *
     * @param \AppBundle\Entity\Decklist $previousDeck
     *
     * @return Deck
     */
    public function setPreviousDeck(\AppBundle\Entity\Decklist $previousDeck = null)
    {
        $this->previousDeck = $previousDeck;

        return $this;
    }

    /**
     * Get previousDeck
     *
     * @return \AppBundle\Entity\Decklist
     */
    public function getPreviousDeck()
    {
        return $this->previousDeck;
    }

    /**
     * Set nextDeck
     *
     * @param \AppBundle\Entity\Decklist $nextDeck
     *
     * @return Deck
     */
    public function setNextDeck(\AppBundle\Entity\Decklist $nextDeck = null)
    {
        $this->nextDeck = $nextDeck;

        return $this;
    }

    /**
     * Get nextDeck
     *
     * @return \AppBundle\Entity\Decklist
     */
    public function getNextDeck()
    {
        return $this->nextDeck;
    }


    /**
     * Get xp
     *
     * @return integer
     */
    public function getXp()
    {
        return $this->xp;
    }


    /**
     * Set xp
     *
     * @param integer $xp
     *
     * @return Deck
     */
    public function setXp($xp)
    {
        $this->xp = $xp;

        return $this;
    }


        /**
     * Get xpSpent
     *
     * @return integer
     */
    public function getXpSpent()
    {
        return $this->xpSpent;
    }


    /**
     * Set xpSpent
     *
     * @param integer $xpSpent
     *
     * @return Deck
     */
    public function setXpSpent($xpSpent)
    {
        $this->xpSpent = $xpSpent;

        return $this;
    }


    /**
     * Get xpSpent
     *
     * @return integer
     */
    public function getXpAdjustment()
    {
        return $this->xpAdjustment;
    }


    /**
     * Set xpSpent
     *
     * @param integer $xpSpent
     *
     * @return Deck
     */
    public function setXpAdjustment($xpAdjustment)
    {
        $this->xpAdjustment = $xpAdjustment;

        return $this;
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
     * Set user
     *
     * @param \AppBundle\Entity\Taboo $taboo
     *
     * @return Deck
     */
    public function setTaboo(\AppBundle\Entity\Taboo $taboo = null)
    {
        $this->taboo = $taboo;

        return $this;
    }

    /**
     * Get Taboo
     *
     * @return \AppBundle\Entity\Taboo
     */
    public function getTaboo()
    {
        return $this->taboo;
    }


    /**
     * Set character
     *
     * @param \AppBundle\Entity\card $character
     *
     * @return Decklist
     */
    public function setCharacter(\AppBundle\Entity\card $character = null)
    {
        $this->character = $character;

        return $this;
    }

    /**
     * Get character
     *
     * @return \AppBundle\Entity\card
     */
    public function getCharacter()
    {
        return $this->character;
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

    /**
     * Set version
     *
     * @param string $version
     *
     * @return Decklist
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }
}
