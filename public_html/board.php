<?php
declare(strict_types=1);
error_reporting(E_ALL);

use MongoDB\Client;
use Miklcct\NationalRailJourneyPlanner\Repositories\MongodbLocationRepository;
use Miklcct\NationalRailJourneyPlanner\Repositories\MongodbServiceRepository;
use Miklcct\NationalRailJourneyPlanner\Enums\TimeType;
use Miklcct\NationalRailJourneyPlanner\Models\ServiceCallWithDestination;
use function Miklcct\ThinPhpApp\Escaper\html;

require_once __DIR__ . '/../vendor/autoload.php';

set_time_limit(300);
ini_set('memory_limit', '4G');

$client = new Client(driverOptions: ['typeMap' => ['array' => 'array']]);
$database = $client->selectDatabase('national_rail');
$stations = new MongodbLocationRepository($database->selectCollection('locations'));
$timetable = new MongodbServiceRepository(
    $database->selectCollection('services')
    , $database->selectCollection('associations')
    , null
    , true
);

$board = $timetable->getDepartureBoard($_GET['station'], new DateTimeImmutable($_GET['from']), new DateTimeImmutable($_GET['to']), TimeType::PUBLIC_DEPARTURE);
if (isset($_GET['filter'])) {
    $_GET['filter'] = (array)$_GET['filter'];
    $board = $board->filter($_GET['filter'], TimeType::PUBLIC_ARRIVAL);
}

?>
<!DOCTYPE html>
<html>
    <head>
        <title>Departures</title>
    </head>
    <body>
        <table border="1">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Train number</th>
                    <th>Destination</th>
                    <th>Calling at</th>
                </tr>
            </thead>
            <tbody>
<?php
foreach ($board->calls as $service_call) {
    $portions_count = count($service_call->destinations);
    $portion_uids = array_keys($service_call->destinations);
?>
                <tr>
                    <td rowspan="<?= html($portions_count) ?>"><?= html($service_call->timestamp->format('H:i')) ?></td>
                    <td rowspan="<?= html($portions_count) ?>"><?= html(substr($service_call->serviceProperty->rsid, 0, 6)) ?></td>
<?php
    foreach ($portion_uids as $i => $uid) {
        if ($i > 0) {
?>
                </tr>
                <tr>
<?php
        }
?>
                    <td><?= html($service_call->destinations[$uid]->location->name) ?></td>
                    <td><?=
                        html(
                            implode(
                                ', '
                                , array_map(
                                    fn(ServiceCallWithDestination $service_call) : string => 
                                        sprintf(
                                            "%s (%s)"
                                            , $service_call->call->location->name
                                            , $service_call->timestamp->format('H:i')
                                        )
                                    , array_filter(
                                        $service_call->subsequentCalls
                                        , fn(ServiceCallWithDestination $service_call) : bool =>
                                            in_array($uid, array_keys($service_call->destinations), true)
                                    )
                                )
                            )
                        )
                    ?></td>
<?php
    }
?>
                </tr>
<?php
}
?>
            </tbody>
        </table>
    </body>
</html>