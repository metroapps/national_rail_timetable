<?php
declare(strict_types = 1);

namespace Miklcct\NationalRailTimetable\Config;

use MongoDB\Database;

class Config {
    public function __construct(
        public readonly ?string $mongodbUri
        , public readonly ?array $mongodbUriOptions
        , public readonly string $databaseName
    ) {
        
    }
}