<?php

namespace TexasHoldemBundle\Stringifier;

use TexasHoldemBundle\DesignPatterns\Singleton;
use TexasHoldemBundle\Gameplay\Rules\HandRanking;

class RankingCardValue extends Singleton
{
    /**
     * Based on the HandStrength ranking gets the name
     * of the HandStrength rankingCardValues.
     *
     * Gets the name(s) of the HandStrength ranking Card(s).
     *
     * @return string the ranking Crads names
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function stringify(array $rankCardValues) {
        $str = '';
        foreach ($rankCardValues as $cards) {
			if (is_object($cards)) {
				$str = sprintf('%s%s', $str, $cards);
			} else if (is_array($cards)) {
				$card_string = '';
				foreach ($cards as $card) {
					$card_string .= $card->__toString();
				}
				$str = sprintf('%s%s', $str, $card_string);
			}
        }
        return $str;
    }
}
