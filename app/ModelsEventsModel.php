<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModelsEventsModel extends Model
{
    protected $table = "events";

    protected $fillable = [
        'id',
        'ref_events_categories_id',
        'event_title',
        'event_month',
        'event_location',
        'event_image',
    ];
}
