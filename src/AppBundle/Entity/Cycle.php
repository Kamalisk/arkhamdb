<?php 

namespace AppBundle\Entity;

class Cycle
{
	
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
    private $position;

    /**
     * @var boolean
     */
    private $isBox;

    /**
     * @var \DateTime
     */
    private $dateCreation;

    /**
     * @var \DateTime
     */
    private $dateUpdate;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $packs;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->packs = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Cycle
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
     * @return Cycle
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
     * @return Cycle
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
     * Set isBox
     *
     * @param boolean $isBox
     *
     * @return Cycle
     */
    public function setIsBox($isBox)
    {
        $this->isBox = $isBox;

        return $this;
    }

    /**
     * Get isBox
     *
     * @return boolean
     */
    public function getIsBox()
    {
        return $this->isBox;
    }

    /**
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     *
     * @return Cycle
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
     * @return Cycle
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
     * Add pack
     *
     * @param \AppBundle\Entity\Pack $pack
     *
     * @return Cycle
     */
    public function addPack(\AppBundle\Entity\Pack $pack)
    {
        $this->packs[] = $pack;

        return $this;
    }

    /**
     * Remove pack
     *
     * @param \AppBundle\Entity\Pack $pack
     */
    public function removePack(\AppBundle\Entity\Pack $pack)
    {
        $this->packs->removeElement($pack);
    }

    /**
     * Get packs
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPacks()
    {
        return $this->packs;
    }
}
