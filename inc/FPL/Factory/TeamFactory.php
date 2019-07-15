<?php

namespace FPL\Factory;

use Util\File as File;
use FPL\Model\TeamData as TeamData;

/**
 *  Factory class to create the Team model
 */
class TeamFactory
{
	private static $_team_data_file = '_my_team_data.json';
	private static $_player_data_file = '_my_team_player_data.json';
	private static $_full_player_data ;

	// return a Team model
	public static function create_team($service, $full_player_data)
	{
		echo "Getting my team data...\n";

		self::$_full_player_data = $full_player_data;

		if($service == null)
		{
			echo "Retreiving team data from cache...\n";
			$data['team_data'] = File::read_json_from(self::$_team_data_file);
			$data['squad_data'] = File::read_json_from(self::$_player_data_file);
		}
		else
		{
			$data = self::retreive_data($service);
		}

		echo "Retreived team data. \n\n";

		return new TeamData($data['team_data'], $data['squad_data']);
	}



	// get the teams data from the injected service 
	public static function retreive_data($service)
	{
		// get transfer to get the team id first
		$transfer_url = 'https://fantasy.premierleague.com/drf/transfers';
		$transfer_data = $service->get($transfer_url)['data'];
		$transfer_data = json_decode($transfer_data, true);
		File::write_json_to('transfer_data.json', $transfer_data);

		// get my team
		$team_url = "https://fantasy.premierleague.com/drf/my-team/" . $transfer_data['entry']['id'] . "/";
		$team_data = $service->get($team_url)['data'];
		$team_data = json_decode($team_data, true);

		// get the player data in a consistent format
		$transfer_player_data = self::extract_team_data($transfer_data['picks']);
		$team_player_data = self::extract_team_data($team_data['picks']);

		// merge team transfer data with team_player_data
		$squad_data = array();
		foreach ($team_player_data as $id => $player) 
		{
			$player['team_player_id'] = $transfer_player_data[$id]['id'];
			$squad_data[$id] = array_merge($transfer_player_data[$id], $player, self::$_full_player_data[$id]);
		}

		// write data to files
		File::write_json_to(self::$_team_data_file, $team_data);
		File::write_json_to(self::$_player_data_file, $squad_data);

		return array(
			'squad_data' => $squad_data,
			'team_data' => $team_data
		);
	}


	// BELOW TBD -- not really factory functions. And could be done more efficiently from FPL I feel but should look later. Not an issue for now
	// Notes to self: probably a general ResponseParser abstract class which will contain split_header_body and parse_header
	// then change current responseParser to ServiceResponseParser with an abstract method 'parse' being current format method
	// then can have TeamResponseParser, TransferResponseParser to handle each responses specifically with parse method

	// merges old player data from incoming raw data
	public static function merge_squad_data($current_squad, $new_squad)
	{
		$new_squad = self::extract_team_data($new_squad['picks']);
		
		$result = array();

		foreach ($current_squad as $key => $value)
		{
			$result[$key] = array_merge($current_squad[$key], $new_squad[$key]);
		}

		return $result;
	}

	// player data comes in quite a consistent form so can extract it with the same rules
	private static function extract_team_data($picks)
	{
		$players = array();

		for($i = 0; $i < count($picks); $i++)
		{
			$players[$picks[$i]['element']] = $picks[$i];
		}

		return $players;
	}

}