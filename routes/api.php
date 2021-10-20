<?php

use App\Models\Match;
use App\Functions\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        $dateTo = Carbon::parse('2021-10-16'); 
        $dateFrom->lt($dateTo); 
        $dateFrom->addDays(1)
    )
    {
        // $dates[] = str_replace('-', '', $dateFrom->toDateString());
        $incrementedDate = str_replace('-', '', $dateFrom->toDateString());
        $page = 1;


        $url = 'https://api.b365api.com/v2/events/ended?sport_id=1&league_id=22614&day='.$incrementedDate.'&token=91390-4sDwuMJTtIhuPJ&page=';

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
