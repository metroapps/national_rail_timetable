<?php
declare(strict_types=1);

namespace App\Models;

use App\Casts\Caterings;
use App\Enums\Power;
use App\Enums\Reservation;
use App\Enums\ShortTermPlanning;
use App\Enums\TrainCategory;
use App\Enums\TrainClass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

abstract class BaseSchedule extends Model
{
    public const SERVICE_PROPERTY_CASTS = [
        'train_category' => TrainCategory::class,
        'power_type' => Power::class,
        'speed' => 'int',
        'train_class' => TrainClass::class,
        'sleepers' => TrainClass::class,
        'reservation' => Reservation::class,
        'catering_code' => Caterings::class,
    ];

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
    protected $casts = [
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
        'stp_indicator' => ShortTermPlanning::class,
    ] + self::SERVICE_PROPERTY_CASTS;

    public abstract function stopTimes(): HasMany;
}
