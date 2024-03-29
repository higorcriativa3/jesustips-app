<?php

use App\Models\User;
use App\Models\Match;
use App\Functions\Stats;
use App\Functions\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;

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

Route::group([

    'middleware' => 'api',

], function ($router) {

    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/me', [AuthController::class, 'me']);
    Route::post('/customer', [CustomerController::class, 'show']);

});

// create a user by csv route
Route::post('/user-create-csv', function (Request $request) {
    $file = 'Lista.csv';
    $csv = file_get_contents(base_path("storage/app/Lista.csv"));
    $array = array_map('str_getcsv', explode(PHP_EOL, $csv));

    $json = json_encode($array);

    foreach($array as $customer){
        $customerExplode = explode(",", $customer[0]);
        // dd($customerExplode[2]);

        try{
            User::create([
                'name' => $customerExplode[0],
                'email' => $customerExplode[1],
                'password' => Hash::make($customerExplode[2]),
            ]);
        } catch(\Exception $e) {
            return response(['Message'=>$e->getMessage()], 400);
        }
    }



    return 'Created';
});

// create a user route
Route::post('/user-create', function (Request $request) {

        try{
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
        } catch(\Exception $e) {
            return response(['Message'=>$e->getMessage()], 400);
        }



    return 'Created';
});

Route::get('/testcron', function (){
    $rawPreviousDate = Carbon::yesterday()->format('Y-m-d');
    $yesterday = str_replace('-', '', $rawPreviousDate);
    // $matches = Http::get();

    $page = 1;

    $url = 'https://api.b365api.com/v2/events/ended?sport_id=1&league_id=22614&day='.$yesterday.'&token=91390-4sDwuMJTtIhuPJ&page=';

    do{
        $response = Http::get($url . $page)->json();

        if(!isset($response["success"])) {
            if($response["success"] != 1) {
                return response(["message" => "error: {$response["error_detail"]}"], 500);
            }  
        }

        foreach($response['results'] as $key => $match) {
            $home = Helpers::dividePlayerAndTeam($match["home"]["name"]);
            $away = Helpers::dividePlayerAndTeam($match["away"]["name"]);

            try{
                $newMatch = Match::firstOrCreate(
                    ["match_id" => $match["id"]],
                    [
                    "match_date" => Helpers::convertEpochToDateTime($match["time"]),
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

        // file_put_contents(base_path("storage/app/pageControl.txt"), $page);
        
    } while(!empty($response['results']));
    return $page;
});

Route::get('/ended/fromdate/{date}', function($date){
    // ini_set('max_execution_time', 0);
    $dateFrom = Carbon::parse($date);
    $dates = array();

    for(
        $dateTo = Carbon::parse('2021-11-29'); 
        $dateFrom->lt($dateTo); 
        $dateFrom->addDays(1)
    )
    {
        // $dates[] = str_replace('-', '', $dateFrom->toDateString());
        $incrementedDate = str_replace('-', '', $dateFrom->toDateString());
        $page = 1;


        $url = 'https://api.b365api.com/v2/events/ended?sport_id=1&league_id=22537&day='.$incrementedDate.'&token=91390-4sDwuMJTtIhuPJ&page=';

        // $obj = file_get_contents(base_path("storage/app/testFromDate.json"));

        do{
            $response = Http::get($url . $page)->json();
            if(!isset($response["success"])) {
                if($response["success"] != 1) {
                    return response(["message" => "error: {$response["error_detail"]}"], 500);
                }  
            }
    
            foreach($response['results'] as $key => $match) {
                $home = Helpers::dividePlayerAndTeam($match["home"]["name"]);
                $away = Helpers::dividePlayerAndTeam($match["away"]["name"]);
    
                try{
                    $newMatch = Match::firstOrCreate(
                        ["match_id" => $match["id"]],
                        [
                        "match_date" => Helpers::convertEpochToDateTime($match["time"]),
                        "league_id" => $match["league"]["id"],
                        "league_name" => $match["league"]["name"],
                        "home_player" => $home["name"],
                        "home_team" => $home["team"],
                        "away_player" => $away["name"],
                        "away_team" => $away["team"],
                        "score" => $match["ss"],
                    ]);

                    // $newMatch[] = [
                    //     ["match_id" => $match["id"]],
                    //     [
                    //         "match_date" => Helpers::convertEpochToDateTime($match["time"]),
                    //         "league_id" => $match["league"]["id"],
                    //         "league_name" => $match["league"]["name"],
                    //         "home_player" => $home["name"],
                    //         "home_team" => $home["team"],
                    //         "away_player" => $away["name"],
                    //         "away_team" => $away["team"],
                    //         "score" => $match["ss"],
                    //     ]
                    // ];

                    // $json = json_encode($newMatch);

                    // file_put_contents(base_path("storage/app/testFromDate.json"), $obj . $json);

                    
                }catch(\Exception $e) {
                    return response(["message:" => $e->getMessage()], 504);
                }
            }
    
            $page++;

            // dd($response['results']);
    
            file_put_contents(base_path("storage/app/pageControl.txt"), $page ." - ". $incrementedDate);
            
        } while(!empty($response['results']));
    }

    return 'done';
});

Route::get("/testgcp", function(){
    $matches = Match::where('match_date', "2021-10-19")->get();

    return $matches;
});

Route::get('/inplay', function(){
    //ini_set("memory_limit", "-1");
    //set_time_limit(0);
    function convertOddToDecimal($odd) {
        $explodeOdd = explode("/", $odd);
        if($explodeOdd[0] != 0 && $explodeOdd[1] != 0) {
            $numerator = $explodeOdd[0];
            $denominator = $explodeOdd[1];
            $convertedOdd = (intval($numerator)/intval($denominator)) + 1;

            return $convertedOdd;
        } else {
            return 1;
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

    $games = array_merge($inplayFilter8min["results"], $inplayFilter["results"]);

    // Return if have no games inplay
    if(empty($games)){
        return "No games inplay";
    }

    // Push IDs into array of matches IDs
    foreach ($games as $key => $match) {

        $stats = Stats::attacks($match["our_event_id"]);
       

        $home = dividePlayerAndTeam($match["home"]["name"]);
        $away = dividePlayerAndTeam($match["away"]["name"]);
         
        $rawStatistics = Stats::statistics($home["name"], $away["name"]);

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
                            "over" => [
                                "odd" => convertOddToDecimal($type[$oddkey+4]["OD"]),
                                "lastten" => $stats["over"]["lastten"],
                                "all" => $stats["over"]["all"]
                            ],
                            "under" => [
                                "odd" => convertOddToDecimal($type[$oddkey+6]["OD"]),
                                "lastten" => $stats["under"]["lastten"],
                                "all" => $stats["under"]["lastten"]
                            ]
                        ];
                    }

                    // Goals home
                    if(
                        str_contains($odd["NA"], $home["name"]) && 
                        str_contains($odd["NA"], "Goals")
                    ) 
                    {
                        
                        $stats = Stats::overAndUnderHomeAway($home, $away, $type[$oddkey+2]["NA"]);
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
                                "all" => $stats["home"]["under"]["lastten"]
                            ]
                        ];
                    }

                    // Goals away
                    if(
                        str_contains($odd["NA"], $away["name"]) && 
                        str_contains($odd["NA"], "Goals")
                    ) 
                    {

                        $stats = Stats::overAndUnderHomeAway($home, $away, $type[$oddkey+2]["NA"]);

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
                    if(str_contains($odd["NA"], "Both Teams To Score")){
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

    $json = json_encode($inplayMatchs);
    file_put_contents(base_path("storage/app/live.json"), $json);
    return 1;
});

Route::get("/odd", function(){
    $json = file_get_contents(base_path("storage/app/live.json"));
    return $json;
});

Route::get("/odd/22", function(){
    $json = file_get_contents(base_path("storage/app/liveFifa22.json"));
    return $json;
});

Route::post('/headtohead', function(Request $request){
    $home = $request->home;
    $away = $request->away;

    $statistics = Stats::statistics($home, $away);
    $headtohead = Stats::headToHead($home, $away);

    $bothScoreYes = 0; $goalAvarageHome = 0; $goalAvarageAway = 0;


    // Treat zeros
    if(
        $statistics["bothToScore"] != 0 &&
        $statistics["player2Goals"] != 0 &&
        $statistics["matches"] != 0

    ) {
        $bothScoreYes = round(($statistics["bothToScore"] / $statistics["matches"]) *100, 2);
        $goalAvarageHome = round($statistics["player1Goals"] / $statistics["matches"], 2);
        $goalAvarageAway = round($statistics["player2Goals"] / $statistics["matches"], 2);
    }

    $generalStats = [
        "homeWinsPerc" => $statistics["player1WinsPerc"],
        "awayWinsPerc" => $statistics["player2WinsPerc"],
        "drawsPerc" => $statistics["player2WinsPerc"],
        "matchesCount" => $statistics["matches"],
        "bothScoreYes" => $bothScoreYes,
        "goalAvarageHome" => $goalAvarageHome,
        "goalAvarageAway" => $goalAvarageAway,

    ];

    $mergedArrays = array_merge($generalStats, $headtohead);

    return $mergedArrays;
});

Route::post('/headtohead/22', function(Request $request){
    $home = $request->home;
    $away = $request->away;

    $statistics = Stats::statisticsFifa22($home, $away);
    $headtohead = Stats::headToHeadFifa22($home, $away);

    $bothScoreYes = 0; $goalAvarageHome = 0; $goalAvarageAway = 0;


    // Treat zeros
    if(
        $statistics["bothToScore"] != 0 &&
        $statistics["player2Goals"] != 0 &&
        $statistics["matches"] != 0

    ) {
        $bothScoreYes = round(($statistics["bothToScore"] / $statistics["matches"]) *100, 2);
        $goalAvarageHome = round($statistics["player1Goals"] / $statistics["matches"], 2);
        $goalAvarageAway = round($statistics["player2Goals"] / $statistics["matches"], 2);
    }

    $generalStats = [
        "homeWinsPerc" => $statistics["player1WinsPerc"],
        "awayWinsPerc" => $statistics["player2WinsPerc"],
        "drawsPerc" => $statistics["player2WinsPerc"],
        "matchesCount" => $statistics["matches"],
        "bothScoreYes" => $bothScoreYes,
        "goalAvarageHome" => $goalAvarageHome,
        "goalAvarageAway" => $goalAvarageAway,

    ];

    $mergedArrays = array_merge($generalStats, $headtohead);

    return $mergedArrays;
});

Route::get('/customers', [CustomerController::class, 'index']);

Route::post('/players', function (Request $request){
    $players = Match::where("league_name", "=", $request->league)
    ->distinct("home_player")
    ->pluck("home_player");

    return $players;
});

Route::get('/players/12min', function (Request $request){
    $players = Match::where("league_name", "=", "Esoccer Liga Pro - 12 mins play")
    ->distinct("home_team")
    ->pluck("home_team");

    return $players;
});

Route::get('/scores', function (){
    // Initiate scores array
    $scores = array();

    // Get inplay events as JSON
    $inplayFilter = Http::get("https://api.b365api.com/v1/bet365/inplay_filter?sport_id=1&league_id=10048139&token=91390-4sDwuMJTtIhuPJ")
    ->json();

    $inplayFilter8min = Http::get("https://api.b365api.com/v1/bet365/inplay_filter?sport_id=1&league_id=10047781&token=91390-4sDwuMJTtIhuPJ")
    ->json();

    $inplayFilter12min = Http::get("https://api.b365api.com/v1/bet365/inplay_filter?sport_id=1&league_id=10047670&token=91390-4sDwuMJTtIhuPJ")
    ->json();

    $games = array_merge($inplayFilter12min["results"], $inplayFilter8min["results"], $inplayFilter["results"]);

    // Return if have no games inplay
    if(empty($games)){
        return "No games inplay";
    }

    // Push IDs into array of matches IDs
    foreach ($games as $key => $match) {

        $scores[] = [
            'match_id' => $match["id"],
            'scores' => $match["ss"],
        ];
    }

    return $scores;
});

Route::get('/testinplay/{home}/{away}', function($home, $away){
    // $rawStatistics = Stats::statistics($home, $away);
    //  goals ft
        $stats = Stats::overAndUnderMatchGoals(
            $home, 
            $away,
            "2.5"
        );
        
        $inplayMatch["golsft"] = [
            "handcap" => "2.5",
            "oddOver" => "1.3",
            "oddUnder" => "1.78",
            "overs" => [
                "all" => $stats["overs"],
                "lastten" => $stats["oversLastTen"],
            ]
        ];

    return $inplayMatch;
});

Route::get("/upcoming/{date}", function($date) {
    $upcoming8min = Http::get("https://api.b365api.com/v2/events/upcoming?sport_id=1&league_id=22614&day=". $date ."&token=91390-4sDwuMJTtIhuPJ");
    $upcoming10min = Http::get("https://api.b365api.com/v2/events/upcoming?sport_id=1&league_id=22821&day=". $date ."&token=91390-4sDwuMJTtIhuPJ");
    $upcoming12min = Http::get("https://api.b365api.com/v2/events/upcoming?sport_id=1&league_id=22537&day=". $date ."&token=91390-4sDwuMJTtIhuPJ");

    $upcoming = array_merge($upcoming8min["results"], $upcoming10min["results"], $upcoming12min["results"]);

    return $upcoming;
});

Route::post('/player/find', function(Request $request){
    $matches = Match::where('league_name', $request->league)
    ->where('home_player', $request->player)
    ->get();

    return $matches;
});

