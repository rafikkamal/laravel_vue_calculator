<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GuestHistory extends Model
{
    protected $fillable = ['equation', 'result', 'guest_id'];
}
