<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Parsers;

use Miklcct\NationalRailJourneyPlanner\Models\Station;
use Miklcct\NationalRailJourneyPlanner\Models\Stations;
use function fgets;
use function str_starts_with;

class StationParser {
    public function __construct(private readonly Helper $helper) {
    }

    public function parseFile($file) : Stations {
        // skip headers
        do {
            $line = fgets($file);
        } while (str_starts_with($line, '/!!'));

        // skip file header record
        fgets($file);

        // parse stations
        /** @var array<string, Station> $stationsByCrs */
        $stationsByCrs = [];
        /** @var array<string, Station> $stationsByTiploc */
        $stationsByTiploc = [];
        /** @var array<string, Station> $stationsByName */
        $stationsByName = [];

        for (
            $line = fgets($file);
            str_starts_with($line, 'A');
            $line = fgets($file)
        ) {
            $columns = $this->helper->parseLine(
                $line, [1, 4, 26, 4, 1, 7, 3, 3, 3, 5, 1, 5, 2, 1, 1, 11, 3]
            );
            $station = new Station(
                crsCode: $columns[8]
                , name: $columns[2]
                , interchange: (int)$columns[4]
                , easting: ((int)$columns[9] - 10000) * 100
                , northing: ((int)$columns[11] - 60000) * 100
                , minimumConnectionTime: (int)$columns[12]
            );
            $crsAndMinorCrsBeingTheSame = $columns[6] === $columns[8];
            if ($crsAndMinorCrsBeingTheSame) {
                $stationsByTiploc[$columns[5]] = $station;
                if (
                    !isset($stationsByCrs[$station->crsCode])
                    || $stationsByCrs[$station->crsCode]->interchange === 9
                        && $station->interchange !== 9
                ) {
                    $stationsByCrs[$station->crsCode] = $station;
                    $stationsByName[$station->name] = $station;
                }
            }
        }

        // parse aliases
        while (str_starts_with($line, 'L')) {
            $columns = $this->helper->parseLine($line, [1, 4, 26, 5, 26, 20]);
            if (isset($stationsByName[$columns[2]])) {
                $stationsByName[$columns[4]] = $stationsByName[$columns[2]];
            }
            $line = fgets($file);
        }

        return new Stations($stationsByCrs, $stationsByName, $stationsByTiploc);
    }
}