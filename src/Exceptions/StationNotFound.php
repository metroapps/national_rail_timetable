<?php
declare(strict_types=1);

namespace Metroapps\NationalRailTimetable\Exceptions;

use Teapot\HttpException;
use Teapot\StatusCode\Http;

class StationNotFound extends HttpException {
    public function __construct(string $input) {
        parent::__construct("The station \"$input\" cannot be found.", Http::NOT_FOUND);
    }
}