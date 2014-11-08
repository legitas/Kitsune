<?php

namespace Kitsune\ClubPenguin\Handlers\Game;

use Kitsune\Logging\Logger;
use Kitsune\ClubPenguin\Packets\Packet;

use Kitsune\ClubPenguin\Handlers\Game\FindFour;

trait Multiplayer {
	
	public $rinkPuck = array(0, 0, 0, 0);
	
	protected function handleJoinTable($socket) {
		$penguin = $this->penguins[$socket];
		
		$tableId = Packet::$Data[2];
		
		if(count($this->tablePopulationById[$tableId]) >= 2) { // Add to sepctator array
			$sepctatorCount = count($this->sepctatorsByTableId[$tableId]);

			if(!$sepctatorCount > 5) { // Limit is 5
				$this->sepctatorsByTableId[$tableId][] = $penguin;

				$penguin->tableId = $tableId;

				Logger::Debug("Spectator added to table $tableId");
			} else {
				return;
			}
		} else { // Player
			$seatId = count($this->tablePopulationById[$tableId]);
			
			if($this->gamesByTableId[$tableId] === null) {
				$findFourGame = new FindFour();
				$findFourGame->addPlayer($penguin);

				$this->gamesByTableId[$tableId] = $findFourGame;
			} else {
				$this->gamesByTableId[$tableId]->addPlayer($penguin);
			}

			$this->tablePopulationById[$tableId][$penguin->username] = $penguin;

			$seatId += 1;

			$penguin->send("%xt%jt%{$penguin->room->internalId}%$tableId%$seatId%");

			$penguin->room->send("%xt%ut%{$penguin->room->internalId}%$tableId%$seatId%");
			
			$this->playersByTableId[$tableId][] = $penguin;
			
			$penguin->tableId = $tableId;
		}
	}
	
	// TODO: Check if they're in the Ski Lodge or Attic before sending them the packet
	protected function handleGetTablePopulation($socket) {
		$penguin = $this->penguins[$socket];
		
		$tableIds = array_splice(Packet::$Data, 2);
		
		$tablePopulation = "";
		
		foreach($tableIds as $tableId) {
			if(isset($this->tablePopulationById[$tableId])) {
				$tablePopulation .= sprintf("%d|%d", $tableId, count($this->tablePopulationById[$tableId]));
				$tablePopulation .= "%";
			}
		}
		
		$penguin->send("%xt%gt%{$penguin->room->internalId}%$tablePopulation");
	}


	protected function handleQuitGame($socket) {
		// Not sure if it needs implementing
	}

	protected function handleLeaveTable($socket) {
		$penguin = $this->penguins[$socket];

		$tableId = $penguin->tableId;

		if($tableId !== null) {
			$seatId = array_search($penguin, $this->playersByTableId[$tableId]);
			
			$opponentSeatId = $seatId == 0 ? 1 : 0;

			if(isset($this->playersByTableId[$tableId][$opponentSeatId])) {
				$this->playersByTableId[$tableId][$opponentSeatId]->addCoins(10);
			}

			unset($this->playersByTableId[$tableId][$seatId]);
			unset($this->tablePopulationById[$tableId][$penguin->username]);

			$penguin->room->send("%xt%ut%{$penguin->room->internalId}%$tableId%$seatId%");

			$penguin->tableId = null;

			if(count($this->playersByTableId[$tableId]) == 0) {
				$this->playersByTableId[$tableId] = array();
				$this->gamesByTableId[$tableId] = null;

				foreach($this->sepctatorsByTableId[$tableId] as $spectatorId => $sepctator) {			
					$sepctator->tableId = null;
				}

				$this->sepctatorsByTableId[$tableId] = array();
			}
		}
	}
	
	protected function handleStartGame($socket) {
		$penguin = $this->penguins[$socket];
		
		if($penguin->waddleRoom !== null) {
			$waddlePlayers = array();

			foreach($penguin->room->penguins as $waddlePenguin) {
				array_push($waddlePlayers, sprintf("%s|%d|%d|%s", $waddlePenguin->username, $waddlePenguin->color, $waddlePenguin->hand, $waddlePenguin->username));
			}
		
			$penguin->send("%xt%uz%-1%" . sizeof($waddlePlayers) . '%' . implode('%', $waddlePlayers) . '%');
		} elseif($penguin->tableId !== null) {
			$seatId = count($this->tablePopulationById[$penguin->tableId]) - 1;
			
			$penguin->send("%xt%jz%-1%$seatId%");
			$penguin->room->send("%xt%uz%-1%$seatId%{$penguin->username}%");
			
			if($seatId == 1) {
				foreach($this->playersByTableId[$penguin->tableId] as $player) {
					$player->send("%xt%sz%{$penguin->room->internalId}%0%");
				}
			}
		}
	}

	protected function handleSendMove($socket) {
		$penguin = $this->penguins[$socket];
		
		if($penguin->waddleRoom !== null) {
			array_shift(Packet::$Data);
			
			$penguin->room->send("%xt%zm%" . implode('%', Packet::$Data) . '%');
		} elseif($penguin->tableId !== null) {
			$tableId = $penguin->tableId;

			if(in_array($penguin, $this->playersByTableId[$tableId]) && $this->gamesByTableId[$tableId]->ready() === true) {
				$chipColumn = Packet::$Data[2];
				$chipRow = Packet::$Data[3];
				$seatId = array_search($penguin, $this->playersByTableId[$tableId]);
				$libraryId = $seatId + 1;

				if($this->gamesByTableId[$tableId]->currentPlayer === $libraryId) {	// Prevents player from placing multiple chips on a single turn
					$gameStatus = $this->gamesByTableId[$tableId]->placeChip($chipColumn, $chipRow);

					$recipients = array_merge($this->playersByTableId[$tableId], $this->sepctatorsByTableId[$tableId]);
					
					foreach($recipients as $recipient) {
						$recipient->send("%xt%zm%{$recipient->room->internalId}%$seatId%$chipColumn%$chipRow%");
					}

					$opponentSeatId = $seatId == 0 ? 1 : 0;

					if($gameStatus === FindFour::FoundFour) {
						$penguin->addCoins(10);

						$this->playersByTableId[$tableId][$opponentSeatId]->addCoins(5);
					} elseif($gameStatus === FindFour::Tie) {
						$penguin->addCoins(10);

						$this->playersByTableId[$tableId][$opponentSeatId]->addCoins(10);
					}
				} else {
					Logger::Warn("Attempted to drop multiple chips");
				}
			} else {
				Logger::Warn("Player {$penguin->id} tried dropping a chip before connecting to a player!");
			}
		}
	}
	
	protected function handleGameMove($socket) {
		$penguin = $this->penguins[$socket];

		if($penguin->room->externalId == 802) {
			$this->rinkPuck = array_splice(Packet::$Data, 3);
			
			$puckData = implode('%', $this->rinkPuck);
			
			$penguin->send("%xt%zm%{$penguin->room->internalId}%{$penguin->id}%$puckData%");
		}
	}
	
	protected function handleGetGame($socket) {
		$penguin = $this->penguins[$socket];
		
		if($penguin->room->externalId == 802) {
			$puckData = implode('%', $this->rinkPuck);
		
			$penguin->send("%xt%gz%{$penguin->room->internalId}%$puckData%");
		} elseif($penguin->tableId !== null) {
			$tableId = $penguin->tableId;
			$playerUsernames = array_keys($this->tablePopulationById[$tableId]);
			
			@list($firstPlayer, $secondPlayer) = $playerUsernames;

			$boardString = $this->gamesByTableId[$tableId]->convertToString();
			
			$penguin->send("%xt%gz%-1%$firstPlayer%$secondPlayer%$boardString%");
		}
	}
	
}

?>
