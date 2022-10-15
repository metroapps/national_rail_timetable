<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Views;

use DateTimeImmutable;
use DateTimeZone;
use LogicException;
use Miklcct\NationalRailTimetable\Enums\TimeType;
use Miklcct\NationalRailTimetable\Models\DatedService;
use Miklcct\NationalRailTimetable\Models\Points\HasArrival;
use Miklcct\NationalRailTimetable\Models\Points\HasDeparture;
use Miklcct\NationalRailTimetable\Models\Points\TimingPoint;
use Miklcct\NationalRailTimetable\Models\Service;
use Miklcct\NationalRailTimetable\Models\ServiceProperty;
use Miklcct\ThinPhpApp\View\PhpTemplate;
use Psr\Http\Message\StreamFactoryInterface;

use function Miklcct\ThinPhpApp\Escaper\html;

class ServiceView extends PhpTemplate {
    public function __construct(
        StreamFactoryInterface $streamFactory
        , public readonly DatedService $datedService
        , public readonly bool $permanentOnly
    ) {
        parent::__construct($streamFactory);
    }

    public function getPathToTemplate() : string {
        return __DIR__ . '/../../resource/templates/service.phtml';
    }

    public function getTitle() : string {
        $service = $this->datedService->service;
        if (!$service instanceof Service) {
            throw new LogicException('The service does not run on the day.');
        }
        return sprintf(
            '%s %s %s %s to %s'
            , $this->datedService->date
            , substr($service->getOrigin()->serviceProperty->rsid, 0, 6)
            , $service->getOrigin()->getPublicOrWorkingDeparture()
            , $service->getOrigin()->location->getShortName()
            , $service->getDestination()->location->getShortName()
        );
    }

    public function showRsidWithPortionDescription(string $rsid) : string {
        $main = substr($rsid, 0, 6);
        $suffix = (int)substr($rsid, 6, 2);
        if ($suffix === 0) {
            return '<span class="train_number">' . html($main) . '</span>';
        }
        $portions = [];
        for ($i = 0; $i < 8; ++$i) {
            if ($suffix & (1 << $i)) {
                $portions[] = $i + 1;
            }
        }
        return '<span class="train_number">' . html($main) . '</span>' . ' (Portion ' . implode(' & ', $portions) . ')';
    }

    public function showTime(TimingPoint $point, bool $departure_to_arrival_board) : string {
        $time = $departure_to_arrival_board 
            ? ($point instanceof HasDeparture ? $point->getPublicDeparture() : null) 
            : ($point instanceof HasArrival ? $point->getPublicArrival() : null);
        if ($time === null) {
            return '';
        }
        static $timezone = new DateTimeZone('Europe/London');
        if ($point->location->crsCode !== null) {
            return sprintf(
                '<a href="%s">%s</a>'
                , $this->getBoardLink(
                    $this->datedService->date->toDateTimeImmutable($time, $timezone)
                    , $point->location->crsCode
                    , $departure_to_arrival_board ? 'arrivals' : 'departures'
                )
                , $time
            );
        }
        return $time->__toString();
    }

    public function getBoardLink(DateTimeImmutable $timestamp, string $crs, string $mode) {
        return '/index.php?' . http_build_query(
            [
                'station' => $crs,
                'from' => $timestamp->format('c'),
                'connecting_time' => $timestamp->format('c'),
                'connecting_toc' => $this->datedService->service->toc,
                'permanent_only' => $this->permanentOnly ?? '',
                'mode' => $mode,
            ]
        );
    }


    public function showServiceInformation(ServiceProperty $service_property) {
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
                                                    strlen($service_property->identity) ? 'ID ' . $service_property->identity : '',
                                                    strlen($service_property->headcode) ? 'Headcode ' . $service_property->headcode : '',
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