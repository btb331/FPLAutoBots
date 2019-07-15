<?php

ini_set('memory_limit', '1024M');

include_once('inc/autoload.php');

$config = include('config/config.php');

$online = true;

use FPL\Factory\GameFactory as GameFactory;
use FPL\Factory\TeamFactory as TeamFactory;
use FPL\Controller\TeamController as TeamController;
use FPL\Util\Service as Service;

use FPL\FPL as FPL;

use FPL\Bot\RandomBot as RandomBot;
use FPL\Bot\GreedyMinBot as GreedyMinBot;
use FPL\Bot\GreedyMaxBot as GreedyMaxBot;
use FPL\Bot\WisdomOfCrowdBot as WisdomOfCrowdBot;
use FPL\Bot\WisdomOfCrowdBot2 as WisdomOfCrowdBot2;

$game_data = GameFactory::create_game($online);  // FPL\Model\GameData

$login_details = $config['greedymax'];
//$login_details = $config['random'];
//$login_details = $config['ben'];

$service = $online ? new Service($login_details['username'], $login_details['password'], $login_details['csrf']) : null; 

$team_data = TeamFactory::create_team($service, $game_data->get_players_data()); // FPL\Model\TeamData

$team_controller = new TeamController($team_data, $game_data);

$fpl = new FPL($service, $team_data, $game_data, $team_controller);

$greedymin_bot = new GreedyMaxBot($fpl);
//$random_bot = new RandomBot($fpl);
//$wiseBot = new WisdomOfCrowdBot($fpl);
//$wiseBot2 = new WisdomOfCrowdBot2($fpl);

// // if(first week we running bot) // $game_data->get_next_event_week() == 22 and $game_data->get_next_event_week_deadline() 
// $random_bot->initialise();
// $wiseBot2->initialise();
// $wiseBot->initialise();
// $jack_bot->initialise();
$greedymin_bot->initialise();


// $random_bot->run_bot();
// $greedymin_bot->run_bot();
// $wiseBot->run_bot();



 