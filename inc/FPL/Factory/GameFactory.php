<?php

namespace FPL\Factory;

use FPL\Model\GameData as GameData;
use Util\File as File;
use FPL\Util\Service as Service;

/**
 *  A factory class to create game data
 */
class GameFactory 
{
	private static $_game_data_file = '_game_data.json';
	private static $_all_player_data_file = '_all_player_data.json';

	// return a FPL\Model\Game class
	public static function create_game($online = true)
	{
		if($online)
		{
			$game_data = self::retreive_game_data();
			
			$filetime = File::get_modify_date(self::$_all_player_data_file);
			if($filetime && $filetime > time() - 43200)
			{
				echo "retreiving all player data from cache...\n";
				$all_player_data = File::read_json_from(self::$_all_player_data_file);
			}
			else
			{
				$all_player_data = self::retreive_all_player_data($game_data);
			}
		}
		else
		{
			echo "retreiving all game data from cache...\n";
			$game_data = File::read_json_from(self::$_game_data_file);
			$all_player_data = File::read_json_from(self::$_all_player_data_file);
		}

		echo "all game data retreived.\n";
		return new GameData($game_data, $all_player_data);
	}


	// get the game data
	private static function retreive_game_data()
	{
		echo "getting game data... \n";

		$url = "https://fantasy.premierleague.com/drf/bootstrap-static";
		$game_data = json_decode(Service::unsigned_get($url)['data'], true);
		File::write_json_to(self::$_game_data_file, $game_data);

		echo "game data received... \n";
		return $game_data;
	}

	// get all the player data
	private static function retreive_all_player_data($game_data) 
	{
		echo "getting all player data... \n";
		// extract all the player data
		$elements = $game_data['elements'];

		$players = array(); 

		$count = 0;
		for($i = 0; $i < count($elements); $i++)
		{
			$id = $elements[$i]['id'];
			$url = "https://fantasy.premierleague.com/drf/element-summary/" . $id;
			$player_data = json_decode(Service::unsigned_get($url)['data'], true);

			foreach ($player_data as $key => $value) 
			{
				$elements[$i][$key] = $value;
			}
			$count++;

			if(fmod($count, 30) == 0)
			{
				echo "Done " . $count . " players \n";
			}
			$players[$elements[$i]['id']] = $elements[$i];
		}

		File::write_json_to(self::$_all_player_data_file, $players);

		echo "all player data received... \n";
		return $players;
	}

}