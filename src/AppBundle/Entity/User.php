<?php

namespace AppBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;

/**
 * User
 */
class User extends BaseUser
{
	public function getMaxNbDecks()
	{
		return 2*(100+floor($this->reputation/ 10));
	}

    /**
     * @var \DateTime
     */
    private $dateCreation;

    /**
     * @var \DateTime
     */
    private $dateUpdate;

    /**
     * @var integer
     */
    private $reputation;

    /**
     * @var string
     */
    private $resume;

    /**
     * @var string
     */
    private $color;

    /**
     * @var integer
     */
    private $donation;

    /**
     * @var boolean
     */
    private $isNotifAuthor = true;

    /**
     * @var boolean
     */
    private $isNotifCommenter = true;

    /**
     * @var boolean
     */
    private $isNotifMention = true;

    /**
     * @var boolean
     */
    private $isNotifFollow = true;

    /**
     * @var boolean
     */
    private $isNotifSuccessor = true;

    /**
     * @var boolean
     */
    private $isShareDecks = false;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $decks;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $decklists;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $comments;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $reviews;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $favorites;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $votes;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $reviewvotes;

	public function __construct()
	{
		parent::__construct();

		$this->reputation = 1;
		$this->donation = 0;
	}

    /**
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     *
     * @return User
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
     * @return User
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
     * Set reputation
     *
     * @param integer $reputation
     *
     * @return User
     */
    public function setReputation($reputation)
    {
        $this->reputation = $reputation;

        return $this;
    }

    /**
     * Get reputation
     *
     * @return integer
     */
    public function getReputation()
    {
        return $this->reputation;
    }

    /**
     * Set resume
     *
     * @param string $resume
     *
     * @return User
     */
    public function setResume($resume)
    {
        $this->resume = $resume;

        return $this;
    }

    /**
     * Get resume
     *
     * @return string
     */
    public function getResume()
    {
        return $this->resume;
    }

    /**
     * Set color
     *
     * @param string $color
     *
     * @return User
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get color
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set donation
     *
     * @param integer $donation
     *
     * @return User
     */
    public function setDonation($donation)
    {
        $this->donation = $donation;

        return $this;
    }

    /**
     * Get donation
     *
     * @return integer
     */
    public function getDonation()
    {
        return $this->donation;
    }

    /**
     * Set isNotifAuthor
     *
     * @param boolean $isNotifAuthor
     *
     * @return User
     */
    public function setIsNotifAuthor($isNotifAuthor)
    {
        $this->isNotifAuthor = $isNotifAuthor;

        return $this;
    }

    /**
     * Get isNotifAuthor
     *
     * @return boolean
     */
    public function getIsNotifAuthor()
    {
        return $this->isNotifAuthor;
    }

    /**
     * Set isNotifCommenter
     *
     * @param boolean $isNotifCommenter
     *
     * @return User
     */
    public function setIsNotifCommenter($isNotifCommenter)
    {
        $this->isNotifCommenter = $isNotifCommenter;

        return $this;
    }

    /**
     * Get isNotifCommenter
     *
     * @return boolean
     */
    public function getIsNotifCommenter()
    {
        return $this->isNotifCommenter;
    }

    /**
     * Set isNotifMention
     *
     * @param boolean $isNotifMention
     *
     * @return User
     */
    public function setIsNotifMention($isNotifMention)
    {
        $this->isNotifMention = $isNotifMention;

        return $this;
    }

    /**
     * Get isNotifMention
     *
     * @return boolean
     */
    public function getIsNotifMention()
    {
        return $this->isNotifMention;
    }

    /**
     * Set isNotifFollow
     *
     * @param boolean $isNotifFollow
     *
     * @return User
     */
    public function setIsNotifFollow($isNotifFollow)
    {
        $this->isNotifFollow = $isNotifFollow;

        return $this;
    }

    /**
     * Get isNotifFollow
     *
     * @return boolean
     */
    public function getIsNotifFollow()
    {
        return $this->isNotifFollow;
    }

    /**
     * Set isNotifSuccessor
     *
     * @param boolean $isNotifSuccessor
     *
     * @return User
     */
    public function setIsNotifSuccessor($isNotifSuccessor)
    {
        $this->isNotifSuccessor = $isNotifSuccessor;

        return $this;
    }

    /**
     * Get isNotifSuccessor
     *
     * @return boolean
     */
    public function getIsNotifSuccessor()
    {
        return $this->isNotifSuccessor;
    }

    /**
     * Set isShareDecks
     *
     * @param boolean $isShareDecks
     *
     * @return User
     */
    public function setIsShareDecks($isShareDecks)
    {
        $this->isShareDecks = $isShareDecks;

        return $this;
    }

    /**
     * Get isShareDecks
     *
     * @return boolean
     */
    public function getIsShareDecks()
    {
        return $this->isShareDecks;
    }

    /**
     * Add deck
     *
     * @param \AppBundle\Entity\Deck $deck
     *
     * @return User
     */
    public function addDeck(\AppBundle\Entity\Deck $deck)
    {
        $this->decks[] = $deck;

        return $this;
    }

    /**
     * Remove deck
     *
     * @param \AppBundle\Entity\Deck $deck
     */
    public function removeDeck(\AppBundle\Entity\Deck $deck)
    {
        $this->decks->removeElement($deck);
    }

    /**
     * Get decks
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDecks()
    {
        return $this->decks;
    }

    /**
     * Add decklist
     *
     * @param \AppBundle\Entity\Decklist $decklist
     *
     * @return User
     */
    public function addDecklist(\AppBundle\Entity\Decklist $decklist)
    {
        $this->decklists[] = $decklist;

        return $this;
    }

    /**
     * Remove decklist
     *
     * @param \AppBundle\Entity\Decklist $decklist
     */
    public function removeDecklist(\AppBundle\Entity\Decklist $decklist)
    {
        $this->decklists->removeElement($decklist);
    }

    /**
     * Get decklists
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDecklists()
    {
        return $this->decklists;
    }

    /**
     * Add comment
     *
     * @param \AppBundle\Entity\Comment $comment
     *
     * @return User
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
     * Add review
     *
     * @param \AppBundle\Entity\Review $review
     *
     * @return User
     */
    public function addReview(\AppBundle\Entity\Review $review)
    {
        $this->reviews[] = $review;

        return $this;
    }

    /**
     * Remove review
     *
     * @param \AppBundle\Entity\Review $review
     */
    public function removeReview(\AppBundle\Entity\Review $review)
    {
        $this->reviews->removeElement($review);
    }

    /**
     * Get reviews
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReviews()
    {
        return $this->reviews;
    }

    /**
     * Add favorite
     *
     * @param \AppBundle\Entity\Decklist $favorite
     *
     * @return User
     */
    public function addFavorite(\AppBundle\Entity\Decklist $favorite)
    {
		$favorite->addFavorite($this);
        $this->favorites[] = $favorite;

        return $this;
    }

    /**
     * Remove favorite
     *
     * @param \AppBundle\Entity\Decklist $favorite
     */
    public function removeFavorite(\AppBundle\Entity\Decklist $favorite)
    {
    	$favorite->removeFavorite($this);
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
     * @param \AppBundle\Entity\Decklist $vote
     *
     * @return User
     */
    public function addVote(\AppBundle\Entity\Decklist $vote)
    {
		$vote->addVote($this);
        $this->votes[] = $vote;

        return $this;
    }

    /**
     * Remove vote
     *
     * @param \AppBundle\Entity\Decklist $vote
     */
    public function removeVote(\AppBundle\Entity\Decklist $vote)
    {
    	$vote->removeVote($this);
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
     * Add reviewvote
     *
     * @param \AppBundle\Entity\Review $reviewvote
     *
     * @return User
     */
    public function addReviewvote(\AppBundle\Entity\Review $reviewvote)
    {
        $this->reviewvotes[] = $reviewvote;

        return $this;
    }

    /**
     * Remove reviewvote
     *
     * @param \AppBundle\Entity\Review $reviewvote
     */
    public function removeReviewvote(\AppBundle\Entity\Review $reviewvote)
    {
        $this->reviewvotes->removeElement($reviewvote);
    }

    /**
     * Get reviewvotes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReviewvotes()
    {
        return $this->reviewvotes;
    }
}
