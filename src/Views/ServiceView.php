<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Views;

use DateInterval;
use DateTimeImmutable;
use LogicException;
use Miklcct\NationalRailTimetable\Controllers\BoardQuery;
use Miklcct\NationalRailTimetable\Enums\Activity;
use Miklcct\NationalRailTimetable\Enums\AssociationCategory;
use Miklcct\NationalRailTimetable\Exceptions\UnreachableException;
use Miklcct\NationalRailTimetable\Models\Association;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\DatedService;
use Miklcct\NationalRailTimetable\Models\FullService;
use Miklcct\NationalRailTimetable\Models\LocationWithCrs;
use Miklcct\NationalRailTimetable\Models\Points\DestinationPoint;
use Miklcct\NationalRailTimetable\Models\Points\HasArrival;
use Miklcct\NationalRailTimetable\Models\Points\HasDeparture;
use Miklcct\NationalRailTimetable\Models\Points\TimingPoint;
use Miklcct\NationalRailTimetable\Models\Service;
use Miklcct\NationalRailTimetable\Models\ServiceProperty;
use Miklcct\ThinPhpApp\View\PhpTemplate;
use Psr\Http\Message\StreamFactoryInterface;
use function http_build_query;
use function Miklcct\ThinPhpApp\Escaper\html;

class ServiceView extends PhpTemplate {
    public function __construct(
        StreamFactoryInterface $streamFactory
        , protected readonly FullService $datedService
        , protected readonly bool $permanentOnly
        , protected readonly ?Date $generated
    ) {
        parent::__construct($streamFactory);
    }

    public static function getServiceUrl(string $uid, Date $date, bool $permanent_only = false) {
        return '/service.php?' . http_build_query(
            [
                'uid' => $uid,
                'date' => $date->__toString(),
            ] + ($permanent_only ? ['permanent_only' => '1'] : [])
        );
    }

    protected function getPathToTemplate() : string {
        return __DIR__ . '/../../resource/templates/service.phtml';
    }

    protected function getOriginPortion() : DatedService {
        $origin_portion = $this->datedService;
        while ($origin_portion->divideFrom !== null) {
            $origin_portion = $origin_portion->divideFrom->primaryService;
        }
        return $origin_portion;
    }

    protected function getTitle() : string {
        $service = $this->datedService->service;
        if (!$service instanceof Service) {
            throw new LogicException('The service does not run on the day.');
        }
        $origin_portion = $this->getOriginPortion();
        assert($origin_portion instanceof FullService);
        $service = $origin_portion->service;
        return sprintf(
            '%s %s %s %s to %s'
            , $origin_portion->date
            , substr($service->getOrigin()->serviceProperty->rsid, 0, 6)
            , $service->getOrigin()->getPublicOrWorkingDeparture()
            , $service->getOrigin()->location->getShortName()
            , implode(' and ', array_map(static fn(DestinationPoint $point) => $point->location->getShortName(), $origin_portion->getDestinations()))
        );
    }

    protected function showRsidWithPortionDescription(string $rsid) : string {
        $main = substr($rsid, 0, 6);
        $suffix = (int)substr($rsid, 6, 2);
        if ($suffix === 0) {
            return '<span class="train_number">' . html($main) . '</span>';
        }
        $portions = [];
        for ($i = 0; $i < 8; ++$i) {
            if ($suffix & 1 << $i) {
                $portions[] = $i + 1;
            }
        }
        return '<span class="train_number">' . html($main) . '</span>' . ' (Portion ' . implode(' & ', $portions) . ')';
    }

    protected function showTime(Date $date, Date $board_date, TimingPoint $point, bool $departure_to_arrival_board) : string {
        $time = $departure_to_arrival_board 
            ? ($point instanceof HasDeparture ? $point->getPublicDeparture() : null) 
            : ($point instanceof HasArrival ? $point->getPublicArrival() : null);
        if ($time === null) {
            return '';
        }
        return show_time(
            $date->toDateTimeImmutable($time)
            , $board_date
            , $point->location instanceof LocationWithCrs
                ? $this->getBoardLink(
                    $date->toDateTimeImmutable($time)
                    , $point->location
                    , $departure_to_arrival_board
                ) 
                : null
        );
    }

    protected function getBoardLink(DateTimeImmutable $timestamp, LocationWithCrs $location, bool $arrival_mode) : ?string {
        return (
            new BoardQuery(
                $arrival_mode
                , $location
                , []
                , Date::fromDateTimeInterface($timestamp->sub(new DateInterval($arrival_mode ? 'PT4H30M' : 'P0D')))
                , $timestamp
                , $this->datedService->service->toc
                , $this->permanentOnly
            )
        )->getUrl(BoardView::URL);
    }

    protected function splitIntoPortions(FullService $dated_service, bool $recursed = false) : array {
        if (!$recursed) {
            // assume that a train won't be split from a previous service and merge into an afterward service
            if ($dated_service->divideFrom !== null) {
                return $this->splitIntoPortions($dated_service->divideFrom->primaryService);
            }
            if ($dated_service->joinTo !== null) {
                return $this->splitIntoPortions($dated_service->joinTo->primaryService);
            }
        }
        $result = [];
        $index = 0;
        $result[0][0]['dated_service'] = $dated_service;
        foreach ($dated_service->service->points as $point) {
            $result[$index][0][] = $point;
            $new_portion = false;
            foreach ($dated_service->dividesJoinsEnRoute as $dated_association) {
                $association_point = $dated_service->service->getAssociationPoint($dated_association->associationEntry);
                if ($point->location->tiploc === $association_point->location->tiploc && $point->locationSuffix === $association_point->locationSuffix) {
                    if (!$new_portion) {
                        $result[++$index][0][0] = $point;
                        $result[$index][0]['dated_service'] = $dated_service;
                        $new_portion = true;
                    }
                    $other_portion = $this->splitIntoPortions($dated_association->secondaryService, true);
                    assert($dated_association->associationEntry instanceof Association);
                    switch ($dated_association->associationEntry->category) {
                    case AssociationCategory::DIVIDE:
                        $result[$index][] = $other_portion;
                        break;
                    case AssociationCategory::JOIN:
                        $result[$index - 1][] = $other_portion;
                        break;
                    default:
                        throw new UnreachableException();
                    }
                }
            }
        }
        return $result;
    }

    protected function showPortions(array $portions) : void {
?>
<table class="portion">
<?php
        foreach ($portions as $segment) {
?>
    <tr>
        <td>
            <table class="portion">
                <tr>
<?php
            foreach ($segment as $i => $portion) {
?>
                    <td>
<?php
                if ($i === 0) {
                    $this->showCallingPoints($portion);
                } else {
                    $this->showPortions($portion);
                }
?>
                    </td>
<?php
            }
?>
                </tr>
            </table>
        </td>
    </tr>
<?php
        }
?>
</table>
<?php
    }

    protected function showCallingPoints(array $points) : void {
?>
        <table class="calling_points">
<?php
        $service_property = null;
        foreach ($points as $i => $point) {
            if ($i !== 'dated_service') {
                $show_arrival = $i !== 0 && $point instanceof HasArrival && $point->getPublicArrival() !== null;
                $show_departure = $i !== count($points) - 2 && $point instanceof HasDeparture && $point->getPublicDeparture() !== null;

                if (isset($point->serviceProperty) && $point->serviceProperty != $service_property && $i !== count($points) - 2) {
                    $service_property = $point->serviceProperty;

                    if ($show_arrival && $point->isPublicCall()) {
?>
            <tr>
                <td><?= html($point->location->getShortName()) ?></td>
                <td><?= html($point->platform) ?></td>
                <td class="time"><?= $this->showTime($points['dated_service']->date, $this->getOriginPortion()->date, $point, false) ?></td>
                <td class="time"></td>
                <td><?= implode('<br/>', array_filter(array_map(
                        static fn(Activity $activity) => $activity->getDescription(), $point->activities))) ?></td>
            </tr>

<?php
                    }
                    $this->showServiceInformation($points['dated_service'], $point->serviceProperty);
                    if ($show_departure && $point->isPublicCall()) {
?>
            <tr>
                <td><?= html($point->location->getShortName()) ?></td>
                <td><?= html($point->platform) ?></td>
                <td class="time"></td>
                <td class="time"><?= $this->showTime($points['dated_service']->date, $this->getOriginPortion()->date, $point, true) ?></td>
                <td><?= implode('<br/>', array_filter(array_map(
                        static fn(Activity $activity) => $activity->getDescription(), $point->activities))) ?></td>
            </tr>
<?php
                    }
                } elseif ($point->isPublicCall()) {
?>
            <tr>
                <td><?= html($point->location->getShortName()) ?></td>
                <td><?= html($point->platform) ?></td>
                <td class="time"><?= $show_arrival ? $this->showTime($points['dated_service']->date, $this->getOriginPortion()->date, $point, false) : '' ?></td>
                <td class="time"><?= $show_departure ? $this->showTime($points['dated_service']->date, $this->getOriginPortion()->date, $point, true) : '' ?></td>
                <td><?= implode('<br/>', array_filter(array_map(
                        static fn(Activity $activity) => $activity->getDescription(), $point->activities))) ?></td>
            </tr>
<?php
                }
            }
        }
?>
        </table>
<?php
    }

    protected function showServiceInformation(DatedService $dated_service, ServiceProperty $service_property) : void {
?>
            <tr>
                <td colspan="5" class="train_info">
                    <?=
                        implode(
                            '<br/>'
                            , array_filter(
                                [
                                    $this->showRsidWithPortionDescription($service_property->rsid)
                                    , html(
                                        implode(
                                            ', '
                                            , array_filter(
                                                [
                                                    $service_property->trainCategory->getDescription(),
                                                    $service_property->identity !== '' ? 'ID ' . $service_property->identity : '',
                                                    $service_property->headcode !== '' ? 'Headcode ' . $service_property->headcode : '',
                                                ]
                                            )
                                        )
                                    ),
                                    $service_property->doo ? 'Driver-only operated' : '',
                                    html(
                                        implode(
                                            ', '
                                            , array_filter(
                                                [
                                                    $service_property->power->getDescription(),
                                                    $service_property->timingLoad ? 'Class ' . $service_property->timingLoad : '',
                                                    $service_property->speedMph ? html($service_property->speedMph . ' mph max') : '',
                                                ]
                                            )
                                        )
                                    ),
                                    html(
                                        implode(
                                            ', '
                                            , [
                                                $dated_service->service->shortTermPlanning->getDescription(),
                                                $dated_service->service->period->from->__toString() . ' to ' . $dated_service->service->period->to->__toString(),
                                                implode(array_intersect_key(['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'], array_filter($dated_service->service->period->weekdays))),
                                            ]
                                        )
                                    ),
                                    $service_property->showIcons(),
                                ]
                            )
                        )
                    ?>
                </td>
            </tr>
            <tr><th>Station</th><th>Pl.</th><th>Arrive</th><th>Depart</th><th>Notes</th></tr>
<?php
    }
}