<?php
declare(strict_types=1);

namespace App\Models;

use App\Casts\Caterings;
use App\Enums\Power;
use App\Enums\Reservation;
use App\Enums\ShortTermPlanning;
use App\Enums\TrainCategory;
use App\Enums\TrainClass;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'schedule';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('join_extra', function (Builder $builder) {
            $builder->join('schedule_extra', 'schedule.id', '=', 'schedule_extra.schedule');
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'runs_from' => 'immutable_date',
            'runs_to' => 'immutable_date',
            'monday' => 'bool',
            'tuesday' => 'bool',
            'wednesday' => 'bool',
            'thursday' => 'bool',
            'friday' => 'bool',
            'saturday' => 'bool',
            'sunday' => 'bool',
            'bank_holiday_running' => 'bool',
            'train_category' => TrainCategory::class,
            'power_type' => Power::class,
            'speed' => 'int',
            'train_class' => TrainClass::class,
            'sleepers' => TrainClass::class,
            'reservation' => Reservation::class,
            'catering_code' => Caterings::class,
            'stp_indicator' => ShortTermPlanning::class,
        ];
    }
}
