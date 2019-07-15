<?php

namespace FPL\Bot;

use FPL\Bot\Bot as Bot;
use Util\MArray as MArray;
use FPL\Model\TeamData as TeamData;

/**
 * Tries to bring in player with max score from last week and removes player with lowest score in team at same appropriate price
 */
class GreedyMaxBot extends Bot 
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

	// find player with highest score and transfer in for lowest of appropriate value
	protected function make_transfers()
	{
		$game_data = $this->_FPL->get_game_data();
		$team_data = $this->_FPL->get_team_data();

		$ordered_players = $game_data->get_all_players_by_event_points_desc();
		// don't try to bring in injured players
		$ordered_players = MArray::filter_by($ordered_players, 'news', '==', ""); 
		// remove players already on the team
		foreach($team_data->get_squad_data() as $id => $player) 
		{
			$ordered_players = MArray::filter_by($ordered_players, 'id', '!=', $player['id']); 
		}

		
		// try to find a player with the lowest score at the same price
		foreach ($ordered_players as $id => $player) 
		{	
			// check the player has playered an average of 60 mins for the previous 3 games first
			if($game_data->calc_playing_time($player['id'], 3) > 159) 
			{
				$transfer_out_player = null;
				$ordered_team = $team_data->get_players_by_event_points_asc();

				// amount the player must be to make a valid transfer
				$required_funds = $player['now_cost'] - $team_data->get_bank();
				// filter only players of the same position
				$feasible_players = MArray::filter_by($ordered_team, 'element_type', '=', $player['element_type']);
				// filter only players that cost less than the bank amount
				$feasible_players = MArray::filter_by($feasible_players, 'selling_price', '>=', $required_funds);
				// filter only players with less score than player transferring in
				$feasible_players = MArray::filter_by($feasible_players, 'event_points', '<', $player['event_points']);

				// make sure valid transfer
				$is_available = false;
				while(!$is_available && count($feasible_players) > 0)
				{
					$transfer_out_player = array_shift($feasible_players);
					$is_available = $this->_FPL->is_transfer_valid($transfer_out_player['id'], $player['id']);
				}

				if($is_available)
				{
					// make the transfer
					$transfer = $this->_FPL->transfer([$transfer_out_player['id']], [$player['id']]);
					break;
				}
			}
		}
		
		if(isset($transfer) && $transfer)
		{
			echo "GreedyBot(max) transfer complete\n\n";

			$message = $player['web_name'] . " had the highest score, so I got rid of the loser " . $transfer_out_player['web_name'] . " for him. Big moves.";

			$this->generateMessage($message, "Greedy (Max) Bot");
		}
		else
		{
			echo "GreedyBot(max) transfer incomplete\n\n";
		}
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