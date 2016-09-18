<?php 

namespace AppBundle\Entity;

class Campaign implements \Serializable
{
	public function serialize() {
		return [
				'code' => $this->code,
				'name' => $this->name,
				'cycle_code' => $this->cycle ? $this->cycle->getCode() : null,
				'size' => $this->size,
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
     * @var integer
     */
    private $size;

    /**
     * @var \AppBundle\Entity\Cycle
     */
    private $cycle;

		 /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $scenarios;

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
     * Set size
     *
     * @param integer $size
     *
     * @return Pack
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
    }


    /**
     * Set cycle
     *
     * @param \AppBundle\Entity\Cycle $cycle
     *
     * @return Pack
     */
    public function setCycle(\AppBundle\Entity\Cycle $cycle = null)
    {
        $this->cycle = $cycle;

        return $this;
    }

    /**
     * Get cycle
     *
     * @return \AppBundle\Entity\Cycle
     */
    public function getCycle()
    {
        return $this->cycle;
    }
}
