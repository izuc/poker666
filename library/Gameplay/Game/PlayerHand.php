<?php

namespace TexasHoldemBundle\Gameplay\Game;

use TexasHoldemBundle\Gameplay\Cards\CardCollection;

/**
 * @since  {nextRelease}
 *
 * @author Artur Alves <artur.ze.alves@gmail.com>
 */
class PlayerHand extends CardCollection
{
    protected $strength = null;
	
	public function setStrength($strength)
    {
        $this->strength = $strength;

        return $this;
    }
	
	public function getStrength()
    {
        return $this->strength;
    }
}
