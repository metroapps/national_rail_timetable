<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Controllers;

use LogicException;
use Miklcct\RailOpenTimetableData\Models\Date;
use Miklcct\RailOpenTimetableData\Models\ServiceCancellation;
use Miklcct\RailOpenTimetableData\Repositories\ServiceRepositoryFactoryInterface;
use Miklcct\NationalRailTimetable\Views\ServiceView;
use Miklcct\NationalRailTimetable\Views\ViewMode;
use Miklcct\ThinPhpApp\Controller\Application;
use Miklcct\ThinPhpApp\Response\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teapot\HttpException;
use Teapot\StatusCode\Http;

class ServiceController extends Application {
    public function __construct(
        private readonly ViewResponseFactoryInterface $viewResponseFactory
        , private readonly StreamFactoryInterface $streamFactory
        , private readonly ServiceRepositoryFactoryInterface $serviceRepositoryFactory
    ) {}
    
    public function run(ServerRequestInterface $request) : ResponseInterface {
        $query = $request->getQueryParams();
        sscanf($query['date'], '%d-%d-%d', $year, $month, $day);
        if (count(array_intersect_key(['uid' => null, 'rsid' => null], $query)) !== 1) {
            throw new LogicException('Either the UID or the RSID but not both must be specfified.');
        }
        $permanent_only = !empty($query['permanent_only']);
        $service = null;
        $service_repository = ($this->serviceRepositoryFactory)($permanent_only);
        if (isset($query['uid'])) {
            $service = $service_repository->getService($query['uid'], new Date($year, $month, $day));
        }
        if (isset($query['rsid'])) {
            $service = $service_repository->getServiceByRsid($query['rsid'], new Date($year, $month, $day))[0] ?? null;
        }

        if ($service === null) {
            throw new HttpException('The service cannot be found.', Http::NOT_FOUND);
        }
        if ($service->service instanceof ServiceCancellation) {
            throw new HttpException('The service has been STP cancelled.', Http::NOT_FOUND);
        }
        $service = $service_repository->getFullService($service);

        return ($this->viewResponseFactory)(
            new ServiceView(
                $this->streamFactory
                , $service
                , $permanent_only
                , $service_repository->getGeneratedDate()
                , $query['from'] === 'board' ? ViewMode::BOARD : ViewMode::TIMETABLE
            )
        )->withAddedHeader('Cache-Control', ['public', 'max-age=21600']);
    }
}