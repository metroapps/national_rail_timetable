<?php
declare(strict_types = 1);

namespace Metroapps\NationalRailTimetable\Controllers;

use DateTimeZone;
use LogicException;
use Miklcct\RailOpenTimetableData\Models\Date;
use Miklcct\RailOpenTimetableData\Models\ServiceCancellation;
use Miklcct\RailOpenTimetableData\Repositories\ServiceRepositoryFactoryInterface;
use Metroapps\NationalRailTimetable\Views\ServiceView;
use Metroapps\NationalRailTimetable\Views\ViewMode;
use Miklcct\ThinPhpApp\Controller\Application;
use Miklcct\ThinPhpApp\Response\ViewResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Safe\DateTimeImmutable as SafeDateTimeImmutable;
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
        $path_info = explode('/', trim($request->getServerParams()['PATH_INFO'] ?? '', '/'));
        if (\Safe\preg_match('/^[A-Za-z]\d{5}$/', $path_info[0])) {
            $query['uid'] ??= $path_info[0];
        }
        if (\Safe\preg_match('/^[A-Za-z]{2}(\d{4}|\d{6})$/', $path_info[0])) {
            $query['rsid'] ??= $path_info[0];
        }
        if (isset($path_info[1])) {
            $query['date'] ??= $path_info[1];
        }
        if (!empty($query['date'])) {
            sscanf($query['date'], '%d-%d-%d', $year, $month, $day);
            $date = new Date($year, $month, $day);
        } else {
            $date = Date::today();
        }
        if (count(array_intersect_key(['uid' => null, 'rsid' => null], $query)) !== 1) {
            throw new LogicException('Either the UID or the RSID but not both must be specfified.');
        }
        $permanent_only = !empty($query['permanent_only']);
        $service = null;
        $service_repository = ($this->serviceRepositoryFactory)($permanent_only);
        if (isset($query['uid'])) {
            $service = $service_repository->getService($query['uid'], $date);
        }
        if (isset($query['rsid'])) {
            $service = $service_repository->getServiceByRsid($query['rsid'], $date)[0] ?? null;
        }

        if ($service === null) {
            throw new HttpException('The service cannot be found.', Http::NOT_FOUND);
        }
        if ($service->service instanceof ServiceCancellation) {
            throw new HttpException('The service has been STP cancelled.', Http::NOT_FOUND);
        }
        $service = $service_repository->getFullService($service);

        $response = ($this->viewResponseFactory)(
            new ServiceView(
                $this->streamFactory
                , $service
                , $permanent_only
                , $service_repository->getGeneratedDate()
                , $query['from'] ?? null === 'board' ? ViewMode::BOARD : ViewMode::TIMETABLE
            )
        );
        return !empty($query['date'])
            ? $response->withAddedHeader('Cache-Control', ['public', 'max-age=21600'])
            : $response->withAddedHeader('Cache-Control', 'public')->withAddedHeader(
                'Expires',
                str_replace(
                    '+0000',
                    'GMT',
                    (new SafeDateTimeImmutable('tomorrow'))->setTimezone(new DateTimeZone('UTC'))->format('r')
                )
            );
    }
}