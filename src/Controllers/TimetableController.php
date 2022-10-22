<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Controllers;

use Miklcct\NationalRailTimetable\Enums\TimeType;
use Miklcct\NationalRailTimetable\Models\Date;
use Miklcct\NationalRailTimetable\Models\Location;
use Miklcct\NationalRailTimetable\Models\LocationWithCrs;
use Miklcct\NationalRailTimetable\Models\Time;
use Miklcct\NationalRailTimetable\Repositories\LocationRepositoryInterface;
use Miklcct\NationalRailTimetable\Repositories\ServiceRepositoryFactoryInterface;
use Miklcct\NationalRailTimetable\Views\TimetableView;
use Miklcct\ThinPhpApp\Controller\Application;
use Miklcct\ThinPhpApp\Response\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Safe\DateTimeImmutable;
use Teapot\HttpException;
use Teapot\StatusCode\WebDAV;

class TimetableController extends Application {
    use QueryTrait;

    // this number must be greater than the maximum number of calls for a train
    private const MULTIPLIER = 1000;

    public function __construct(
        private readonly ViewResponseFactoryInterface $viewResponseFactory
        , private readonly StreamFactoryInterface $streamFactory
        , private readonly ServiceRepositoryFactoryInterface $serviceRepositoryFactory
        , private readonly LocationRepositoryInterface $locationRepository
    ) {}

    public function run(ServerRequestInterface $request) : ResponseInterface {
        $query = $request->getQueryParams();

        $station = $this->getQueryStation($query['station']);
        if ($station === null) {
            throw new HttpException('A station must be specified.', WebDAV::UNPROCESSABLE_ENTITY);
        }
        $date = Date::fromDateTimeInterface(new DateTimeImmutable($query['date'] ?: 'now'));
        $board = ($this->serviceRepositoryFactory)(false)->getDepartureBoard(
            $station->getCrsCode()
            , $date->toDateTimeImmutable()
            , $date->toDateTimeImmutable(new Time(28, 30))
            , TimeType::PUBLIC_DEPARTURE
        );
        /** @var Location[] $filter */
        $filter = array_filter(
            array_map(
                $this->getQueryStation(...)
                , (array)($query['filter'] ?? [])
            )
        );
        if ($filter !== []) {
            $board = $board->filterByDestination(
                array_map(static fn(LocationWithCrs $location) => $location->getCrsCode(), $filter)
                , true
            );
        }

        return ($this->viewResponseFactory)(
            new TimetableView(
                $this->streamFactory
                , $station
                , $date
                , $board->groupServices()
                , $filter
            )
        );
    }
}