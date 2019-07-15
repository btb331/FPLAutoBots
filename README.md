###Writing a bot:
- A bot should extend the FPL\Bot\Bot class and takes an FPL class as the only argument
- Must write the following methods in your bot class
1. initialise();      - runs only at the start of the season to initialise the team, could also just initialise manually.
2. make_transfers();  - runs once before new gameweek; runs first. Makes the transfers for this week
3. organise_team();   - runs once before new gameweek; runs second. Choose the positions of the players
4. set_captains();    - runs once before new gameweek; runs last. Set the captain and vice captain

In the bot you can access game data and team data via:
~~~
$game_data = $this->_FPL->get_game_data();
$team_data = $this->_FPL->get_team_data();
~~~
See inc\FPL\Model\GameData or inc\FPL\Model\TeamData for the methods available.

FPL has all the functions to make changes to the team:
set_vice_captain($player_id)
set_captain($player_id)
create_picks($players, $subs) - $players is an array of 11 players IDs to be played. $subs is an array of 4 subs
transfer($players_out, $players_in) 

is_transfer_valid($player_out, $player_in) - convienient method to check that the transfer is valid. i.e. enough money, correct positions, not too many from the same team, not already in squad