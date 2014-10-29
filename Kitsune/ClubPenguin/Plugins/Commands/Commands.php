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
		)
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
		"COLOR" => "updateColor",
		"HEAD" => "updateHead",
		"FACE" => "updateFace",
		"SUMMON" => "summonPenguin",
		"NECK" => "updateNeck",
		"BODY" => "updateBody",
		"HAND" => "updateHand",
		"FEET" => "updateFeet",
		"BACKGROUND" => "updatePhoto",
		"PIN" => "updateFlag",
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
				$roomId = $penguin->room->internalId
				$this->server->joinRoom($penguin, $roomId);
			} else {
				$penguin->send("%xt%cerror%-1%You do not have permission to perform that action.%Hack Attempt%");
			}
		} else {
			$penguin->send("%xt%cerror%-1%You do not have permission to perform that action.%Hack Attempt%");
		}
	}

	private function getID($penguin)
	{
		$penguin->send("%xt%cprompt%-1%{$penguin->username}: Your penguin ID is {$penguin->id}.%");
		}
	}
	
	public function handlePing($tarPlay)
	{
		$tarPlay->send("%xt%cerror%-1%Pong%Server%");
	}
	
	public function handleMyRoom($penguin)
	{
	   $penguin->send("%xt%cprompt%-1%The ID of the room you're in is {$penguin->room->externalId}%");
	}

	
  	public function buyItem($penguin, $arguments)
  	{
		list($itemId) = $arguments;
		
		$this->patchedItems->handleBuyInventory($penguin, $itemId);
	}
	
  	public function buyFurn($penguin, $arguments) {
	list($furnId) = $arguments;
        $penguin->buyFurniture($furnId, $cost = 0);
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
	
	public function updateColor($penguin, $arguments){
	list($itemId) = $arguments;
	$penguin->updateColor($itemId);
	}
	
	public function updateHead($penguin, $arguments){
	list($itemId) = $arguments;
	$penguin->updateHead($itemId);
	}
	
	public function updateFace($penguin, $arguments){
	list($itemId) = $arguments;
	$penguin->updateFace($itemId);
	}
	
	public function updateNeck($penguin, $arguments){
	list($itemId) = $arguments;
	$penguin->updateNeck($itemId);
	}
	
	public function updateBody($penguin, $arguments){
	list($itemId) = $arguments;
	$penguin->updateBody($itemId);
	}
	
	public function updateHand($penguin, $arguments){
	list($itemId) = $arguments;
	$penguin->updateHand($itemId);
	}
	
	public function updateFeet($penguin, $arguments){
	list($itemId) = $arguments;
	$penguin->updateFeet($itemId);
	}
	
	public function updatePhoto($penguin, $arguments){
	list($itemId) = $arguments;
	$penguin->updatePhoto($itemId);
	}
	
	public function updateFlag($penguin, $arguments){
	list($itemId) = $arguments;
	$penguin->updateFlag($itemId);
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
  	  $this->server->joinRoom($penguin, 120); // ?????? This isn't even an update
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
