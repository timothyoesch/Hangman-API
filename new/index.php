<?php
require_once(__DIR__ . "/../config/config.php");

$words = file(__DIR__ . "/../config/words.txt");

$words = array_map('trim', array_filter(
    $words,
    function ($value) {
        return strlen($value) >= 10;
    }
));

$uuid = uniqid("game_");
$randomword = $words[array_rand($words)];
$clue = str_repeat("*", strlen($randomword));
$state = 0;
$strikes = 0;
$guessed = json_encode(array());

$sql = "INSERT into `games` (`game_UUID`, `game_word`, `game_clue`, `game_state`, `game_strikes`, `game_guessed`) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE `game_UUID` = `game_UUID`;";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $uuid, $randomword, $clue, $state, $strikes,$guessed);
$result = $stmt->execute();

if($result == 1) {
    $return = array(
        "status" => 201,
        "msg" => "{$uuid} successfully created!",
        "game ID" => $uuid,
        "url" => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/play/?id={$uuid}",
        "clue" => $clue
    );
} else {
    $return = array(
        "status" => 500,
        "msg" => "{$uuid} could not be created!",
        "game ID" => $uuid,
        "url" => "",
        "clue" => ""
    );
}

header("Content-type:application/json");
echo(json_encode($return));

?>