<?php

namespace AppBundle\Entity;

/**
 * Mwl
 */
class Taboo implements \JsonSerializable
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
	 * @var \DateTime
	 */
	private $dateStart;

	/**
	 * @var \DateTime
	 */
	private $dateUpdate;

	/**
	 * @var boolean
	 */
	private $active;

	/**
	 * @var string
	 */
	private $cards;

	/**
	 * Constructor
	 */
	public function __construct()
	{
			$this->active = false;
	}

	public function __toString()
	{
			return $this->name ?: '(unknown)';
	}

	public function jsonSerialize()
	{
		return [
				'id'            => $this->id,
				'code'          => $this->code,
				'name'          => $this->name,
				'active'        => $this->active,
				'date_start'    => $this->dateStart ? $this->dateStart->format('Y-m-d') : null,
				'date_update'    => $this->dateUpdate ? $this->dateUpdate->format('Y-m-d') : null,
				'cards'         => $this->cards,
		];
	}

	public function toString() {
		return $this->name;
	}

	public function serialize() {
		return [
				'id'            => $this->id,
				'code'          => $this->code,
				'name'          => $this->name,
				'active'        => $this->active,
				'date_start'    => $this->dateStart ? $this->dateStart->format('Y-m-d') : null,
				'date_update'    => $this->dateUpdate ? $this->dateUpdate->format('Y-m-d') : null,
				'cards'         => $this->cards,
		];
	}

	/**
	 * @return integer
	 */
	public function getId()
	{
			return $this->id;
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
			return $this->code;
	}

	/**
	 * @param string $code
	 * @return Taboo
	 */
	public function setCode(string $code)
	{
			$this->code = $code;

			return $this;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
			return $this->name;
	}

	/**
	 * @param string $name
	 * @return Taboo
	 */
	public function setName(string $name)
	{
			$this->name = $name;

			return $this;
	}

	/**
	 * @return \DateTime
	 */
	public function getDateStart()
	{
			return $this->dateStart;
	}

	/**
	 * @param \DateTime $dateStart
	 * @return Taboo
	 */
	public function setDateStart(\DateTime $dateStart)
	{
			$this->dateStart = $dateStart;

			return $this;
	}

	/**
	 * @return \DateTime
	 */
	public function getDateUpdate()
	{
			return $this->dateUpdate;
	}

	/**
	 * @param \DateTime $dateStart
	 * @return Taboo
	 */
	public function setDateUpdate(\DateTime $dateUpdate)
	{
			$this->dateUpdate = $dateUpdate;

			return $this;
	}

	/**
	 * @return boolean
	 */
	public function getActive()
	{
			return $this->active;
	}

	/**
	 * @param boolean $active
	 * @return Taboo
	 */
	public function setActive(bool $active)
	{
			$this->active = $active;

			return $this;
	}

	/**
	 * @return array
	 */
	public function getCards()
	{
			return $this->cards;
	}

	/**
	 * @param array $cards
	 * @return Taboo
	 */
	public function setCards(string $cards): self
	{
			$this->cards = $cards;

			return $this;
	}
}
