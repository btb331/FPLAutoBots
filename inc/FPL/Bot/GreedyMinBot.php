<?php

namespace FPL\Bot;

use FPL\Bot\Bot as Bot;
use Util\MArray as MArray;
use FPL\Model\TeamData as TeamData;

/**
 * Remove player with lowest score in last gameweek (if tie, remove player with highest cost). Add (legal) player with highest score in last gameweek.
 */
class GreedyMinBot extends Bot 
{
	// Manually initialise team
	public function initialise()
	{
		echo "Initialising...\n";
		// for all players run the transfer script
		for($i = 0; $i < 15; $i++)
		{
			$this->make_transfers();
		}

		$this->organise_team();
		$this->set_captains();
		echo "Initialising complete.\n\n";
	}

	// find player with lowest score and highest cost, transfer for a player with highest score from last week at the same price
	protected function make_transfers()
	{
		$game_data = $this->_FPL->get_game_data();
		$team_data = $this->_FPL->get_team_data();

		$ordered_players = $team_data->get_players_by_event_points_asc();

		$transfer = false;
		while(!$transfer)
		{
			if(count($ordered_players) < 1) break;

			$transfer_out_player = $this->get_next_most_expensive($ordered_players);
			// get max amount to spend on a player
			$bank = $team_data->get_bank() + $transfer_out_player['selling_price'];
			// highest scoring players first
			$feasible_players = $game_data->get_all_players_by_event_points_desc();
			// filter only players of the same position
			$feasible_players = MArray::filter_by($feasible_players, 'element_type', '=', $transfer_out_player['element_type']);
			// filter only players that cost less than the bank amount
			$feasible_players = MArray::filter_by($feasible_players, 'now_cost', '<=', $bank);
			// filter by remove flagged players
			$feasible_players = MArray::filter_by($feasible_players, 'news', '==', "");

			// make sure the player does not belong in the team
			$is_available = false;
			while(!$is_available && count($feasible_players) > 0)
			{
				$transfer_in_player = array_shift($feasible_players);
				if($game_data->calc_playing_time($transfer_in_player['id'], 3) > 159) 
				{
					$is_available = $this->_FPL->is_transfer_valid($transfer_out_player['id'], $transfer_in_player['id']);
				}
			}

			if($is_available)
			{
				// make the transfer
				$transfer = $this->_FPL->transfer([$transfer_out_player['id']], [$transfer_in_player['id']]);
			}
		}		
		
		if($transfer)
		{
			echo "GreedyBot(min) transfer complete\n\n";

			$message = $transfer_out_player['web_name'] . " had the lowest score, so I swapped him for " . $transfer_in_player['web_name'] . ". Great stuff.";

			$this->generateMessage($message, "Greedy (Min) Bot");
		}
		else
		{
			echo "GreedyBot(min) transfer incomplete\n\n";
		}
	}

	private function get_next_most_expensive(&$players)
	{
		if(count($players) == 0) return false;
		$rtn = null;
		$index = 0;
		// get the player with the lowest score and highest price to remove
		foreach($players as $id => $player)
		{
			if($rtn == null || 
			  ($rtn['event_points'] == $player['event_points'] && $player['selling_price'] > $rtn['selling_price'])
			)
			{
				$rtn = $player;
				$index = $id;
			}
		}
		unset($players[$index]);
		return $rtn;
	}

	// Put players with the highest scores from last week on
	protected function organise_team()
	{
		$team_data = $this->_FPL->get_team_data();

		$subs = array();

		$ordered_players = $team_data->get_players_by_event_points_asc();
		
		// flagged players should be first on the bench
		$flagged = $team_data->get_flagged_players();

		foreach($flagged as $id => $player)
		{
			// if there is room for that many subs left, add the flagged player
			if(MArray::count_by($subs, 'element_type', $player['element_type']) < TeamData::max_subs($player['element_type']))
				$subs[$id] = $player;
		}

		// ensure 1 keeper is in the subs array first
		$sub_keeper_count = MArray::count_by($subs, 'element_type', 1);
		// if no keeper in the subs, pick the worst scoring
		if($sub_keeper_count < 1)
		{
			$team_keepers = $team_data->get_keepers();
			$worst_keeper = reset($team_keepers); // first element
			foreach ($team_keepers as $id => $keeper) 
			{
				if($keeper['event_points'] < $worst_keeper['event_points'])
					$worst_keeper = $keeper;
			}
			$subs[$worst_keeper['id']] = $worst_keeper;
		}
		elseif($sub_keeper_count > 1) // if it has both keepers in there, remove the one with most chance of playing
		{
			$team_keepers = $team_data->get_keepers();
			$best_keeper = reset($team_keepers);
			foreach ($team_keepers as $id => $keeper) 
			{
				if($keeper['chance_of_playing_next_round'] > $best_keeper['chance_of_playing_next_round'])
					$best_keeper = $keeper;
			}
			unset($subs[$best_keeper['id']]);
		}	

		// now fill the remaining places from lowest scoring players
		foreach ($ordered_players as $id => $player) 
		{
			if(count($subs) >= TeamData::max_subs()) break;

			// if player isn't already in subs and max isn't hit for that element_type
			if(!array_key_exists($id, array_keys($subs)) && 
				MArray::count_by($subs, 'element_type', $player['element_type']) < TeamData::max_subs($player['element_type']))
				$subs[$id] = $player;
		}

		$sub_keys = array_keys($subs);
		// check which values are in squad by not in subs
		$team_keys = array_values(array_diff($team_data->get_squad_ids(), $sub_keys));

		$this->_FPL->create_picks($team_keys, $sub_keys);
		echo "Team selected\n\n";
	}

	// captains are the two highest scoring players from last week
	protected function set_captains()
	{
		$team_data = $this->_FPL->get_team_data();

		$players = $team_data->get_players_by_event_points_desc();
		$players = MArray::filter_by($players, 'is_sub', '=', 0); // only playing players
		$ordered_players = MArray::order_by($players, 'event_points', false);

		$i = 0;
		foreach($ordered_players as $id => $player)
		{
			if($i == 0)
				$this->_FPL->set_captain($id);
			elseif($i == 1)
				$this->_FPL->set_vice_captain($id);
			else 
				break;
			$i++;
		}

		echo "Captains set \n\n";
	}
}