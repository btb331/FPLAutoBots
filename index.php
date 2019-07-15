<?php
$messages = array();
$points = array();
$files = scandir('actions');
foreach($files as $file) {
	if(strpos($file, "txt")!== false){
		$file = str_replace("..", "", $file);
		$name = str_replace(".txt", "", $file);
		$message = file_get_contents("actions/". $file);
        if($name == "gameweek"){
            $gameweek = $message;
            continue;
        }
		$message = json_decode($message, true);
		$messages[$name] = $message;
	}
}

$files = scandir('actions/points/');
foreach($files as $file) {
    if(strpos($file, "txt")!== false){
        $file = str_replace("..", "", $file);
        $name = str_replace(".txt", "", $file);
        $message = file_get_contents("actions/points/". $file);
        $message = json_decode($message, true);
        $points[$name] = $message;
    }
}




function getURL($id, $gameWeek){
return "https://fantasy.premierleague.com/a/team/".$id. "/" . "event/" . $gameWeek;
}

?>

<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style type="text/css">
#FPL {
    font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
    border-collapse: collapse;
    width: 90%;
    margin: auto;
    text-align: center;
}

#FPL td, #FPL th {
    border: 1px solid #ddd;
    padding: 8px;
    max-width: 300px;
    word-wrap: break-word;
}

#FPL th {
    padding-top: 12px;
    padding-bottom: 12px;
    text-align: left;
    background-color: #38063c;
    color: white;
    text-align: center;
}

#FPL #title{
	text-align: left;
	background: #00fc87;
	color: #38063c;
	border: none;
}

#logo{
	height:38%;
}
a {
    color: black;
    text-decoration: none;
}

</style>
</head>
<body>
<table id="FPL">
<tr id="title"><td style="border:none;"><img id="logo" src="actions/pl_icon.svg"/></td><td style="border:none;font-size:30px;" colspan="3"><div style="left:15%; position: relative;"> FPL Autobots</div> </td></tr>
<tr><th>Bot</th><th>Transfer</th><th>Game Week</th><th>Total</th></tr>
<?php 


foreach ($messages as $name => $message) {
    if($name=="gameWeek"){
        continue;
    }
	print '<tr><td><a href="'. getURL($message['id'], $gameweek) . '">' . $name . "</a></td><td>" . $message['message'] . "</td><td>" . $points[$name]['gwPoints'] . "</td><td>" . $points[$name]['points'] . "</td><tr>";
}
?>
</table>
</body>
</html>