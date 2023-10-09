$(document).ready(function() {
    var gameId;
    var lastAction = {};
	var lastBet = {};
    var currentAmount = 0;
    var betSizeSlider = document.getElementById('bet-size');
    var betDisplay = document.getElementById('bet-display');
	
	// Define the CountdownTimer class
    class CountdownTimer {
        constructor() {
            this.intervalId = null;
            this.remainingTime = 0;
        }
        
		start(timeInSeconds, callback) {
			// If the timer is already running, do nothing.
			if (this.intervalId) {
				return;
			}
			
			this.remainingTime = timeInSeconds;
			this.intervalId = setInterval(() => {
				if (this.remainingTime > 0) {
					callback(this.remainingTime);
					this.remainingTime--;
				} else {
					this.stop();
				}
			}, 1000);
		}

        stop() {
            if (this.intervalId) {
                clearInterval(this.intervalId);
                this.intervalId = null;
                this.remainingTime = 0;
            }
        }
    }
	
	countdownTimer = new CountdownTimer();
	
	function convertCard(card) {
		var rank = card.slice(0, -1).toUpperCase();  // get everything except the last character and make it uppercase
		var suit = card.slice(-1).toLowerCase();  // get the last character and make it lowercase

		var rankMapping = {
			'A': 'A',
			'K': 'K',
			'Q': 'Q',
			'J': 'J',
			'T': 'T',
			'9': 'nine',
			'8': 'eight',
			'7': 'seven',
			'6': 'six',
			'5': 'five',
			'4': 'four',
			'3': 'three',
			'2': 'two'
		};

		var suitMapping = {
			'h': 'heart',
			'd': 'diamond',
			'c': 'club',
			's': 'spade'
		};

		rank = rankMapping[rank] || rank;
		suit = suitMapping[suit] || suit;

		return [rank, suit];
	}

	function updateGame() {
		$.get('poker.php', {
			action: 'getGameState',
			game_id: gameId,
			table_id: tableId
		}, function(data) {
			if (data.success) {
	
				$('#community-cards').empty();
				data.communityCards.forEach(function(card) {
					var classes = convertCard(card);
					$('#community-cards').append('<div class="community-card-slot card"><div class="rank ' + classes[0] + '"></div><div class="suit ' + classes[1] + '"></div></div>');
				});
				$('.community-card-slot').show();
				$('.central-pot-slot').text(data.details.table_pot);
				$('.central-pot-slot').css('visibility', 'visible');
				
				if (data.details.last_bet) {
					lastBet = data.details.last_bet;
					// Update the current amount based on the last action
					if ((lastBet.action === 'bet' || lastBet.action === 'raise' || lastBet.action === 'big_blind' || lastBet.action === 'small_blind')) {
						currentAmount = lastBet.amount; // replace this with the actual amount from your backend
					}
				}
				
				if (data.details.last_action) {
					lastAction = data.details.last_action;
					// Update the current amount based on the last action
					if ((lastAction.action === 'bet' || lastAction.action === 'raise' || lastAction.action === 'big_blind' || lastAction.action === 'small_blind')) {
						currentAmount = lastAction.amount; // replace this with the actual amount from your backend
					}
				}

                // Update the slider min and max values
                betSizeSlider.min = currentAmount;
                betSizeSlider.max = currentAmount * 10; // replace 10 with the actual multiplier you want to use
				
				
				var now = new Date();
				var utcDate = new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate(), now.getUTCHours(), now.getUTCMinutes(), now.getUTCSeconds()));
				var receivedTimestamp = Math.floor(utcDate.getTime() / 1000);
				
				if (data.details.move == playerId) {
					var lastActionTimestamp = receivedTimestamp;
					
					// Iterate over each player
					for (var position in data.players) {
						var player = data.players[position];
						
						// If this player made the last move, use their timestamp
						if (player.player_id == data.details.last_move && player.last_action) {
							lastActionTimestamp = Number(player.last_action.timestamp);
							break;  // No need to check other players
						}
					}
					
					console.log(data.details.player_actions);
					
					updateActionPanel(data.details.player_actions);

					// Calculate the time difference in seconds
					var timeDifference = receivedTimestamp - lastActionTimestamp;

					// If the time difference is less than or equal to 20 seconds, show the control panel
					if (timeDifference <= 20) {
						$('#control-panel').show();
						var countdown = 20 - timeDifference;

						countdownTimer.start(countdown, (remainingTime) => {
							$('#countdown').text('Time left: ' + remainingTime + ' seconds');
							if (remainingTime <= 0) {
								$('#control-panel').hide();
								$('#countdown').empty();
							}
						});
					} else {
						$('#control-panel').hide();
						$('#countdown').empty();
					}
				}
				
				$('.player-spot').each(function(index) {
					var position = $(this).data('position');
					var player = data.players[position];
					if (player) {
						var playerNameInput = $(this).find('.player-name-input');
						$(this).find('h5').text(player.player_name + '(' + player.pot + ')');
						$(this).find('.card-slot').each(function(cardIndex) {
							var card = player['card' + (cardIndex + 1)];
							var classes = convertCard(card);
							$(this).addClass('card');
							var rankDiv = $('<div/>', { 'class': 'rank ' + classes[0] });
							var suitDiv = $('<div/>', { 'class': 'suit ' + classes[1] });
							$(this).empty().append(rankDiv, suitDiv);
						});
						$(this).find('.bet-slot').text(player.bet);
						$(this).find('.bet-slot').show();
						$(this).find('.btn').hide();
						$(this).find('.card-slot').show();
					} else {
						$(this).find('h5').text("");
						$(this).find('.card-slot').each(function(cardIndex) {
							$(this).text("");
						});
						$(this).find('.card-slot').hide();
						$(this).find('.bet-slot').hide();
						if (playerId) {
							$(this).find('.btn').show();
						} else {
							$(this).find('.btn').hide();
						}
					}
				});
				$('#message-area').html('Previous Game: ' + data.message);
			} else {
				if (data.players) {
					$('.player-spot').each(function(index) {
						var position = $(this).data('position');
						var player = data.players[position];
						if (player) {
							var playerNameInput = $(this).find('.player-name-input');
							$(this).find('h5').text(player.player_name);
							$(this).find('.card-slot').each(function(cardIndex) {
								var card = player['card' + (cardIndex + 1)];
								var classes = convertCard(card);
								$(this).addClass('card');
								var rankDiv = $('<div/>', { 'class': 'rank ' + classes[0] });
								var suitDiv = $('<div/>', { 'class': 'suit ' + classes[1] });
								$(this).empty().append(rankDiv, suitDiv);
							});
							$(this).find('.bet-slot').text(player.bet);
							$(this).find('.bet-slot').show();
							$(this).find('.btn').hide();
							$(this).find('.card-slot').show();
						} else {
							$(this).find('h5').text("");
							$(this).find('.card-slot').each(function(cardIndex) {
								$(this).text("");
							});
							$(this).find('.card-slot').hide();
							$(this).find('.bet-slot').hide();
							if (playerId) {
								$(this).find('.btn').show();
							} else {
								$(this).find('.btn').hide();
							}
						}
					});
				}
			}
		});
	}

	function startGame() {
		$.get('poker.php', {
			action: 'startGame',
			table_id: tableId
		}, function(data) {
			gameId = data.game_id;
			updateGame();
		});
	}

	function performAction(action, amount) {
        $.get('poker.php', {
            action: action,
            amount: amount
        }, function(data) {
			countdownTimer.stop();
			$('#countdown').empty();
			$('#control-panel').hide();
            updateGame();
        });
    }

	function updateActionPanel(player_actions) {
		// Disable all action buttons initially
		$('#check-button').prop('disabled', true).hide();
		$('#call-button').prop('disabled', true).hide();
		$('#raise-button').prop('disabled', true).hide();
		$('#bet-button').prop('disabled', true).hide();

		// Enable the fold button always
		$('#fold-button').prop('disabled', false).show();
			
		$('#bet-size').hide();

		// Enable the appropriate action buttons according to the player_actions
		player_actions.forEach(actionObject => {
			let action = actionObject.action;
			switch (action) {
				case 'call':
					$('#call-button').prop('disabled', false).show();
					break;
				case 'raise':
					$('#bet-size').show();
					$('#raise-button').prop('disabled', false).show();
					break;
				case 'check':
					$('#check-button').prop('disabled', false).show();
					break;
				case 'bet':
					$('#bet-size').show();
					$('#bet-button').prop('disabled', false).show();
					break;
			}
		});
	}

	
	$('#bet-button').click(function() {
		var amount = $('#bet-size').val();
		performAction('bet', amount);
	});
	$('#check-button').click(function() {
		performAction('check', 0);
	});
	$('#call-button').click(function() {
		performAction('call', 0);
	});
	$('#raise-button').click(function() {
		var amount = $('#bet-size').val();
		performAction('raise', amount);
	});
	$('#fold-button').click(function() {
		performAction('fold', 0);
	});

	// Handle login
	$('#loginButton').click(function() {
		$.ajax({
			type: 'POST',
			url: 'action.php?action=login',
			data: $('#loginForm').serialize(),
			dataType: 'json',
			success: function(response) {
				if (!response.success) {
					$('#loginError').text(response.error).show();
				} else {
					playerId = response.player_id;
					$('.player-spot .btn').show();
					$('#login, #register').hide();
					$('#logout').show();
					$('#loginModal').modal('hide');
				}
			}
		});
	});
	// Handle registration
	$('#registerButton').click(function() {
		$.ajax({
			type: 'POST',
			url: 'action.php?action=register',
			data: $('#registerForm').serialize(),
			dataType: 'json',
			success: function(response) {
				if (!response.success) {
					$('#registerError').text(response.error).show();
				} else {
					playerId = response.player_id;
					$('.player-spot .btn').show();
					$('#login, #register').hide();
					$('#registerModal').modal('hide');
				}
			}
		});
	});
	// Display join buttons only when the player is logged in
	if (playerId) {
		$('.player-spot .btn').show();
		$('#login, #register').hide();
		$('#logout').show();
	} else {
		$('.player-spot .btn').hide();
		$('#login, #register').show();
		$('#logout').hide();
	}
	
	$('.community-card-slot').hide();
	$('.card-slot').hide();
	
	// Retrieve player's name and desired position on the table when they join
	$('.player-spot .btn').click(function() {
		// Get the desired position
		var position = $(this).closest('.player-spot').data('position');
		$.get('poker.php', {
			action: 'join',
			position: position
		}, function(data) {
			if (data.success) {
				updateGame();
			}
		});
	});
	$('#logout').click(function() {
		// Get the desired position
		var position = $(this).closest('.player-spot').data('position');
		$.ajax({
			type: 'POST',
			url: 'action.php?action=logout&table_id='+tableId,
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					playerId = 0;
					$('.player-spot .btn').hide();
					$('#login, #register').show();
					$('#logout').hide();
				}
			}
		});
	});
	
	// Get the slider element
	var betSizeSlider = document.getElementById('bet-size');

	// Get the bet display element
	var betDisplay = document.getElementById('bet-display');

    // Listen for changes in the slider's value
    betSizeSlider.oninput = function() {
        // Update the bet display with the new value
        betDisplay.textContent = this.value;
    }

    setInterval(function() {
        updateGame();
    }, 1000);
	
    updateGame();
});