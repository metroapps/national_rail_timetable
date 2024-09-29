<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class StopTime extends BaseStopTime {
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stop_time';

    public function scheduleModel() : BelongsTo {
        return $this->belongsTo(Schedule::class, 'schedule');
    }

    public function tiploc() : BelongsTo {
        return $this->belongsTo(Tiploc::class, 'location', 'tiploc_code');
    }

    public function physicalStation() : HasOneThrough {
        return $this->hasOneThrough(PhysicalStation::class, Tiploc::class, 'tiploc_code', 'tiploc_code', 'location', 'tiploc_code');
    }

    public function serviceChange() : HasOne {
        return $this->hasOne(ServiceChange::class, 'stop');
    }
}
