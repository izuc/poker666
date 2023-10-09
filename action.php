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
    case 'register':
		if ($_SERVER["REQUEST_METHOD"] == "POST") {
			$username = $_POST['username'];
			$password = $_POST['password'];

			$playerId = PokerAction::CreatePlayer($username, $password);
			if ($playerId) {
				$_SESSION['player_id'] = $playerId;
				echo json_encode(['success' => true, 'player_id' => $playerId]);
			} else {
				echo json_encode(['success' => false, 'error' => 'Error creating account']);
			}
		}
        break;

    case 'login':
		if ($_SERVER["REQUEST_METHOD"] == "POST") {
			$username = $_POST['username'];
			$password = $_POST['password'];

			$playerId = PokerAction::AuthenticatePlayer($username, $password);
			if ($playerId) {
				$_SESSION['player_id'] = $playerId; 
				echo json_encode(['success' => true, 'player_id' => $playerId]); 
			} else {
				echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
			}
		}
        break;
    case 'logout':
		if ($_SERVER["REQUEST_METHOD"] == "POST") {
			
			if ($playerId && $tableId) {
				PokerAction::Logout($playerId, $tableId);
			}
			
			unset($_SESSION['player_id']);
			session_destroy();
			
			echo json_encode(['success' => true]); 
		}
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>
