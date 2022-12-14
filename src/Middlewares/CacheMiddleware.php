<?php
declare(strict_types=1);

namespace Metroapps\NationalRailTimetable\Middlewares;

use DateTimeZone;
use Metroapps\NationalRailTimetable\Controllers\BoardQuery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Safe\DateTimeImmutable as SafeDateTimeImmutable;
use Teapot\StatusCode\Http;
use function str_replace;

class CacheMiddleware implements MiddlewareInterface {
    public ?BoardQuery $query = null;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface {
        return $this->addCacheHeader($handler->handle($request));
    }

    public function addCacheHeader(ResponseInterface $response) : ResponseInterface {
        if ($this->query === null || $response->getStatusCode() !== Http::OK) {
            return $response;
        }
        $response = $response->withAddedHeader('Cache-Control', 'public')->withAddedHeader('Cache-Control', 'max-age=7200');
        return $this->query->station !== null && $this->query->date === null
            ? $response->withAddedHeader('Cache-Control', 'public')->withAddedHeader(
                'Expires',
                str_replace(
                    '+0000',
                    'GMT',
                    (new SafeDateTimeImmutable('tomorrow'))->setTimezone(new DateTimeZone('UTC'))->format('r')
                )
            )
            : $response;
    }
}