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

}
