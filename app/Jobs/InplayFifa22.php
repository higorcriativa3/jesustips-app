<?php

namespace App\Jobs;

use App\Models\Match;
use App\Functions\Helpers;
use \App\Functions\Stats;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

class InplayFifa22
{

  /**
   * Execute the job.
   *
   * @return void
   */
  public function __invoke() {

        // Execute every second
        $count = 0;
        while ($count < 59) {
            $startTime =  Carbon::now();

            // Initiate array of matches IDs
            $inplayMatchs = array();
        
            // Get inplay events as JSON
            $inplayFilter = Http::get("https://api.b365api.com/v1/bet365/inplay_filter?sport_id=1&league_id=10048139&token=91390-4sDwuMJTtIhuPJ")
            ->json();

   		    $inplayFilter8min = Http::get("https://api.b365api.com/v1/bet365/inplay_filter?sport_id=1&league_id=10047781&token=91390-4sDwuMJTtIhuPJ")
            ->json();

            $inplayFilter12min = Http::get("https://api.b365api.com/v1/bet365/inplay_filter?sport_id=1&league_id=10047670&token=91390-4sDwuMJTtIhuPJ")
            ->json();

            // Return if have no games inplay
            if(
                !isset($inplayFilter["results"]) && 
                !isset($inplayFilter8min["results"]) && 
                !isset($inplayFilter12min["results"])
            ){
                file_put_contents(base_path("storage/app/liveFifa22.json"), "No games inplay");
                return "No games inplay";
            }
        
            $games = array_merge($inplayFilter["results"], $inplayFilter8min["results"], $inplayFilter12min["results"]); 
        
            // Return if have no games inplay
            if(empty($games)){
                file_put_contents(base_path("storage/app/liveFifa22.json"), "No games inplay");
                return "No games inplay";
            }
        
            // Push IDs into array of matches IDs
            foreach ($games as $key => $match) {
              // if($match["league"]["name"] == 'Esoccer Live Arena - 10 mins play') {
              //   file_put_contents(base_path("storage/app/test10min.txt"), '10min');
              // }
                $stats = Stats::attacks($match["our_event_id"]);
                
        
                $home = dividePlayerAndTeam($match["home"]["name"]);
                $away = dividePlayerAndTeam($match["away"]["name"]);
                    
                $rawStatistics = Stats::statisticsFifa22($home["name"], $away["name"]);
        
                // dd($statistics);
        
                // Explode score if not null
                $score = isset($match["ss"]) ?
                            explode("-", $match["ss"]) :
                            "0";
        
                // Initiate match schema
                $inplayMatch = [
                    "id" => $match["id"],
                    "bet365id" => $match["ev_id"],
                    "time" => $stats["timer"],
                    "league" => $match["league"]["name"],
                    "home" => [
                        "name" => $home["name"],
                        "team" => $home["team"],
                        "score" => isset($score[0]) ? $score[0] : ""
                    ],
                    "away" => [
                        "name" => $away["name"],
                        "team" => $away["team"],
                        "score" => isset($score[1]) ? $score[1] : ""
                    ],
                    "golsft" => null,
                    "homegols" => null,
                    "awaygols" => null,
                    "winnerft" => null,
                    "doublechance" => null,
                    "bothscore" => null,
                    "attacks" => $stats["attacks"],
                    "dangerousattacks" => $stats["dangerousattacks"],
                    "nextgol" => null
                ];
        
                $getOdd = Http::get("https://api.b365api.com/v1/bet365/event?token=91390-4sDwuMJTtIhuPJ&FI={$match['id']}")
                ->json();
        
                if(!isset($getOdd["results"])){continue;}
        
                // For each match result
                foreach($getOdd["results"] as $typekey => $type) {
        
                    foreach($type as $oddkey => $odd) {
        
                        // Search market group
                        if(array_search("MG", $odd)) {
        
                            //  Fulltime result
                            if($odd["NA"] == "Fulltime Result"){
                                $inplayMatch["winnerft"] = [
                                    "title" => "winner",
                                    "home" => [
                                        "odd" => convertOddToDecimal($type[$oddkey+2]["OD"]),
                                        "lastten" => $rawStatistics["player1WinsLastTenPerc"],
                                        "all" => $rawStatistics["player1WinsPerc"]
                                    ],
                                    "away" => [
                                        "odd" => convertOddToDecimal($type[$oddkey+4]["OD"]),
                                        "lastten" => $rawStatistics["player2WinsLastTenPerc"],
                                        "all" => $rawStatistics["player2WinsLastTenPerc"]
                                    ],
                                    "draw" => [
                                        "odd" => convertOddToDecimal($type[$oddkey+3]["OD"]),
                                        "lastten" => $rawStatistics["drawLastTenPerc"],
                                        "all" => $rawStatistics["drawsPerc"]
                                    ],
                                ];
                            }
        
                            //  goals ft
                            if($odd["NA"] == "Match Goals"){
                                $stats = Stats::overAndUnderMatchGoals(
                                    $home["name"], 
                                    $away["name"], 
                                    $type[$oddkey+2]["NA"]);
                                
                                $inplayMatch["golsft"] = [
                                    "handcap" => $type[$oddkey+2]["NA"],
                                    "oddOver" => convertOddToDecimal($type[$oddkey+4]["OD"]),
                                    "oddUnder" => convertOddToDecimal($type[$oddkey+6]["OD"]),
                                    "overs" => [
                                        "all" => $stats["overs"],
                                        "lastten" => $stats["oversLastTen"],
                                    ]
                                ];
                            }
        
                            // Goals home
                            if(
                                str_contains($odd["NA"], $home["name"]) && 
                                str_contains($odd["NA"], "Goals")
                            ) 
                            {
                                
                                $stats = Stats::overAndUnderHomeAwayFifa22($home, $away, $type[$oddkey+2]["NA"]);
                                $inplayMatch["homegols"] = [
                                    "handcap" => $type[$oddkey+2]["NA"],
                                    "over" => [
                                        "odd" => convertOddToDecimal($type[$oddkey+4]["OD"]),
                                        "lastten" => $stats["home"]["over"]["lastten"],
                                        "all" => $stats["home"]["over"]["all"]
                                    ],
                                    "under" => [
                                        "odd" => convertOddToDecimal($type[$oddkey+6]["OD"]),
                                        "lastten" => $stats["home"]["under"]["lastten"],
                                        "all" => $stats["home"]["under"]["all"]
                                    ]
                                ];
                            }
        
                            // Goals away
                            if(
                                str_contains($odd["NA"], $away["name"]) && 
                                str_contains($odd["NA"], "Goals")
                            ) 
                            {
        
                                $stats = Stats::overAndUnderHomeAwayFifa22($home, $away, $type[$oddkey+2]["NA"]);
        
                                $inplayMatch["awaygols"] = [
                                    "handcap" => $type[$oddkey+2]["NA"],
                                    "over" => [
                                        "odd" => convertOddToDecimal($type[$oddkey+4]["OD"]),
                                        "lastten" => $stats["away"]["over"]["lastten"],
                                        "all" => $stats["away"]["over"]["all"]
                                    ],
                                    "under" => [
                                        "odd" => convertOddToDecimal($type[$oddkey+6]["OD"]),
                                        "lastten" => $stats["away"]["under"]["lastten"],
                                        "all" => $stats["away"]["under"]["all"]
                                    ]
                                ];
                            }
        
                            // Both to score
                            if(str_contains($odd["NA"], "Both Teams to Score")){
                                $lastTen = $rawStatistics["bothToScoreLastTen"];
                                $all = $rawStatistics["bothToScore"];
                                $matches = $rawStatistics["matches"];
                                
                                if($matches == 0) {
                                    $matches = 1;
                                }
        
                                if($all == 0) {
                                    $all = 1;
                                }
        
                                if($lastTen == 0) {
                                    $lastTen = 1;
                                }
        
                                // dd([$all, $matches]);
        
                                $inplayMatch["bothscore"] = [
                                    "yes" => [
                                        "odd" => isset($type[$oddkey+2]["OD"]) ?
                                                    convertOddToDecimal($type[$oddkey+2]["OD"]) :
                                                    "0",
        
                                        "lastten" => round(($lastTen / 10) * 100, 2),
                                        "all" => round(($all / $matches) * 100, 2)
                                    ],
                                    "no" => [
                                        "odd" => isset($type[$oddkey+3]["OD"]) ?
                                                    convertOddToDecimal($type[$oddkey+3]["OD"]) :
                                                    "0",
        
                                        "lastten" => round(((10 - $lastTen) / 10) * 100, 2),
                                        "all" => round((($matches - $all) / $matches) * 100, 2)
                                    ],
                                ];
                            }
        
                            // Double chance
                            if(str_contains($odd["NA"], "Double Chance")) {
        
                                $onex = $rawStatistics["player1Wins"] + $rawStatistics["draws"];
                                $twox = $rawStatistics["player2Wins"] + $rawStatistics["draws"];
                                $both = $rawStatistics["player1Wins"] + $rawStatistics["player2Wins"];
        
                                $onexlastten = $rawStatistics["player1WinsLastTen"] + $rawStatistics["drawLastTen"];
                                $twoxlastten = $rawStatistics["player2WinsLastTen"] + $rawStatistics["drawLastTen"];
                                $bothlastten = $rawStatistics["player1WinsLastTen"] + $rawStatistics["player2WinsLastTen"];
        
                                $matches = $rawStatistics["matches"];
        
                                $nonZero = 1;
        
                                // Treat zero
                                if($matches == 0) {
                                    $nonZero = 0;
                                }
        
                                $inplayMatch["doublechance"] = [
                                    "onex" => [
                                        "odd" => convertOddToDecimal($type[$oddkey+2]["OD"]),
                                        "lastten" => $nonZero ? round(($onexlastten / 10) * 100, 2) : 0,
                                        "all" => $nonZero ? round(($onex / $matches) * 100, 2) : 0
                                    ],
                                    "xtwo" => [
                                        "odd" => convertOddToDecimal($type[$oddkey+3]["OD"]),
                                        "lastten" => $nonZero ? round(($twoxlastten / 10) * 100, 2) : 0,
                                        "all" => $nonZero ? round(($twox / $matches) * 100, 2) : 0
                                    ],
                                    "both" => [
                                        "odd" => convertOddToDecimal($type[$oddkey+4]["OD"]),
                                        "lastten" => $nonZero ? round(($bothlastten / 10) * 100, 2) : 0,
                                        "all" => $nonZero ? round(($both / $matches) * 100, 2) : 0
                                    ],
                
                                ];
                            }
        
                            // Next Goal
                            if($odd["type"] == "MG" && str_contains($odd["NA"], "Goal")) {
                                // dd($type[$oddkey+2]);
                                $inplayMatch["nextgol"] = [
                                    "home" => isset($type[$oddkey+2]["OD"]) ? 
                                                convertOddToDecimal($type[$oddkey+2]["OD"]) : 0,
        
                                    "away" => isset($type[$oddkey+4]["OD"]) ? 
                                                convertOddToDecimal($type[$oddkey+4]["OD"]) : 0           
                                ];
                            }
                        }
        
                        
                    }
                }
        
                $inplayMatchs[] = $inplayMatch;
            }

            $inplayJson = json_encode($inplayMatchs);
        
            file_put_contents(base_path("storage/app/liveFifa22.json"), $inplayJson);

            $endTime = Carbon::now();
            $totalDuration = $endTime->diffInSeconds($startTime);
            if($totalDuration > 0) {
                $count +=  $totalDuration;
            }
            else {
                $count++;
            }
	    sleep(1);
	}
  }
    
}


