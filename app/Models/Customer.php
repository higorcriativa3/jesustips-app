<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'user_id',
        'username',
        'first_name',
        'last_name',
        'email',
        'date_last_active',
        'date_registered',
        'country',
        'postcode',
        'city',
        'state'
    ];
}
