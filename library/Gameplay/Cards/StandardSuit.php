<?php

namespace TexasHoldemBundle\Gameplay\Cards;

/**
 * A Suit with a name and a symbol.
 */
class StandardSuit extends Suit
{
	
	/**
     * Standard Suit Spades ♠.
     *
     * @var string
     */
    const SPADES = ['Spades', '♠', 's', 1];
	
	/**
     * Standard Suit Hearts ♥.
     *
     * @var string
     */
    const HEARTS = ['Hearts', '♥', 'h', 2];

    /**
     * Standard Suit Diamonds ♦.
     *
     * @var string
     */
    const DIAMONDS = ['Diamonds', '♦', 'd', 3];
	
	/**
     * Standard Suit Clubs ♣.
     *
     * @var string
     */
    const CLUBS = ['Clubs', '♣', 'c', 4];

    
}
