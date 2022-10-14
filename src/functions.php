<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner;

use function array_slice;

/**
 * Rotate an array
 *
 * @param array $array
 * @param int $offset positive to rotate to the left, negative to the right
 * @return void
 */
function array_rotate(array $array, int $offset) : array {
    return array_merge(
        array_slice($array, $offset)
        , array_slice($array, 0, $offset)
    );
}

/**
 * Get the list of all TOCs in code => name format
 */
function get_all_tocs() : array {
    static $result;
    $result ??= json_decode(file_get_contents(__DIR__ . '/../resource/toc.json'), true);
    return $result;
}

/**
 * Get the full version of truncated station name
 */
function get_full_station_name(string $name) : string {
    static $mapping;
    $mapping ??= json_decode(file_get_contents(__DIR__ . '/../resource/long_station_names.json'), true);
    return $mapping[$name] ?? $name;
}