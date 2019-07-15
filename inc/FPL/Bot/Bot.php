<?php

namespace FPL\Bot;

use Util\File as File;

abstract class Bot 
{
	protected $_FPL;

	abstract public function initialise();
	abstract protected function make_transfers();
	abstract protected function organise_team();
	abstract protected function set_captains();

	public function __construct($fpl) 
	{
		$this->_FPL = $fpl;
	}

	// 
	public function run_bot() 
	{
		$this->make_transfers();
		$this->organise_team();
		$this->set_captains();
	}

	public function generateMessage($message, $name)
	{
		$team_data = $this->_FPL->get_team_data();
		$id = $team_data->get_team_id();
		$json = '{"message":"' . $message .  '","id":"' . $id . '"}';
		$name = "actions/" . $name . ".txt";
		//echo $json;
		File::write_to($name, $json);
	}

	public function getPoints($name){
		$team_data = $this->_FPL->get_team_data();
		$points = $team_data->get_team_data()['entry']['summary_overall_points'];
		$gwPoints = $team_data->get_team_data()['entry']['summary_event_points'];
		$id = $team_data->get_team_id();
		$json = '{"points":"' . $points . '","gwPoints":"' . $gwPoints . '","id":"' . $id . '"}';
		$name = "actions/points/" . $name . ".txt";

		//echo $json;
		File::write_to($name, $json);
	}

}