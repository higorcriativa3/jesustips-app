<?php

namespace App\Functions;

use App\Models\Match;
use Illuminate\Support\Facades\Http;

class Stats {

  public static function attacks($matchId) {
    $response = Http::get("https://api.b365api.com/v1/event/view?token=91390-4sDwuMJTtIhuPJ&event_id={$matchId}")
    ->json();

    $stats = [
      "attacks" => [
        "home" => isset($response["results"][0]["stats"]["attacks"][0]) ? 
                  $response["results"][0]["stats"]["attacks"][0] : "0",
                  
        "away" => isset($response["results"][0]["stats"]["attacks"][1]) ? 
                  $response["results"][0]["stats"]["attacks"][1] : "0"
      ],
      "dangerousattacks" => [
        "home" => isset($response["results"][0]["stats"]["dangerous_attacks"][0]) ? 
                  $response["results"][0]["stats"]["dangerous_attacks"][0] : "0",

        "away" => isset($response["results"][0]["stats"]["dangerous_attacks"][1]) ? 
                  $response["results"][0]["stats"]["dangerous_attacks"][1] : "0",
      ]
    ];

    return $stats;
  }

  public static function statistics($home, $away) {
    // $url = 'https://api.b365api.com/v1/team?token='. env('API_TOKEN') . '&sport_id=1';
    // $result = Http::get($url);

    $statistics = Match::where([
      'home_player' => 'Quavo',
      'away_player' => 'Walker'
      ])
      ->get()
      ->toArray();

    // dd($statistics);

    $player1 = 'Quavo';
    $player2 = 'Walker';

    $player1Goals = 0;
    $player2Goals = 0;

    $player1Wins = 0;
    $player2Wins = 0;

    $countMatches = 0;

    $overGoals1dot5 = 0;
    $underGoals1dot5 = 0;

    $moreThenOne = 0;
    $lessThenOne = 0;

    $draws = 0;

    // $result = file_get_contents(base_path('storage/app/endeds.json'));

    // // return $result;

    // $rawMatches = json_decode($result, true);

    $matches = array();

    // Find player in history object
    // foreach($statistics as $team) {
    //     if(
    //         str_contains($team['home']['name'], $player1) != true && 
    //         str_contains($team['away']['name'], $player2) != true ||
    //         str_contains($team['home']['name'], $player2) != true &&
    //         str_contains($team['away']['name'], $player1) != true
    //     )
    //     {
    //         $matches[] = $team;
    //     }
    // }

    // dd($matches);

    foreach($statistics as $event) {
        $countMatches++;
        // explode home to get player
        /** Without Explode
         * 
         * $firstExplodeHome = explode("(", $event["home"]["name"]);
         * $secondExplodeHome = explode(")", $firstExplodeHome[1]);
         * $home = $secondExplodeHome[0];
         */

        $home = $event["home_player"];
        

        // explode away to get player
        /** Without explode
         * $firstExplodeAway = explode("(", $event["away"]["name"]);
         * $secondExplodeAway = explode(")", $firstExplodeAway[1]);
         * $away = $secondExplodeAway[0];
         */
        
        $away = $event["away_player"];

        // dd($event["away"]["name"]);
        // Get team goals
        $scoreExploded = explode('-', $event["score"]);
        $homeGoals = intval($scoreExploded[0]);
        $awayGoals = intval($scoreExploded[1]);

        $totalGoals = $homeGoals + $awayGoals;

        if($totalGoals > 1.5) {
            $overGoals1dot5++;
        }
        if($totalGoals < 1.5) {
            $underGoals1dot5++;
        }


        // Count players goals and check who wins
        if($home == $player1) {
            $player1Goals += $homeGoals;
            $player2Goals += $awayGoals;

            // Increment to winner
            if($homeGoals > $awayGoals) {
                $player1Wins++;
            } elseif($homeGoals == $awayGoals) {
                $draws++;
            } else {
                $player2Wins++;
            }

        } else {
            $player2Goals += $homeGoals;
            $player1Goals += $awayGoals;

            // Increment to winner
            if($homeGoals > $awayGoals) {
                $player2Wins++;
            } elseif($homeGoals == $awayGoals) {
                $draws++;
            }
            else {
                $player1Wins++;
            }
        }

        // Both goal
        if($homeGoals > 1 || $awayGoals > 1) {
            $moreThenOne++;
        }

        // Both don't make goal
        if($homeGoals < 1 || $awayGoals < 1) {
            $lessThenOne++;
        }
    }

    /** Return players matchs */
    // return $matches;

    /** Return Players goals */
    // return [
    //     "player1goals" => $player1Goals, 
    //     "player2goals" => $player2Goals,
    //     "player1wins" => $player1Wins,
    //     "player2wins" => $player2Wins,
        
    // ];

    $objToView = [
        'player1' => $player1,
        'player2' => $player2,
        'player1Goals' => $player1Goals,
        'player2Goals' => $player2Goals,
        'player1Wins' => $player1Wins,
        'player2Wins' => $player2Wins,
        'draws' => $draws,
        'matches' => $countMatches,
        'overGoals1dot5' => $overGoals1dot5,
        'underGoals1dot5' => $underGoals1dot5,
        'moreThenOne' => $moreThenOne,
        'lessThenOne' => $lessThenOne
    ];

    return $objToView;
  }
}