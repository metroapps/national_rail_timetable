<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceChange extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'service_change';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = BaseSchedule::SERVICE_PROPERTY_CASTS;

    public function stopTime() : BelongsTo {
        return $this->belongsTo(BaseStopTime::class, 'stop');
    }

    public function tiploc() : BelongsTo {
        return $this->belongsTo(Tiploc::class, 'location', 'tiploc_code');
    }
}
