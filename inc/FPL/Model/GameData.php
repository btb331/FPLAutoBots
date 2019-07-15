<?php
/**
 *  Contains the main game data
 */

namespace FPL\Model;

use Util\MArray as MArray;

class GameData
{
	private $_game_data;
	private $_all_players_data;
	

	function __construct($game_data, $all_players_data)
	{
		$this->_game_data = $game_data;
		$this->_all_players_data = $all_players_data;
	}


	// Getters
	
	public function get_players_data()
	{
		return $this->_all_players_data;
	}

	public function get_game_data()
	{
		return $this->_game_data;
	}

	public function get_next_event_week()
	{
		return $this->_game_data['next-event'];
	}


	public function get_next_event_week_deadline()
	{
		return $this->_game_data['events'][$this->get_next_event_week() - 1]['deadline_time'];
	}

	public function get_sorted_all_non_flagged_players()
	{
		// returns all player sorted in their position and only those who can play next game
		$data_sorted = array(array(), array(), array(), array(), array());

		foreach ($this->_all_players_data as $player => $player_data) 
		{
			if($player_data['chance_of_playing_next_round'] == null || $player_data['chance_of_playing_next_round'] == 100)
			{
				$player_type = $player_data['element_type'];
				array_push($data_sorted[$player_type], $player);
			}
		}
		return $data_sorted;
	}


	public function get_all_players_by_price_desc()
	{
		return MArray::order_by($this->_all_players_data, 'now_cost', false);
	}

	public function get_all_players_by_price_asc()
	{
		return MArray::order_by($this->_all_players_data, 'now_cost', true);
	}

	public function get_all_players_by_event_points_desc()
	{
		return MArray::order_by($this->_all_players_data, 'event_points', false);
	}

	public function get_all_players_by_event_points_asc()
	{
		return MArray::order_by($this->_all_players_data, 'event_points', true);
	}


	public function get_all_keepers()
	{
		return MArray::filter_by($this->_all_players_data, 'element_type', '=', 1);
	}

	public function get_all_defenders()
	{
		return MArray::filter_by($this->_all_players_data, 'element_type', '=', 2);
	}

	public function get_all_midfielders()
	{
		return MArray::filter_by($this->_all_players_data, 'element_type', '=', 3);
	}

	public function get_all_strikers()
	{
		return MArray::filter_by($this->_all_players_data, 'element_type', '=', 4);
	}


	// return the total playing time for the player with $player_id for the previous number of $gameweeks
	public function calc_playing_time($player_id, $gameweeks)
	{
		$history = array_reverse($this->_all_players_data[$player_id]['history']);

		$minutes_played = 0;
		$i = 0;
		foreach ($history as $id => $info) 
		{	
			if($i >= $gameweeks) break;
			$minutes_played += $info['minutes'];
			$i++;
		}
		return $minutes_played;
	}

}
