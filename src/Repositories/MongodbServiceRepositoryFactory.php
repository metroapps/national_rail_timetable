<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Repositories;

use MongoDB\Collection;

class MongodbServiceRepositoryFactory implements ServiceRepositoryFactoryInterface {
    public function __construct(
        private readonly Collection $servicesCollection
        , private readonly Collection $associationsCollection
        , private readonly ?DepartureBoardsCacheInterface $departureBoardsCache = null
    ) {}

    public function __invoke(bool $permanentOnly = false): ServiceRepositoryInterface {
        return new MongodbServiceRepository($this->servicesCollection, $this->associationsCollection, $this->departureBoardsCache, $permanentOnly);
    }
}