<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start(); // Start the session
require_once(__DIR__ . '/library/auto_load.php');

header('Content-Type: application/json');

$action = $_GET['action'] ?? null;
$playerId = (int)(isset($_SESSION['player_id']) ? $_SESSION['player_id'] : 0);
$tableId = (int)(isset($_GET['table_id']) ? $_GET['table_id'] : 1);

switch ($action) {
    case 'startGame':
		if ($playerId) {
			$gameId = PokerAction::LoadOrCreateGame($tableId); // You'll need to adjust this according to how you want to create games
			echo json_encode(['game_id' => $gameId]);
		}
        break;
		
    case 'join':
		if ($playerId) {
			$position = (int)$_GET['position'];
			PokerAction::AddPlayer($playerId, $tableId, $position);
		}
        break;
		
    case 'getGameState':
		$gameState = PokerAction::GetGameState($playerId, $tableId);
		echo json_encode($gameState);
        break;

    case 'bet':
        $amount = $_GET['amount'] ?? null;
        if ($amount === null) {
            echo json_encode(['error' => 'Bet amount not provided']);
            break;
        }

        $result = PokerAction::Bet($tableId, $playerId, $amount);
        echo json_encode(['result' => $result]);
        break;

    case 'call':
        $result = PokerAction::Call($tableId, $playerId);
        echo json_encode(['result' => $result]);
        break;

    case 'raise':
        $amount = $_GET['amount'] ?? null;
        if ($amount === null) {
            echo json_encode(['error' => 'Raise amount not provided']);
            break;
        }

        $result = PokerAction::Raise($tableId, $playerId, $amount);
        echo json_encode(['result' => $result]);
        break;

    case 'check':
        $result = PokerAction::Check($tableId, $playerId);
        echo json_encode(['result' => $result]);
        break;

    case 'fold':
        $result = PokerAction::Fold($tableId, $playerId);
        echo json_encode(['result' => $result]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>
