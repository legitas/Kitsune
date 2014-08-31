<?php

namespace Kitsune\ClubPenguin\Plugins\Commands;

use Kitsune\Logging\Logger;
use Kitsune\ClubPenguin\Packets\Packet;
use Kitsune\ClubPenguin\Plugins\Base\Plugin;

final class Commands extends Plugin {
	
	public $dependencies = array("PatchedItems" => "loadPatchedItems");
	
	public $worldHandlers = array(
		"s" => array(
			"m#sm" => array("handlePlayerMessage", self::Both)
		),
	);
	
	public $xmlHandlers = array(null);
	
	
	private $commandPrefixes = array("!", "/", ":", "@");
	
	private $commands = array(
		"AI" => "buyItem",
		"JR" => "joinRoom",
		"PING" => "handlePing",
		"ID" => "getID",
		"GLOBAL" => "handleGlobal",
		"COINS" => "handleCoinsAnimation",
		"AF" => "buyFurn",
		"AC" => "addCoins",
		"HEAD" => "handleUpdateHead",
		"FACE" => "handleUpdateFace",
		"NECK" => "handleUpdateNeck",
		"HAND" => "handleUpdateHand",
		"BODY" => "handleUpdateBody",
		"FEET" => "handleUpdateFeet",
		"BACKGROUND" => "handleUpdatePhoto",
		"PIN" => "handleUpdateFlag",
		"UP" => "handleMascotUpdate",
		"MASCOT" => "handleMascotUpdate",
		"NICK" => "handleChangeNick",
		"ROOM" => "handleMyRoom",
		"SUMMON" => "summonPenguin",
		"TELEPORT" => "handleTeleport",
		"TRANSFORM" => "handleAvatarTransform",
		"USERS" => "usersOnline"
	);
	
	private $mutedPenguins = array();
	
	private $patchedItems;
	
	public function __construct($server)
	{
		$this->server = $server;
	}
	
	public function onReady()
	{
		parent::__construct(__CLASS__);
	}
	
	public function loadPatchedItems()
	{
		$this->patchedItems = $this->server->loadedPlugins["PatchedItems"];
	}

	public function handleGlobal($penguin, $arrData) {
	if($penguin->moderator){
		unset($arrData[0]);
		$message = Packet::$Data[3];
		$messageParts = explode(" ", $message);
		$arguments = array_splice($messageParts, 1);
		$message = implode(" ", $arguments);
		$penguin->room->send("%xt%cerror%-1%$message%Server%");
		} else if(!$penguin->moderator) {
		$penguin->send("%xt%cerror%-1%You do not have permission to perform this action.%Hack Attempt%");
		$this->server->joinRoom($penguin, 100);
		}
            }
    
	    public function handleChangeNick($penguin, $arguments)
	    {
	    $blockedNicks = array("", "", "", "", "", "");
	    if(!in_array($blockedNicks, $arguments)) {
	    if($penguin->moderator){
	    list($newNick) = $arguments;
	    $penguin->updateNick($newNick);
	    $this->server->joinRoom($penguin, 100);
	    } else {
	    $penguin->room->send("%xt%cerror%-1%You do not have permission to perform that action.%Hack Attempt%");
	    }
	    } else {
	    $penguin->room->send("%xt%cerror%-1%You do not have permission to perform that action.%Hack Attempt%");
	    }
	    }

	private function getID($tarID, $socket)
	{
		$penguin = $this->penguins[$socket];
		$playerId = Packet::$Data[2];

		if(is_numeric($playerId)) {
			$tarID->send("%xt%cerror%-1%Your ID is: {$playerId}%Server%");
		}
	}
	
	public function handlePing($tarPlay)
	{
		$tarPlay->send("%xt%cerror%-1%Pong%Server%");
	}
	
	public function handleMyRoom($tarPlay, $penguin)
	{
	    $myRoom = Packet::$Data[3];
		$tarPlay->send("%xt%cerror%-1%You're in room ID: {$penguin->room->externalId}%Server%");
	}

	
  	public function buyItem($penguin, $arguments)
  	{
		list($itemId) = $arguments;
		
		$this->patchedItems->handleBuyInventory($penguin, $itemId);
	}
	
  	public function buyFurn($penguin, $arguments) 
  	{
		list($furnitureId) = $arguments;

	    $cost = $this->furniture[$furnitureId];
		if($penguin->coins < $cost) {
			return $penguin->send("%xt%e%-1%401%");
		} else {
			$penguin->buyFurniture($furnitureId, $cost);
		}
	}

   private function summonPenguin($penguin, $arguments) {
                if($penguin->moderator) {
                        list($username) = $arguments;
                       
                        $username = strtolower($username);
                       
                        foreach($this->server->penguins as $aPenguin) {
                                if(strtolower($aPenguin->username) == $username) {
                                        $this->server->joinRoom($aPenguin, $penguin->room->externalId);
                                       
                                        break;
                                }
                        }
                }
        }
		 private function usersOnline($penguin, $arguments) {
                $userCount = count($this->server->penguins);
               
                $penguin->send("%xt%cerror%-1%There are $userCount user(s) online!%Users%");
        }
 
  	private function addCoins($penguin, $arguments)
  	{
		list($amount) = $arguments;
		
		$penguin->send("%xt%zo%{$penguin->room->internalId}%{$penguin->coins}%%0%0%0%");
		$penguin->setCoins($penguin->coins + $amount);
		$penguin->getPlayerString();
	}

	public function handleCoinsAnimation($penguin, $arguments)
	{
	    list($amount) = $arguments;
		$penguin->room->send("%xt%puffledig%{$penguin->room->internalId}%{$penguin->id}%$puffleId%0%0%$amount%{$penguin->puffleQuest['firstDig']}%false%");
	}
	
	private function handleUpdateHead($penguin, $arguments)
	{
		list($itemId) = $arguments;
		
		$penguin->room->send("%xt%uph%{$penguin->room->internalId}%{$penguin->id}%$itemId%");
	}
	
	private function handleUpdateFace($penguin, $arguments)
	{
		list($itemId) = $arguments;
		
		$penguin->room->send("%xt%upf%{$penguin->room->internalId}%{$penguin->id}%$itemId%");
	}
	
	private function handleUpdateNeck($penguin, $arguments)
	{
		list($itemId) = $arguments;
		
		$penguin->room->send("%xt%upn%{$penguin->room->internalId}%{$penguin->id}%$itemId%");
	}
	
	private function handleUpdateBody($penguin, $arguments)
	{
		list($itemId) = $arguments;
		
		$penguin->room->send("%xt%upb%{$penguin->room->internalId}%{$penguin->id}%$itemId%");
	}
	
	private function handleUpdateHand($penguin, $arguments)
	{
		list($itemId) = $arguments;
		
		$penguin->room->send("%xt%upa%{$penguin->room->internalId}%{$penguin->id}%$itemId%");
	}
	
	private function handleUpdateFeet($penguin, $arguments)
	{
		list($itemId) = $arguments;
		
		$penguin->room->send("%xt%upe%{$penguin->room->internalId}%{$penguin->id}%$itemId%");
	}

	private function handleUpdatePhoto($penguin, $arguments)
	{
		list($itemId) = $arguments;
		
		$penguin->room->send("%xt%upp%{$penguin->room->internalId}%{$penguin->id}%$itemId%");
	}
	
	private function handleUpdateFlag($penguin, $arguments)
	{
		list($itemId) = $arguments;
		
		$penguin->room->send("%xt%upl%{$penguin->room->internalId}%{$penguin->id}%$itemId%");
	}
		
	private function handleMascotUpdate($penguin, $arguments)
	{
	list($mascot) = $arguments;
	switch($mascot){
	 case 'cadence':
	 case 'ca':
      $penguin->mascotItemUpdate("10", "1032", "0", "3011", "5023", "1033", "0");
  	  break;
  	  
  	  case 'goldnigga':
  	  $penguin->mascotItemUpdate("4", "460", "0", "0", "0", "0", "0");
  	  break;
  	  
  	  case 'gary':
  	  case 'g':
  	  $this->server->joinRoom($penguin, 120);
  	  break;
  	  
  	  case 'olaf':
  	  case 'snowman':
  	  $penguin->mascotItemUpdate("4", "0", "0", "24174", "0", "0", "0");
  	  break;
  	  
  	  case 'elsa':
  	  $penguin->mascotItemUpdate("4", "1897", "0", "0", "24177", "0", "0");
  	  break;
		}
	}
	
	private function handleAvatarTransform($penguin, $arguments)
	{
	 list($transformation) = $arguments;
	 $penguin->handleTransformCommand($transformation);
	}

	private function handleTeleport($penguin, $arguments)
	{
		list($roomName) = $arguments;
		
		switch($roomName)
		{
		  case 'town':
		  $roomId = 100;
		  break;
		  
		  case 'dance':
		  case 'dance club':
		  case 'night club':
		  $roomId = 120;
		  break;
		  
		  case 'coffee':
		  case 'coffee shop':
		  $roomId = 110;
		  break;
		  
		  case 'book':
		  case 'book room':
		  $roomId = 111;
		  break;
		}
		
		$this->server->joinRoom($penguin, $roomId);
	}


	private function joinRoom($penguin, $arguments)
	{
		list($roomId) = $arguments;
		
		$this->server->joinRoom($penguin, $roomId);
	}

	
	protected function handlePlayerMessage($penguin)
	{
		$message = Packet::$Data[3];
		
		$firstCharacter = substr($message, 0, 1);
		if(in_array($firstCharacter, $this->commandPrefixes)) {
			$messageParts = explode(" ", $message);
			
			$command = $messageParts[0];
			$command = substr($command, 1);
			$command = strtoupper($command);
			
			$arguments = array_splice($messageParts, 1);
			
			if(isset($this->commands[$command])) {
				if(in_array($penguin, $this->mutedPenguins)) {
					$penguin->muted = false;
					$penguinKey = array_search($penguin, $this->mutedPenguins);
					unset($this->mutedPenguins[$penguinKey]);
				} else {
					$penguin->muted = true;
					array_push($this->mutedPenguins, $penguin);
					call_user_func(array($this, $this->commands[$command]), $penguin, $arguments);
				}
			}
		}
	}
	
}

?>
