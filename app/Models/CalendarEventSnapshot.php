<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarEventSnapshot extends Model
{
    protected $fillable = ['user_id', 'calendar_id', 'event_id', 'summary', 'description', 'starts_at', 'ends_at', 'start_is_date', 'end_is_date'];
}
