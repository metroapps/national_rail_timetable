<?php
declare(strict_types=1);

namespace Miklcct\NationalRailJourneyPlanner\Models;

class TiplocLocation extends Location {
    use BsonSerializeTrait;

    public function __construct(
        string $tiploc
        , ?string $crsCode
        , string $name
        , public readonly ?int $stanox
    ) {
        parent::__construct($tiploc, $crsCode, $name);
    }
}