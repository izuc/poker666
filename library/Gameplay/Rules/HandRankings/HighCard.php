<?php

namespace TexasHoldemBundle\Gameplay\Rules\HandRankings;

use TexasHoldemBundle\Gameplay\Cards\CardCollection;

class HighCard extends AbstractRanking
{
    /**
     * Check if there is one pair in the CardCollection.
     *
     * @param CardCollection $cards
     *
     * @return bool TRUE on success, FALSE on failure
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function hasRanking(CardCollection $cards): bool
    {
        return true;
    }

    /**
     * Gets this ranking's card values
     *
     * @param CardCollection $cards
     *
     * @return array Card values
     */
    public function getValue(CardCollection $cards): array
    {
        $occurrences = $this->getCardOccurrences($cards);
        $rankCards = $occurrences[0];

        return [$rankCards];
    }

    /**
     * Gets the kickers
     *
     * @param CardCollection $cards
     * @param array          $rankCards
     *
     * @return array The kickers
     */
    public function getKickers(CardCollection $cards, array $rankCards)
    {
        return array_slice(
            $this->getPossibleKickers($cards, $rankCards),
            0,
            4
        );
    }
}
