<?php

namespace TexasHoldemBundle\Gameplay\Cards;

/**
 * A Suit with a name and a symbol
 */
class Suit
{
    /**
     * The Suit's name
     *
     * @var string
     */
    private $name;

    /**
     * The Suit's symbol
     *
     * @var string
     */
    private $symbol;

    /**
     * The Suit's abbreviation
     *
     * @var string
     */
    private $abbreviation;
	
	/**
     * The Suit's ranking
     *
     * @var int
     */
	private $ranking;

    /**
     * Constructor
     *
     * @param  string $name The Suit's name
     * @param  string $symbol The Suit's symbol
     * @param  string $abbr The Suit's abbreviation
     */
    public function __construct($name = null, $symbol = null, $abbr = null, $ranking = null)
    {
        $this->setName($name);
        $this->setSymbol($symbol);
        $this->setAbbreviation($abbr);
		$this->setRanking($ranking);
    }

    /**
     * Return a string representation of the Suit
     *
     * @return string The Card represented as a string
     */
    public function __toString()
    {
        return $this->abbreviation;
    }

    /**
     * Get the Suit's name
     *
     * @return string The Suit's name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the Suit's name
     *
     * @param  string $name The Suit's name
     *
     * @return Suit
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the Suit's symbol
     *
     * @return string The Suit's symbol
     */
    public function getSymbol()
    {
        return $this->symbol;
    }

    /**
     * Set the Suit's symbol
     *
     * @param  string $symbol The Suit's symbol
     *
     * @return Suit
     */
    public function setSymbol($symbol)
    {
        $this->symbol = $symbol;
        return $this;
    }

    /**
     * Get the Suit's abbreviation
     *
     * @return string The Suit's abbreviation
     */
    public function getAbbreviation()
    {
        return $this->abbreviation;
    }

    /**
     * Set the Suit's abbreviation
     *
     * @param  string $abbreviation The Suit's abbreviation
     *
     * @return Suit
     */
    public function setAbbreviation($abbreviation)
    {
        $this->abbreviation = $abbreviation;
        return $this;
    }
	
	/**
     * Get the Suit's ranking
     *
     * @return int The Suit's ranking
     */
    public function getRanking()
    {
        return $this->ranking;
    }
	
	/**
     * Set the Suit's ranking
     *
     * @param  int The Suit's ranking
     *
     * @return Suit
     */
    public function setRanking($ranking)
    {
        $this->ranking = $ranking;
        return $this;
    }
}
