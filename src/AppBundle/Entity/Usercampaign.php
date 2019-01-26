<?php 

namespace AppBundle\Entity;

class Usercampaign implements \Serializable
{
	public function serialize() {
		return [
				'name' => $this->name
		];
	}
	
	public function unserialize($serialized) {
		throw new \Exception("unserialize() method unsupported");
	}
	
	public function toString() {
		return $this->name;
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
     * @var \Doctrine\Common\Collections\Collection
     */
    private $user_scenarios;

    /**
     * @var \AppBundle\Entity\User
     */
    private $user;


    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $decks;

    /**
     * Constructor
     */
    public function __construct()
    {

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
     * @return Type
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
     * Set user
     *
     * @param \AppBundle\Entity\User $user
     *
     * @return Campaign
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

}
