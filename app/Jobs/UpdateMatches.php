<?php

namespace App\Jobs;

use App\Models\Match;
use App\Functions\Helpers;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
// use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Contracts\Queue\ShouldBeUnique;

class UpdateMatches
{

  /**
   * Execute the job.
   *
   * @return void
   */
  public function __invoke() {
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

                // $newMatch[] = [
                //         ["match_id" => $match["id"]],
                //         [
                //             "match_date" => Helpers::convertEpochToDateTime($match["time"]),
                //             "league_id" => $match["league"]["id"],
                //             "league_name" => $match["league"]["name"],
                //             "home_player" => $home["name"],
                //             "home_team" => $home["team"],
                //             "away_player" => $away["name"],
                //             "away_team" => $away["team"],
                //             "score" => $match["ss"],
                //         ]
                //     ];

                //     $json = json_encode($newMatch);

                    file_put_contents(base_path("storage/app/pageControl.txt"), "Success");
            }catch(\Exception $e) {
                return $e->getMessage();
            }
        }

        $page++;

        // file_put_contents(base_path("storage/app/pageControl.txt"), $page);
        
    } while(!empty($response['results']));
  }
    
}
