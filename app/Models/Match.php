<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Match extends Model
{
    use HasFactory;

    protected $fillable = [
        "match_id",
        "league_id",
        "league_name",
        "home_player",
        "home_team",
        "away_player",
        "away_team",
        "score",
    ];
}
