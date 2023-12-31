<?php

namespace TexasHoldemBundle\Gameplay\Rules;

use TexasHoldemBundle\Gameplay\Cards\CardCollection;
use TexasHoldemBundle\Gameplay\Game\HandStrength;
use TexasHoldemBundle\Gameplay\Rules\HandRankings\RankingMediator;
use TexasHoldemBundle\Gameplay\Rules\HandRankings\RoyalFlush;
use TexasHoldemBundle\Gameplay\Rules\HandRankings\StraightFlush;

class HandEvaluator
{
    /**
     * Compare an array of HandStrengths and return the same array sorted by
     * the best HandStrengths.
     *
     * @param array $hands
     *
     * @return array The sorted array of HandStrengths
     */
    public function compareHands(array $hands)
    {
        usort($hands, [$this, 'compareTwoHands']);

        return $hands;
    }

    /**
     * Gets the strength of a collection of cards.
     *
     * @param CardCollection $cards
     *
     * @return HandStrength|null
     */
    public function getStrength(CardCollection $cards)
    {
        // if ($cards->getSize() < 5 || $cards->getSize() > 7) {
            // return null;
        // }

        $rankingMediator = new RankingMediator();
        $ranking = $rankingMediator->getRanking($cards);
        $rankCardValues = $rankingMediator->getRankCardsValues($cards, $ranking);
        $kickers = $rankingMediator->getKickers($cards, $ranking, $rankCardValues);

        return new HandStrength($ranking, $rankCardValues, $kickers);
    }

    /**
     * Compare two HandStrengths.
     *
     * @param HandStrength $first  The first HandStrength
     * @param HandStrength $second The second HandStrength
     *
     * @return int 1 if the second hand is stronger than the first,
     *             -1 if the first hand is stronger than the second,
     *             0 if both hands have the same strength
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    public function compareTwoHands(HandStrength $first, HandStrength $second)
    {
		//echo var_export($first->getKickers());
		
        // Compare HandRankings
        if ($first->getRanking() > $second->getRanking()) {
            return -1;
        } else if ($first->getRanking() < $second->getRanking()) {
            return 1;
        }

        // Both HandStrength's have the same HandRanking.
        // Compare their ranking Card values
        $result = $this->compareCards(
            $first->getRankingCardValues(),
            $second->getRankingCardValues()
        );
        if (0 != $result) {
            return $result;
        }
		
        // Both ranking Card values are the same.
        // Compare their kicker Card values
        return $this->compareCards($first->getKickers(), $second->getKickers());
    }

    /**
     * Compare two arrays of Card values.
     * Each array is sorted with a reverse order and contain the
     * values of each Card.
     *
     * @param array $firstKickers
     * @param array $secondKickers
     *
     * @return int
     */
    private function compareCards(array $cards1, array $cards2)
	{
		
        for ($i = 0; $i < count($cards1) || $i < count($cards2); ++$i) {
			if (isset($cards1[$i]) && isset($cards2[$i])) { 
				if ($cards1[$i]->getValue() > $cards2[$i]->getValue()) {
					return -1;
				} else if ($cards1[$i]->getValue() < $cards2[$i]->getValue()) {
					return 1;
				}
			}
        }

        return 0;
    }
	
}
