<?php
require_once(__DIR__ . "/../config/config.php");
header("Content-type:application/json");

$data = json_decode(file_get_contents('php://input'));
if (!property_exists($data, "userkey")) {
    $return = array(
        "status" => 400,
        "msg" => "You did not provide a userkey. If you don't have one, please contact timothy@kpunkt.ch",
    );
    echo(json_encode($return));
    exit;
}
$userkey = $data->userkey;


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

$sql = "INSERT into `games` (`game_UUID`, `game_word`, `game_clue`, `game_state`, `game_strikes`, `game_guessed`, `game_user`) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE `game_UUID` = `game_UUID`;";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssss", $uuid, $randomword, $clue, $state, $strikes,$guessed,$userkey);
$result = $stmt->execute();

if($result == 1) {
    $return = array(
        "status" => 201,
        "msg" => "{$uuid} successfully created!",
        "game_ID" => $uuid,
        "url" => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/play/?id={$uuid}",
        "clue" => $clue
    );
} else {
    $return = array(
        "status" => 500,
        "msg" => "{$uuid} could not be created!",
        "game_ID" => $uuid,
        "url" => "",
        "clue" => ""
    );
}

echo(json_encode($return));

?>