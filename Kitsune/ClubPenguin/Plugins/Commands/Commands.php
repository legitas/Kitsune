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
		"TRANSFORM" => "handleAvatarTransform",
		"USERS" => "usersOnline"
	);
	
	private $mutedPenguins = array();
	
	private $patchedItems;
	
	public function __construct($server){
		$this->server = $server;
	}
	
	public function onReady(){
		parent::__construct(__CLASS__);
	}
	
	public function loadPatchedItems() {
		$this->patchedItems = $this->server->loadedPlugins["PatchedItems"];
	}

	private function handleGlobal($penguin, $arguments) {
	if($penguin->moderator) {
		foreach($this->server->penguins as $allPenguins) {
			$message = implode(" ", $arguments);
			$allPenguins->send("%xt%cprompt%-1%$message%");
		}
	} else {
		$penguin->send("%xt%cerror%-1%You don't have permission to perform that action%Error%");
		}
	}
    
	public function handleChangeNick($penguin, $arguments) {
		$blockedNicks = array("", "", "", "", "", "");
		if(!in_array($blockedNicks, $arguments)) {
			if($penguin->moderator){
			list($newNick) = $arguments;
			$penguin->updateNick($newNick);
			$this->server->joinRoom($penguin, $penguin->room->externalId);
			} else {
			$penguin->send("%xt%cerror%-1%You do not have permission to perform that action.%Error%");
			}
		}
	}

	private function getID($penguin) {
		$penguin->send("%xt%cprompt%-1%{$penguin->username}: Your penguin ID is {$penguin->id}.%");
	}
	
	public function handlePing($penguin) {
		$penguin->send("%xt%cerror%-1%Pong%Server%");
	}
	
	public function handleMyRoom($penguin) {
	   $penguin->send("%xt%cprompt%-1%The ID of the room you're in is {$penguin->room->externalId}%");
	}
	
  	public function buyItem($penguin, $arguments) {
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
 
  	private function addCoins($penguin, $arguments) {
		list($amount) = $arguments;
		$penguin->send("%xt%zo%{$penguin->room->internalId}%{$penguin->coins}%%0%0%0%");
		$penguin->setCoins($penguin->coins + $amount);
		$penguin->getPlayerString();
	}

	public function handleCoinsAnimation($penguin, $arguments) {
	    list($amount) = $arguments;
		$penguin->room->send("%xt%puffledig%{$penguin->room->internalId}%{$penguin->id}%$puffleId%0%0%$amount%{$penguin->puffleQuest['firstDig']}%false%");
	}
	
	public function updateColor($penguin, $arguments) {
	list($itemId) = $arguments;
	$penguin->updateColor($itemId);
	}
	
	public function updateHead($penguin, $arguments) {
	list($itemId) = $arguments;
	$penguin->updateHead($itemId);
	}
	
	public function updateFace($penguin, $arguments) {
	list($itemId) = $arguments;
	$penguin->updateFace($itemId);
	}
	
	public function updateNeck($penguin, $arguments) {
	list($itemId) = $arguments;
	$penguin->updateNeck($itemId);
	}
	
	public function updateBody($penguin, $arguments) {
	list($itemId) = $arguments;
	$penguin->updateBody($itemId);
	}
	
	public function updateHand($penguin, $arguments) {
	list($itemId) = $arguments;
	$penguin->updateHand($itemId);
	}
	
	public function updateFeet($penguin, $arguments) {
	list($itemId) = $arguments;
	$penguin->updateFeet($itemId);
	} 
	
	public function updatePhoto($penguin, $arguments) {
	list($itemId) = $arguments;
	$penguin->updatePhoto($itemId);
	}
	
	public function updateFlag($penguin, $arguments) {
	list($itemId) = $arguments;
	$penguin->updateFlag($itemId);
	}
	
	private function handleMascotUpdate($penguin, $arguments) {
		if($penguin->moderator) {
			list($mascot) = $arguments;
			switch($mascot)
			{
				case '0': // Reset
				$penguin->handleTransformCommand(0);
				$penguin->mascotItemUpdate("0", "0", "0", "0", "0", "0", "0");
				break;
				
				case 'rh': // Rockhopper
				$penguin->handleTransformCommand(0);
				$penguin->mascotItemUpdate("5", "1692", "152", "161", "4946", "0", "0");
				break;
				
				case 'srh': // Santa Rockhopper
				$penguin->handleTransformCommand(0);
				$penguin->mascotItemUpdate("5", "1753", "0", "0", "0", "0", "0");
				break;
				
				case 'h': // Herbert
				$penguin->handleTransformCommand(10);
				$penguin->mascotItemUpdate("15", "0", "1737", "0", "0", "0", "0");
				break;
				
				case 'sa': // Sasquach
				$penguin->handleTransformCommand(35);
				$penguin->mascotItemUpdate("15", "0", "0", "0", "1917", "0", "0");
				break;
				
				case 'g': // Gary
				$penguin->handleTransformCommand(0);
				$penguin->mascotItemUpdate("1", "0", "2113", "0", "4022", "0", "0");
				break;
				
				case 'ca': // Cadence
				$penguin->handleTransformCommand(0);
				$penguin->mascotItemUpdate("10", "1701", "0", "3011", "4955", "1034", "1033");
				break;
				
				case 's': // Sensei
				$penguin->handleTransformCommand(0);
				$penguin->mascotItemUpdate("14", "1622", "2015", "4485", "0", "0", "6177");
				break;
				
				case 'r': // Rookie
				$penguin->handleTransformCommand(0);
				$penguin->mascotItemUpdate("2", "1783", "2030", "0", "4365", "0", "0");
				break;
				
				case 'ph': // Puffle Handler
				$penguin->handleTransformCommand(0);
				$penguin->mascotItemUpdate("9", "1384", "0", "0", "4555", "0", "0");
				break;
				
				case 'aa': // Aunt Arctic
				$penguin->handleTransformCommand(0);
				$penguin->mascotItemUpdate("2", "1562", "2007", "0", "4814", "0", "0");
				break;
				
				case 'ktf': // Kermit the Frog - transformation sprite doesn't work?
				$penguin->handleTransformCommand(33);
				$penguin->mascotItemUpdate("15", "1805", "0", "0", "0", "0", "0");
				break;
				
				case 'sb': // Stompin' Bob
				$penguin->handleTransformCommand(0);
				$penguin->mascotItemUpdate("5", "1274", "0", "5105", "4383", "5106", "0");
				break;
				
				case 'pk': // Petey K
				$penguin->handleTransformCommand(0);
				$penguin->mascotItemUpdate("2", "1273", "2034", "3082", "4381", "0", "6078");
				break;
				
				case 'gb': // G Billy
				$penguin->handleTransformCommand(0);
				$penguin->mascotItemUpdate("1", "1275", "0", "0", "4384", "5107", "6080");
				break;
				
				case 'fr': // Franky
				$penguin->handleTransformCommand(0);
				$penguin->mascotItemUpdate("7", "0", "0", "0", "4382", "0", "6079");
				break;
			}
		} else {
			$penguin->send("%xt%cerror%-1%You do not have permission to perform that action.%Error%");
		}
	}
	
	private function handleAvatarTransform($penguin, $arguments) {
	 list($transformation) = $arguments;
	 $penguin->handleTransformCommand($transformation);
	}

	private function joinRoom($penguin, $arguments) {
		list($roomId) = $arguments;
		$this->server->joinRoom($penguin, $roomId);
	}
	
	protected function handlePlayerMessage($penguin) {
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
