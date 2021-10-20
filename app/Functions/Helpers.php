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
}

