<?php
session_start(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Poker Game</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
	<style>
	body {
		background-color: #000;
		display: flex;
		justify-content: center;
		align-items: center;
		height: 100vh;
		margin: 0;
		padding: 0;
	}
	#container {
		height: 100%;
		width: 100%;
		margin: 0 auto;
		max-width: 1500px;
	}
	#game-board {
		width: 100%;
		height: 100%;
		display: grid;
		grid-template-rows: repeat(5, 1fr);
		grid-template-columns: repeat(5, 1fr);
		justify-items: center;
		align-items: center;
		position: relative;
		box-sizing: border-box;
		overflow-x: hidden;
		overflow-y: auto;
	}
	#game-board::before {
		content: "";
		background-image: url(logo.svg);
		background-repeat: no-repeat;
		background-position: center;
		background-size: contain;
		opacity: 0.1;  /* Adjust the opacity as needed, 0 is fully transparent, 1 is fully opaque */
		position: absolute;
		top: 0;
		right: 0;
		bottom: 0;
		left: 0;
		z-index: -1;  /* Ensures the pseudo-element is behind the content of the element */
	}
	.player-spot {
		position: relative;
		display: flex;
		flex-direction: column;
		align-items: center;
		margin: 1rem;
	}
	.player-spot.top {
		grid-row: 1 / 2;
		grid-column: 3 / 4;
	}
	.player-spot.bottom {
		grid-row: 5 / 6;
		grid-column: 3 / 4;
	}
	.player-spot.left {
		grid-row: 3 / 4;
		grid-column: 1 / 2;
		transform: rotate(-90deg);
		margin-left: 50px;
	}
	.player-spot.right {
		grid-row: 3 / 4;
		grid-column: 5 / 6;
		transform: rotate(90deg);
		margin-right: 50px;
	}
	.card-slot {
		width: 10vw;
		height: 16vw;
		max-width: 70px;
		max-height: 100px;
		border-radius: 5px;
		background: #fff;
		margin: 1vw;
		display: none;
	}
	.bet-slot {
		width: 12vw;
		height: 12vw;
		max-width: 120px;
		max-height: 120px;
		display: flex;
		align-items: center;
		justify-content: center;
		display: none;
		margin-top: 100px;
		text-align: center;
		color: #FFF;
		font-size: 25pt;
		padding: 20%;
	}
	.player-spot .d-flex {
		position: absolute;
		top: 35%;
		left: 50%;
		transform: translate(-50%, -50%);
	}
	.player-spot.bottom .d-flex {
		top: 60%;
	}
	.player-spot.bottom .bet-slot {
		margin-top: unset;
		margin-bottom: 100px;
	}
	.community-card-slot {
		width: 10vw;
		height: 16vw;
		max-width: 70px;
		max-height: 100px;
		border: 1px solid #000;
		border-radius: 5px;
		background: #fff;
		margin: 1vw;
		display: none;
	}
	.central-pot-slot {
		width: 20vw;
		height: 20vw;
		max-width: 200px;
		max-height: 200px;
		display: flex;
		align-items: center;
		justify-content: center;
		border-radius: 20px;
		color: white;
		font-weight: bold;
		font-size: 5vw;
		position: absolute;
		visibility: hidden;
	}
	#community-cards {
		z-index: 1;
		justify-content: space-around;
		padding: 1rem 0;
		position: absolute;
		top: 30%;
	}
	#control-panel {
		text-align: center;
		bottom: 0;
		padding: 1%;
		width: 100%;
		position: fixed;
		overflow: hidden;
		color: #fff;
		display: none;
	}
	#game {
		position: fixed;
		top: 7%;
		width: 100%;
		height: 85%;
		border-radius: 20px;
		border: 1vw solid #664d38;
		background-color: #3B1414;
	}
	.header {
		position: fixed;
		top: 0;
		padding: 1%;
		z-index: 100;
		width: 100%;
	}
	.join {
		display: none;
		background-color: #000;
		border-color: #000
	}
	.join:hover {
		background-color: #000!important;
		border-color: #000!important;
	}
	.heart:before, .heart:after { content: "♥"; color: red; }
	.diamond:before, .diamond:after { content: "♦"; color: red; }
	.club:before, .club:after { content: "♣"; }
	.spade:before, .spade:after { content: "♠"; }
	.A:before, .A:after { content: "A"; }
	.K:before, .K:after { content: "K"; }
	.Q:before, .Q:after { content: "Q"; }
	.J:before, .J:after { content: "J"; }
	.T:before, .T:after { content: "10"; }
	.nine:before, .nine:after { content: "9"; }
	.eight:before, .eight:after { content: "8"; }
	.seven:before, .seven:after { content: "7"; }
	.six:before, .six:after { content: "6"; }
	.five:before, .five:after { content: "5"; }
	.four:before, .four:after { content: "4"; }
	.three:before, .three:after { content: "3"; }
	.two:before, .two:after { content: "2"; }
	.card {
		border: 1px solid black;
		border-radius: 10px;
		position: relative;
		background-color: white;
		font-family: Arial, sans-serif;
		color: black;
		text-align: center;
		padding: 10px;
		box-sizing: border-box;
	}
	.card:before, .card:after {
		position: absolute;
		font-size: 20px;
	}
	.card:before {
		top: 10px;
		left: 10px;
	}
	.card:after {
		bottom: 10px;
		right: 10px;
	}
	.rank:before, .rank:after {
		position: absolute;
		font-size: 20px;
	}
	.rank:before {
		top: 10px;
		left: 10px;
	}
	.rank:after {
		bottom: 10px;
		right: 10px;
	}
	.suit:before, .suit:after {
		position: absolute;
		font-size: 20pt;
	}
	.suit:before {
		top: 10px;
		right: 10px;
	}
	.suit:after {
		bottom: 10px;
		left: 10px;
	}
	.player-spot h5 {
		color: #FFF;
		position: absolute;
		z-index: 100;
		top: 0%;
		font-weight: bold;
	}
	.player-spot.bottom h5 {
		color: #FFF;
		position: absolute;
		z-index: 100;
		top: unset;
		bottom: 0%;
		font-weight: bold;
	}
	@media screen and (max-width: 600px) {
		.suit:before, .suit:after {
			position: absolute;
			font-size: 14pt;
		}
	}
	@media screen and (max-width: 500px) {
		.rank:before, .suit:before {
			position: absolute;
			font-size: 20px;
			left: 50%;
			transform: translateX(-50%);
		}

		.rank:before {
			top: 10%;
		}

		.suit:before {
			top: 25%;
			left: 45%;
			font-size: 30pt;
		}

		.rank:after, .suit:after {
			display: none;
		}
	}
	@media screen and (max-width: 450px) {
		.suit:before {
			top: 40%;
			left: 35%;
			font-size: 20pt;
		}
	}
	
	#message-area {
		z-index: 1000;
		color: #FFF;
		position: fixed;
		top: 50px;
	}
	</style>
</head>
<body>
	<div class="header">
		<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#loginModal" id="login">Login</button>
		<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#registerModal" id="register">Register</button>
		<button type="button" class="btn btn-primary" id="logout">Logout</button>
	</div>
	<div id="message-area"></div>
	<div id="game">
			<div id="container">
					<div id="game-board">
							<?php
							  $positions = [
								1 => 'left',
								2 => 'top',
								3 => 'right',
								4 => 'bottom',
							  ];
							  
							  foreach($positions as $position => $positionClass) { ?>
									<div class="player-spot <?php echo $positionClass; ?>" data-position="<?php echo $position; ?>">
										<a href="#" class="btn btn-primary btn-block join">Join</a>
										<h5 class="text-center mb-3"></h5>
										<div class="d-flex">
											<div class="card-slot"></div>
											<div class="card-slot"></div>
										</div>
										<div class="text-center mt-3">
											<div class="bet-slot"></div>
										</div>
									</div>
								<?php
							  }
							?>
							<div id="community-cards" class="d-flex justify-content-center">
									<div class="community-card-slot"></div>
									<div class="community-card-slot"></div>
									<div class="community-card-slot"></div>
									<div class="community-card-slot"></div>
									<div class="community-card-slot"></div>
							</div>
							<div class="central-pot-slot"></div>
					</div>
			</div>
	</div>
	<div id="control-panel">
		<div id="countdown"></div>
		<div id="actions">
			<small>Bet Amount: $<span id="bet-display">0</span></small>
			<div class="text-center">
				<input type="range" id="bet-size" min="0" max="100" step="1">
				<button id="check-button" class="btn btn-primary mx-1">Check</button>
				<button id="call-button" class="btn btn-primary mx-1">Call</button>
				<button id="raise-button" class="btn btn-primary mx-1">Raise</button>
				<button id="bet-button" class="btn btn-primary mx-1">Bet</button>
				<button id="fold-button" class="btn btn-primary mx-1">Fold</button>
			</div>
		</div>
	</div>
	<!-- Login Modal -->
	<div class="modal fade" id="loginModal" tabindex="-1" role="dialog" aria-labelledby="loginModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="loginModalLabel">Login</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form id="loginForm">
						<div class="form-group">
							<label for="loginUsername">Username</label>
							<input type="text" class="form-control" id="loginUsername" name="username" required>
						</div>
						<div class="form-group">
							<label for="loginPassword">Password</label>
							<input type="password" class="form-control" id="loginPassword" name="password" required>
						</div>
						<div class="form-group">
							<div class="alert alert-danger" id="loginError" role="alert" style="display:none;"></div>
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
					<button type="button" class="btn btn-primary" id="loginButton">Login</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Register Modal -->
	<div class="modal fade" id="registerModal" tabindex="-1" role="dialog" aria-labelledby="registerModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="registerModalLabel">Register</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<form id="registerForm">
						<div class="form-group">
							<label for="registerUsername">Username</label>
							<input type="text" class="form-control" id="registerUsername" name="username" required>
						</div>
						<div class="form-group">
							<label for="registerPassword">Password</label>
							<input type="password" class="form-control" id="registerPassword" name="password" required>
						</div>
						<div class="form-group">
							<div class="alert alert-danger" id="registerError" role="alert" style="display:none;"></div>
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
					<button type="button" class="btn btn-primary" id="registerButton">Register</button>
				</div>
			</div>
		</div>
	</div>

	<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
	<script>
		var tableId = <?php echo $_GET['table_id'] ?? 1 ?>;
		var playerId = <?php echo $_SESSION['player_id'] ?? 0 ?>;
	</script>
	<script src="game.js"></script>
</body>
</html>
