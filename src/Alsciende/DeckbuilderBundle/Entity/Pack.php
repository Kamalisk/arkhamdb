<?php

namespace Alsciende\DeckbuilderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Alsciende\DeckbuilderBundle\Model\PackInterface;

/**
 * Pack
 *
 * @ORM\MappedSuperclass
 */
abstract class Pack implements PackInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=1024)
     */
    private $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_creation", type="datetime")
     */
    private $dateCreation;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_update", type="datetime")
     */
    private $dateUpdate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_release", type="date", nullable=true)
     */
    private $dateRelease;

    /**
     * @var integer
     *
     * @ORM\Column(name="size", type="smallint")
     */
    private $size;

    /**
     * @var integer
     *
     * @ORM\Column(name="position", type="smallint")
     */
    private $position;


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
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     * @return Pack
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
     * @return Pack
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
     * Set dateRelease
     *
     * @param \DateTime $dateRelease
     * @return Pack
     */
    public function setDateRelease($dateRelease)
    {
        $this->dateRelease = $dateRelease;

        return $this;
    }

    /**
     * Get dateRelease
     *
     * @return \DateTime 
     */
    public function getDateRelease()
    {
        return $this->dateRelease;
    }

    /**
     * Set size
     *
     * @param integer $size
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
     * Set position
     *
     * @param integer $position
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
     * @ORM\ManyToOne(targetEntity="Alsciende\DeckbuilderBundle\Model\CycleInterface")
     * @ORM\JoinColumn(name="cycle_id", referencedColumnName="id")
     **/
    private $cycle;
    

    /**
     * Set cycle
     *
     * @param \Alsciende\DeckbuilderBundle\Model\CycleInterface $cycle
     * @return Pack
     */
    public function setCycle(\Alsciende\DeckbuilderBundle\Model\CycleInterface $cycle = null)
    {
        $this->cycle = $cycle;

        return $this;
    }

    /**
     * Get cycle
     *
     * @return \Alsciende\DeckbuilderBundle\Model\CycleInterface
     */
    public function getCycle()
    {
        return $this->cycle;
    }
}
