<?php
	require_once(__DIR__ . '/DB.php');
	require_once(__DIR__ . '/Gameplay/DataStructures/Collection.php');
	require_once(__DIR__ . '/Gameplay/Cards/Suit.php');
	require_once(__DIR__ . '/Gameplay/Cards/Card.php');
	require_once(__DIR__ . '/Gameplay/Cards/CardCollection.php');
	require_once(__DIR__ . '/Gameplay/Cards/Deck.php');
	require_once(__DIR__ . '/Gameplay/Cards/StandardCard.php');
	require_once(__DIR__ . '/Gameplay/Cards/StandardCardFactory.php');
	require_once(__DIR__ . '/Gameplay/Cards/StandardDeck.php');
	require_once(__DIR__ . '/Gameplay/Cards/StandardSuit.php');
	require_once(__DIR__ . '/Gameplay/Cards/StandardSuitFactory.php');
	require_once(__DIR__ . '/Gameplay/Game/TableEvent.php');
	require_once(__DIR__ . '/Gameplay/Game/TableEventLogger.php');
	require_once(__DIR__ . '/Gameplay/Game/TableFactory.php');
	require_once(__DIR__ . '/Gameplay/Game/TableSubject.php');
	require_once(__DIR__ . '/Gameplay/Game/TableObserver.php');
	require_once(__DIR__ . '/Gameplay/Game/Table.php');
	require_once(__DIR__ . '/Gameplay/Game/CommunityCards.php');
	require_once(__DIR__ . '/Gameplay/Game/Dealer.php');
	require_once(__DIR__ . '/Gameplay/Game/HandStrength.php');
	require_once(__DIR__ . '/Gameplay/Game/Muck.php');
	require_once(__DIR__ . '/Gameplay/Game/Player.php');
	require_once(__DIR__ . '/Gameplay/Game/PlayerActions.php');
	require_once(__DIR__ . '/Gameplay/Game/PlayerHand.php');
	require_once(__DIR__ . '/Gameplay/Game/Stack.php');
	require_once(__DIR__ . '/DesignPatterns/Singleton.php');
	require_once(__DIR__ . '/Gameplay/Rules/HandRanking.php');
	require_once(__DIR__ . '/Gameplay/Rules/HandEvaluator.php');
	require_once(__DIR__ . '/Gameplay/Rules/HandRankings/AbstractRanking.php');
	require_once(__DIR__ . '/Stringifier/RankingCardValue.php');
	require_once(__DIR__ . '/Stringifier/StandardCardValue.php');
	require_once(__DIR__ . '/Gameplay/Rules/HandRankings/RankingMediator.php');
	require_once(__DIR__ . '/Gameplay/Rules/HandRankings/HighCard.php');
	require_once(__DIR__ . '/Gameplay/Rules/HandRankings/OnePair.php');
	require_once(__DIR__ . '/Gameplay/Rules/HandRankings/TwoPairs.php');
	require_once(__DIR__ . '/Gameplay/Rules/HandRankings/ThreeOfAKind.php');
	require_once(__DIR__ . '/Gameplay/Rules/HandRankings/FourOfAKind.php');	
	require_once(__DIR__ . '/Gameplay/Rules/HandRankings/FullHouse.php');
	require_once(__DIR__ . '/Gameplay/Rules/HandRankings/Flush.php');
	require_once(__DIR__ . '/Gameplay/Rules/HandRankings/Straight.php');
	require_once(__DIR__ . '/Gameplay/Rules/HandRankings/RoyalFlush.php');
	require_once(__DIR__ . '/Gameplay/Rules/HandRankings/StraightFlush.php');
	require_once(__DIR__ . '/PokerAction.php');
?>