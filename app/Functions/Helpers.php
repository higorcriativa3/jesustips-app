<?php
namespace App\Functions;

use DateTime;

class Helpers {
  public static function dividePlayerAndTeam($string) {
    $team = explode("(", $string);
    $name = explode(")", $team[1]);

    $obj = [
        "name" => trim($name[0]),
        "team" => trim($team[0]),
    ];

    return $obj;
  }

  public static function convertEpochToDateTime($epoch) {
    $dt = new DateTime("@$epoch");  // convert UNIX timestamp to PHP DateTime
    return $dt->format('Y-m-d H:i:s'); // output = 2017-01-01 00:00:00
  }

  public static function headToHead($playerVsPlayer1, $playerVsPlayer2) {
    $playerVsPlayer = array_merge($playerVsPlayer1, $playerVsPlayer2);

    $overs = [
        "1.5" => 0,
        "2.5" => 0,
        "3.5" => 0,
        "4.5" => 0,
        "5.5" => 0,
        "6.5" => 0,
        "7.5" => 0,
        "8.5" => 0,
        "9.5" => 0,
        "10.5" => 0,
    ];

    $oversPercentage = array();

    foreach($playerVsPlayer as $event) {
        if($event["score"] && $event["score"] != "") {
            $score = explode('-', $event["score"]);
            $sumScore = $score[0] + $score[1];
        } else {
            $sumScore = 0;
        }

        for($handcapControl = 1.5; $handcapControl <= 10.5; $handcapControl++){

            if($sumScore > $handcapControl){
                $overs["{$handcapControl}"]++;
            }
        }
    }

    // Convert overs to percentage
    foreach($overs as $key => $over) {
        if($over != 0 && $playerVsPlayer != 0){
            $oversPercentage[$key] = round(($over / count($playerVsPlayer)) * 100, 2);
        }
    }

    return [
      'percentage' => $oversPercentage,
      'playerVsPlayer' => $playerVsPlayer 
    ];
  }
}

