<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Repositories;

use MongoDB\Collection;
use MongoDB\Database;

class MongodbServiceRepositoryFactory implements ServiceRepositoryFactoryInterface {
    public function __construct(
        private readonly Database $database
        , private readonly ?DepartureBoardsCacheInterface $departureBoardsCache = null
    ) {}

    public function __invoke(bool $permanentOnly = false): ServiceRepositoryInterface {
        return new MongodbServiceRepository($this->database, $this->departureBoardsCache, $permanentOnly);
    }
}