<?php

namespace FPL\Bot;

use FPL\Bot\Bot as Bot;

//transfer - selects the most transfered in player, then tries to transfer out the most transfered out player in the same position. If no valid transfer is found, select the next most transfered player and repeat

// squad pick - selects the highest owned players from the previous gameweek

// captain & vice - selects the higest owned players from the previous gameweek

// initialise - choose the top 15 most popular players - within the budget 

class WisdomOfCrowdBot2 extends Bot
{

	private function get_squad_with_net_transfers(){
		$squadTransfers = array();
		$squad = $this->_FPL->get_team_data()->get_squad_ids();
		$data = $this->_FPL->get_game_data()->get_players_data();
		
		foreach ($squad as $player) {
			$pData = $data[$player];
			$transfersIn = $pData['transfers_in_event'];
			$transfersOut = $pData['transfers_out_event'];
			$net = $transfersIn - $transfersOut;
			$squadTransfers[$player] = $net;
		}

		return $squadTransfers;
	}

	private function get_squad_with_selectedBy(){
		$squadTransfers = array(array(), array(),array(),array(),array());
		$squad = $this->_FPL->get_team_data()->get_squad_ids();
		$data = $this->_FPL->get_game_data()->get_players_data();
			$gameWeek = $this->_FPL->get_game_data()->get_next_event_week()-2;
		
		foreach ($squad as $player) {
			$pData = $data[$player];
			$type = $pData['element_type'];
			$squadTransfers[$type][$player] = $pData['selected_by_percent'];
		}

		return $squadTransfers;
	}

	protected function make_transfers()
	{
		echo "Starting transfer\n";

		$game_data = $this->_FPL->get_game_data();
		$team_data = $this->_FPL->get_team_data();

		$data = $game_data->get_players_data();
		$squad = $team_data->get_squad_ids();
		$bank = $team_data->get_team_data()['entry']['bank']; 

		$playerTransfers= array();

		foreach ($data as $player => $pData) {
			$transfersIn = $pData['transfers_in_event'];
			$transfersOut = $pData['transfers_out_event'];
			$net = $transfersIn - $transfersOut;
			$type = $pData['element_type'];
			$playerTransfers[$type][$player] = $net;
		}
			// print_r(	$playerTransfers[2]);
		for($i=1; $i<count($playerTransfers)+1; $i++){
				arsort($playerTransfers[$i]);
			}

		$squadTransfers = $this->get_squad_with_net_transfers();

			asort($squadTransfers);

		$validTransfer = false;

		$count = 0;
		$playerTry = 0;

		while(!$validTransfer){
			
			$playersOrdered = array_keys($squadTransfers);
			$playerOut = $playersOrdered[$playerTry];
			$playerOutType = $data[$playerOut]['element_type'];
			$playerIn = array_keys($playerTransfers[$playerOutType])[$count];

			echo "Trying to transfer in " . $data[$playerIn]['web_name'] . "...";
			if(in_array($playerIn, $squad)){
				echo "already have him! \n";
				$playerTry++;
				continue;
			}

			$isValidTransfer = $this->_FPL->is_transfer_valid($playerOut, $playerIn);

				echo " trying to remove " . $data[$playerOut]['web_name'];
				// print_r($squadTransfers);

			if($isValidTransfer){
				echo "\n";
				$validTransfer = true;
				echo $data[$playerOut]['web_name'] . " choosen to remove" . "\n";
				echo $data[$playerIn]['web_name'] . " choosen to add" . "\n";
				$this->_FPL->transfer([$playerOut], [$playerIn]);
				echo "Transfer done \n\n";
			}else{
				echo "...falied \n";
				if($count>=count($playerTransfers[$playerOutType])-1){
					$playerTry++;
					$count=0;
				}else{
						$count++;
					}
			}

			if($playerTry>11){
				echo "no transfer possible";
				$message = "I couldn't follow the crowd... I have failed you";

				$this->generateMessage($message, "Wisdom Of Crowd Bot 2");
				return;

			}

		}
		$message = "Your fellow humans mostly sold " . $data[$playerOut]['web_name'] . " so I did too, I decided to buy " . $data[$playerIn]['web_name'] . ".";

		$this->generateMessage($message, "Wisdom Of Crowd Bot 2");
	}

	protected function organise_team()
	{
		$game_data = $this->_FPL->get_game_data();
		$team_data = $this->_FPL->get_team_data();
		$data = $game_data->get_players_data();
		
		$squadPos = $team_data->get_team_by_position();
		$squad = $this->_FPL->get_team_data()->get_squad_ids();
		$gameWeek = $game_data->get_next_event_week()-2;



		$squadTransfersPos = $this->get_squad_with_selectedBy();

		$subs = array();
		$team = array();

		$squadTransfers = array();

		foreach ($squad as $player) {
			$pData = $data[$player];
			$squadTransfers[$player] = $pData['selected_by_percent'];
		}

		asort($squadTransfers);

		// print_r($squadTransfers);

		$subCount = array(0,1,2,3,2);
		$count = 0;
		while(count($subs)<4){
			if($count==0){
					asort($squadTransfersPos[1]);
					$player = array_keys($squadTransfersPos[1])[0];
					array_push($subs, $player);
					array_splice($squad, array_search($player, $squad), 1);
					$subCount[1]--;
			}
			$player = array_keys($squadTransfers)[$count];
			$count++;
			if(in_array($player, $subs)){
				continue;
			}
			$type = $pData['element_type'];
			if($subCount[$type]==0){ //if we already have 2 of that positon, select a player again
				continue; 
			}
			$subCount[$type]--;
			$pData = $data[$player];
			array_push($subs, $player);
			array_splice($squad, array_search($player, $squad), 1); //remove from player squad
		}
			// print_r($subs);
			// echo "team\n";
			// print_r($squad);

		$this->_FPL->create_picks($squad, $subs);

	}

	protected function set_captains()
	{
		echo "Choosing captain\n";
		$game_data = $this->_FPL->get_game_data();
		$team_data = $this->_FPL->get_team_data();
		$data = $game_data->get_players_data();
		$sData = $team_data->get_squad_data();
		
		$squadPos = $team_data->get_team_by_position();
		$squad = $this->_FPL->get_team_data()->get_squad_ids();
		$gameWeek = $game_data->get_next_event_week()-2;

		$squadData = array();

		foreach ($squad as $player) {
			$pData = $data[$player];
			$squadData[$player] = $pData['selected_by_percent'];;
		}

		arsort($squadData);

		$captain = array_keys($squadData)[0];

		$vice = array_keys($squadData)[1];

		echo $data[$captain]['web_name'] . " selected as captain \n";
		echo $data[$vice]['web_name'] . " selected as captain \n";
		echo "Captains chosen";

		if(!$sData[$captain]['is_captain'])
		{	
			$this->_FPL->set_captain($captain);
		}

		if(!$sData[$vice]['is_vice_captain'])
		{
			$this->_FPL->set_vice_captain($vice);
		}

	}

	public function initialise()
	{
		echo "intialising Wisdom Of Crowd Bot \n\n";

		$game_data = $this->_FPL->get_game_data();
		$team_data = $this->_FPL->get_team_data();
		$data = $game_data->get_players_data();
		$bank = 1000;
		$playerDataPos = array();
		$playerData = array();

		foreach ($data as $player => $pData) {
			$playerDataPos[$data[$player]['element_type']][$player] = $pData['selected_by_percent'];
			$playerData[$player] = $pData['selected_by_percent'];
		}

		arsort($playerData);

		$squad = array();

		$squadCounter = array(0, 2, 5, 5, 3);

		$teamCounter = array();

		$removedPlayers = array();

		for($i=1; $i<=count($playerDataPos); $i++){
			asort($playerDataPos[$i]);
			$players = array_keys($playerDataPos[$i]);
			$player = $players[count($players)-1];
			array_push($squad, $player);
			$squadCounter[$i]--;
			$bank -= $data[$player]['now_cost'];
			echo "Added " . $data[$player]['web_name'] . "\n";
			echo $bank . " left in bank \n\n";
		}

		$playerCount = 0;
		while(count($squad)<15){
			if($playerCount>count($data)-1){
				echo "No more players left\n\n";
				die;
			}
			$player = array_keys($playerData)[$playerCount];
			echo "Trying to add " . $data[$player]['web_name'] . "...";
			if(in_array($player, $squad)){
				echo "already got him \n\n";
				$playerCount++;
				continue;
			}
			$cost = $data[$player]['now_cost'];
			$type = $data[$player]['element_type'];
			if($squadCounter[$type]==0){
				echo "No room \n\n";
				$playerCount++;
				continue;
			}
			$team = $data[$player]['team_code'];
			if(isset($teamCounter[$team]) && $teamCounter[$team]==3){
				echo "Too many players from this team \n\n";
				$playerCount++;
				continue;
			}
			if($bank>$cost){
				array_push($squad, $player);
				echo "success\n";
				$bank -= $cost;
				echo $bank . " left in bank \n\n";
				$playerCount++;
				$squadCounter[$type]--;
				if(array_key_exists($team, $teamCounter)){
					$teamCounter[$team]++;
				}else{
					$teamCounter[$team] = 1;
				}
			}else{
				$playerTry = 0;
				$hasAdded = false;
				while(!$hasAdded){
					if($playerTry>count($squad)-1){
						break;
					}
					$toRemovePlayer = $squad[count($squad)-1-$playerTry];
					if($data[$toRemovePlayer]['element_type']!=$data[$player]['element_type'] || $data[$toRemovePlayer]['now_cost']<$data[$player]['now_cost']){
						$playerTry++;
						continue;
					}else{
						$removedTeam = $data[$toRemovePlayer]['team_code'];
						array_splice($squad, array_search($toRemovePlayer, $squad), 1);
						array_push($squad, $player);
						$bank-= $cost;
						$bank+= $data[$toRemovePlayer]['now_cost'];
						$playerCount++;
						$hasAdded = true;
						array_push($removedPlayers, $toRemovePlayer);
						if(array_key_exists($team, $teamCounter)){
							$teamCounter[$team]++;
						}else{
							$teamCounter[$team] = 1;
						}
						$teamCounter[$removedTeam]--;
						echo " removed " . $data[$toRemovePlayer]['web_name'] . " to make possible... success \n";
						echo $bank . " left in the bank\n\n";
					}
				}
				if(!$hasAdded){
					$playerCount++;

					echo "failed\n\n";
				}
			}
		}
		
		foreach ($removedPlayers as $player) {
			if(in_array($player, $squad)){
				continue;
			}
			for($i=count($squad)-1; $i>0; $i--){
				$squadPlayer = $squad[$i];
				if($playerData[$squadPlayer]>$playerData[$player] || $data[$player]['element_type']!=$data[$squadPlayer]['element_type']){
					continue;
				}
				if($bank+$data[$squadPlayer]['now_cost']>$data[$player]['now_cost']){
					array_splice($squad, array_search($squadPlayer, $squad), 1);
					array_push($squad, $player);
					$bank = $bank - $data[$player]['now_cost'] + $data[$squadPlayer]['now_cost'];
					echo "With spare cash added back " . $data[$player]['web_name'] . " for " . $data[$squadPlayer]['web_name'] . "\n";
					break;
				}
			}
		}

		$oldSquad = $team_data->get_squad_ids();

		$duplicatePlayers = array();

			//check to see if players picked were already in the team (tranfer throws errors other wise)
		for($i=0; $i<count($squad); $i++){
			if(in_array($squad[$i], $oldSquad)){
				array_push($duplicatePlayers, $squad[$i]);
			}
		}

		foreach ($duplicatePlayers as $player) {
			array_splice($oldSquad, array_search($player, $oldSquad), 1);
			array_splice($squad, array_search($player, $squad), 1);
		}


		$this->_FPL->transfer($oldSquad, $squad);

		// die;
		$this->organise_team();

		$this->set_captains();

		return;
	
	}

	
}