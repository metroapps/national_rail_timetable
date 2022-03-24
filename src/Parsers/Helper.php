<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Parsers;

use function array_slice;

class Helper {
    public function parseLine(string $line, array $widths) : array {
        return $widths === []
            ? []
            : array_merge(
                [rtrim(substr($line, 0, $widths[0]))]
                , $this->parseLine(substr($line, $widths[0]), array_slice($widths, 1))
            );
    }
}