<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Views;

use DateTimeImmutable;
use Miklcct\NationalRailTimetable\Enums\Activity;
use Miklcct\NationalRailTimetable\Models\Date;
use function implode;
use function Miklcct\NationalRailTimetable\is_development;
use function Miklcct\ThinPhpApp\Escaper\html;

function show_time(DateTimeImmutable $timestamp, Date $base, string $link = null) : string {
    $interval = $base->toDateTimeImmutable()->diff($timestamp->setTime(0, 0));
    $day_offset = $interval->days * ($interval->invert ? -1 : 1);
    $time_string = $timestamp->format('H:i') 
        . ((int)$timestamp->format('s') > 30 ? '½' : '');
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

function show_script_tags(string $basename) : void {
    if (is_development()) {
?>
        <script type="module" src="http://localhost:5173/@vite/client"></script>
        <script type="module" src="http://localhost:5173/<?= html($basename) ?>.ts"></script>
<?php
    } else {
        $manifest = json_decode(file_get_contents(__DIR__ . '/../../public_html/dist/manifest.json'), true);
?>
        <script type="module" src="/dist/<?= html($manifest["$basename.ts"]['file']) ?>"></script>
        <link rel="stylesheet" href="/dist/<?= html($manifest["$basename.css"]['file']) ?>" />
<?php
    }
}