<?php
declare(strict_types = 1);

namespace Metroapps\NationalRailTimetable\Config;
class Config {
    public function __construct(
        public readonly ?string $mongodbUri
        , public readonly ?array $mongodbUriOptions
        , public readonly string $databaseName
        , public readonly string $alternativeDatabaseName
        , public readonly string $siteName
    ) {
        
    }
}