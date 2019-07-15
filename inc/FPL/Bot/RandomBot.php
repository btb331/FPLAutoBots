<?php

namespace FPL\Bot;

use FPL\Bot\Bot as Bot;

class RandomBot extends Bot
{

	protected function make_transfers()
	{
		echo "Starting Random transfer \n";
		$game_data = $this->_FPL->get_game_data();
		$team_data = $this->_FPL->get_team_data();

		//Get data needed for function
		$data = $game_data->get_players_data();
		$squad = $team_data->get_squad_ids();
		$injs = $team_data->get_flagged_players();

		//check if there are injured players in squad, randomly choose injured player, if no injuries randomly chose player
		if(count($injs) == 0)
		{
			$chosen_player = $squad[array_rand($squad)];
		} 
		else
		{
			$chosen_player = array_rand($injs);
		}

		//start an array to count the number of players from each team.
		$teamCount = array();

		//loop through squad, adding team_code to the array if not there and incrementing the value if it is
		//Result is array with team_code as keys and number of players from that team as value
		for($i=0; $i<count($squad); $i++){
			$team = $data[$squad[$i]]['team_code'];
			if(array_key_exists($team, $teamCount)){
				$teamCount[$team]++;
			}else{
				$teamCount[$team] = 1;
			}
		}

		echo $data[$chosen_player]['web_name'] . " choosen to remove" . "\n";

		 
		//Get position of chosen player and value of player
		$type = $data[$chosen_player]['element_type'];
		$player_value = $team_data->get_squad_data()[$chosen_player]['selling_price'];
		//get data in array where keys are the position and value is array of players
		$data_sorted = $this->get_playing_players(); 

		$bank = $team_data->get_team_data()['entry']['bank']; // should be $team_data->get_bank();

		$validTransfer = false;
		
		//keep trying untill valid player is found
		while(!$validTransfer)
		{
			//chosen player is randomly chosen from the array of players of the same position
			$chosen_new_player = array_rand($data_sorted[$type]);
			$team = $data[$chosen_new_player]['team_code'];
			$validTransfer = $this->_FPL->is_transfer_valid($chosen_player, $chosen_new_player);
			echo $data[$chosen_new_player]['web_name'] . " choosen to add" . "\n";
		}
			
		$this->_FPL->transfer([$chosen_player], [$chosen_new_player]);

		echo "random transfer done! \n\n" ;

		$message = "I choose to randomly remove " . $data[$chosen_player]['web_name'] . " and to buy " . $data[$chosen_new_player]['web_name'] . ". Crazy";

		$this->generateMessage($message, "Random Bot");



	}

	protected function organise_team()
	{
		$random_team = $this->randomize_team();
		
		$this->_FPL->create_picks($random_team['picks'], $random_team['subs']);
	}

	protected function set_captains()
	{
		echo "Randomizing captain \n";
		$game_data = $this->_FPL->get_game_data();
		$team_data = $this->_FPL->get_team_data();

		$data = $game_data->get_players_data();
		$squad = $team_data->get_squad_data();

		//need to get the team and the subs so that the captain chosen is not on the bench
		$team = array();
		$subs = array();
		foreach ($squad as $player => $player_data) 
		{
			if($player_data['is_sub'])
			{
				array_push($subs, $player);
			}
			else
			{
				array_push($team, $player);
			}
		}
		
		//choose captain from team
		$captain = $team[array_rand($team)];
		echo $data[$captain]['web_name'] . " chosen as captain \n";

		//if player isn't already captain
		if(!$squad[$captain]['is_captain'])
		{
			$this->_FPL->set_captain($captain);
		}

		//get vice captain that isn't the captain just chosen
		$valid_vice = false;
		while(!$valid_vice)
		{
				$vice = $team[array_rand($team)];
				if($vice != $captain)
				{
					$valid_vice = true;
				}
		}

		echo $data[$vice]['web_name'] . " chosen as vice captain \n";

		//if player isn't arlead set as vice captain
		if(!$squad[$vice]['is_vice_captain'])
		{
			$this->_FPL->set_vice_captain($vice);
		}
		echo "Randomizing captains done! \n\n";
	}

	public function initialise()
	{
		echo "intialising Random Bot \n";
		$game_data = $this->_FPL->get_game_data();
		$team_data = $this->_FPL->get_team_data();		

		$data = $game_data->get_players_data();
		$dataSorted = $this->get_playing_players();
		$squad = $team_data->get_squad_ids();


		$newPicks = array();
		//typePicks array counts the number of players in each position, 0 isn't a position hence the first 0, GK=1 so 1 is in the 2nd position 
		$typePicks = array(0, 2, 5, 5, 3);
		$teamCount = array();

		$bank = 1000; //players vales 10x normal value
		
		//the loop below decreases the position that is picked in $typePicks, so the sum of $typePicks is how many players left to chose, when it's 0 then we're done
//		$sum = array_sum($typePicks); 

		while(array_sum($typePicks) > 0)
		{
			//$sum = array_sum($typePicks);
			$type = array_rand($typePicks); // randomly choose position to pick

			if($typePicks[$type]==0){ //if we have already used our alloction of chosen position then try again
				continue;
			}

			$typePicks[$type]=$typePicks[$type]-1; // reduce the position counter of type picks
			$validPlayer = false;
			$count = 0; //start a count, so that if more than 10 tries to get a player happen, we reset the team
			while(!$validPlayer){
				echo $bank . " left in bank \n";
				$count++;
				$player = array_rand($dataSorted[$type]); //choose a player of the correct position

				//check if already selected 3 player from team, if so try again, else increment the team counter, if first player from team start with value 1
				$team = $data[$player]['team_code'];
				if(array_key_exists($team, $teamCount)){ 
					if($teamCount[$team]==3){
						continue;
					}else{
						$teamCount[$team]++;
					}
				}else{
						$teamCount[$team] = 1;
				}

				$cost = $data[$player]['now_cost'];

				//if player already selected, try again
				if(in_array($player, $newPicks)){
					continue;
				}

				//tried 10 times so reset the bank, the type picked (minus the player already picked), and the newPick array
				if($count==11){
					$bank=1000;
					$typePicks = array(0,2,5,5,3);
					$typePicks[$type]=$typePicks[$type]-1;
					$newPicks = array();
				}

				//if player is less than in the bank, we can choose them 
				if($cost<$bank){
					$validPlayer = true;
					$bank = $bank - $cost;
				}
			}
			echo $data[$player]['web_name'] . " has been picked \n";
			array_push($newPicks, $player);
		}
		echo "Team picked \n";

		//check to see if players picked were already in the team (tranfer throws errors other wise)
		for($i=0; $i<count($newPicks); $i++){
			if(in_array($newPicks[$i], $squad)){
				array_splice($squad, array_search($newPicks[$i], $squad), 1);
				array_splice($newPicks, array_search($newPicks[$i], $newPicks), 1);
			}
		}
		//print_r($teamCount);
		$this->_FPL->transfer($squad, $newPicks);
	}

	private function randomize_team()
	{
		echo "Randomizing sqaud ...\n";
		$game_data = $this->_FPL->get_game_data();
		$team_data = $this->_FPL->get_team_data();

		$data = $game_data->get_players_data();
		$squad = $team_data->get_squad_ids();
		$injs = $team_data->get_flagged_players();

		//array that will contain the player from differnt positions, i.e. GK =1 , so second array would be GK's
		$squadPositions = array(array(), array(), array(), array(), array());


		//sort squad into the players positions
		for($i = 0; $i < count($squad); $i++)
		{
			$type = $data[$squad[$i]]['element_type'];
			array_push($squadPositions[$type], $squad[$i]);
		}

		$subs = array(); //choose subs as much easier
		$subCont = array(0,1,2,3,2);

		//this loop picks injs player for the bench
		while(count($subs)<4 && count($injs)>0){
			$player = "";

			//if 3 subs but no GK, then we need to select an inj GK if is there is one, else we have picked all the inj subs
			if((count($subs)==3) && $subCont[1]==1){ 
				$player = "No goalkeeper";
				foreach ($injs as $injPlayer => $value) {
					$type = $data[$injPlayer]['element_type'];
					if($type==1){
						$player = $injPlayer;
					}
				}
			}
			if($player == "No goalkeeper"){
				break;
			}
			$player = array_rand($injs);
			$type = $data[$player]['element_type'];
			if($subCont[$type]==0){ //we can only pick a max of 2 of each type (except GK)
				continue;
			}

			$subCont[$type]--; //keep track of number of each positon selected

			array_push($subs, $player);
			array_splice($squad, array_search($player, $squad), 1); // remove player from squad so not picked again
			unset($injs[$player]); //remove from injs player so not selected again
			array_splice($squadPositions[$type], array_search($player, $squadPositions[$type]), 1); // remove from sorted array so not picked again
		}
		$count = 0;
		while(count($subs)<4)
		{
			if($count == 0 && $subCont[1] == 1){ // first need to select a goalkeeper if we haven't already from inj list
				$randPos = 1; 
			}else{
				$randPos = rand(2, 4); // choose a position to put on the bench
			}
			if($subCont[$randPos]==0){ //if we already have 2 of that positon, select a player again
				continue; 
			}
			$subCont[$randPos]--;  //increament count of the chosen player position 
			$player = $squadPositions[$randPos][array_rand($squadPositions[$randPos])]; //chose player from posiiton chosen
			array_push($subs, $player);
			array_splice($squad, array_search($player, $squad), 1); //remove from player squad
			array_splice($squadPositions[$randPos], array_search($player, $squadPositions[$randPos]), 1); //remove form sorted array
			$count++;
		}
		
		//print_r($squad);
		//print_r($subs);
		echo "Done \n\n";
		return array("picks" => $squad, "subs" => $subs);
	}


	// TBD -- have a feeling this could be in team controller (or game model most likely) but I'll leave it to Ben for now
	private function get_playing_players()
	{
		$game_data = $this->_FPL->get_game_data();

		$data = $game_data->get_players_data();
		$data_sorted = array(array(), array(), array(), array(), array());
		$gameweek = $game_data->get_next_event_week();
		foreach ($data as $player => $player_data) 
		{
			$history = $player_data['history'];
			$gameTime = 0;
			for($i = $gameweek-2; $i > $gameweek-5; $i--)
			{ //go back 3 gameweeks
				if(!array_key_exists($i, $history))
				{
					continue;
				}
				$gameTime+= $history[$i]['minutes'];
				if($gameTime > (75*3) && ($player_data['chance_of_playing_next_round'] == null || $player_data['chance_of_playing_next_round'] == 100))
				{
					$player_type = $player_data['element_type'];
					$data_sorted[$player_type][$player] = $player_data['now_cost'];	
				}
			}
		}
		return $data_sorted;
	}
}