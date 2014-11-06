<?php

namespace Kitsune\ClubPenguin\Handlers\Game;

use Kitsune\ClubPenguin\Packets\Packet;

use Kitsune\ClubPenguin\Handlers\Game\FindFour;

trait Multiplayer {

	public $tablePopulationById = array(205 => array(), 206 => array(), 207 => array());
	public $playersByTableId = array(205 => array(), 206 => array(), 207 => array());

	public $gamesByTableId = array(205 => null, 206 => null, 207 => null);
	
	public $rinkPuck = array(0, 0, 0, 0);
	
	protected function handleJoinTable($socket) {
		$penguin = $this->penguins[$socket];
		
		$tableId = Packet::$Data[2];
		
		if(count($this->tablePopulationById[$tableId]) == 2) { // This is temporary ?
			return;
		} else {
			$seatId = count($this->tablePopulationById[$tableId]);
			
			if(empty($this->gamesByTableId[$tableId])) {
				$findFourGame = new FindFour();
				$findFourGame->addPlayer($penguin);

				$this->gamesByTableId[$tableId] = $findFourGame;
			} else {
				// TODO: Spectator MODE - remember!!
				$this->gamesByTableId[$tableId]->addPlayer($penguin);
			}

			$this->tablePopulationById[$tableId][$penguin->username] = $penguin;

			$seatId += 1; // Don't ask me why plz

			$penguin->send("%xt%jt%{$penguin->room->internalId}%$tableId%$seatId%");

			$penguin->room->send("%xt%ut%{$penguin->room->internalId}%$tableId%$seatId%");
			
			$this->playersByTableId[$tableId][] = $penguin; // May not always happen?
			
			$penguin->tableId = $tableId;
		}
	}
	
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

	protected function handleLeaveTable($socket) {
		$penguin = $this->penguins[$socket];

		$tableId = $penguin->tableId;

		if($tableId !== null) {
			$seatId = array_search($penguin, $this->playersByTableId[$tableId]);

			unset($this->playersByTableId[$tableId][$seatId]);

			$penguin->room->send("%xt%ut%{$penguin->room->internalId}%$tableId%$seatId%");

			$penguin->tableId = null;
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

	public function resetTable($tableId) {
		$this->tablePopulationById[$tableId] = array();

		$this->gamesByTableId[$tableId] = null;
	}

	protected function handleSendMove($socket) {
		$penguin = $this->penguins[$socket];
		
		if($penguin->waddleRoom !== null) {
			array_shift(Packet::$Data);
			
			$penguin->room->send("%xt%zm%" . implode('%', Packet::$Data) . '%');
		} elseif($penguin->tableId !== null) {
			$chipColumn = Packet::$Data[2];
			$chipRow = Packet::$Data[3];

			$tableId = $penguin->tableId;
			$seatId = array_search($penguin, $this->playersByTableId[$tableId]);
			
			$gameStatus = $this->gamesByTableId[$tableId]->placeChip($chipColumn, $chipRow);
			
			foreach($this->playersByTableId[$tableId] as $player) {
				$player->send("%xt%zm%{$player->room->internalId}%$seatId%$chipColumn%$chipRow%");
			}

			$opponentSeatId = $seatId == 0 ? 1 : 0;

			if($gameStatus === FindFour::FoundFour) {
				$penguin->addCoins(10);

				$this->playersByTableId[$tableId][$opponentSeatId]->addCoins(5);

				$this->resetTable($tableId);
			} elseif($gameStatus === FindFour::Tie) {
				$penguin->addCoins(10);

				$this->playersByTableId[$tableId][$opponentSeatId]->addCoins(10);

				$this->resetTable($tableId);
			}
		}
	}
	
	protected function handleGameMove($socket) {
		$penguin = $this->penguins[$socket];
		
		$this->rinkPuck = array_splice(Packet::$Data, 3);
		
		$puckData = implode('%', $this->rinkPuck);
		
		$penguin->send("%xt%zm%{$penguin->room->internalId}%{$penguin->id}%$puckData%");
	}
	
	protected function handleGetGame($socket) {
		$penguin = $this->penguins[$socket];
		
		if($penguin->room->externalId == 802) {
			$puckData = implode('%', $this->rinkPuck);
		
			$penguin->send("%xt%gz%{$penguin->room->internalId}%$puckData%");
		} else {
			$tableId = $penguin->tableId;
			$playerUsernames = array_keys($this->tablePopulationById[$tableId]);
			
			@list($firstPlayer, $secondPlayer) = $playerUsernames;

			$boardString = $this->gamesByTableId[$tableId]->convertToString();
			
			$penguin->send("%xt%gz%-1%$firstPlayer%$secondPlayer%$boardString%");
		}
	}
	
}

?>
