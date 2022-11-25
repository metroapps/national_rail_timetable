<?php
declare(strict_types = 1);

namespace Metroapps\NationalRailTimetable\Views;

use DateInterval;
use DateTimeImmutable;
use Metroapps\NationalRailTimetable\Controllers\BoardQuery;
use Miklcct\RailOpenTimetableData\Enums\Activity;
use Miklcct\RailOpenTimetableData\Enums\Catering;
use Miklcct\RailOpenTimetableData\Enums\Mode;
use Miklcct\RailOpenTimetableData\Enums\Reservation;
use Miklcct\RailOpenTimetableData\Models\Date;
use Miklcct\RailOpenTimetableData\Models\LocationWithCrs;
use Miklcct\RailOpenTimetableData\Models\ServiceCall;
use Miklcct\RailOpenTimetableData\Models\ServiceProperty;
use function implode;
use function Miklcct\RailOpenTimetableData\get_all_tocs;
use function Miklcct\ThinPhpApp\Escaper\html;
use function Safe\json_decode;

function show_time(DateTimeImmutable $timestamp, Date $base, string $link = null) : string {
    $interval = $base->toDateTimeImmutable()->diff($timestamp->setTime(0, 0));
    $day_offset = $interval->days * ($interval->invert ? -1 : 1);
    $time_string = $timestamp->format('H:i') 
        . ((int)$timestamp->format('s') > 30 ? '½' : '');
    /** @noinspection HtmlUnknownTarget */
    return ($link !== null
        ? sprintf('<a href="%s">%s</a>', html($link), html($time_string))
        : html($time_string)
    )
        . ($day_offset 
            ? sprintf(
                '<sup class="day_offset"><abbr title="%s">%+d</abbr></sup>'
                , match ($day_offset) {
                    1 => 'next day',
                    -1 => 'previous day',
                    default => sprintf('%+d days', $day_offset)
                }, $day_offset) 
            : ''
        );
}

/**
 * @param Activity[] $activities
 */
function show_activities(array $activities) : string {
    return implode(
        ''
        , array_map(
            static fn(Activity $activity) =>
                match ($activity) {
                    Activity::REQUEST_STOP => '<abbr class="activity" title="request stop">x</abbr>',
                    Activity::PICK_UP => '<abbr class="activity" title="pick up only">u</abbr>',
                    Activity::SET_DOWN => '<abbr class="activity" title="set down only">s</abbr>',
                    default => ''
                }
            , $activities
        )
    );
}

function show_script_tags(string $entry_point, bool $recursed = false) : void {
    if (is_development()) {
?>
        <script type="module" src="http://localhost:5173/@vite/client"></script>
        <script type="module" src="http://localhost:5173/<?= html($entry_point) ?>"></script>
<?php
    } else {
        $manifest = json_decode(file_get_contents(__DIR__ . '/../../public_html/dist/manifest.json'), true);
        if (!$recursed) {
?>
        <script type="module" src="/dist/<?= html($manifest[$entry_point]['file']) ?>"></script>
<?php
        }
        foreach ($manifest[$entry_point]['css'] ?? [] as $css_file) {
?>
        <link rel="stylesheet" href="/dist/<?= html($css_file) ?>">
<?php
        }
        foreach ($manifest[$entry_point]['imports'] ?? [] as $recursion) {
            show_script_tags($recursion, true);
        }
?>
<?php
    }
}

function show_toc(string $toc) : string {
    return sprintf('<abbr title="%s">%s</abbr>', html(get_all_tocs()[$toc] ?? ''), html($toc));
}

function show_facilities(ServiceCall $service_call) : string {
    return show_facility_icon($service_call->mode) . show_service_property_icons($service_call->serviceProperty);
}

function get_arrival_link(string $url, ServiceCall $service_call, BoardQuery $query) : ?string {
    $location = $service_call->call->location;
    if (!$location instanceof LocationWithCrs) {
        return null;
    }
    return (
        new BoardQuery(
            $query->arrivalMode
            , $location
            , []
            , []
            , Date::fromDateTimeInterface(
                $service_call->timestamp->sub(
                    new DateInterval($query->arrivalMode ? 'PT4H30M' : 'P0D')
                )
            )
            , $service_call->timestamp
            , $service_call->toc
            , $query->permanentOnly
        )
    )->getUrl($url);
}

function show_facility_icon(Catering|Mode|Reservation $item) : string {
    return match($item) {
        Catering::BUFFET => '<img class="facility" src="/images/buffet.png" alt="buffet" title="Buffet" />',
        Catering::FIRST_CLASS_RESTAURANT => '<img class="facility" src="/images/first_class_restaurant.png" alt="first class restaurant" title="Restaurant for first class passengers" />',
        Catering::HOT_FOOD => '<img class="facility" src="/images/first_class_restaurant.png" alt="hot food" title="Hot food" />',
        Catering::RESTAURANT => '<img class="facility" src="/images/restaurant.png" alt="restaurant" title="Restaurant" />',
        Catering::TROLLEY => '<img class="facility" src="/images/trolley.png" alt="restaurant" title="Trolley" />',
        Mode::BUS => '<img class="mode" src="/images/bus.png" alt="bus" title="Bus service" /><br/>',
        Mode::SHIP => '<img class="mode" src="/images/ship.png" alt="ship" title="Ferry service" /><br/>',
        Reservation::AVAILABLE => '<img class="facility" src="/images/reservation_available.png" alt="reservation available" title="Reservation available" />',
        Reservation::RECOMMENDED => '<img class="facility" src="/images/reservation_recommended.png" alt="reservation recommended" title="Reservation recommended" />',
        Reservation::COMPULSORY => '<img class="facility" src="/images/reservation_compulsory.png" alt="reservation compulsory" title="Reservation compulsory" />',
        default => '',
    };
}

function show_service_property_icons(ServiceProperty $service_property) : string {
    $result = '';
    foreach ($service_property->caterings as $catering) {
        $result .= show_facility_icon($catering);
    }
    $result .= show_facility_icon($service_property->reservation);
    if ($service_property->seatingClasses[1]) {
        $result .= '<img class="facility" src="/images/first_class.png" alt="first class" title="First class available" />';
    }
    if (array_filter($service_property->sleeperClasses)) {
        $result .= '<img class="facility" src="/images/sleeper.png" alt="sleeper" title="Sleeper available" />';
    }
    return $result;
}