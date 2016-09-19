<?php 

namespace AppBundle\Entity;

class Scenario implements \Serializable
{
	public function serialize() {
		return [
				'code' => $this->code,
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
    private $code;
    
    
     /**
     * @var string
     */
    private $name;
    
     /**
     * @var \AppBundle\Entity\Campaign
     */
    private $campaign;
		
		 /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $encounters;


    /**
     * @var integer
     */
    private $position;

		
		/**
     * Add campaign
     *
     * @param \AppBundle\Entity\Campaign $campaign
     *
     * @return Scenario
     */
    public function setCampaign(\AppBundle\Entity\Campaign $campaign)
    {
        $this->campaign = $campaign;
        return $this;
    }

    /**
     * Get campaigns
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCampaign()
    {
        return $this->campaign;
    }
    

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
     * Set code
     *
     * @param string $code
     *
     * @return Pack
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
     *
     * @return Pack
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
     * Set position
     *
     * @param integer $position
     *
     * @return Pack
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
     * Add Encounter
     *
     * @param \AppBundle\Entity\Encounter $encounter
     *
     * @return Type
     */
    public function addEncounter(\AppBundle\Entity\Encounter $encounter)
    {
        $this->encounters[] = $encounter;

        return $this;
    }

    /**
     * Remove Encounter
     *
     * @param \AppBundle\Entity\Encounter $encounter
     */
    public function removeEncounter(\AppBundle\Entity\Encounter $encounter)
    {
        $this->encounter->removeElement($encounter);
    }

    /**
     * Get Encounter
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEncounters()
    {
        return $this->encounters;
    }
}
