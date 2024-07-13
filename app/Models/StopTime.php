<?php
declare(strict_types=1);

namespace App\Models;

use App\Casts\Activities;
use App\Casts\Allowance;
use App\ValueObjects\Time;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StopTime extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stop_time';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public function schedule() : BelongsTo {
        return $this->belongsTo(Schedule::class, 'schedule');
    }

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
