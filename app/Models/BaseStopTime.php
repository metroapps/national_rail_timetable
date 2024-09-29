<?php
declare(strict_types=1);

namespace App\Models;

use App\Casts\Activities;
use App\Casts\Allowance;
use App\ValueObjects\Time;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;

abstract class BaseStopTime extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public abstract function scheduleModel() : BelongsTo;

    public abstract function physicalStation() : Relation;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_arrival_time' => Time::class,
            'scheduled_departure_time' => Time::class,
            'scheduled_pass_time' => Time::class,
            'public_arrival_time' => Time::class,
            'public_departure_time' => Time::class,
            'activity' => Activities::class,
            'engineering_allowance' => Allowance::class,
            'pathing_allowance' => Allowance::class,
            'performance_allowance' => Allowance::class,
        ];
    }
}
