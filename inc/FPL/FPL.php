<?php
namespace FPL;

use FPL\Factory\TeamFactory as TeamFactory;
use Util\MArray as MArray;

class FPL
{
	//Service
	private $_Service ;

	// All game data
	private $_GameData;

	//team data
	private $_TeamData;

	
	function __construct($service, $team_data, $game_data)
	{
		$this->_Service = $service;
		$this->_TeamData = $team_data;
		$this->_GameData = $game_data;		
	}


	public function create_picks($players, $subs)
	{
		if(!$this->_Service) return false;

		if(count($subs)!=4) 
		{
			echo "Need 4 subs";
			return false;
		}

		if(count($players)!=11)
		{
			echo "Need 11 players";
			return false;
		}
		
		echo "updating team ...";

		$picks = $this->_TeamData->get_squad_data();
		$data = $this->_GameData->get_players_data();

		$vice = false;
		$captain = false;

		$formation_count = array(0,0,0,0,0);

		for($i = 0; $i < count($players); $i++)
		{
			if(!array_key_exists($players[$i], $picks))
			{
				echo "Player not in team"
				return false;
			}
			
			$type = $data[$players[$i]]['element_type'];
			$formation_count[$type]++;
		}
		//print_r($formation_count);

		for($i = 1; $i < count($formation_count); $i++)
		{
			$formation_count[$i] = $formation_count[$i] + $formation_count[$i-1];
		}

		$count = array(0,0,0,0,0);
		$positions = array();

		for($i = 0; $i < count($players); $i++)
		{
			$player = $players[$i];
			$type = $data[$player]['element_type'];
			$position = ($formation_count[$type-1]) + $count[$type] + 1;
			$count[$type]++;
			$positions[$player] = $position;
			if($picks[$player]['is_vice_captain']){
				$vice = $player;
				//echo "vice is ". $vice;
			}
			if($picks[$player]['is_captain']){
				$captain = $player;
			//	echo "captain is ". $vice;
			}
		}


		$sub_count = 13;
		for($i = 0; $i < count($subs); $i++)
		{
			$player = $subs[$i];
			$type = $data[$player]['element_type'];
			
			if($type == 1)
			{
				$positions[$player] = 12;
			}
			else
			{
				$positions[$player] = $sub_count;
				$sub_count++;
			}
		}

		$data = array('picks' => array());

		if($vice == false && $captain == false)
		{
			$captain = $players[array_rand($players)];
			while(!$vice)
			{
				$rand_vice = $players[array_rand($players)];
				if($rand_vice != $captain)
				{
					$vice = $rand_vice;
				}
			}
		}

		if($vice == false && $captain != false)
		{
			while(!$vice)
			{
				$rand_vice = $players[array_rand($players)];
				if($rand_vice != $captain)
				{
					$vice = $rand_vice;
				}
			}
		}

		if($captain == false && $vice != false)
		{
			while(!$captain)
			{
				$rand_capt = $players[array_rand($players)];
				if($rand_capt != $vice){
					$captain = $rand_capt;
				}
			}
		}

		for($i = 0; $i < count($players); $i++)
		{
			$player = $players[$i];
			if($player == $captain)
			{
				$picks[$player]['is_captain'] = true;
			}
			if($player == $vice)
			{
				$picks[$player]['is_vice_captain'] = true;
			}
			$picks[$player]['position'] = $positions[$player]; 
			$picks[$player]['is_sub'] = false;
			array_push($data['picks'], $picks[$player]);
		}


		for($i = 0; $i < count($subs); $i++)
		{
			$player = $subs[$i];
			$picks[$player]['position'] = $positions[$player]; 
			$picks[$player]['is_sub'] = true;
			$picks[$player]['is_captain'] = false;
			$picks[$player]['is_vice_captain'] = false;
			array_push($data['picks'], $picks[$player]);
		}

		//print_r($picks);

		$url = "https://fantasy.premierleague.com/drf/my-team/". $this->_TeamData->get_team_id() ."/";

		$response = $this->_Service->post($url, json_encode($data), ['Referer: https://fantasy.premierleague.com/a/team/my']);

		if($response['code'] == 200)
		{
			$team_data = json_decode($response['data'], true);
			$squad_data = TeamFactory::merge_squad_data($this->_TeamData->get_squad_data(), $team_data);
			$this->_TeamData->set_team_data($team_data);
			$this->_TeamData->set_squad_data($squad_data);
			echo "Team updated.\n\n";
			return true;
		}
		else
		{
			echo "Team not updated \nError:\n\n";
			echo $response['data'];
			return false;
		}
	}

	// sets the captain of the team by player id
	public function set_captain($player_id)
	{
		if(!$this->_Service) return;

		if($this->_TeamData->get_team_id() == null) 
		{
			echo 'must set the team id before calling set_captain()';
			return;
		}

		$this->vice_or_captain($player_id, false);
	}

	// sets the vice captain of the team by player id
	public function set_vice_captain($player_id)
	{
		if(!$this->_Service) return;

		if($this->_TeamData->get_team_id() == null) 
		{
			echo 'must set the team id before calling set_vice_captain()';
			return;
		}

		$this->vice_or_captain($player_id, true);
	}


	// check if the transfer is valid, show errors and return boolean
	public function is_transfer_valid($player_out, $player_in)
	{
		$err = array();

		$all_player_data = $this->_GameData->get_players_data();
		$team_player_data = $this->_TeamData->get_squad_data();
		$bank = $this->_TeamData->get_bank();

		// team codes
		$player_in_team = $all_player_data[$player_in]['team_code'];
		$player_out_team = $all_player_data[$player_out]['team_code'];
		// values
		$player_in_value = $all_player_data[$player_in]['now_cost'];
		$player_out_value = $team_player_data[$player_out]['selling_price'];
		// player position 
		$player_in_type =  $all_player_data[$player_in]['element_type'];
		$player_out_type =  $all_player_data[$player_out]['element_type'];

		$team_count = MArray::count_by($team_player_data, 'team_code', $player_in_team);
		if($player_out_team == $player_in_team) $team_count--;

		if($player_in_value > $player_out_value + $bank)
		{
			array_push($err, 'Not enough money.');
		}

		if($player_in_type != $player_out_type)
		{
			array_push($err, 'Not same player position.');
		}

		if($this->_TeamData->is_player_in_team($player_in))
		{
			array_push($err, 'Already in squad.');
		}

		if($team_count >= 3)
		{
			array_push($err, 'Too many players from the same team.');
		}

		if(count($err))
		{
			echo "Transfer not valid: \n";
			foreach ($err as $key => $value) 
			{
				echo $value . "\n";
			}
			echo "\n";
			return false;
		}
		else
		{
			return true;
		}
	}


	// make a transfer of a player
	public function transfer($players_out, $players_in) 
	{
		if(!$this->_Service) return;

		if(count($players_out)==0 & count($players_in)==0){
			echo "No transfers being made\n";
			return;
		}

		if($this->_TeamData->get_team_id() == null && $this->_GameData->get_next_event_week() == null) 
		{
			echo 'must set the team_id and event_week before calling transfer()';
			return;
		}
		if(count($players_out) != count($players_in))
		{
			echo "Number of players in must be the same as players out \n";
			return;
		}

		$url = "https://fantasy.premierleague.com/drf/transfers";

		$all_data = $this->_GameData->get_players_data();

		$names_in = '';
		$names_out = '';

		for($i = 0; $i < count($players_in); $i++)
		{
			$names_in .= $all_data[$players_in[$i]]['web_name'] . ', ';
			$names_out .= $all_data[$players_out[$i]]['web_name'] . ', ';
		}

		echo "Transferring in " .  $names_in . "\n for \n" . $names_out . "\n";


		$players_in_type = array(array(), array(), array(), array(), array());
		$players_out_type = array(array(), array(), array(), array(), array());

		for($i = 0; $i < count($players_in); $i++)
		{
			$type = $all_data[$players_in[$i]]['element_type'];
			array_push($players_in_type[$type], $players_in[$i]);

			$type = $all_data[$players_out[$i]]['element_type'];
			array_push($players_out_type[$type], $players_out[$i]);
		}

		for($i = 0; $i < count($players_in_type); $i++)
		{
			if(count($players_in_type[$i]) != count($players_out_type[$i]))
			{
				echo "same number of each type must be chosen";
				return;
			}
		}

		$transfer_array = [];

		for($i = 0; $i < count($players_in_type); $i++)
		{
			for($j = 0; $j < count($players_in_type[$i]); $j++)
			{
				if($players_in_type[$i][$j] == $players_out_type[$i][$j])
				{
					continue;
				}
				$temp_data = [
					'element_in' => $players_in_type[$i][$j],
					'element_out' => $players_out_type[$i][$j],
					'selling_price' => $this->_TeamData->get_squad_data()[$players_out_type[$i][$j]]['selling_price'],
					'purchase_price' => $all_data[$players_in_type[$i][$j]]['now_cost']	
				];
				array_push($transfer_array, $temp_data);
			}
		}

		$data = [
			'confirmed' => true,
			'entry' => $this->_TeamData->get_team_id(),
			'event' => $this->_GameData->get_next_event_week(),
			'wildcard' => false,
			'freehit' => false,
			'transfers' => $transfer_array
		];

		//print_r(json_encode($data));

		$response = $this->_Service->post($url, json_encode($data), ['Referer: https://fantasy.premierleague.com/a/squad/transfers']);

		if($response['code'] == 200)
		{
			echo "Players successfully transfered.\n";
			$data = TeamFactory::retreive_data($this->_Service);
			$this->_TeamData->set_team_data($data['team_data']);
			$this->_TeamData->set_squad_data($data['squad_data']);
			return true;
		}
		else
		{
			echo "Players not transfered.\nError:\n";
			echo $response['data'];
			return false;
		}
	}

	// Private Functions
	// sets the vice captain or captain
	private function vice_or_captain($player_id, $is_vice)
	{		
		$target = $is_vice ? 'vice captain' : 'captain';

		echo 'Setting ' . $target . ' to ' . $this->_GameData->get_players_data()[$player_id]['web_name'] . "\n";

		$url = "https://fantasy.premierleague.com/drf/my-team/". $this->_TeamData->get_team_id() ."/";

		$swap = false;

		$data = array('picks' => array());
		// first get current captain and current vice to check if we must swap
		foreach($this->_TeamData->get_squad_data() as $id => $p_data)
		{
			if($p_data['is_captain']) 
			{
				$captain = $id;
				// set swap here to perform swap after the loop as vice captain may not be set yet
				if($id == $player_id && $is_vice) $swap = true;
			}

			if($p_data['is_vice_captain']) 
			{
				$vice = $id;
				// set swap here to perform swap after the loop as captain may not be set yet
				if($id == $player_id && !$is_vice) $swap = true;
			}
		}

		// Swap them over if trying to make current vice captain, captain
		if($swap) 
		{
			$tmp = $captain;
			$captain = $vice;
			$vice = $tmp;
		}
		else
		{
			foreach($this->_TeamData->get_squad_data() as $id => $p_data)
			{
				if($id == $player_id)	
				{
					if($is_vice)
						$vice = $player_id;
					else 
						$captain = $player_id;
				}
			}
		}

		foreach($this->_TeamData->get_squad_data() as $id => $p_data)
		{
			$p_data['is_captain'] = ($id == $captain)  ;
			$p_data['is_vice_captain'] = ($id == $vice) ;
			
			array_push($data['picks'], $p_data);
		}

		$response = $this->_Service->post($url, json_encode($data), ['Referer: https://fantasy.premierleague.com/a/team/my']);

		if($response['code'] == 200)
		{
			echo $this->_GameData->get_players_data()[$player_id]['web_name'] . " is now the {$target}.\n";

			$team_data = json_decode($response['data'], true);
			$squad_data = TeamFactory::merge_squad_data($this->_TeamData->get_squad_data(), $team_data);
			$this->_TeamData->set_team_data($team_data);
			$this->_TeamData->set_squad_data($squad_data);
			return true;
		}
		else
		{
			echo $this->_GameData->get_players_data()[$player_id]['web_name'] . " not {$target}ed.\nError:\n";
			echo $response['data'] . "\n";
			return false;
		}
	}


	// getters
	public function get_game_data()
	{
		return $this->_GameData;
	}

	public function get_team_data()
	{
		return $this->_TeamData;
	}
}