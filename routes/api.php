<?php

use App\Models\Match;
use \App\Functions\Stats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/odd', function(){
    function convertOddToDecimal($odd) {
        $explodeOdd = explode("/", $odd);
        if($explodeOdd[0] != 0 && $explodeOdd[1] != 0) {
            $numerator = $explodeOdd[0];
            $denominator = $explodeOdd[1];
            $convertedOdd = (intval($numerator)/intval($denominator)) + 1;

            return $convertedOdd;
        } else {
            return 'is not possible to convert';
        }
    }

    function dividePlayerAndTeam($string) {
        $team = explode("(", $string);
        $name = explode(")", $team[1]);

        $obj = [
            "name" => trim($name[0]),
            "team" => trim($team[0]),
        ];

        return $obj;
    }
    // Initiate array of matches IDs
    $inplayMatchs = array();

    // Get inplay events as JSON
    $inplayFilter = Http::get("https://api.b365api.com/v1/bet365/inplay_filter?sport_id=1&league_id=10048139&token=91390-4sDwuMJTtIhuPJ")
    ->json();

    $inplayFilter8min = Http::get("https://api.b365api.com/v1/bet365/inplay_filter?sport_id=1&league_id=10047781&token=91390-4sDwuMJTtIhuPJ")
    ->json();

    if($inplayFilter8min["results"] != [] && $inplayFilter["results"] != []){
        $games = array_merge($inplayFilter8min["results"], $inplayFilter["results"]);
    } elseif($inplayFilter8min["results"] != []) {
        $games = $inplayFilter8min["results"];
    } elseif($inplayFilter["results"] != []) {
        $games = $inplayFilter["results"];
    } else {
        return "No games inplay";
    }
    // Push IDs into array of matches IDs
    foreach ($games as $key => $match) {

        $stats = Stats::attacks($match["our_event_id"]);
       

        $home = dividePlayerAndTeam($match["home"]["name"]);
        $away = dividePlayerAndTeam($match["away"]["name"]);
         
        // $statistics = Stats::statistics($home, $away);

        // dd($statistics);

        // Explode score if not null
        $score = isset($match["ss"]) ?
                 explode("-", $match["ss"]) :
                 "0";

        // Initiate match schema
        $inplayMatch = [
            "id" => $match["id"],
            "time" => $match["time_status"],
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

        // $results = $getOdd["results"];

        if(!isset($getOdd["results"])){continue;}

        // dd($results);

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
                                "lastten" => "90%",
                                "all" => "83%"
                            ],
                            "away" => [
                                "odd" => convertOddToDecimal($type[$oddkey+4]["OD"]),
                                "lastten" => "50%",
                                "all" => "43%"
                            ],
                            "draw" => [
                                "odd" => convertOddToDecimal($type[$oddkey+3]["OD"]),
                                "lastten" => "50%",
                                "all" => "43%"
                            ],
                        ];
                    } else {
                        $inplayMatch["winnerft"] = null;
                    }

                    //  goals ft
                    if($odd["NA"] == "Match Goals"){
                        $inplayMatch["golsft"] = [
                            "handcap" => $type[$oddkey+2]["NA"],
                            "over" => [
                                "odd" => convertOddToDecimal($type[$oddkey+4]["OD"]),
                                "lastten" => "90%",
                                "all" => "83%"
                            ],
                            "under" => [
                                "odd" => convertOddToDecimal($type[$oddkey+6]["OD"]),
                                "lastten" => "50%",
                                "all" => "43%"
                            ]
                        ];
                    }

                    // Goals home
                    if(
                        str_contains($odd["NA"], $home["name"]) && 
                        str_contains($odd["NA"], "Goals")) 
                        {
                        $inplayMatch["homegols"] = [
                            "handcap" => $type[$oddkey+2]["NA"],
                            "over" => [
                                "odd" => convertOddToDecimal($type[$oddkey+4]["OD"]),
                                "lastten" => "90%",
                                "all" => "83%"
                            ],
                            "under" => [
                                "odd" => convertOddToDecimal($type[$oddkey+6]["OD"]),
                                "lastten" => "50%",
                                "all" => "43%"
                            ]
                        ];
                    }

                    // Goals away
                    if(
                        str_contains($odd["NA"], $away["name"]) && 
                        str_contains($odd["NA"], "Goals")
                        ) 

                        {

                        $inplayMatch["awaygols"] = [
                            "handcap" => $type[$oddkey+2]["NA"],
                            "over" => [
                                "odd" => convertOddToDecimal($type[$oddkey+4]["OD"]),
                                "lastten" => "90%",
                                "all" => "83%"
                            ],
                            "under" => [
                                "odd" => convertOddToDecimal($type[$oddkey+6]["OD"]),
                                "lastten" => "50%",
                                "all" => "43%"
                            ]
                        ];
                    }

                    // Both to score
                    if(str_contains($odd["NA"], "Both Teams To Score")){

                        $inplayMatch["bothscore"] = [
                            "yes" => [
                                "odd" => isset($type[$oddkey+2]["OD"]) ?
                                         convertOddToDecimal($type[$oddkey+2]["OD"]) :
                                         "0",

                                "lastten" => "10",
                                "all" => "3"
                            ],
                            "no" => [
                                "odd" => isset($type[$oddkey+3]["OD"]) ?
                                         convertOddToDecimal($type[$oddkey+3]["OD"]) :
                                         "0",

                                "lastten" => "10",
                                "all" => "3"
                            ],
                        ];
                    }

                    // Double chance
                    if(str_contains($odd["NA"], "Double Chance")) {

                        $inplayMatch["doublechance"] = [
                            "onex" => [
                                "odd" => convertOddToDecimal($type[$oddkey+2]["OD"]),
                                "lastten" => "80%",
                                "all" => "53%"
                            ],
                            "xtwo" => [
                                "odd" => convertOddToDecimal($type[$oddkey+3]["OD"]),
                                "lastten" => "80%",
                                "all" => "53%"
                            ],
                            "both" => [
                                "odd" => convertOddToDecimal($type[$oddkey+4]["OD"]),
                                "lastten" => "80%",
                                "all" => "53%"
                            ],
        
                        ];
                    }

                    // Next Goal
                    if($odd["type"] == "MG" && str_contains($odd["NA"], "Goal")) {
                        // dd($type[$oddkey+2]);
                        $inplayMatch["nextgol"] = [
                            "home" => isset($type[$oddkey+2]["OD"]) ? 
                                      convertOddToDecimal($type[$oddkey+2]["OD"]) : "0",

                            "away" => isset($type[$oddkey+4]["OD"]) ? 
                                      convertOddToDecimal($type[$oddkey+4]["OD"]) : "0"           
                        ];
                    }
                }

                
            }
        }

        $inplayMatchs[] = $inplayMatch;
    }

    return $inplayMatchs;
});

Route::get('/ended', function(){

    $page = 1000;
    $perPage;
    $total;

    function dividePlayerAndTeam($string) {
        $team = explode("(", $string);
        $name = explode(")", $team[1]);

        $obj = [
            "name" => trim($name[0]),
            "team" => trim($team[0]),
        ];

        return $obj;
    }

    function convertEpochToDateTime($epoch) {
        $dt = new DateTime("@$epoch");  // convert UNIX timestamp to PHP DateTime
        return $dt->format('Y-m-d H:i:s'); // output = 2017-01-01 00:00:00
    }

    $url = 'https://api.b365api.com/v2/events/ended?sport_id=1&league_id=22821&token=91390-4sDwuMJTtIhuPJ&page=';

    do{
        $response = Http::get($url . $page)->json();

        if(!isset($response["success"])) {
            if($response["success"] != 1) {
                return response(["message" => "error: {$response["error_detail"]}"], 500);
            }  
        }
        $per_page = $response['pager']['per_page'];
        $total = $response['pager']['total'];

        foreach($response['results'] as $key => $match) {
            $home = dividePlayerAndTeam($match["home"]["name"]);
            $away = dividePlayerAndTeam($match["away"]["name"]);

            try{
                $newMatch = Match::create([
                    "match_id" => $match["id"],
                    "match_date" => convertEpochToDateTime($match["time"]),
                    "league_id" => $match["league"]["id"],
                    "league_name" => $match["league"]["name"],
                    "home_player" => $home["name"],
                    "home_team" => $home["team"],
                    "away_player" => $away["name"],
                    "away_team" => $away["team"],
                    "score" => $match["ss"],
                ]);
            }catch(\Exception $e) {
                return $e->getMessage();
            }
        }

        $page++;

        file_put_contents(base_path("storage/app/pageControl.txt"), $page);
        
    } while($page < 1001);

    return 'done';
});

Route::get('/test', function() {
    return 'test';
});
