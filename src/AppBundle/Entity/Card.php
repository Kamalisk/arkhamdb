<?php 

namespace AppBundle\Entity;

use Alsciende\DeckbuilderBundle\Entity\Card as BaseCard;
use Alsciende\DeckbuilderBundle\Model\CardInterface;

class Card extends BaseCard implements CardInterface
{
	
    /**
     * @var integer
     */
    private $quantity;

    /**
     * @var integer
     */
    private $gold;

    /**
     * @var integer
     */
    private $claim;

    /**
     * @var integer
     */
    private $initiative;

    /**
     * @var integer
     */
    private $reserve;

    /**
     * @var integer
     */
    private $strength;

    /**
     * @var string
     */
    private $keywords;

    /**
     * @var string
     */
    private $flavor;

    /**
     * @var string
     */
    private $illustrator;

    /**
     * @var boolean
     */
    private $is_unique;

    /**
     * @var boolean
     */
    private $is_limited;

    /**
     * @var boolean
     */
    private $is_loyal;

    /**
     * @var boolean
     */
    private $is_military;

    /**
     * @var boolean
     */
    private $is_intrigue;

    /**
     * @var boolean
     */
    private $is_power;


    /**
     * Set quantity
     *
     * @param integer $quantity
     * @return Card
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
     * Set gold
     *
     * @param integer $gold
     * @return Card
     */
    public function setGold($gold)
    {
        $this->gold = $gold;

        return $this;
    }

    /**
     * Get gold
     *
     * @return integer 
     */
    public function getGold()
    {
        return $this->gold;
    }

    /**
     * Set claim
     *
     * @param integer $claim
     * @return Card
     */
    public function setClaim($claim)
    {
        $this->claim = $claim;

        return $this;
    }

    /**
     * Get claim
     *
     * @return integer 
     */
    public function getClaim()
    {
        return $this->claim;
    }

    /**
     * Set initiative
     *
     * @param integer $initiative
     * @return Card
     */
    public function setInitiative($initiative)
    {
        $this->initiative = $initiative;

        return $this;
    }

    /**
     * Get initiative
     *
     * @return integer 
     */
    public function getInitiative()
    {
        return $this->initiative;
    }

    /**
     * Set reserve
     *
     * @param integer $reserve
     * @return Card
     */
    public function setReserve($reserve)
    {
        $this->reserve = $reserve;

        return $this;
    }

    /**
     * Get reserve
     *
     * @return integer 
     */
    public function getReserve()
    {
        return $this->reserve;
    }

    /**
     * Set strength
     *
     * @param integer $strength
     * @return Card
     */
    public function setStrength($strength)
    {
        $this->strength = $strength;

        return $this;
    }

    /**
     * Get strength
     *
     * @return integer 
     */
    public function getStrength()
    {
        return $this->strength;
    }

    /**
     * Set keywords
     *
     * @param string $keywords
     * @return Card
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;

        return $this;
    }

    /**
     * Get keywords
     *
     * @return string 
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * Set flavor
     *
     * @param string $flavor
     * @return Card
     */
    public function setFlavor($flavor)
    {
        $this->flavor = $flavor;

        return $this;
    }

    /**
     * Get flavor
     *
     * @return string 
     */
    public function getFlavor()
    {
        return $this->flavor;
    }

    /**
     * Set illustrator
     *
     * @param string $illustrator
     * @return Card
     */
    public function setIllustrator($illustrator)
    {
        $this->illustrator = $illustrator;

        return $this;
    }

    /**
     * Get illustrator
     *
     * @return string 
     */
    public function getIllustrator()
    {
        return $this->illustrator;
    }

    /**
     * Set is_unique
     *
     * @param boolean $isUnique
     * @return Card
     */
    public function setIsUnique($isUnique)
    {
        $this->is_unique = $isUnique;

        return $this;
    }

    /**
     * Get is_unique
     *
     * @return boolean 
     */
    public function getIsUnique()
    {
        return $this->is_unique;
    }

    /**
     * Set is_limited
     *
     * @param boolean $isLimited
     * @return Card
     */
    public function setIsLimited($isLimited)
    {
        $this->is_limited = $isLimited;

        return $this;
    }

    /**
     * Get is_limited
     *
     * @return boolean 
     */
    public function getIsLimited()
    {
        return $this->is_limited;
    }

    /**
     * Set is_loyal
     *
     * @param boolean $isLoyal
     * @return Card
     */
    public function setIsLoyal($isLoyal)
    {
        $this->is_loyal = $isLoyal;

        return $this;
    }

    /**
     * Get is_loyal
     *
     * @return boolean 
     */
    public function getIsLoyal()
    {
        return $this->is_loyal;
    }

    /**
     * Set is_military
     *
     * @param boolean $isMilitary
     * @return Card
     */
    public function setIsMilitary($isMilitary)
    {
        $this->is_military = $isMilitary;

        return $this;
    }

    /**
     * Get is_military
     *
     * @return boolean 
     */
    public function getIsMilitary()
    {
        return $this->is_military;
    }

    /**
     * Set is_intrigue
     *
     * @param boolean $isIntrigue
     * @return Card
     */
    public function setIsIntrigue($isIntrigue)
    {
        $this->is_intrigue = $isIntrigue;

        return $this;
    }

    /**
     * Get is_intrigue
     *
     * @return boolean 
     */
    public function getIsIntrigue()
    {
        return $this->is_intrigue;
    }

    /**
     * Set is_power
     *
     * @param boolean $isPower
     * @return Card
     */
    public function setIsPower($isPower)
    {
        $this->is_power = $isPower;

        return $this;
    }

    /**
     * Get is_power
     *
     * @return boolean 
     */
    public function getIsPower()
    {
        return $this->is_power;
    }
}