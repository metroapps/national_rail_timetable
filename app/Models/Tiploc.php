<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tiploc extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tiploc';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public function stopTimes(): HasMany {
        return $this->hasMany(StopTime::class, 'location', 'tiploc_code');
    }

    public function physicalStation() : HasOne {
        return $this->hasOne(PhysicalStation::class, 'tiploc_code', 'tiploc_code');
    }

    public function serviceChanges() : HasMany {
        return $this->hasMany(ServiceChange::class, 'location', 'tiploc_code');
    }
}
