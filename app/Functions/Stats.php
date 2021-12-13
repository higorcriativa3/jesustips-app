<?php

namespace App\Functions;

use App\Models\Match;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Stats {

  public static function attacks($matchId) {
    $response = Http::get("https://api.b365api.com/v1/event/view?token=91390-4sDwuMJTtIhuPJ&event_id={$matchId}")
    ->json();

    $stats = [
        "timer" => isset($response["results"][0]["timer"]["tm"]) ? 
                    $response["results"][0]["timer"]["tm"] : "0",
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

	  // dd($home);
	  //
	  //

	  $home = Str::lower($home);
	  $away = Str::lower($away);

    $statistics1 = Match::where('home_player', 'ILIKE', "%{$home}%")
        ->where('away_player', 'ILIKE', "%{$away}%")
        ->get()
        ->toArray();

    $statistics2 = Match::where('home_player', 'ILIKE', "%{$away}%")
    ->where('away_player', 'ILIKE', "%{$home}%")
    ->get()
    ->toArray();

    $statistics = array_merge($statistics1, $statistics2);

    // dd($statistics);

    $player1 = $home;
    $player2 = $away;

    $player1Goals = 0;
    $player2Goals = 0;

    $player1Wins = 0;
    $player2Wins = 0;

    $countMatches = 0;

    $overGoals1dot5 = 0;
    $underGoals1dot5 = 0;

    $player1WinsLastTen = 0;
    $player1WinsLastTenPerc = 0;
    $player2WinsLastTen = 0;
    $player2WinsLastTenPerc = 0;
    $drawLastTen = 0;
    $drawLastTenPerc = 0;

    $bothToScore = 0;
    $bothToScoreLastTen = 0;
    $lessThenOne = 0;

    $draws = 0;

    $matches = array();

    foreach($statistics as $key =>$event) {
        $countMatches++;
        // explode home to get player
        /** Without Explode
         * 
         * $firstExplodeHome = explode("(", $event["home"]["name"]);
         * $secondExplodeHome = explode(")", $firstExplodeHome[1]);
         * $home = $secondExplodeHome[0];
         */

        $home = Str::lower($event["home_player"]);
        

        // explode away to get player
        /** Without explode
         * $firstExplodeAway = explode("(", $event["away"]["name"]);
         * $secondExplodeAway = explode(")", $firstExplodeAway[1]);
         * $away = $secondExplodeAway[0];
         */
        
        $away = Str::lower($event["away_player"]);

        // dd($event["away"]["name"]);
        // Get team goals
        if(isset($event["score"]) && $event["score"] != "") {
            $scoreExploded = explode('-', $event["score"]);


            $homeGoals = intval($scoreExploded[0]);
            $awayGoals = intval($scoreExploded[1]);

            $totalGoals = $homeGoals + $awayGoals;
        } else {
            $scoreExploded = 0;


            $homeGoals = 0;
            $awayGoals = 0;

            $totalGoals = 0;
        }
        

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
        if($homeGoals > 1 && $awayGoals > 1) {
            $bothToScore++;
        }

        // Partial last ten
        if($key == 9 && $countMatches != 0) {
            $bothToScoreLastTen = $bothToScore;
            if($player1Wins != 0) {
                $player1WinsLastTenPerc = round(($player1Wins / $countMatches) * 100, 2);
                $player1WinsLastTen = $player1Wins;
            }

            if($draws != 0) {
                $drawLastTenPerc = round(($draws / $countMatches) * 100, 2);
                $drawLastTen = $draws;
            }

            if($player2Wins != 0) {
                $player2WinsLastTenPerc = round(($player2Wins / $countMatches) * 100, 2);
                $player2WinsLastTen = $player2Wins;
            }
        }

        // Both don't make goal
        if($homeGoals < 1 || $awayGoals < 1) {
            $lessThenOne++;
        }
    }
    
    $player1WinsPerc = 0;
    $player2WinsPerc = 0;
    $drawsPerc = 0;

    if($player1Wins != 0 && $countMatches != 0) {
        $player1WinsPerc = round(($player1Wins / $countMatches) * 100, 2);
    }
    if($player2Wins != 0 && $countMatches != 0) {
        $player2WinsPerc = round(($player2Wins / $countMatches) * 100, 2);
    }
    if($draws != 0 && $countMatches != 0) {
        $drawsPerc = round(($draws / $countMatches) * 100, 2);
    }

    $objToView = [
        'player1' => $player1,
        'player2' => $player2,
        'player1Goals' => $player1Goals,
        'player2Goals' => $player2Goals,
        'player1Wins' => $player1Wins,
        'player1WinsPerc' => $player1WinsPerc,
        'player1WinsLastTen' => $player1WinsLastTen,
        'player1WinsLastTenPerc' => $player1WinsLastTenPerc,
        'player2Wins' => $player2Wins,
        'player2WinsPerc' => $player2WinsPerc,
        'player2WinsLastTen' => $player2WinsLastTen,
        'player2WinsLastTenPerc' => $player2WinsLastTenPerc,
        'draws' => $draws,
        'drawsPerc' => $drawsPerc,
        'drawLastTen' => $drawLastTen,
        'drawLastTenPerc' => $drawLastTenPerc,
        'matches' => $countMatches,
        'overGoals1dot5' => $overGoals1dot5,
        'underGoals1dot5' => $underGoals1dot5,
        'bothToScore' => $bothToScore,
        'bothToScoreLastTen' => $bothToScoreLastTen,
        'lessThenOne' => $lessThenOne
    ];

    return $objToView;
  }

  public static function overAndUnderMatchGoals($home, $away, $odd) {
    $results = Match::where('home_player', 'ILIKE', "%{$home}%")
        ->where('away_player', 'ILIKE', "%{$away}%")
        ->orderBy('match_date', 'DESC')
        ->get()
        ->toArray();

    $overCount = 0;
    $underCount = 0;
    $eventCount = 0;

    $parcialOver  = 0;
    $parcialUnder = 0;
    $parcialEvent = 0;

    $lastTenOver = 0;
    $lastTenUnder = 0;

    foreach($results as $key => $event){
        if($event['score'] && $event['score'] != "" ){
            $score = explode("-", $event['score']);
            $sumScore = $score[0] + $score[1];
        } else {

            $sumScore = 0;
        }
        

        if($sumScore > $odd) {
            $overCount++;
        } else {
            $underCount++;
        }

        $eventCount++;

        if($key == 9 && $eventCount != 0) {
            if($overCount != 0) {
                $lastTenOver = round(($overCount / $eventCount) * 100, 2);
            }

            if($underCount != 0) {
                $lastTenUnder = round(($underCount / $eventCount) * 100, 2);
            }
            
        }
    }

    $overAll = 0;
    $underAll = 0;

    if($overCount != 0 && $eventCount != 0) {
        $overAll = round(($overCount / $eventCount) * 100, 2);
    }

    if($underCount != 0 && $eventCount != 0) {
        $underAll = round(($underCount / $eventCount) * 100, 2);
    }
    

    $return = [
        "over" => [
            "lastten" => $lastTenOver,
            "all" => $overAll
        ],
        "under" => [
            "lastten" => $lastTenUnder,
            "all" => $underAll
        ],
    ];

    return $return;
  }

  public static function overAndUnderHomeAway($home, $away, $odd) {
    $results = Match::where('home_player', 'ILIKE', "%{$home['name']}%")
        ->where('away_player', 'ILIKE', "%{$away['name']}%")
        ->orderBy('match_date', 'DESC')
        ->get()
        ->toArray();

    // Initiate over arrays
    $homeOverCount = 0;
    $homeOverLastTen = 0;

    $awayOverCount = 0;
    $awayOverLastTen = 0;

    // Initiate under arrays
    $homeUnderCount = 0;
    $homeUnderLastTen = 0;

    $awayUnderCount = 0;
    $awayUnderLastTen = 0;

    $eventCount = 0;

    foreach($results as $key => $event){
        if($event['score'] && $event['score'] != "") {
            $score = explode("-", $event['score']);
            $sumScore = $score[0] + $score[1];
        } else {
            $score = [0,0];
            $sumScore = 0;
        }
        

        if($score[0] > $odd) {
            $homeOverCount++;
        } else {
            $homeUnderCount++;
        }

        if($score[1] > $odd) {
            $awayOverCount++;
        } else {
            $awayUnderCount++;
        }

        $eventCount++;

        if($key == 9 && $eventCount != 0) {
            // Home over
            if($homeOverCount != 0 && $homeUnderCount != 0) {
                $homeOverLastTen = round(($homeOverCount / $eventCount) * 100, 2);
            }

            // Home under
            if($homeUnderCount != 0 && $homeUnderCount != 0) {
                $homeUnderLastTen = round(($homeUnderCount / $eventCount) * 100, 2);
            }

            // Away over
            if($awayOverCount != 0 && $awayUnderCount != 0) {
                $awayOverLastTen = round(($awayOverCount / $eventCount) * 100, 2);
            }

            // Away under
            if($awayUnderCount != 0 && $awayUnderCount != 0) {
                $awayUnderLastTen = round(($awayUnderCount / $eventCount) * 100, 2);
            }
        }
    }

    $homeOverAll = 0;
    $homeUnderAll = 0;

    $awayOverAll = 0;
    $awayUnderAll = 0;

    // Home over
    if($homeOverCount != 0 && $homeUnderCount != 0) {
        $homeOverAll = round(($homeOverCount / $eventCount) * 100, 2);
    }

    // Home under
    if($homeUnderCount != 0 && $homeUnderCount != 0) {
        $homeUnderAll = round(($homeUnderCount / $eventCount) * 100, 2);
    }

    // Away over
    if($awayOverCount != 0 && $awayUnderCount != 0) {
        $awayOverAll = round(($awayOverCount / $eventCount) * 100, 2);
    }

    // Away under
    if($awayUnderCount != 0 && $awayUnderCount != 0) {
        $awayUnderAll = round(($awayUnderCount / $eventCount) * 100, 2);
    }
    

    $return = [
        "home" => [
            "over" => [
                "lastten" => $homeOverLastTen,
                "all" => $homeOverAll
            ],
            "under" => [
                "lastten" => $homeUnderLastTen,
                "all" => $homeUnderAll
            ]
        ],
        "away" => [
            "over" => [
                "lastten" => $awayOverLastTen,
                "all" => $awayOverAll
            ],
            "under" => [
                "lastten" => $awayUnderLastTen,
                "all" => $awayUnderAll
            ]
        ]
    ];

    return $return;
  }

  public static function headToHead($home, $away) {
    $lastFiveHome = Match::where('home_player', 'ILIKE', "%{$home}%")
                            ->orWhere('away_player', 'ILIKE', "%{$home}%")
                            ->orderBy('match_date', 'DESC')
                            ->limit(5)
                            ->get()
                            ->toArray();

    $lastFiveAway = Match::where('home_player','ILIKE', "%{$away}%")
                        ->orWhere('away_player', 'ILIKE', "%{$away}%")
                        ->orderBy('match_date', 'DESC')
                        ->limit(5)
                        ->get()
                        ->toArray();

    $playerVsPlayer1 = Match::where('home_player', 'ILIKE', "%{$home}%")
    ->where('away_player', 'ILIKE', "%{$away}%")
    ->orderBy('match_date', 'DESC')
    ->get()
    ->toArray();

    $playerVsPlayer2 = Match::where('home_player', 'ILIKE', "%{$away}%")
    ->where('away_player', 'ILIKE', "%{$home}%")
    ->orderBy('match_date', 'DESC')
    ->get()
    ->toArray();

    // DB::table('matches')
    // ->select('*')
    // ->where(DB::raw('lower(home_player)'), 'like', '%' . strtolower($home) . '%');

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

    return[
        'lastFiveHome' => $lastFiveHome,
        'lastFiveAway' => $lastFiveAway,
        'overs' => $oversPercentage,
        'playerVsPlayer' => $playerVsPlayer
    ];
  }

  public static function statisticsFifa22($home, $away) {
    // $url = 'https://api.b365api.com/v1/team?token='. env('API_TOKEN') . '&sport_id=1';
    // $result = Http::get($url);

    // dd($home);

	  $home = Str::lower($home);
	  $away = Str::lower($away);

    $statistics1 = Match::where('home_player', 'ILIKE', "%{$home}%")
        ->where('away_player', 'ILIKE', "%{$away}%")
        ->where('match_date', '>', "2021-11-01")
        ->get()
        ->toArray();

    $statistics2 = Match::where('home_player', 'ILIKE', "%{$away}%")
    ->where('away_player', 'ILIKE', "%{$home}%")
    ->where('match_date', '>', "2021-11-01")
    ->get()
    ->toArray();

    $statistics = array_merge($statistics1, $statistics2);

    // dd($statistics);

    $player1 = $home;
    $player2 = $away;

    $player1Goals = 0;
    $player2Goals = 0;

    $player1Wins = 0;
    $player2Wins = 0;

    $countMatches = 0;

    $overGoals1dot5 = 0;
    $underGoals1dot5 = 0;

    $player1WinsLastTen = 0;
    $player1WinsLastTenPerc = 0;
    $player2WinsLastTen = 0;
    $player2WinsLastTenPerc = 0;
    $drawLastTen = 0;
    $drawLastTenPerc = 0;

    $bothToScore = 0;
    $bothToScoreLastTen = 0;
    $lessThenOne = 0;

    $draws = 0;

    $matches = array();

    foreach($statistics as $key =>$event) {
        $countMatches++;

        // explode home to get player
        /** Without Explode
         *
         * $firstExplodeHome = explode("(", $event["home"]["name"]);
         * $secondExplodeHome = explode(")", $firstExplodeHome[1]);
         * $home = $secondExplodeHome[0];
         */

        $home = Str::lower($event["home_player"]);


        // explode away to get player
        /** Without explode
         * $firstExplodeAway = explode("(", $event["away"]["name"]);
         * $secondExplodeAway = explode(")", $firstExplodeAway[1]);
         * $away = $secondExplodeAway[0];
         */

        $away = Str::lower($event["away_player"]);

        // dd($event["away"]["name"]);
        // Get team goals
        if(isset($event["score"]) && $event["score"] != "") {
            $scoreExploded = explode('-', $event["score"]);


            $homeGoals = intval($scoreExploded[0]);
            $awayGoals = intval($scoreExploded[1]);

            $totalGoals = $homeGoals + $awayGoals;
        } else {
            $scoreExploded = 0;


            $homeGoals = 0;
            $awayGoals = 0;

            $totalGoals = 0;
        }


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
        if($homeGoals > 1 && $awayGoals > 1) {
            $bothToScore++;
        }

        // Partial last ten
        if($key == 9 && $countMatches != 0) {
            $bothToScoreLastTen = $bothToScore;
            if($player1Wins != 0) {
                $player1WinsLastTenPerc = round(($player1Wins / $countMatches) * 100, 2);
                $player1WinsLastTen = $player1Wins;
            }

            if($draws != 0) {
                $drawLastTenPerc = round(($draws / $countMatches) * 100, 2);
                $drawLastTen = $draws;
            }

            if($player2Wins != 0) {
                $player2WinsLastTenPerc = round(($player2Wins / $countMatches) * 100, 2);
                $player2WinsLastTen = $player2Wins;
            }
        }

        // Both don't make goal
        if($homeGoals < 1 || $awayGoals < 1) {
            $lessThenOne++;
        }
    }

    $player1WinsPerc = 0;
    $player2WinsPerc = 0;
    $drawsPerc = 0;

    if($player1Wins != 0 && $countMatches != 0) {
        $player1WinsPerc = round(($player1Wins / $countMatches) * 100, 2);
    }
    if($player2Wins != 0 && $countMatches != 0) {
        $player2WinsPerc = round(($player2Wins / $countMatches) * 100, 2);
    }
    if($draws != 0 && $countMatches != 0) {
        $drawsPerc = round(($draws / $countMatches) * 100, 2);
    }

    $objToView = [
        'player1' => $player1,
        'player2' => $player2,
        'player1Goals' => $player1Goals,
        'player2Goals' => $player2Goals,
        'player1Wins' => $player1Wins,
        'player1WinsPerc' => $player1WinsPerc,
        'player1WinsLastTen' => $player1WinsLastTen,
        'player1WinsLastTenPerc' => $player1WinsLastTenPerc,
        'player2Wins' => $player2Wins,
        'player2WinsPerc' => $player2WinsPerc,
        'player2WinsLastTen' => $player2WinsLastTen,
        'player2WinsLastTenPerc' => $player2WinsLastTenPerc,
        'draws' => $draws,
        'drawsPerc' => $drawsPerc,
        'drawLastTen' => $drawLastTen,
        'drawLastTenPerc' => $drawLastTenPerc,
        'matches' => $countMatches,
        'overGoals1dot5' => $overGoals1dot5,
        'underGoals1dot5' => $underGoals1dot5,
        'bothToScore' => $bothToScore,
        'bothToScoreLastTen' => $bothToScoreLastTen,
        'lessThenOne' => $lessThenOne
    ];

    return $objToView;
  }

  public static function overAndUnderMatchGoalsFifa22($home, $away, $odd) {
    $results = Match::where('home_player', 'ILIKE', "%{$home}%")
        ->where('away_player', 'ILIKE', "%{$away}%")
        ->where('match_date', '>', "2021-11-01")
        ->orderBy('match_date', 'DESC')
        ->get()
        ->toArray();

    $overCount = 0;
    $underCount = 0;
    $eventCount = 0;

    $parcialOver  = 0;
    $parcialUnder = 0;
    $parcialEvent = 0;

    $lastTenOver = 0;
    $lastTenUnder = 0;

    foreach($results as $key => $event){
        if($event['score'] && $event['score'] != "" ){
            $score = explode("-", $event['score']);
            $sumScore = $score[0] + $score[1];
        } else {

            $sumScore = 0;
        }


        if($sumScore > $odd) {
            $overCount++;
        } else {
            $underCount++;
        }

        $eventCount++;

        if($key == 9 && $eventCount != 0) {
            if($overCount != 0) {
                $lastTenOver = round(($overCount / $eventCount) * 100, 2);
            }

            if($underCount != 0) {
                $lastTenUnder = round(($underCount / $eventCount) * 100, 2);
            }

        }
    }

    $overAll = 0;
    $underAll = 0;

    if($overCount != 0 && $eventCount != 0) {
        $overAll = round(($overCount / $eventCount) * 100, 2);
    }

    if($underCount != 0 && $eventCount != 0) {
        $underAll = round(($underCount / $eventCount) * 100, 2);
    }


    $return = [
        "over" => [
            "lastten" => $lastTenOver,
            "all" => $overAll
        ],
        "under" => [
            "lastten" => $lastTenUnder,
            "all" => $underAll
        ],
    ];

    return $return;
  }

  public static function overAndUnderHomeAwayFifa22($home, $away, $odd) {
    $results = Match::where('home_player', 'ILIKE', "%{$home['name']}%")
        ->where('away_player', 'ILIKE', "%{$away['name']}%")
        ->where('match_date', '>', "2021-11-01")
        ->orderBy('match_date', 'DESC')
        ->get()
        ->toArray();

    // Initiate over arrays
    $homeOverCount = 0;
    $homeOverLastTen = 0;

    $awayOverCount = 0;
    $awayOverLastTen = 0;

    // Initiate under arrays
    $homeUnderCount = 0;
    $homeUnderLastTen = 0;

    $awayUnderCount = 0;
    $awayUnderLastTen = 0;

    $eventCount = 0;

    foreach($results as $key => $event){
        if($event['score'] && $event['score'] != "") {
            $score = explode("-", $event['score']);
            $sumScore = $score[0] + $score[1];
        } else {
            $score = [0,0];
            $sumScore = 0;
        }


        if($score[0] > $odd) {
            $homeOverCount++;
        } else {
            $homeUnderCount++;
        }

        if($score[1] > $odd) {
            $awayOverCount++;
        } else {
            $awayUnderCount++;
        }

        $eventCount++;

        if($key == 9 && $eventCount != 0) {
            // Home over
            if($homeOverCount != 0 && $homeUnderCount != 0) {
                $homeOverLastTen = round(($homeOverCount / $eventCount) * 100, 2);
            }

            // Home under
            if($homeUnderCount != 0 && $homeUnderCount != 0) {
                $homeUnderLastTen = round(($homeUnderCount / $eventCount) * 100, 2);
            }

            // Away over
            if($awayOverCount != 0 && $awayUnderCount != 0) {
                $awayOverLastTen = round(($awayOverCount / $eventCount) * 100, 2);
            }

            // Away under
            if($awayUnderCount != 0 && $awayUnderCount != 0) {
                $awayUnderLastTen = round(($awayUnderCount / $eventCount) * 100, 2);
            }
        }
    }

    $homeOverAll = 0;
    $homeUnderAll = 0;

    $awayOverAll = 0;
    $awayUnderAll = 0;

    // Home over
    if($homeOverCount != 0 && $homeUnderCount != 0) {
        $homeOverAll = round(($homeOverCount / $eventCount) * 100, 2);
    }

    // Home under
    if($homeUnderCount != 0 && $homeUnderCount != 0) {
        $homeUnderAll = round(($homeUnderCount / $eventCount) * 100, 2);
    }

    // Away over
    if($awayOverCount != 0 && $awayUnderCount != 0) {
        $awayOverAll = round(($awayOverCount / $eventCount) * 100, 2);
    }

    // Away under
    if($awayUnderCount != 0 && $awayUnderCount != 0) {
        $awayUnderAll = round(($awayUnderCount / $eventCount) * 100, 2);
    }


    $return = [
        "home" => [
            "over" => [
                "lastten" => $homeOverLastTen,
                "all" => $homeOverAll
            ],
            "under" => [
                "lastten" => $homeUnderLastTen,
                "all" => $homeUnderAll
            ]
        ],
        "away" => [
            "over" => [
                "lastten" => $awayOverLastTen,
                "all" => $awayOverAll
            ],
            "under" => [
                "lastten" => $awayUnderLastTen,
                "all" => $awayUnderAll
            ]
        ]
    ];

    return $return;
  }

  public static function headToHeadFifa22($home, $away) {
    $lastFiveHome = Match::where('home_player', 'ILIKE', "%{$home}%")
                            ->orWhere('away_player', 'ILIKE', "%{$home}%")
                            ->where('match_date', '>', "2021-11-01")
                            ->orderBy('match_date', 'DESC')
                            ->limit(5)
                            ->get()
                            ->toArray();

    $lastFiveAway = Match::where('home_player','ILIKE', "%{$away}%")
                        ->orWhere('away_player', 'ILIKE', "%{$away}%")
                        ->where('match_date', '>', "2021-11-01")
                        ->orderBy('match_date', 'DESC')
                        ->limit(5)
                        ->get()
                        ->toArray();

    $playerVsPlayer1 = Match::where('home_player', 'ILIKE', "%{$home}%")
    ->where('away_player', 'ILIKE', "%{$away}%")
    ->where('match_date', '>', "2021-11-01")
    ->orderBy('match_date', 'DESC')
    ->get()
    ->toArray();

    $playerVsPlayer2 = Match::where('home_player', 'ILIKE', "%{$away}%")
    ->where('away_player', 'ILIKE', "%{$home}%")
    ->where('match_date', '>', "2021-11-01")
    ->orderBy('match_date', 'DESC')
    ->get()
    ->toArray();

    // DB::table('matches')
    // ->select('*')
    // ->where(DB::raw('lower(home_player)'), 'like', '%' . strtolower($home) . '%');

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

    return[
        'lastFiveHome' => $lastFiveHome,
        'lastFiveAway' => $lastFiveAway,
        'overs' => $oversPercentage,
        'playerVsPlayer' => $playerVsPlayer
	];
  }
 }
