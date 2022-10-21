<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Views;

use Miklcct\NationalRailTimetable\Models\Location;
use Miklcct\NationalRailTimetable\Models\ServiceCallWithDestination;
use Miklcct\NationalRailTimetable\Models\ServiceCallWithDestinationAndCalls;
use Miklcct\ThinPhpApp\View\PhpTemplate;
use Psr\Http\Message\StreamFactoryInterface;

class TimetableView extends PhpTemplate {
    public function __construct(
        StreamFactoryInterface $streamFactory
        , protected readonly array $timetables
        , protected readonly array $filterCrs
    ) {
        parent::__construct($streamFactory);
    }

    protected function getPathToTemplate(): string {
        return __DIR__ . '/../../resource/templates/timetable.phtml';
    }

    protected function getGroupDestinations(array $timetable) : array {
        ['stations' => $stations, 'calls' => $calls] = $timetable;
        return array_values(
            array_unique(
                array_map(
                    static fn(Location $location) => $location->getShortName()
                    // filter the stations
                    , array_filter(
                        array_slice($stations, 1)
                        , static fn(Location $location) 
                            // which doesn't have a service
                            => array_filter(
                                $calls[0]
                                , static fn(ServiceCallWithDestinationAndCalls $call) =>
                                    // calling at the location specified
                                    array_filter(
                                        $call->subsequentCalls
                                        , static fn(ServiceCallWithDestination $subsequent_call) =>
                                            $subsequent_call->call->location->crsCode === $location->crsCode
                                            // and have further calls beyond it
                                            && array_filter(
                                                $call->subsequentCalls
                                                , static fn(ServiceCallWithDestination $further_call) =>
                                                    array_intersect_key($subsequent_call->destinations, $further_call->destinations)
                                                    && $further_call->timestamp > $subsequent_call->timestamp
                                            ) !== []
                                    ) !== []
                            ) === []
                    )
                )
            )
        );
    }
}