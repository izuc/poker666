<?php

namespace TexasHoldemBundle\Gameplay\Rules\HandRankings;

use TexasHoldemBundle\Gameplay\Cards\CardCollection;
use TexasHoldemBundle\Gameplay\Cards\StandardCard;
use TexasHoldemBundle\Gameplay\Cards\StandardSuitFactory;
use TexasHoldemBundle\Gameplay\Cards\StandardCardFactory;

abstract class AbstractRanking
{
    abstract public function hasRanking(CardCollection $cards): bool;

    protected function hasFlush(CardCollection $cards) 
	{
        $suits = [];
        foreach ($cards->getCards() as $card) {
            $suits[] = $card->getSuit()->getName();
        }

        $suits = array_count_values($suits);
		
        rsort($suits, SORT_NUMERIC);

        return $suits[0] >= 5;
    }
	
	protected function calcStraight($cards)
	{
		
		usort($cards, function($a, $b) {
			$a = str_split($a, strlen($a) - 1);
			$b = str_split($b, strlen($b) - 1);
			if ($a[0] == $b[0]) return 0;
			return ($a[0] < $b[0]) ? 1 : -1;
		});
		
		$set = array(array_shift($cards));
		foreach ($cards as $card) {
			$lastCard = str_split($set[count($set)-1], strlen($set[count($set)-1]) - 1);
			$card_arr = str_split($card, strlen($card) - 1);
			
			if ((int)$lastCard[0] - 1 != (int)$card_arr[0]) {
				$set = array($card);
			} else {
				$set[] = $card;
			}
			if (count($set) == 5) {
				break;
			}
		}
		
		if (count($set) == 5) {
			
			$cards = array();
			$cardFactory = new StandardCardFactory();
			$suitFactory = new StandardSuitFactory();
			foreach($set as $key => $val) {
				$card_arr = str_split($val, strlen($val) - 1);
				if ((int)$card_arr[0] == 1) $set[$key] = '14'.$card_arr[1];
				$cards[] = $cardFactory->make((int)$card_arr[0], $suitFactory->makeFromAbbr($card_arr[1]));  
			}
			
			return $cards;
		}
		
		return array();
	}
	
	protected function findStraight($cards)
	{
	
		$array = array();
        foreach ($cards->getCards() as $card) {
            $array[] = $card->getValue().$card->getSuit()->getAbbreviation();
			if ($card->getValue() == 14) {
				$array[] = '01'.$card->getSuit()->getAbbreviation();
			}
        }
		
		return $this->calcStraight($array);
	}
	
	protected function findStraightFlush($hand)
	{
		
		$suits = array('c'=>array(),'d'=>array(),'h'=>array(),'s'=>array());
		
		foreach ($hand->getCards() as $card) {
			$suits[$card->getSuit()->getAbbreviation()][] = $card->getValue().$card->getSuit()->getAbbreviation();
		}
		
		$cards = array();
		foreach ($suits as $suit) {
			if (count($suit) >= 5) {
				$cards = $suit;
				break;
			}
		}
		
		$array = array();
		foreach ($cards as $card) {
			$array[] = $card;
			$card_arr = str_split($card, strlen($card) - 1);
			if ((int)$card_arr[0] == 14) {
				$array[] = '01'.$card_arr[1];
			}
		}
		
		return $this->calcStraight($array);
	}	

    protected function hasStraight(CardCollection $cards)
	{
		return (!empty($this->findStraight($cards)));
    }
	
	protected function hasStraightFlush(CardCollection $cards)
	{
		return (!empty($this->findStraightFlush($cards)));
    }


    protected function countCardOccurrences(CardCollection $cards)
	{
        $values = array_fill(2, 13, 0);
        foreach ($cards->getCards() as $card) {
            ++$values[$card->getValue()];
        }
        rsort($values);
        return $values;
    }

    protected function getStraightValue(CardCollection $cards)
	{
        return $this->findStraight($cards);
    }

    protected function getCardOccurrencesAddWheel(CardCollection $cards)
	{
        $occurrences = $this->getOccurrences($cards);
        $occurrences[1] = $occurrences[14];

        return $occurrences;
    }

    protected function getCardOccurrences(CardCollection $cards)
	{
        $occurrences = $this->getOccurrences($cards);

        krsort($occurrences);
		
		usort($occurrences, function($a, $b) {
			if (count($a) == count($b)) return 0;
			return (count($a) < count($b)) ? 1 : -1;
		});

        return $occurrences;
    }
	
	
	protected function getOccurrences(CardCollection $cards)
	{
        $occurrences = array();
        foreach ($cards->getCards() as $card) {
			$occurrences[$card->getValue()][] = $card;
        }
		
        return $occurrences;
    }

    protected function getPossibleKickers(CardCollection $cards, array $rankCards)
	{
        $kickers = [];
		
        foreach ($cards->getCards() as $card) {
            $kickers[$card->getID()] = $card;
        }
		
		foreach ($rankCards as $card) {
			if (is_array($card)) {
				foreach ($card as $c) {
					unset($kickers[$c->getID()]);
				}
			} else { 
				unset($kickers[$card->getID()]);
			}
        }
		
		usort($kickers, function($a, $b) {
			if ($a->getID() == $b->getID()) return 0;
			return ($a->getValue() < $b->getValue()) ? 1 : -1;
		});
		
        return $kickers;
    }
}
