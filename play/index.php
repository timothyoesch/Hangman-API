<?php
require_once(__DIR__ . "/../config/config.php");
header("Content-type:application/json");

function playerLost($userkey, $conn) {
    $sql = "SELECT * from `players` WHERE `player_UUID`= ?;";
    $stmt = $conn->prepare($sql);
    $stmt-> bind_param("s", $userkey);
    $stmt->execute();
    $playerstats = $stmt->get_result()->fetch_assoc();

    if (!$playerstats) {
        $sql = "INSERT into `players` (`player_UUID`, `player_wins`, `player_losses`) VALUES (?,?,?);";
        $stmt = $conn->prepare($sql);
        $losses = 1;
        $wins = 0;
        $stmt->bind_param("sss", $userkey, $wins, $losses);
        $stmt->execute();
    } else {
        $sql = "UPDATE `players` SET `player_losses`=? WHERE `player_UUID`=?;";
        $stmt = $conn->prepare($sql);
        $losses = $playerstats["player_losses"] + 1;
        $stmt->bind_param("ss", $losses, $userkey);
        $stmt->execute();
    }
}

function playerWon($userkey, $conn) {
    $sql = "SELECT * from `players` WHERE `player_UUID`= ?;";
    $stmt = $conn->prepare($sql);
    $stmt-> bind_param("s", $userkey);
    $stmt->execute();
    $playerstats = $stmt->get_result()->fetch_assoc();

    if (!$playerstats) {
        $sql = "INSERT into `players` (`player_UUID`, `player_wins`, `player_losses`) VALUES (?,?,?);";
        $stmt = $conn->prepare($sql);
        $losses = 0;
        $wins = 1;
        $stmt->bind_param("sss", $userkey, $wins, $losses);
        $stmt->execute();
    } else {
        $sql = "UPDATE `players` SET `player_wins`=? WHERE `player_UUID`=?;";
        $stmt = $conn->prepare($sql);
        $wins = $playerstats["player_wins"] + 1;
        $stmt->bind_param("ss", $wins, $userkey);
        $stmt->execute();
    }
}


if(!isset($_GET["id"])) {
    $return = array(
        "status" => 404,
        "msg" => "No game ID set!",
    );
    echo(json_encode($return));
    exit;
};

// Fetch Game Stats
$sql = "SELECT * from `games` WHERE `game_UUID` = ?;";
$stmt = $conn->prepare($sql);
$stmt-> bind_param("s", $_GET["id"]);
$stmt->execute();
$gamestats = $stmt->get_result()->fetch_assoc();
if ($gamestats["game_state"] == 0) {
    $gamestate = "Open";
} else if ($gamestats["game_state"] == 1) {
    $gamestate = "won";
} else {
    $gamestate = "lost";
}


// Return Game stats
if (!isset($_GET["type"])) {
    $return = array(
        "status" => 200,
        "msg" => "Information about the game fetched!",
        "clue" => $gamestats["game_clue"],
        "strikes" => $gamestats["game_strikes"],
        "guessed" => json_decode($gamestats["game_guessed"]),
        "gamestate" => $gamestate
    );
    echo(json_encode($return));
    exit;
}

if ($gamestats["game_state"] !== 0) {
    $return = array(
        "status" => 200,
        "msg" => "This game has already been {$gamestate}"
    );
    echo(json_encode($return));
    exit;
}

// Handle letter guess

if ($_GET["type"] == "letter") {
    $data = json_decode(file_get_contents('php://input'));
    $word = $gamestats["game_word"];
    if (!property_exists($data, "letter")) {
        $return = array(
            "status" => 400,
            "msg" => "You wanted to guess a letter but didn't provide a letter!",
        );
        echo(json_encode($return));
        exit;
    }
    if (!property_exists($data, "userkey")) {
        $return = array(
            "status" => 400,
            "msg" => "You did not provide a userkey. If you don't have one, please contact timothy@kpunkt.ch",
        );
        echo(json_encode($return));
        exit;
    }
    $letter = $data->letter;
    $userkey = $data->userkey;
    $clue = $gamestats["game_clue"];
    $uuid = $gamestats["game_UUID"];
    $guessed = json_decode($gamestats["game_guessed"], TRUE);
    array_push($guessed, $letter);
    $guessed_json = json_encode($guessed);
    $sql = "UPDATE `games` SET `game_guessed` = ? WHERE `game_UUID`= ?;";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $guessed_json, $uuid);
    $result = $stmt->execute();

    if (!strpos($word, $letter) && strpos($word, $letter) !== 0 ) {
        $strikes = $gamestats["game_strikes"] + 1;
        $uuid = $gamestats["game_UUID"];
        $sql = "UPDATE `games` SET `game_strikes` = ? WHERE `game_UUID`= ?;";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $strikes, $uuid);
        $result = $stmt->execute();
        if ($result == 1) {
            if ($strikes > 5) {
                $state = 2;
                $sql = "UPDATE `games` SET `game_state` = ? WHERE `game_UUID`= ?;";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $state, $uuid);
                $result = $stmt->execute();
                playerLost($userkey, $conn);
                $return = array(
                    "status" => "lost",
                    "msg" => "You lost the game!",
                    "clue" => $gamestats["game_clue"],
                    "strikes" => $strikes,
                    "guessed" => $guessed,
                    "gamestate" => "Lost"
                );
                echo(json_encode($return));
                exit;
            } else {
                $return = array(
                    "status" => "strike",
                    "msg" => "You recieved a strike!",
                    "clue" => $gamestats["game_clue"],
                    "strikes" => $strikes,
                    "guessed" => $guessed,
                    "gamestate" => $gamestate
                );
                echo(json_encode($return));
                exit;
            }
            
        }
    }

    $positions = [];
    $pos_last = 0;
    while( ($pos_last = strpos($word, $letter, $pos_last)) !== false ) {
        $positions[] = $pos_last;
        $pos_last = $pos_last + strlen($letter);
    }

    foreach ($positions as $position) {
        $clue[$position] = $letter;
    }

    $sql = "UPDATE `games` SET `game_clue` = ? WHERE `game_UUID`= ?;";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $clue, $uuid);
    $result = $stmt->execute();
    if ($result == 1) {
        $return = array(
            "status" => "correctletter",
            "msg" => "You guessed a correct letter!",
            "clue" => $clue,
            "strikes" => $gamestats["game_strikes"],
            "guessed" => $guessed,
            "gamestate" => $gamestate
        );
        echo(json_encode($return));
        exit;
    }
}


// Handle word guess

if ($_GET["type"] == "word") {
    $data = json_decode(file_get_contents('php://input'));
    $word = $gamestats["game_word"];
    if (!property_exists($data, "word")) {
        $return = array(
            "status" => 400,
            "msg" => "You wanted to guess a word but didn't provide a word!",
        );
        echo(json_encode($return));
        exit;
    }
    if (!property_exists($data, "userkey")) {
        $return = array(
            "status" => 400,
            "msg" => "You did not provide a userkey. If you don't have one, please contact timothy@kpunkt.ch",
        );
        echo(json_encode($return));
        exit;
    }
    $userkey = $data->userkey;
    $guess = $data->word;

    $uuid = $gamestats["game_UUID"];

    if ($word == $guess) {
        $state = 1;
        $sql = "UPDATE `games` SET `game_state` = ? WHERE `game_UUID`= ?;";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $state, $uuid);
        $result = $stmt->execute();
        playerWon($userkey, $conn);
        $return = array(
            "status" => "won",
            "msg" => "You won the game!",
            "clue" => $word,
            "strikes" => $gamestats["game_strikes"],
            "guessed" => json_decode($gamestats["game_guessed"]),
            "gamestate" => "Won"
        );
        echo(json_encode($return));
        exit;
    } else {
        $state = 2;
        $sql = "UPDATE `games` SET `game_state` = ? WHERE `game_UUID`= ?;";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $state, $uuid);
        $result = $stmt->execute();
        playerLost($userkey, $conn);
        $return = array(
            "status" => "lost",
            "msg" => "You lost the game!",
            "clue" => $gamestats["game_clue"],
            "strikes" => $gamestats["game_strikes"],
            "guessed" => json_decode($gamestats["game_guessed"]),
            "gamestate" => "Lost"
        );
        echo(json_encode($return));
        exit;
    }

}