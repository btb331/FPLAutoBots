<?php 
ini_set('memory_limit', '1024M');

include_once('inc/autoload.php');

$config = include('config/config.php');

use FPL\Factory\GameFactory as GameFactory;
use FPL\Factory\TeamFactory as TeamFactory;
use FPL\Controller\TeamController as TeamController;
use FPL\Util\Service as Service;
use Util\File as File;

use FPL\FPL as FPL;

use FPL\Bot\RandomBot as RandomBot;
use FPL\Bot\GreedyMinBot as GreedyMinBot;
use FPL\Bot\GreedyMaxBot as GreedyMaxBot;
use FPL\Bot\WisdomOfCrowdBot as WisdomOfCrowdBot;
use FPL\Bot\WisdomOfCrowdBot2 as WisdomOfCrowdBot2;

// online mode
$online = true;

// overall game data
$game_data = GameFactory::create_game($online);  // FPL\Model\GameData

// creating service if online or leave as null if not
$botData = array();
foreach ($config as $bot => $loginData) {
	$service = $online ? new Service($loginData['username'], $loginData['password'], $loginData['csrf']) : null; 
	$team = TeamFactory::create_team($service, $game_data->get_players_data());
	$fpl = new FPL($service, $team, $game_data);
	$botData[$bot] = array("service"=>$service, "teamData"=>$team, "fpl"=>$fpl);
}

$bots = array();
$bots['random'] = new RandomBot($botData['random']['fpl']);
$bots['wise1'] = new WisdomOfCrowdBot($botData['wise1']['fpl']);
$bots['wise2'] = new WisdomOfCrowdBot2($botData['wise2']['fpl']);
$bots['greedymin'] = new GreedyMinBot($botData['greedymin']['fpl']);
$bots['greedymax'] = new GreedyMaxBot($botData['greedymax']['fpl']);

$bots['random']->getPoints('Random Bot');
$bots['wise1']->getPoints('Wisdom Of Crowd Bot 1');
$bots['wise2']->getPoints('Wisdom Of Crowd Bot 2');
$bots['greedymin']->getPoints('Greedy (Min) Bot');
$bots['greedymax']->getPoints('Greedy (Max) Bot');


$deadline =  strtotime($game_data->get_next_event_week_deadline());
$today = strtotime("now");

$currentWeek = $game_data->get_next_event_week() - 1;
File::write_to("/actions/gameweek.txt", $currentWeek);


if($deadline-$today < 86400) // check if the next deadline is in the next 24 hours
{
	echo "run bots...\n";
	foreach ($bots as $botName => $bot) {
		$bot->run_bot();
		// $bot->initialise();
	}
}else{
	echo "Deadline is not tommorow";
}

?>