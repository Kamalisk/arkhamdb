<?php

namespace AppBundle\Entity;

class Card implements \Gedmo\Translatable\Translatable, \Serializable
{
	private function snakeToCamel($snake) {
		$parts = explode('_', $snake);
		return implode('', array_map('ucfirst', $parts));
	}
	
	public function serialize() {
		$serialized = [];
		if(empty($this->code)) return $serialized;
	
		$mandatoryFields = [
				'code',
				'position',
				'quantity',
				'name'
		];
	
		$optionalFields = [
				'illustrator',
				'flavor',
				'traits',
				'text',
				'cost',
				'octgn_id',
				'subname',
				'bonded_to',
				'bonded_count',
				'xp',
				'deck_limit',
				'back_text',
				'back_name',
				'back_flavor',
				'permanent',
				'hidden',
				'double_sided',
				'is_unique',
				'exile',
				'exceptional',
				'myriad'
		];
	
		$externalFields = [
				'faction',
				'faction2',
				'pack',
				'type',
				'encounter',
				'linked_to',
				'alternate_of'
		];

		$transFields = [
				'real_name',
				'real_slot',
				'real_text',
				'real_traits'
		];
	
		switch($this->type->getCode()) {
			case 'asset':
				$mandatoryFields[] = 'cost';
				$optionalFields[] = 'skill_willpower';
				$optionalFields[] = 'skill_intellect';
				$optionalFields[] = 'skill_combat';
				$optionalFields[] = 'skill_agility';
				$optionalFields[] = 'skill_wild';
				$optionalFields[] = 'health';
				$optionalFields[] = 'sanity';				
				$optionalFields[] = 'restrictions';
				$optionalFields[] = 'slot';
				$optionalFields[] = 'encounter_position';
				break;
			case 'event':
				$mandatoryFields[] = 'cost';
				$optionalFields[] = 'skill_willpower';
				$optionalFields[] = 'skill_intellect';
				$optionalFields[] = 'skill_combat';
				$optionalFields[] = 'skill_agility';
				$optionalFields[] = 'skill_wild';
				$optionalFields[] = 'restrictions';
				break;
			case 'skill':
				$optionalFields[] = 'skill_willpower';
				$optionalFields[] = 'skill_intellect';
				$optionalFields[] = 'skill_combat';
				$optionalFields[] = 'skill_agility';
				$optionalFields[] = 'skill_wild';
				$optionalFields[] = 'restrictions';
				break;
			case 'investigator':
				$mandatoryFields[] = 'skill_willpower';
				$mandatoryFields[] = 'skill_intellect';
				$mandatoryFields[] = 'skill_combat';
				$mandatoryFields[] = 'skill_agility';
				$mandatoryFields[] = 'health';
				$mandatoryFields[] = 'sanity';
				$mandatoryFields[] = 'deck_requirements';
				$mandatoryFields[] = 'deck_options';
				break;
			case "treachery":
				$externalFields[] = "subtype";
				$optionalFields[] = 'restrictions';
				$optionalFields[] = 'encounter_position';
				break;
			case "enemy":
				$optionalFields[] = 'enemy_damage';
				$optionalFields[] = 'enemy_horror';
				$optionalFields[] = 'enemy_fight';
				$optionalFields[] = 'enemy_evade';
				$optionalFields[] = 'victory';
				$optionalFields[] = 'vengeance';
				$optionalFields[] = 'health';
				$optionalFields[] = 'health_per_investigator';
				$optionalFields[] = 'encounter_position';
				break;
			case "location":
				$optionalFields[] = 'victory';
				$optionalFields[] = 'vengeance';
				$optionalFields[] = 'shroud';
				$optionalFields[] = 'clues';
				$optionalFields[] = 'clues_fixed';
				$optionalFields[] = 'encounter_position';
				break;
			case "agenda":
				$optionalFields[] = 'doom';
				$optionalFields[] = 'encounter_position';
				$optionalFields[] = 'stage';
				break;
			case "act":
				$optionalFields[] = 'clues';
				$optionalFields[] = 'encounter_position';
				$optionalFields[] = 'stage';
				break;
			case "adventure":
				$optionalFields[] = 'encounter_position';
				break;
		}
	
		foreach($optionalFields as $optionalField) {
			$getterString = $optionalField;
			$getter = 'get' . $this->snakeToCamel($getterString);
			$serialized[$optionalField] = $this->$getter();
			if(!isset($serialized[$optionalField]) || $serialized[$optionalField] === '') unset($serialized[$optionalField]);
		}
	
		foreach($mandatoryFields as $mandatoryField) {
			$getterString = $mandatoryField;
			$getter = 'get' . $this->snakeToCamel($getterString);
			$serialized[$mandatoryField] = $this->$getter();
		}
	
		foreach($externalFields as $externalField) {
			$getter = 'get' . $this->snakeToCamel($externalField);
			if ($this->$getter()){
				$serialized[$externalField.'_code'] = $this->$getter()->getCode();
			}
		}

		foreach($transFields as $transField) {
			$getter = 'get' . $this->snakeToCamel($transField);
			$serialized[$transField] = $this->$getter();
			if(!isset($serialized[$transField]) || $serialized[$transField] === '') unset($serialized[$transField]);
		}
	
		ksort($serialized);
		return $serialized;
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
	private $position;


	/**
	 * @var integer
	 */
	private $encounterPosition;


	/**
	 * @var string
	 */
	private $code;

	/**
	 * @var string
	 */
	private $name;
	
	/**
	 * @var string
	 */
	private $realName;
	
	/**
	 * @var string
	 */
	private $backName;

	/**
	 * @var string
	 */
	private $subname;

	/**
	 * @var integer
	 */
	private $cost;

	/**
	 * @var string
	 */
	private $text;
	
	/**
	 * @var string
	 */
	private $realText;
	
	
	/**
	 * @var string
	 */
	private $backText;

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
	private $quantity;

	/**
	 * @var integer
	 */
	private $skillWillpower;

	/**
	 * @var integer
	 */
	private $skillIntellect;

	/**
	 * @var integer
	 */
	private $skillCombat;

	/**
	 * @var integer
	 */
	private $skillAgility;

	/**
	 * @var integer
	 */
	private $skillWild;

	/**
	 * @var integer
	 */
	private $xp;


	/**
	 * @var integer
	 */
	private $shroud;


	/**
	 * @var integer
	 */
	private $doom;


	/**
	 * @var integer
	 */
	private $clues;

	/**
	 * @var boolean
	 */
	private $cluesFixed;

	/**
	 * @var integer
	 */
	private $health;

	/**
	 * @var boolean
	 */
	private $healthPerInvestigator;


	/**
	 * @var integer
	 */
	private $sanity;
	

	/**
	 * @var integer
	 */
	private $enemyFight;
	

	/**
	 * @var integer
	 */
	private $enemyEvade;
	

	/**
	 * @var integer
	 */
	private $enemyDamage;
	

	/**
	 * @var integer
	 */
	private $enemyHorror;
	


	/**
	 * @var integer
	 */
	private $victory;
	
	/**
	 * @var integer
	 */
	private $vengeance;

	/**
	 * @var integer
	 */
	private $deckLimit;

	/**
	 * @var string
	 */
	private $traits;


	/**
	 * @var string
	 */
	private $realTraits;


	/**
	 * @var string
	 */
	private $deckRequirements;
	
		/**
	 * @var string
	 */
	private $deckOptions;
	
	/**
	 * @var string
	 */
	private $restrictions;

	/**
	 * @var string
	 */
	private $slot;

	/**
	 * @var integer
	 */
	private $stage;

	/**
	 * @var string
	 */
	private $flavor;
	
	 /**
	 * @var string
	 */
	private $backFlavor;

	/**
	 * @var string
	 */
	private $illustrator;

	/**
	 * @var boolean
	 */
	private $isUnique;
	
	 /**
	 * @var boolean
	 */
	private $exile;
	
	 /**
	 * @var boolean
	 */
	private $exceptional;
	
	/**
	 * @var boolean
	 */
	private $hidden;
	
	
	/**
	 * @var boolean
	 */
	private $permanent;
	
	/**
	 * @var boolean
	 */
	private $doubleSided;

	/**
	 * @var string
	 */
	private $octgnId;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 */
	private $reviews;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 */
	private $alternates;

	/**
	 * @var \AppBundle\Entity\Pack
	 */
	private $pack;

	/**
	 * @var \AppBundle\Entity\Type
	 */
	private $type;

	/**
	 * @var \AppBundle\Entity\Faction
	 */
	private $faction;

	/**
	 * @var \AppBundle\Entity\Faction
	 */
	private $faction2;
  
		/**
	 * @var \AppBundle\Entity\Subtype
	 */
	private $subtype;

	/**
	 * @var \AppBundle\Entity\Card
	 */
	private $linked_from;

	/**
	 * @var \AppBundle\Entity\Card
	 */
	private $linked_to;

	/**
	 * @var \AppBundle\Entity\Card
	 */
	private $alternate_of;

	/**
	* @var \AppBundle\Entity\Encounter
	*/
	private $encounter;

	/**
	 * Constructor
	 */
	public function __construct()
	{
	  $this->reviews = new \Doctrine\Common\Collections\ArrayCollection();
		$this->alternates = new \Doctrine\Common\Collections\ArrayCollection();
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
	 * Set position
	 *
	 * @param integer $position
	 *
	 * @return Card
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
	 * Set encounter position
	 *
	 * @param integer $encounterPosition
	 *
	 * @return Card
	 */
	public function setEncounterPosition($encounterPosition)
	{
		$this->encounterPosition = $encounterPosition;

		return $this;
	}

	/**
	 * Get encounter position
	 *
	 * @return integer
	 */
	public function getEncounterPosition()
	{
		return $this->encounterPosition;
	}



	/**
	 * Set code
	 *
	 * @param string $code
	 *
	 * @return Card
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
	 * @return Card
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
	 * Set realName
	 *
	 * @param string $realname
	 *
	 * @return Card
	 */
	public function setRealName($realName)
	{
		$this->realName = $realName;

		return $this;
	}

	/**
	 * Get realName
	 *
	 * @return string
	 */
	public function getRealName()
	{
		return $this->realName;
	}
	
	 /**
	 * Set backName
	 *
	 * @param string $backName
	 *
	 * @return Card
	 */
	public function setBackName($backName)
	{
		$this->backName = $backName;

		return $this;
	}

	/**
	 * Get backName
	 *
	 * @return string
	 */
	public function getBackName()
	{
		return $this->backName;
	}
	
	
		/**
	 * Set subname
	 *
	 * @param string $subname
	 *
	 * @return Card
	 */
	public function setSubname($subname)
	{
		$this->subname = $subname;

		return $this;
	}

	/**
	 * Get subname
	 *
	 * @return string
	 */
	public function getSubname()
	{
		return $this->subname;
	}

	/**
	 * Set cost
	 *
	 * @param integer $cost
	 *
	 * @return Card
	 */
	public function setCost($cost)
	{
		$this->cost = $cost;

		return $this;
	}

	/**
	 * Get cost
	 *
	 * @return integer
	 */
	public function getCost()
	{
		return $this->cost;
	}

	/**
	 * Set text
	 *
	 * @param string $text
	 *
	 * @return Card
	 */
	public function setText($text)
	{
		$this->text = $text;

		return $this;
	}

	/**
	 * Get text
	 *
	 * @return string
	 */
	public function getText()
	{
		return $this->text;
	}
	
	
	 /**
	 * Set real text
	 *
	 * @param string $text
	 *
	 * @return Card
	 */
	public function setRealText($text)
	{
		$this->realText = $text;

		return $this;
	}

	/**
	 * Get real text
	 *
	 * @return string
	 */
	public function getRealText()
	{
		return $this->realText;
	}
	
		/**
	 * Set backText
	 *
	 * @param string $backText
	 *
	 * @return Card
	 */
	public function setBackText($backText)
	{
		$this->backText = $backText;

		return $this;
	}

	/**
	 * Get backText
	 *
	 * @return string
	 */
	public function getBackText()
	{
		return $this->backText;
	}
	

	/**
	 * Set dateCreation
	 *
	 * @param \DateTime $dateCreation
	 *
	 * @return Card
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
	 * @return Card
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
	 * Set quantity
	 *
	 * @param integer $quantity
	 *
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
	 * Set health
	 *
	 * @param integer $health
	 *
	 * @return Card
	 */
	public function setHealth($health)
	{
		$this->health = $health;

		return $this;
	}

	/**
	 * Get health
	 *
	 * @return integer
	 */
	public function getHealth()
	{
		return $this->health;
	}


	/**
	 * Set healthPerInvestigator
	 *
	 * @param boolean $healthPerInvestigator
	 *
	 * @return Card
	 */
	public function setHealthPerInvestigator($healthPerInvestigator)
	{
		$this->healthPerInvestigator = $healthPerInvestigator;

		return $this;
	}

	/**
	 * Get healthPerInvestigator
	 *
	 * @return boolean
	 */
	public function getHealthPerInvestigator()
	{
		return $this->healthPerInvestigator;
	}


	/**
	 * Set sanity
	 *
	 * @param integer $sanity
	 *
	 * @return Card
	 */
	public function setSanity($sanity)
	{
		$this->sanity = $sanity;

		return $this;
	}

	/**
	 * Get sanity
	 *
	 * @return integer
	 */
	public function getSanity()
	{
		return $this->sanity;
	}


	/**
	 * Set enemy fight
	 *
	 * @param integer $enemyFight
	 *
	 * @return Card
	 */
	public function setEnemyFight($enemyFight)
	{
		$this->enemyFight = $enemyFight;

		return $this;
	}

	/**
	 * Get enemyFight
	 *
	 * @return integer
	 */
	public function getEnemyFight()
	{
		return $this->enemyFight;
	}


	/**
	 * Set enemy Evade
	 *
	 * @param integer $enemyEvade
	 *
	 * @return Card
	 */
	public function setEnemyEvade($enemyEvade)
	{
		$this->enemyEvade = $enemyEvade;

		return $this;
	}

	/**
	 * Get enemyEvade
	 *
	 * @return integer
	 */
	public function getEnemyEvade()
	{
		return $this->enemyEvade;
	}


	/**
	 * Set damage health
	 *
	 * @param integer $enemyDamage
	 *
	 * @return Card
	 */
	public function setEnemyDamage($enemyDamage)
	{
		$this->enemyDamage = $enemyDamage;

		return $this;
	}

	/**
	 * Get damageHealth
	 *
	 * @return integer
	 */
	public function getEnemyDamage()
	{
		return $this->enemyDamage;
	}


	/**
	 * Set damage sanity
	 *
	 * @param integer $enemyHorror
	 *
	 * @return Card
	 */
	public function setEnemyHorror($enemyHorror)
	{
		$this->enemyHorror = $enemyHorror;

		return $this;
	}

	/**
	 * Get damageSanity
	 *
	 * @return integer
	 */
	public function getEnemyHorror()
	{
		return $this->enemyHorror;
	}



	/**
	 * Set victory
	 *
	 * @param integer $victory
	 *
	 * @return Card
	 */
	public function setVictory($victory)
	{
		$this->victory = $victory;

		return $this;
	}

	/**
	 * Get victory
	 *
	 * @return integer
	 */
	public function getVictory()
	{
		return $this->victory;
	}



	/**
	 * Set vengeance
	 *
	 * @param integer $vengeance
	 *
	 * @return Card
	 */
	public function setVengeance($vengeance)
	{
		$this->vengeance = $vengeance;

		return $this;
	}

	/**
	 * Get vengeance
	 *
	 * @return integer
	 */
	public function getVengeance()
	{
		return $this->vengeance;
	}



	/**
	 * Set deckLimit
	 *
	 * @param integer $deckLimit
	 *
	 * @return Card
	 */
	public function setDeckLimit($deckLimit)
	{
		$this->deckLimit = $deckLimit;

		return $this;
	}

	/**
	 * Get deckLimit
	 *
	 * @return integer
	 */
	public function getDeckLimit()
	{
		return $this->deckLimit;
	}

	/**
	 * Set skillWillpower
	 *
	 * @param integer $skillWillpower
	 *
	 * @return Card
	 */
	public function setSkillWillpower($skillWillpower)
	{
		$this->skillWillpower = $skillWillpower;

		return $this;
	}

	/**
	 * Get skillWillpower
	 *
	 * @return integer
	 */
	public function getSkillWillpower()
	{
		return $this->skillWillpower;
	}
	
		/**
	 * Set skillIntellect
	 *
	 * @param integer $skillIntellect
	 *
	 * @return Card
	 */
	public function setSkillIntellect($skillIntellect)
	{
		$this->skillIntellect = $skillIntellect;

		return $this;
	}

	/**
	 * Get skillIntellect
	 *
	 * @return integer
	 */
	public function getSkillIntellect()
	{
		return $this->skillIntellect;
	}
	
	
		/**
	 * Set skillCombat
	 *
	 * @param integer $skillCombat
	 *
	 * @return Card
	 */
	public function setSkillCombat($skillCombat)
	{
		$this->skillCombat = $skillCombat;

		return $this;
	}

	/**
	 * Get skillCombat
	 *
	 * @return integer
	 */
	public function getSkillCombat()
	{
		return $this->skillCombat;
	}
	
	
		/**
	 * Set skillAgility
	 *
	 * @param integer $skillAgility
	 *
	 * @return Card
	 */
	public function setSkillAgility($skillAgility)
	{
		$this->skillAgility = $skillAgility;

		return $this;
	}

	/**
	 * Get skillAgility
	 *
	 * @return integer
	 */
	public function getSkillAgility()
	{
		return $this->skillAgility;
	}
	
		/**
	 * Set skillWild
	 *
	 * @param integer $skillWild
	 *
	 * @return Card
	 */
	public function setSkillWild($skillWild)
	{
		$this->skillWild = $skillWild;

		return $this;
	}

	/**
	 * Get skillWild
	 *
	 * @return integer
	 */
	public function getSkillWild()
	{
		return $this->skillWild;
	}

	/**
	 * Set xp
	 *
	 * @param integer $xp
	 *
	 * @return Card
	 */
	public function setXp($xp)
	{
		$this->xp = $xp;

		return $this;
	}

	/**
	 * Get xp
	 *
	 * @return integer
	 */
	public function getXp()
	{
		return $this->xp;
	}



	/**
	 * Set shroud
	 *
	 * @param integer $shroud
	 *
	 * @return Card
	 */
	public function setShroud($shroud)
	{
		$this->shroud = $shroud;

		return $this;
	}

	/**
	 * Get shroud
	 *
	 * @return integer
	 */
	public function getShroud()
	{
		return $this->shroud;
	}




	/**
	 * Set clues
	 *
	 * @param integer $clues
	 *
	 * @return Card
	 */
	public function setClues($clues)
	{
		$this->clues = $clues;

		return $this;
	}

	/**
	 * Get clues
	 *
	 * @return integer
	 */
	public function getClues()
	{
		return $this->clues;
	}
	


	/**
	 * Set cluesFixed
	 *
	 * @param boolean $cluesFixed
	 *
	 * @return Card
	 */
	public function setCluesFixed($cluesFixed)
	{
		$this->cluesFixed = $cluesFixed;

		return $this;
	}

	/**
	 * Get cluesFixed
	 *
	 * @return boolean
	 */
	public function getCluesFixed()
	{
		return $this->cluesFixed;
	}



	/**
	 * Set doom
	 *
	 * @param integer $doom
	 *
	 * @return Card
	 */
	public function setDoom($doom)
	{
		$this->doom = $doom;

		return $this;
	}

	/**
	 * Get doom
	 *
	 * @return integer
	 */
	public function getDoom()
	{
		return $this->doom;
	}





	/**
	 * Set traits
	 *
	 * @param string $traits
	 *
	 * @return Card
	 */
	public function setTraits($traits)
	{
		$this->traits = $traits;

		return $this;
	}

	/**
	 * Get real traits
	 *
	 * @return string
	 */
	public function getRealTraits()
	{
		return $this->realTraits;
	}
	
	/**
	 * Set traits
	 *
	 * @param string $traits
	 *
	 * @return Card
	 */
	public function setRealTraits($traits)
	{
		$this->realTraits = $traits;

		return $this;
	}

	/**
	 * Get traits
	 *
	 * @return string
	 */
	public function getTraits()
	{
		return $this->traits;
	}
	
	/**
	 * Set deckRequirements
	 *
	 * @param string $deckRequirements
	 *
	 * @return Card
	 */
	public function setDeckRequirements($deckRequirements)
	{
		$this->deckRequirements = $deckRequirements;

		return $this;
	}

	/**
	 * Get deckRequirements
	 *
	 * @return string
	 */
	public function getDeckRequirements()
	{
		return $this->deckRequirements;
	}
	
	
		/**
	 * Set deckOptions
	 *
	 * @param string $deckOptions
	 *
	 * @return Card
	 */
	public function setDeckOptions($deckOptions)
	{
		$this->deckOptions = $deckOptions;
		return $this;
	}

	/**
	 * Get deckOptions
	 *
	 * @return string
	 */
	public function getdeckOptions()
	{
		return $this->deckOptions;
	}
	
		/**
	 * Set restrictions
	 *
	 * @param string $restrictions
	 *
	 * @return Card
	 */
	public function setRestrictions($restrictions)
	{
		$this->restrictions = $restrictions;

		return $this;
	}

	/**
	 * Get restrictions
	 *
	 * @return string
	 */
	public function getRestrictions()
	{
		return $this->restrictions;
	}
	
		/**
	 * Set slot
	 *
	 * @param string $slot
	 *
	 * @return Card
	 */
	public function setSlot($slot)
	{
		$this->slot = $slot;

		return $this;
	}

	/**
	 * Get slot
	 *
	 * @return string
	 */
	public function getSlot()
	{
		return $this->slot;
	}


	/**
	 * Set stage
	 *
	 * @param integer $stage
	 *
	 * @return Card
	 */
	public function setStage($stage)
	{
		$this->stage = $stage;

		return $this;
	}

	/**
	 * Get stage
	 *
	 * @return integer
	 */
	public function getStage()
	{
		return $this->stage;
	}


	/**
	 * Set flavor
	 *
	 * @param string $flavor
	 *
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
	 * Set backFlavor
	 *
	 * @param string $backFlavor
	 *
	 * @return Card
	 */
	public function setBackFlavor($backFlavor)
	{
		$this->backFlavor = $backFlavor;

		return $this;
	}

	/**
	 * Get backFlavor
	 *
	 * @return string
	 */
	public function getBackFlavor()
	{
		return $this->backFlavor;
	}

	/**
	 * Set illustrator
	 *
	 * @param string $illustrator
	 *
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
	 * Set isUnique
	 *
	 * @param boolean $isUnique
	 *
	 * @return Card
	 */
	public function setIsUnique($isUnique)
	{
		$this->isUnique = $isUnique;

		return $this;
	}

	/**
	 * Get isUnique
	 *
	 * @return boolean
	 */
	public function getIsUnique()
	{
		return $this->isUnique;
	}

	/**
	 * Set exile
	 *
	 * @param boolean $exile
	 *
	 * @return Card
	 */
	public function setExile($exile)
	{
		$this->exile = $exile;

		return $this;
	}

	/**
	 * Get exile
	 *
	 * @return boolean
	 */
	public function getExile()
	{
		return $this->exile;
	}

	/**
	 * Set hidden
	 *
	 * @param boolean $hidden
	 *
	 * @return Card
	 */
	public function setHidden($hidden)
	{
		$this->hidden = $hidden;

		return $this;
	}

	/**
	 * Get hidden
	 *
	 * @return boolean
	 */
	public function getHidden()
	{
		return $this->hidden;
	}

	/**
	 * Set permanent
	 *
	 * @param boolean $permanent
	 *
	 * @return Card
	 */
	public function setPermanent($permanent)
	{
		$this->permanent = $permanent;

		return $this;
	}

	/**
	 * Get permanent
	 *
	 * @return boolean
	 */
	public function getPermanent()
	{
		return $this->permanent;
	}


	/**
	 * Set Exceptional
	 *
	 * @param boolean $exceptional
	 *
	 * @return Card
	 */
	public function setExceptional($exceptional)
	{
		$this->exceptional = $exceptional;

		return $this;
	}

	/**
	 * Get exceptional
	 *
	 * @return boolean
	 */
	public function getExceptional()
	{
		return $this->exceptional;
	}


	/**
	 * Set doubleSided
	 *
	 * @param boolean $doubleSided
	 *
	 * @return Card
	 */
	public function setDoubleSided($doubleSided)
	{
		$this->doubleSided = $doubleSided;

		return $this;
	}

	/**
	 * Get doubleSided
	 *
	 * @return boolean
	 */
	public function getDoubleSided()
	{
		return $this->doubleSided;
	}


	/**
	 * Set octgnId
	 *
	 * @param boolean $octgnId
	 *
	 * @return Card
	 */
	public function setOctgnId($octgnId)
	{
		$this->octgnId = $octgnId;

		return $this;
	}

	/**
	 * Get octgnId
	 *
	 * @return boolean
	 */
	public function getOctgnId($part=0)
	{
		if ($part){
			$parts = explode(":", $this->octgnId);
			if (isset($parts[$part-1])){
				return $parts[$part-1];
			}
			return "";
		} else {
			return $this->octgnId;
		}
	}

	/**
	 * Add review
	 *
	 * @param \AppBundle\Entity\Review $review
	 *
	 * @return Card
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
	 * Add alternates
	 *
	 * @param \AppBundle\Entity\Card $alternate
	 *
	 * @return Card
	 */
	public function addAlternate(\AppBundle\Entity\Card $alternate)
	{
		$this->alternates[] = $alternate;

		return $this;
	}

	/**
	 * Remove review
	 *
	 * @param \AppBundle\Entity\Card $alternate
	 */
	public function removeAlternate(\AppBundle\Entity\Card $alternate)
	{
		$this->alternates->removeElement($alternate);
	}

	/**
	 * Get alternates
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getAlternates()
	{
		return $this->alternates;
	}
	/**
	 * Set pack
	 *
	 * @param \AppBundle\Entity\Pack $pack
	 *
	 * @return Card
	 */
	public function setPack(\AppBundle\Entity\Pack $pack = null)
	{
		$this->pack = $pack;

		return $this;
	}

	/**
	 * Get pack
	 *
	 * @return \AppBundle\Entity\Pack
	 */
	public function getPack()
	{
		return $this->pack;
	}

	/**
	 * Set type
	 *
	 * @param \AppBundle\Entity\Type $type
	 *
	 * @return Card
	 */
	public function setType(\AppBundle\Entity\Type $type = null)
	{
		$this->type = $type;

		return $this;
	}

	/**
	 * Get type
	 *
	 * @return \AppBundle\Entity\Type
	 */
	public function getType()
	{
		return $this->type;
	}
	
		/**
	 * Set subtype
	 *
	 * @param \AppBundle\Entity\Subtype $subtype
	 *
	 * @return Card
	 */
	public function setSubtype(\AppBundle\Entity\Subtype $subtype = null)
	{
		$this->subtype = $subtype;

		return $this;
	}

	/**
	 * Get subtype
	 *
	 * @return \AppBundle\Entity\Subtype
	 */
	public function getSubtype()
	{
		return $this->subtype;
	}

	/**
	 * Set faction
	 *
	 * @param \AppBundle\Entity\Faction $faction
	 *
	 * @return Card
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
	 * Set faction2
	 *
	 * @param \AppBundle\Entity\Faction $faction2
	 *
	 * @return Card
	 */
	public function setFaction2(\AppBundle\Entity\Faction $faction2 = null)
	{
		$this->faction2 = $faction2;

		return $this;
	}

	/**
	 * Get faction
	 *
	 * @return \AppBundle\Entity\Faction
	 */
	public function getFaction2()
	{
		return $this->faction2;
	}

	
		/**
	 * set linkedTo
	 *
	 * @param \AppBundle\Entity\Card $card
	 *
	 * @return Card
	 */
	public function setLinkedTo(\AppBundle\Entity\Card $linkedTo = null)
	{
		$this->linked_to = $linkedTo;
		return $this;
	}

	/**
	 * Get linkedTo
	 *
	 * @return \AppBundle\Entity\Card
	 */
	public function getLinkedTo()
	{
		return $this->linked_to;
	}

		/**
	 * set alternateOf
	 *
	 * @param \AppBundle\Entity\Card $card
	 *
	 * @return Card
	 */
	public function setAlternateOf(\AppBundle\Entity\Card $alternateOf = null)
	{
		$this->alternate_of = $alternateOf;
		return $this;
	}

	/**
	 * Get alternateOf
	 *
	 * @return \AppBundle\Entity\Card
	 */
	public function getAlternateOf()
	{
		return $this->alternate_of;
	}
	
	 /**
	 * set Encounter
	 *
	 * @param \AppBundle\Entity\Encounter $encounter
	 *
	 * @return Card
	 */
	public function setEncounter(\AppBundle\Entity\Encounter $encounter = null)
	{
		$this->encounter = $encounter;

		return $this;
	}
	
	/**
	 * Get encounter
	 *
	 * @return \AppBundle\Entity\Encounter
	 */
	public function getEncounter()
	{
		return $this->encounter;
	}

	/*
	* I18N vars
	*/
	private $locale = 'en';

	public function setTranslatableLocale($locale)
	{
		$this->locale = $locale;
	}	

	/**
	 * Add linkedFrom
	 *
	 * @param \AppBundle\Entity\Card $linkedFrom
	 *
	 * @return Card
	 */
	public function addLinkedFrom(\AppBundle\Entity\Card $linkedFrom)
	{
		$this->linked_from[] = $linkedFrom;

		return $this;
	}

	/**
	 * Remove linkedFrom
	 *
	 * @param \AppBundle\Entity\Card $linkedFrom
	 */
	public function removeLinkedFrom(\AppBundle\Entity\Card $linkedFrom)
	{
		$this->linked_from->removeElement($linkedFrom);
	}

	/**
	 * Get linkedFrom
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getLinkedFrom()
	{
		return $this->linked_from;
	}
	/**
	 * @var boolean
	 */
	private $myriad = false;

	/**
	 * @var String
	 */
	private $bondedTo;

	/**
	 * @var Integer
	 */
	private $bondedCount;

	/**
	 * Set myriad
	 *
	 * @param boolean $myriad
	 *
	 * @return Card
	 */
	public function setMyriad($myriad)
	{
		$this->myriad = $myriad;

		return $this;
	}

	/**
	 * Get myriad
	 *
	 * @return boolean
	 */
	public function getMyriad()
	{
		return $this->myriad;
	}

	/**
	 * Set bondedTo
	 *
	 * @param $realName
	 *
	 * @return Card
	 */
	public function setBondedTo($realName)
	{
		$this->bondedTo = $realName;

		return $this;
	}

	/**
	 * Get bondedTo
	 *
	 * @return string
	 */
	public function getBondedTo()
	{
		return $this->bondedTo;
	}

		/**
	 * Set bondedCount
	 *
	 * @param $count
	 *
	 * @return Card
	 */
	public function setBondedCount($count)
	{
		$this->bondedCount = $count;

		return $this;
	}

	/**
	 * Get bondedCount
	 *
	 * @return integer
	 */
	public function getBondedCount()
	{
		return $this->bondedCount;
	}
    /**
     * @var string
     */
    private $realSlot;


    /**
     * Set realSlot.
     *
     * @param string $realSlot
     *
     * @return Card
     */
    public function setRealSlot($realSlot)
    {
        $this->realSlot = $realSlot;

        return $this;
    }

    /**
     * Get realSlot.
     *
     * @return string
     */
    public function getRealSlot()
    {
        return $this->realSlot;
    }
}
