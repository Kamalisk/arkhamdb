<?php 

namespace AppBundle\Entity;

class Deck extends \AppBundle\Model\ExportableDeck implements \AppBundle\Model\ExportableDeckInterface
{
	public function getArrayExport($withUnsavedChanges = false)
	{
		$array = parent::getArrayExport($withUnsavedChanges);
		$array['problem'] = $this->getProblem();
		$array['tags'] = $this->getTags();
		
		if($withUnsavedChanges)
		{
			$cards = $array['slots'];
			
			$snapshots =[];
			
			/**
			 * All changes, with the newest at position 0
			*/
			$changes = $this->getChanges();
			
			/**
			 * Saved changes, with the newest at position 0
			 * @var $savedChanges Deckchange[]
			*/
			$savedChanges =[];
			
			/**
			 * Unsaved changes, with the oldest at position 0
			 * @var $unsavedChanges Deckchange[]
			*/
			$unsavedChanges =[];
			
			foreach($changes as $change) {
				if($change->getIsSaved()) {
					array_push($savedChanges, $change);
				}
				else {
					array_unshift($unsavedChanges, $change);
				}
			}
			$array['unsaved'] = count($unsavedChanges);
			
			// recreating the versions with the variation info, starting from $preversion
			$preversion = $cards;
			foreach ( $savedChanges as $change ) {
				$variation = json_decode ( $change->getVariation(), TRUE );
				$row = [
						'variation' => $variation,
						'is_saved' => $change->getIsSaved(),
						'content' => $preversion,
						'date_creation' => $change->getDateCreation()->format('c'),
				];
				array_unshift ( $snapshots, $row );
			
				// applying variation to create 'next' (older) preversion
				foreach ( $variation[0] as $code => $qty ) {
					$preversion[$code] = $preversion[$code] - $qty;
					if ($preversion[$code] == 0) unset ( $preversion[$code] );
				}
				foreach ( $variation[1] as $code => $qty ) {
					if (! isset ( $preversion[$code] )) $preversion[$code] = 0;
					$preversion[$code] = $preversion[$code] + $qty;
				}
				ksort ( $preversion );
			}
			
			// add last know version with empty diff
			$row = [
					'variation' => null,
					'is_saved' => true,
					'content' => $preversion,
					'date_creation' => $this->getDateCreation()->format('c')
			];
			array_unshift ( $snapshots, $row );
			
			// recreating the snapshots with the variation info, starting from $postversion
			$postversion = $cards;
			foreach ( $unsavedChanges as $change ) {
				$variation = json_decode ( $change->getVariation(), TRUE );
				$row = [
						'variation' => $variation,
						'is_saved' => $change->getIsSaved(),
						'date_creation' => $change->getDateCreation()->format('c'),
				];
				array_unshift ( $snapshots, $row );
					
				// applying variation to postversion
				foreach ( $variation[0] as $code => $qty ) {
					if (! isset ( $postversion[$code] )) $postversion[$code] = 0;
					$postversion[$code] = $postversion[$code] + $qty;
				}
				foreach ( $variation[1] as $code => $qty ) {
					$postversion[$code] = $postversion[$code] - $qty;
					if ($postversion[$code] == 0) unset ( $postversion[$code] );
				}
				ksort ( $postversion );
					
				// add postversion with variation that lead to it
				$row['content'] = $postversion;
				array_push ( $snapshots, $row );
			}
			
			// current deck is newest snapshot
			$array['slots'] = $postversion;
			$array['history'] = $snapshots;
			
		}
	
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
    private $problem;

    /**
     * @var string
     */
    private $tags;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $slots;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $children;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $changes;

    /**
     * @var \AppBundle\Entity\User
     */
    private $user;

    /**
     * @var \AppBundle\Entity\Faction
     */
    private $faction;

    /**
     * @var \AppBundle\Entity\Pack
     */
    private $lastPack;

    /**
     * @var \AppBundle\Entity\Decklist
     */
    private $parent;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->slots = new \Doctrine\Common\Collections\ArrayCollection();
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->changes = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Deck
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
     *
     * @return Deck
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
     * @return Deck
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
     * @return Deck
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
     * Set problem
     *
     * @param string $problem
     *
     * @return Deck
     */
    public function setProblem($problem)
    {
        $this->problem = $problem;

        return $this;
    }

    /**
     * Get problem
     *
     * @return string
     */
    public function getProblem()
    {
        return $this->problem;
    }

    /**
     * Set tags
     *
     * @param string $tags
     *
     * @return Deck
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
     * @param \AppBundle\Entity\Deckslot $slot
     *
     * @return Deck
     */
    public function addSlot(\AppBundle\Entity\Deckslot $slot)
    {
        $this->slots[] = $slot;

        return $this;
    }

    /**
     * Remove slot
     *
     * @param \AppBundle\Entity\Deckslot $slot
     */
    public function removeSlot(\AppBundle\Entity\Deckslot $slot)
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
     * Add child
     *
     * @param \AppBundle\Entity\Decklist $child
     *
     * @return Deck
     */
    public function addChild(\AppBundle\Entity\Decklist $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param \AppBundle\Entity\Decklist $child
     */
    public function removeChild(\AppBundle\Entity\Decklist $child)
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
     * Add change
     *
     * @param \AppBundle\Entity\Deckchange $change
     *
     * @return Deck
     */
    public function addChange(\AppBundle\Entity\Deckchange $change)
    {
        $this->changes[] = $change;

        return $this;
    }

    /**
     * Remove change
     *
     * @param \AppBundle\Entity\Deckchange $change
     */
    public function removeChange(\AppBundle\Entity\Deckchange $change)
    {
        $this->changes->removeElement($change);
    }

    /**
     * Get changes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * Set user
     *
     * @param \AppBundle\Entity\User $user
     *
     * @return Deck
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
     * Set faction
     *
     * @param \AppBundle\Entity\Faction $faction
     *
     * @return Deck
     */
    public function setFaction(\AppBundle\Entity\Faction $faction = null)
    {
        $this->faction = $faction;

        return $this;
    }

    /**
     * Get faction
     *
     * @return \AppBundle\Entity\Faction
     */
    public function getFaction()
    {
        return $this->faction;
    }

    /**
     * Set lastPack
     *
     * @param \AppBundle\Entity\Pack $lastPack
     *
     * @return Deck
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
     * @param \AppBundle\Entity\Decklist $parent
     *
     * @return Deck
     */
    public function setParent(\AppBundle\Entity\Decklist $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \AppBundle\Entity\Decklist
     */
    public function getParent()
    {
        return $this->parent;
    }
}
