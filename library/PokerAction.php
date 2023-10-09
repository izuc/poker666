<?php
	if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}

	$PlayerId = (int)(isset($_SESSION['player_id']) ? $_SESSION['player_id'] : 0);

	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	use TexasHoldemBundle\DB;
	use TexasHoldemBundle\Gameplay\Cards\Suit;
	use TexasHoldemBundle\Gameplay\Cards\Card;
	use TexasHoldemBundle\Gameplay\Cards\Deck;
	use TexasHoldemBundle\Gameplay\Cards\CardCollection;
	use TexasHoldemBundle\Gameplay\Cards\StandardCard;
	use TexasHoldemBundle\Gameplay\Cards\StandardDeck;
	use TexasHoldemBundle\Gameplay\Cards\StandardSuit;
	use TexasHoldemBundle\Gameplay\Cards\StandardSuitFactory;
	use TexasHoldemBundle\Gameplay\Game\Dealer;
	use TexasHoldemBundle\Gameplay\Game\PlayerHand;
	use TexasHoldemBundle\Gameplay\Game\Player;
	use TexasHoldemBundle\Gameplay\Game\Table;
	use TexasHoldemBundle\Gameplay\Rules\HandEvaluator;
	
	class PokerAction {
		
		const STAGES = ['Pre-flop', 'Post-flop', 'Post-turn', 'Post-river', 'Showdown'];
		
		public static function CreateCards() {
			$db = new DB();
			
			$suits = [
				StandardSuit::CLUBS,
				StandardSuit::DIAMONDS,
				StandardSuit::HEARTS,
				StandardSuit::SPADES,
			];
			
			$suitsFactory = new StandardSuitFactory();

			foreach ($suits as $suitName) {
				$suit = $suitsFactory->makeFromAbbr($suitName[2]);
				for ($i = 2; $i <= 14; $i++) {
					
					$card = new StandardCard($i, $suit);
					$card_id = $card->getFaceValue().$suit->__toString();
					
					$db->query("INSERT INTO cards SET card_id = ?, card_number = ?, card_suit = ?", array($card_id, $i, $suit->getAbbreviation()));
				}
			}
			
		}
		
		public static function SavePlayerAction($game_id, $player_id, $action, $amount = 0) {
			$db = new DB();
			$stmt = $db->query("SELECT current_stage FROM games WHERE game_id = ?", array($game_id));
			$stmt->bind_result($current_stage);
			if ($stmt->fetch()) {
				$db->query("INSERT INTO player_actions (game_id, player_id, stage, action, amount) VALUES (?, ?, ?, ?, ?)", array($game_id, $player_id, self::STAGES[$current_stage], $action, $amount));
			}
		}

		public static function assignBlinds($table_id, $game_id, $small_blind_amount, $big_blind_amount) {
			$db = new DB();

			// Get the blind positions
			$stmt = $db->query("SELECT dealer, small_blind, big_blind FROM games WHERE game_id = ?", array($game_id));
			$stmt->bind_result($dealer, $small_blind, $big_blind);
			$stmt->fetch();
			
			// Assign the small blind
			$db->query("INSERT INTO hands (player_id, game_id, bet) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE player_id = VALUES(player_id), game_id = VALUES(game_id), bet = VALUES(bet)", array($small_blind, $game_id, $small_blind_amount));

			// Assign the big blind
			$db->query("INSERT INTO hands (player_id, game_id, bet) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE player_id = VALUES(player_id), game_id = VALUES(game_id), bet = VALUES(bet)", array($big_blind, $game_id, $big_blind_amount));
			
			self::SavePlayerAction($game_id, $small_blind, 'small_blind', $small_blind_amount);
			self::SavePlayerAction($game_id, $big_blind, 'big_blind', $big_blind_amount);
		}

		
		public static function Bet($table_id, $player_id, $amount) {
			$db = new DB();
			
			$game_id = self::GetRunningGameId($table_id);
			
			if ($game_id && self::IsPlayersTurn($game_id, $player_id)) {
				
				$game_state = self::LoadGameState($table_id);
				$player_actions = $game_state['details']['player_actions'];

				// Check if player is allowed to bet
				$can_bet = false;
				foreach ($player_actions as $action) {
					if ($action['action'] === 'bet') {
						$can_bet = true;
						break;
					}
				}

				if ($can_bet) {
					// Get player's current pot
					$pot = self::GetPlayerPot($table_id, $player_id);
					
					if ($pot > 0) {

						// Check if player is going all in
						$all_in = ($pot <= $amount) ? 1 : 0;

						// If player does not have enough in pot, go all in
						if ($pot < $amount) {
							$amount = $pot;
						}

						// Update player's pot
						$db->query("UPDATE seats SET pot = pot - ? WHERE table_id = ? AND player_id = ?", array($amount, $table_id, $player_id));

						// Update player's bet and all_in status
						$db->query("UPDATE hands SET bet = bet + ?, all_in = ? WHERE game_id = ? AND player_id = ?", array($amount, $all_in, $game_id, $player_id));
						
						self::SavePlayerAction($game_id, $player_id, 'bet', $amount);
						
						self::NextTurn($game_id);
						return true;
					}
				}
				
			}
			
			return false;
		}

		public static function Call($table_id, $player_id) {
			$db = new DB();

			$game_id = self::GetRunningGameId($table_id);

			if ($game_id && self::IsPlayersTurn($game_id, $player_id)) {
				$game_state = self::LoadGameState($table_id);
				$player_actions = $game_state['details']['player_actions'];

				// Check if player is allowed to call
				$can_call = false;
				foreach ($player_actions as $action) {
					if ($action['action'] === 'call') {
						$can_call = true;
						break;
					}
				}

				if ($can_call) {
					// Get the highest bet on the table
					$amount = (int)self::GetLastAmount($game_id);

					// Get player's current pot
					$pot = self::GetPlayerPot($table_id, $player_id);

					if ($pot > 0) {

						// Check if player is going all in
						$all_in = ($pot <= $amount) ? 1 : 0;

						// If player does not have enough in pot, go all in
						if ($pot < $amount) {
							$amount = $pot;
						}

						// Update player's pot
						$db->query("UPDATE seats SET pot = pot - ? WHERE table_id = ? AND player_id = ?", array($amount, $table_id, $player_id));

						// Update player's bet and all_in status
						$db->query("UPDATE hands SET bet = bet + ?, all_in = ? WHERE game_id = ? AND player_id = ?", array($amount, $all_in, $game_id, $player_id));

						self::SavePlayerAction($game_id, $player_id, 'call', $amount);

						self::NextTurn($game_id);
						return true;
					}
				}
			}
			return false;
		}

		public static function Raise($table_id, $player_id, $amount) {
			$db = new DB();
			
			$game_id = self::GetRunningGameId($table_id);
			
			if ($game_id && self::IsPlayersTurn($game_id, $player_id)) {
				
				$game_state = self::LoadGameState($table_id);
				$player_actions = $game_state['details']['player_actions'];

				// Check if player is allowed to raise
				$can_raise = false;
				foreach ($player_actions as $action) {
					if ($action['action'] === 'raise') {
						$can_raise = true;
						break;
					}
				}

				if ($can_raise) {

					// Get player's current pot
					$pot = self::GetPlayerPot($table_id, $player_id);
					
					if ($pot > 0) {

						// Check if player is going all in
						$all_in = ($pot <= $amount) ? 1 : 0;

						// If player does not have enough in pot, go all in
						if ($pot < $amount) {
							$amount = $pot;
						}

						// Update player's pot
						$db->query("UPDATE seats SET pot = pot - ? WHERE table_id = ? AND player_id = ?", array($amount, $table_id, $player_id));

						// Update player's bet and all_in status
						$db->query("UPDATE hands SET bet = bet + ?, all_in = ? WHERE game_id = ? AND player_id = ?", array($amount, $all_in, $game_id, $player_id));
						
						self::SavePlayerAction($game_id, $player_id, 'raise', $amount);
						
						self::NextTurn($game_id);
						return true;
					}
					
				}
				
			}
			
			return false;
		}
		
		// Check function
		public static function Check($table_id, $player_id) {
			// Check function usually doesn't do anything in this context, 
			// as it means the player is not betting, raising or folding, 
			// and the game will proceed to the next player or the next round.
			
			$game_id = self::GetRunningGameId($table_id);
			
			//echo $player_id;
			
			if ($game_id && self::IsPlayersTurn($game_id, $player_id)) {
				
				$game_state = self::LoadGameState($table_id);
				$player_actions = $game_state['details']['player_actions'];

				// Check if player is allowed to check
				$can_check = false;
				foreach ($player_actions as $action) {
					if ($action['action'] === 'check') {
						$can_check = true;
						break;
					}
				}

				if ($can_check) {
				
					self::SavePlayerAction($game_id, $player_id, 'check');
					
					self::NextTurn($game_id);
					return true;
					
				}
			}
			
			return false;
		}
		
		// Fold function
		public static function Fold($table_id, $player_id) {
			$db = new DB();

			$game_id = self::GetRunningGameId($table_id);
			
			if ($game_id && self::IsPlayersTurn($game_id, $player_id)) {
				// Mark player as folded
				$db->query("UPDATE hands SET fold = 1, all_in = 0 WHERE player_id = ? AND game_id = ?", array($player_id, $game_id));
				
				self::SavePlayerAction($game_id, $player_id, 'fold');
				
				self::NextTurn($game_id);
				
				return true;
			}
			
			return false;
		}

		public static function GetPlayerPot($table_id, $player_id) {
			$db = new DB();
			
			if ($stmt = $db->query("SELECT pot FROM seats WHERE table_id = ? AND player_id = ?", array($table_id, $player_id))) {
				$stmt->bind_result($pot);
				if ($stmt->fetch()) {
					return $pot;
				}
			}
			
			return null;
		}
		
		public static function GetLastRunningGameId($table_id) {
			$db = new DB();
			
			if ($stmt = $db->query("SELECT MAX(game_id) FROM games WHERE table_id = ?", array($table_id))) {
				$stmt->bind_result($last_game_id);
				if ($stmt->fetch()) {
					return $last_game_id;
				}
			}
			
			return null;
		}
		
		public static function GetPlayerBalance($player_id) {
			$db = new DB();
			if ($stmt = $db->query("SELECT balance FROM players WHERE player_id = ?", array($player_id))) {
				$stmt->bind_result($balance);
				if ($stmt->fetch()) {
					return $balance;
				}
			}
			
			return null;
		}

		// Get highest bet
		public static function GetLastAmount($game_id) {
			$db = new DB();
			
			// Fetch the current stage
			$stmt = $db->query("SELECT current_stage, last_move FROM games WHERE game_id = ?", array($game_id));
			$stmt->bind_result($current_stage, $last_move);
			$stmt->fetch();
			
			$last_bet = self::GetLastBet($game_id, $current_stage);
			if ($last_bet) {
				return $last_bet['amount'];
			}
			
			return 0;
		}
		
		public static function CreateTable($table_name, $table_seats) {
			$db = new DB();
			$db->query("INSERT INTO tables SET table_name = ?, table_seats = ?", array($table_name, $table_seats));
			return $db->getLastInsertID();
		}
		
		public static function LoadTable($table_id) {
			$db = new DB();
			if ($stmt = $db->query("SELECT table_id, table_name, table_seats FROM tables WHERE table_id = ?", array($table_id))) {								
				$stmt->bind_result($table_id, $table_name, $table_seats);
				if ($stmt->fetch()) {
					return new Table($table_name, $table_seats);
				}
			}
			return null;
		}
		
		public static function getActivePlayers($table_id) {
			$db = new DB();
			return $db->getObjects("SELECT player_id, position FROM seats WHERE table_id = ? AND active = 1 ORDER BY position", array($table_id));
		}
		
		public static function rotatePositions($players, $shifts) {
			for($i = 0; $i < $shifts; $i++) {
				array_push($players, array_shift($players));
			}
			return $players;
		}
		
		public static function CreateGame($table_id) {
			$db = new DB();

			// Initialize the previous game details
			$previous_game_details = null;

			// Fetch the game_id, current_stage, dealer, small_blind, big_blind from the last game for the specified table_id
			$stmt = $db->query("SELECT game_id, current_stage, dealer, small_blind, big_blind, result FROM games WHERE table_id = ? ORDER BY game_id DESC LIMIT 1", array($table_id));
			$stmt->bind_result($last_game_id, $current_stage, $lastDealer, $lastSmallBlind, $lastBigBlind, $lastResult);

			if ($stmt->fetch()) {
				$previous_game_details = array(
					'dealer' => $lastDealer,
					'small_blind' => $lastSmallBlind,
					'big_blind' => $lastBigBlind
				);

				// If the current_stage value is greater than 0
				if ($current_stage > 0) {
					self::calculateAllPlayerWinnings($last_game_id, $table_id);

					// Set is_running to 0 for the previous game
					$db->query("UPDATE games SET is_running = 0 WHERE game_id = ?", array($last_game_id));
				}
			}

			// Get active players and their positions
			$active_players = self::getActivePlayers($table_id);
			$players_count = count($active_players);

			// Ensure there are at least 2 active players
			if ($players_count < 2) {
				throw new Exception("Not enough players to start the game");
			}

			// Determine rotation start position based on the previous game details
			$rotation_start = $previous_game_details !== null ? $previous_game_details['dealer'] : 1;

			// Rotate player positions
			$rotated_players = self::rotatePositions($active_players, $rotation_start);

			$dealer_id = $rotated_players[0]['player_id'];
			$last_dealer = $dealer_id;
			$small_blind_id = $dealer_id;  // In a 2-player game, the dealer is also the small blind
			$big_blind_id = $rotated_players[1]['player_id']; // The other player is the big blind
			$move_id = $big_blind_id; // In a 2-player game, big blind will have the first move

			if ($players_count >= 3) {
				$small_blind_id = $rotated_players[1]['player_id'];
				$move_id = $rotated_players[2]['player_id'];
			}
			if ($players_count >= 4) {
				$big_blind_id = $rotated_players[2]['player_id'];
				$move_id = $rotated_players[3]['player_id'];
			}
			
			$message = '';
			$winners = json_decode($lastResult);
			foreach ($winners as $winner) {
				$message .= $winner->player_name . ' - ' . $winner->strength . '<br />';
			}

			// Insert a new game with calculated positions
			$db->query("INSERT INTO games (table_id, dealer, last_dealer, small_blind, big_blind, move, last_move, msg, turn_count, current_stage, is_running) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 1)", 
						array($table_id, $dealer_id, $last_dealer, $small_blind_id, $big_blind_id, $big_blind_id, $move_id, $message));
			
			$game_id = $db->getLastInsertID();

			self::assignBlinds($table_id, $game_id, 50, 100);

			return $game_id;
		}
		
		public static function NextTurn($game_id) {
			$db = new DB();

			// Get the current game details
			$game = $db->getObject("SELECT move, last_move, table_id, turn_count FROM games WHERE game_id = ?", array($game_id));

			// Get active players and their positions
			$active_players = self::getActivePlayers($game['table_id']);
			
			// Check if there's only one player who hasn't folded yet.
			$players_not_folded = $db->getObjects("SELECT player_id FROM hands WHERE game_id = ? AND fold = 0", array($game_id));
			if (count($players_not_folded) <= 1) {
				self::CreateGame($game['table_id']);
				return;
			}

			// Find the index of the current move player
			$move_index = array_search($game['move'], array_column($active_players, 'player_id'));

			// Create a new array where the player whose turn it is now is at the front
			$ordered_players = array_merge(array_slice($active_players, $move_index), array_slice($active_players, 0, $move_index));
			
			$players_all_in = $db->getObjects("SELECT player_id FROM hands WHERE game_id = ? AND all_in = 1", array($game_id));
			
			// If all active players have gone all in
			if (count($players_all_in) == count($active_players)) {
				// Move to next stage
				$db->query("UPDATE games SET current_stage = current_stage + 1, turn_count = 0 WHERE game_id = ?", array($game_id));
				return;
			}

			do {
				// Rotate the ordered players array
				$rotated_players = self::rotatePositions($ordered_players, 1);
				// Calculate new move
				$move_id = $rotated_players[0]['player_id'];
				$player_state = $db->getObject("SELECT fold, all_in FROM hands WHERE player_id = ? AND game_id = ?", array($move_id, $game_id));
				
			} while($player_state['fold'] == 1 || $player_state['all_in'] == 1);

			// Update the move and increment the turn count
			$db->query("UPDATE games SET move = ?, last_move = ?, turn_count = turn_count + 1 WHERE game_id = ?", array($move_id, $game['move'], $game_id));

			// Check if the round is over
			if ($game['turn_count'] + 1 >= count($active_players)) {
				// A round is over when everyone has had a turn
				// Update the current_stage and turn_count in the database
				$db->query("UPDATE games SET current_stage = current_stage + 1, turn_count = 0 WHERE game_id = ?", array($game_id));
			}
		}

		
		public static function IsPlayersTurn($game_id, $player_id) {
			$db = new DB();
			
			// Get the current turn from the games table
			$stmt = $db->query("SELECT move FROM games WHERE game_id = ?", array($game_id));
			$stmt->bind_result($current_turn);
			
			if ($stmt->fetch() && $current_turn == $player_id) {
				return true;
			}
			
			return false;
		}
		
		public static function AutoFold($player_id, $game_id) {
			$db = new DB();
			
			if (self::IsPlayersTurn($game_id, $player_id)) {
				
				$stmt = $db->query("SELECT table_id, current_stage, last_move FROM games WHERE game_id = ?", array($game_id));
				$stmt->bind_result($table_id, $current_stage, $last_move);
				
				if ($stmt->fetch()) {
					
					if ($last_move) {
						
						$last_action = self::GetLastPlayerAction($game_id, $current_stage, $last_move);
						
						// Assuming GetLastPlayerAction returns a datetime of the last action
						if ($last_action !== null) {
							$datetime = new DateTime('now', new DateTimeZone('Australia/Sydney'));
							$datetime->setTimezone(new DateTimeZone('UTC'));
							$now_timestamp = $datetime->getTimestamp();
							
							$last_action_timestamp = $last_action['timestamp'];

							// calculate the difference
							$time_difference = $now_timestamp - $last_action_timestamp;
							
							// if more than 20 seconds fold
							if ($time_difference > 20) {
								self::Fold($table_id, $player_id);
							}
						}
						
					}
				}
			}
		}
		
		public static function GetGameState($player_id, $table_id) {
			$game_id = null;
			
			try {
				$game_id = self::LoadOrCreateGame($table_id);
			} catch (Exception $ex) {}
			
			if ($game_id === null) {
								
				// Get the player's data
				$db = new DB();
				$stmt = $db->query("SELECT player_id FROM seats WHERE table_id = ?", array($table_id));
				$stmt->bind_result($player_id);
				$players_data = array();
				while ($stmt->fetch()) {
					$player_data = self::LoadPlayerState($player_id);
					if ($player_data !== null) {
						$players_data[$player_data['position']] = $player_data;
					}
				}

				return array(
					'success' => false,
					'message' => 'No game running',
					'players' => $players_data
				);
			}
			
			PokerAction::RunGame($game_id, $table_id);
			self::AutoFold($player_id, $game_id);
			
			return self::LoadGameState($table_id);
		}
		
		public static function LoadPlayerState($player_id) {
			$db = new DB();
			
			// Query to get player state from the database
			if ($stmt = $db->query("SELECT p.player_name, s.pot, s.position
									FROM players p 
									LEFT JOIN seats s ON p.player_id = s.player_id 
									WHERE p.player_id = ?", array($player_id))) {
				$stmt->bind_result($player_name, $pot, $position);
				
				// If the query returns a result
				if ($stmt->fetch()) {
					// Return the player state
					return array(
						'player_id' => $player_id,
						'player_name' => $player_name,
						'pot' => $pot,
						'card1' => '',
						'card2' => '',
						'position' => $position
					);
				}
			}
			
			// If no result is returned from the query, return null
			return null;
		}

		
		public static function AuthenticatePlayer($username, $password) {
			$db = new DB();
			if ($stmt = $db->query("SELECT player_id FROM players WHERE player_name = ? AND password = ?", array($username, md5($password)))) {								
				$stmt->bind_result($player_id);
				if ($stmt->fetch()) {
					return $player_id;
				}
			}
			return null;
		}

		public static function CreatePlayer($username, $password) {
			$db = new DB();
			$db->query("INSERT INTO players SET player_name = ?, password = ?", array($username, md5($password)));
			return $db->getLastInsertID();				
		}
		
		public static function LoadPlayer($player_id) {
			$db = new DB();
			if ($stmt = $db->query("SELECT player_name FROM players WHERE player_id = ?", array($player_id))) {								
				$stmt->bind_result($player_name);
				
				if ($stmt->fetch()) {
					return new Player($player_id, $player_name);
				}
				
			}
			return null;
		}
		
		public static function JoinGame($table_id, $player_id, $game_id, $position) {
			$db = new DB();
			$db->query("INSERT INTO hands (player_id, game_id, position) VALUES (?, ?, ?) 
						ON DUPLICATE KEY UPDATE player_id = VALUES(player_id), game_id = VALUES(game_id), position = VALUES(position)", 
						array($player_id, $game_id, $position));
		}
		
		public static function AddPlayer($player_id, $table_id, $position) {
			$db = new DB();
			$balance = self::GetPlayerBalance($player_id);
			if ($balance >= 1000) {
				$db->query("UPDATE players SET balance = balance - 1000 WHERE player_id = ?", array($player_id));
				$db->query("INSERT INTO seats (table_id, player_id, position, pot) VALUES (?, ?, ?, ?)
							ON DUPLICATE KEY UPDATE table_id = VALUES(table_id), player_id = VALUES(player_id), position = VALUES(position), pot = VALUES(pot)", array($table_id, $player_id, $position, 1000));
			}
		}

		
		public static function Logout($player_id, $table_id) {
			$db = new DB();
			$pot = self::GetPlayerPot($table_id, $player_id);
			$db->query("UPDATE players SET balance = balance + ? WHERE player_id = ?", array($pot, $player_id));
			$db->query("DELETE FROM seats WHERE table_id = ? AND player_id = ?", array($table_id, $player_id));
		}
		
		public static function UpdateCommunityCards($cards, $game_id) {
			$db = new DB();
			
			$card1 = $cards[0]->getID();
			$card2 = $cards[1]->getID();
			$card3 = $cards[2]->getID();
			if (isset($cards[3])) {
				$card4 = $cards[3]->getID();
			} else {
				$card4 = '';
			}
			if (isset($cards[4])) {
				$card5 = $cards[4]->getID();
			} else {
				$card5 = '';
			}
			
			$db->query("UPDATE games SET card1 = ?, card2 = ?, card3 = ?, card4 = ?, card5 = ? WHERE game_id = ?", array($card1, $card2, $card3, $card4, $card5, $game_id));
		}
		
		public static function UpdateHand(Player $player, $game_id) {
			$db = new DB();
			$player_id = (int)$player->getID();
			$hand = $player->getHand();
			$cards = $hand->getCards();
			$card1 = $cards[0]->getID();
			$card2 = $cards[1]->getID();
			$db->query("UPDATE hands SET card1 = ?, card2 = ? WHERE player_id = ? AND game_id = ?", array($card1, $card2, $player_id, $game_id));
		}
		
		public static function CalcWinners($table, $table_id, $game_id) {
			$db = new DB();
			$evaluator = new HandEvaluator();
			$i = 0;
			$ranking = 1;
			$pot = 0;

			if ($stmt = $db->query("SELECT SUM(bet) as pot FROM hands WHERE game_id = ?", array($game_id))) {
				$stmt->bind_result($pot);
				if ($stmt->fetch()) {
					$pot = $pot;
				}
			}

			$players = $table->getWinners();
			$winners = array();

			foreach ($players as $player) {
				$player_id = (int)$player->getID();
				$all_in = $db->getObject("SELECT h.bet, h.all_in, p.player_name FROM hands h INNER JOIN players p ON p.player_id = h.player_id WHERE h.player_id = ? AND h.game_id = ?", array($player_id, $game_id));
				$player->bet = $all_in['bet'];
				$player->all_in = $all_in['all_in'];
				$player->player_name = $all_in['player_name'];
			}

			usort($players, function($a, $b) {
				return $a->all_in - $b->all_in;
			});

			$remaining_pot = $pot;
			$rank_1_players = [];
			$total_contribution_rank_1 = 0;

			foreach ($players as $player) {
				$player_id = (int)$player->getID();

				$folded = $db->getObject("SELECT fold FROM hands WHERE player_id = ? AND game_id = ?", array($player_id, $game_id));
				if ($folded['fold'] == 1) {
					continue;
				}

				if (isset($players[$i-1]) && ($table->compareTwoPlayers($player,$players[$i-1]) == 0)) {
					$ranking--;
				}
				
				$player->ranking = $ranking;

				// Keep track of all players with rank 1 and their total contribution
				if ($ranking == 1) {
					$rank_1_players[] = $player;
					$total_contribution_rank_1 += max($player->bet, $player->all_in);
				}
				

				$ranking++;
				$i++;
			}

			// Now calculate the winnings for each rank 1 player
			foreach ($rank_1_players as $player) {
				$player_contribution = max($player->bet, $player->all_in);

				if ($total_contribution_rank_1 != 0) {
					$eligible_amount = $pot * ($player_contribution / $total_contribution_rank_1);
				} else {
					$eligible_amount = $pot;
				}

				if ($remaining_pot < $eligible_amount) {
					$winning_amount = $remaining_pot;
					$remaining_pot = 0;
				} else {
					$winning_amount = $eligible_amount;
					$remaining_pot -= $winning_amount;
				}

				$db->query("UPDATE hands SET result = ?, ranking = ?, pot = ? WHERE player_id = ? AND game_id = ?", array($player->getHand()->getStrength()->__toString(), $player->ranking, $winning_amount, $player->getID(), $game_id));

				$winners[] = array('player_id' => $player->getID(), 'player_name' => $player->player_name, 'ranking' => $player->ranking, 'strength' => $player->getHand()->getStrength()->__toString(), 'winnings' => $winning_amount);
			}
			
			$db->query("UPDATE games SET pot = ?, result = ? WHERE game_id = ?", array($pot, json_encode($winners), $game_id));
		}
		
		public static function calculateAllPlayerWinnings($game_id, $table_id) {
			$db = new DB();
			
			$data = $db->getObject("SELECT result AS winners FROM games WHERE game_id = ?", array($game_id));
			$result = json_decode($data['winners']);
			
			if (is_array($result)) {
				foreach ($result as $row) {
					if ($row->ranking == 1 && $row->winnings > 0) {
						$db->query("UPDATE seats SET pot = pot + ? WHERE player_id = ? AND table_id = ?", array($row->winnings, $row->player_id, $table_id));
					}
				}
			}
		}
		
		public static function DisplayPlayers($players) {
			foreach ($players as $player) {
				echo $player->getName() . ' - ' . $player->getHand()->toCliOutput(). '<br />';
			}
		}
		
		public static function DisplayCommunityCards($cards) {
			echo 'Community cards: '.$cards->toCliOutput(). '<br /><br />';
		}
		
		public static function DisplayWinners($game_id) {
			$db = new DB();
			$value = '';
			if ($stmt = $db->query("SELECT p.player_id, p.player_name, h.card1, h.card2, h.result, h.ranking FROM hands h INNER JOIN players p ON p.player_id = h.player_id WHERE h.game_id = ? ORDER BY h.ranking ASC", array($game_id))) {								
				$stmt->bind_result($player_id, $player_name, $card1, $card2, $result, $ranking);
			
				while ($stmt->fetch()) {
					$value .= $ranking . ') ' . $player_name . ' - [' . $card1 . '],[' . $card2  . ']<br />';
					$value .= $result . '<br /><hr />';
				}
			}
			return $value;
		}
		
		public static function RunGame($game_id, $table_id) {
			$db = new DB();
					
			$stmt = $db->query("SELECT COUNT(*) FROM seats WHERE table_id = ?", array($table_id));
			$stmt->bind_result($count);

			if ($stmt->fetch()) {
				if ($count >= 2) {
					if ($game_id !== null) {
					
						$table = PokerAction::LoadTable($table_id);
						
						// Get the current current_stage and community cards
						$stmt = $db->query("SELECT current_stage, card1, card2, card3, card4, card5 FROM games WHERE game_id = ?", array($game_id));
						$stmt->bind_result($current_stage, $card1, $card2, $card3, $card4, $card5);
						$stmt->fetch();

						$deck = PokerAction::LoadDeck($game_id);

						$dealer = new Dealer($deck, $table);
						$dealer->setTable($table);
						
						if ($stmt = $db->query("SELECT player_id, position FROM seats WHERE table_id = ?", array($table_id))) {
							$stmt->bind_result($player_id, $position);
							while ($stmt->fetch()) {
								$player = PokerAction::LoadPlayer($player_id);
								if ($player !== null) {
									$table->addPlayer($player);
									PokerAction::JoinGame($table_id, $player_id, $game_id, $position);
								}
							}
						}

						$dealer->deal();

						foreach ($table->getPlayers() as $player) {
							PokerAction::UpdateHand($player, $game_id);
						}

						switch ($current_stage) {
							case 1:
								$dealer->dealFlop();
								$cards = $table->getCommunityCards()->getCards();
								PokerAction::UpdateCommunityCards($cards, $game_id);
								break;
							case 2:
								$dealer->dealFlop();
								$dealer->dealTurn();
								$cards = $table->getCommunityCards()->getCards();
								PokerAction::UpdateCommunityCards($cards, $game_id);
								break;
							case 3:
								$dealer->dealFlop();
								$dealer->dealTurn();
								$dealer->dealRiver();
								$cards = $table->getCommunityCards()->getCards();
								PokerAction::UpdateCommunityCards($cards, $game_id);
								break;
						}
						if (!empty($table->getPlayers())) {
							// Calculates the winners based on the available cards.
							PokerAction::CalcWinners($table, $table_id, $game_id);
						}
					}
				}
			}
		}
		
		public static function GetPlayerActions($game_id, $move, $dealer, $small_blind, $big_blind, $current_stage, $last_bet, $last_action) {
			$actions = array();

			if ($game_id) {
				$action = (!empty($last_bet['action'])) ? $last_bet['action'] : $last_action['action'];
				$amount = ($last_bet['amount']) ? $last_bet['amount'] : $last_action['amount'];

				if (($current_stage >= 0) && ($big_blind == $move)) {
					array_push($actions, array('action' => 'check', 'amount' => 0), array('action' => 'bet', 'amount' => $amount));
				} else {
					switch ($action) {
						case 'big_blind':
						case 'small_blind':
						case 'bet':
						case 'raise':
							if ($amount > 0) {
								array_push($actions, array('action' => 'call', 'amount' => $amount), array('action' => 'raise', 'amount' => $amount*2));
							}
							break;
						case 'check':
							array_push($actions, array('action' => 'check', 'amount' => 0), array('action' => 'bet', 'amount' => $amount));
							break;
						case '':
							// At the start of the round
							array_push($actions, array('action' => 'check', 'amount' => 0), array('action' => 'bet', 'amount' => $amount));
							break;
					}
				}
				// Fold is always an option
				array_push($actions, array('action' => 'fold', 'amount' => 0));
			}
			return $actions;
		}
		
		public static function GetLastBet($game_id, $current_stage) {
			global $PlayerId;
			
			$db = new DB();
			$stmt = $db->query("SELECT player_id, action, MAX(amount) AS max_amount, MAX(created_at) AS latest_timestamp 
								FROM player_actions 
								WHERE game_id = ? AND stage = ?
								GROUP BY action, player_id", array($game_id, self::STAGES[$current_stage]));
			$stmt->bind_result($player_id, $action, $amount, $created_at);
			if ($stmt->fetch()) {
				$datetime = new DateTime($created_at, new DateTimeZone('Australia/Sydney'));
				$datetime->setTimezone(new DateTimeZone('UTC'));
				$timestamp = $datetime->getTimestamp();
				return array('player_id' => $player_id, 'action' => $action, 'amount' => $amount, 'timestamp' => $timestamp);
			}
			$datetime = new DateTime('now', new DateTimeZone('Australia/Sydney'));
			$datetime->setTimezone(new DateTimeZone('UTC'));
			$timestamp = $datetime->getTimestamp();
			return array('player_id' => 0, 'action' => '', 'amount' => 0, 'timestamp' => $timestamp);
		}
		
		public static function GetLastPlayerAction($game_id, $current_stage, $player_id) {
			$db = new DB();
			$stmt = $db->query("SELECT player_id, action, amount, created_at FROM player_actions WHERE game_id = ? AND stage = ? AND player_id = ? ORDER BY created_at DESC LIMIT 1", array($game_id, self::STAGES[$current_stage], $player_id));
			$stmt->bind_result($player_id, $action, $amount, $created_at);
			if ($stmt->fetch()) {
				$datetime = new DateTime($created_at, new DateTimeZone('Australia/Sydney'));
				$datetime->setTimezone(new DateTimeZone('UTC'));
				$timestamp = $datetime->getTimestamp();
				return array('player_id' => $player_id, 'action' => $action, 'amount' => $amount, 'timestamp' => $timestamp);
			}
			$datetime = new DateTime('now', new DateTimeZone('Australia/Sydney'));
			$datetime->setTimezone(new DateTimeZone('UTC'));
			$timestamp = $datetime->getTimestamp();
			return array('player_id' => 0, 'action' => '', 'amount' => 0, 'timestamp' => $timestamp);
		}
		
		public static function LoadGameState($table_id) {
			global $PlayerId;
			$db = new DB();
			$players = array();
			$default_card = ''; // or whatever default you want
			$default_message = ''; // or whatever default you want
			$default_details = array('move' => 0, 'dealer' => 0, 'current_stage' => 0, 'table_pot' => 0, 'last_move' => 0);
			
			if ($stmt = $db->query("SELECT game_id, move, dealer, small_blind, big_blind, current_stage, pot, last_move, card1, card2, card3, card4, card5, msg, timestamp FROM games WHERE table_id = ? ORDER BY game_id DESC LIMIT 1", array($table_id))) {
				$stmt->bind_result($game_id, $move, $dealer, $small_blind, $big_blind, $current_stage, $table_pot, $last_move, $card1, $card2, $card3, $card4, $card5, $msg, $timestamp);
				if ($stmt->fetch()) {
					$last_bet = self::GetLastBet($game_id, $current_stage);
					$last_action = self::GetLastPlayerAction($game_id, $current_stage, $last_move);
					$player_actions = self::GetPlayerActions($game_id, $move, $dealer, $small_blind, $big_blind, $current_stage, $last_bet, $last_action);
					
					$details = array('move' => $move ?? 0, 'dealer' => $dealer ?? 0, 'current_stage' => $current_stage ?? 0, 'table_pot' => $table_pot ?? 0, 'last_move' => $last_move ?? 0, 'player_actions' => $player_actions, 'last_action' => $last_action, 'last_bet' => $last_bet);
					$communityCards = [$card1 ?? $default_card, $card2 ?? $default_card, $card3 ?? $default_card, $card4 ?? $default_card, $card5 ?? $default_card];
					$msg = $msg ?? $default_message;
					
					if ($stmt = $db->query("SELECT 
												p.player_id, 
												p.player_name, 
												h.card1, 
												h.card2, 
												s.pot, 
												h.bet, 
												s.position 
											FROM 
												players p 
											INNER JOIN 
												seats s ON s.player_id = p.player_id 
											LEFT JOIN 
												hands h ON h.player_id = p.player_id AND h.game_id = ? 
											WHERE 
												s.table_id = ?", array($game_id, $table_id))) {
						$stmt->bind_result($player_id, $player_name, $player_card1, $player_card2, $pot, $bet, $position);
						while ($stmt->fetch()) {
							if ($position) {
								$last_action = self::GetLastPlayerAction($game_id, $current_stage, $player_id);
								
								if ($player_id != $PlayerId) {
									$player_card1 = $default_card;
									$player_card2 = $default_card;
								}
								
								$players[$position] = array('player_id' => $player_id, 'player_name' => $player_name, 'card1' => $player_card1 ?? $default_card, 'card2' => $player_card2 ?? $default_card, 'pot' => $pot ?? 0, 'bet' => $bet ?? 0, 'position' => $position, 'last_action' => $last_action);
							}
						}
					}
					
					return array(
						'success' => true,
						'details' => $details,
						'communityCards' => $communityCards,
						'players' => $players,
						'message' => $msg
					);
				}
			}
			
			return array(
				'success' => false,
				'message' => 'Could not load game state.'
			);
		}
		
		public static function LoadOrCreateGame($table_id) {
			$db = new DB();
			
			// Check if a game is running for the selected table
			$game_id = self::GetRunningGameId($table_id);
			
			if ($game_id === null) {
				// No game running, create a new game
				$game_id = self::CreateGame($table_id);
				if ($game_id === null) {
					return null;
				}
			}
			
			return $game_id;
		}
		
		public static function GetRunningGameId($table_id) {
			$db = new DB();
			$minimum_player_count = 2; // Replace this with the actual minimum player count

			if ($stmt = $db->query("SELECT g.game_id 
									FROM games g 
									LEFT JOIN seats s ON g.table_id = s.table_id
									WHERE g.table_id = ? AND g.is_running = 1 AND g.current_stage < 4
									GROUP BY g.game_id 
									HAVING COUNT(s.player_id) >= ?
									ORDER BY g.game_id DESC 
									LIMIT 1", array($table_id, $minimum_player_count))) {
										
				$stmt->bind_result($game_id);
				if ($stmt->fetch()) {
					return $game_id;
				}
			}

			return null;
		}

		public static function CreateDeck($game_id) {
			$db = new DB();
			if ($stmt = $db->query("SELECT card_id FROM cards ORDER BY RAND()")) {
				$stmt->bind_result($card_id);
				$i = 1;
				while ($stmt->fetch()) {
					$db->query("INSERT INTO deck SET game_id = ?, card_id = ?, card_order = ?", array($game_id, $card_id, $i));
					$i++;
				}
			}
		}
		
		public static function LoadDeck($game_id) {
			$db = new DB();
			$deck = new Deck();
			$suitsFactory = new StandardSuitFactory();

			$stmt = $db->query("SELECT COUNT(*) FROM deck WHERE game_id = ?", array($game_id));
			$stmt->bind_result($deckExists);
			$stmt->fetch();

			// If the deck doesn't exist, create it
			if (!$deckExists) {
				self::CreateDeck($game_id);
			}
			
			if ($stmt = $db->query("SELECT d.card_id, c.card_number, c.card_suit FROM deck d INNER JOIN cards c ON c.card_id = d.card_id WHERE game_id = ? ORDER BY card_order ASC", array($game_id))) {
				$stmt->bind_result($card_id, $card_number, $card_suit);
				while ($stmt->fetch()) {
					$suit = $suitsFactory->makeFromAbbr($card_suit);
					$deck->addCard(new StandardCard($card_number, $suit));
				}
			}
			
			return $deck;
		}

		
	}
	
?>