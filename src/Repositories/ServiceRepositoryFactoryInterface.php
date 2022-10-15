<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Repositories;

interface ServiceRepositoryFactoryInterface {
    public function __invoke(bool $permanentOnly = false) : ServiceRepositoryInterface;
}