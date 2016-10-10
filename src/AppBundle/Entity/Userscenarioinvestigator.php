<?php 

namespace AppBundle\Entity;

class Userscenarioinvestigator implements \Serializable
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
     * @var integer
     */
    private $trauma_mental;
    
	/**
     * @var integer
     */
    private $trauma_physical;

        /**
     * @var AppBundle\Entity\Userscenarioinvestigator
     */
    private $userscenario;

		 /**
     * @var AppBundle\Entity\Card
     */
    private $investigator;

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

}
