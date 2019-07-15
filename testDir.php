<?php
echo __DIR__;
echo "\n";
echo dirname($_SERVER['SCRIPT_NAME']);

$txt_data = fopen(__DIR__ . "/actions/gameweek.txt", "r") or die("Unable to open file!");
$data = fread($txt_data, filesize(__DIR__ . "/actions/gameweek.txt"));
fclose($txt_data);
print_r($data);
echo $data;
?>