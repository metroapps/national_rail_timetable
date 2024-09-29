<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class PhysicalStation extends Model
{
    /**
     * Value for cate_interchange_status to denote that this entry represents
     * a secondary TIPLOC for the station
     */
    public const INTERCHANGE_STATUS_SECONDARY = 9;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'physical_station';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public function tiploc() : BelongsTo {
        return $this->belongsTo(Tiploc::class, 'tiploc_code', 'tiploc_code');
    }

    public function stopTimes() : HasManyThrough {
        return $this->hasManyThrough(StopTime::class, Tiploc::class, 'tiploc_code', 'location', 'tiploc_code', 'tiploc_code');
    }

    public function ZStopTimes() : HasMany {
        return $this->hasMany(ZStopTime::class, 'location', 'crs_code');
    }

    public function scopePrimary(Builder $query) : void {
        $query->where('cate_interchange_status', '<>', self::INTERCHANGE_STATUS_SECONDARY);
    }
}
