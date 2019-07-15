<?php 

namespace FPL\Model;

use Util\MArray as MArray;
 
class TeamData
{

	private $_team_data; //raw data from the JSON

	private $_squad_data; // associtve array where keys are the players in the squad

	function __construct($team_data, $squad_data)
	{
		$this->_team_data = $team_data;
		$this->_squad_data = $squad_data;
	}

	// Main Setters
	public function set_team_data($team_data)
	{
		$this->_team_data = $team_data;
	}

	public function set_squad_data($squad_data)
	{
		$this->_squad_data = $squad_data;
	}

	// Main Getters
	public function get_team_data()
	{
		return $this->_team_data;
	}

	public function get_squad_data()
	{
		return $this->_squad_data;
	}


	// The team's data
	// team id
	public function get_team_id()
	{
		return $this->_team_data['entry']['id'];
	}

	// team name
	public function get_team_name()
	{
		return $this->_team_data['entry']['name'];
	}

	// amount of money in the bank
	public function get_bank()
	{
		return $this->_team_data['entry']['bank'];
	}




	// The squad data
	// get a list of player id's
	public function get_squad_ids()
	{
		return array_keys($this->_squad_data);
	}

	public function get_keepers()
	{
		return MArray::filter_by($this->_squad_data, 'element_type', '=', 1);
	}

	public function get_defenders()
	{
		return MArray::filter_by($this->_squad_data, 'element_type', '=', 2);
	}

	public function get_midfielders()
	{
		return MArray::filter_by($this->_squad_data, 'element_type', '=', 3);
	}

	public function get_strikers()
	{
		return MArray::filter_by($this->_squad_data, 'element_type', '=', 4);
	}

	public function get_subs()
	{
		return MArray::filter_by($this->_squad_data, 'is_sub', '=', 1);
	}

	public function get_playing()
	{
		return MArray::filter_by($this->_squad_data, 'is_sub', '=', 0);
	}

	// get players ordered by price inc
	public function get_players_by_selling_price_asc()
	{
		return MArray::order_by($this->_squad_data, 'selling_price', true);
	}

	// get players ordered by price des
	public function get_players_by_selling_price_desc()
	{
		return MArray::order_by($this->_squad_data, 'selling_price', false);
	}

	// get players ordered by event points asc
	public function get_players_by_event_points_asc()
	{
		return MArray::order_by($this->_squad_data, 'event_points', true);
	}

	// get players ordered by event points desc
	public function get_players_by_event_points_desc()
	{
		return MArray::order_by($this->_squad_data, 'event_points', false);
	}

	// players who have a null or less than 100% chance of playing next event
	public function get_flagged_players()
	{
		return MArray::filter_by($this->_squad_data, 'news', '!=', "");
	}

	// check if a player with given id exists in the team
	public function is_player_in_team($id)
	{
		return in_array($id, $this->get_squad_ids());
	}


	// Static function
	// max amount of subs for each position
	// Can allow max of 1 keeper, 3 def/mid or and 2 forwards. With 4 in total
	public static function max_subs($position = null)
	{
		switch ($position) 
		{
			case 1:
				$max = 1;
				break;
			case 3: 
				$max = 3;
				break;
			case 2:
			case 4:
				$max = 2;
				break;
			default:
				$max = 4;
				break;
		}
		return $max;
	}

	public function get_team_by_position()
	{
		//return an array with keys as position and values as array of players in squad in that position.
		$squad = $this->get_squad_data();
		$returArray = array(array(),array(),array(),array(),array());

		foreach ($squad as $player) {		
				$type = $player['element_type'];
				array_push($returArray[$type], $squad);
		}
			return $returArray;
	}

}