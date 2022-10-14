<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable\Repositories;

use MongoDB\Collection;
use Miklcct\NationalRailTimetable\Models\FixedLink;

class MongodbFixedLinkRepository implements FixedLinkRepositoryInterface {
    public function __construct(private readonly Collection $collection) {
    }

    /**
     * @param FixedLink[] $fixed_links
     */
    public function insert(array $fixed_links) : void {
        if ($fixed_links !== []) {
            $this->collection->insertMany($fixed_links);
        }
    }

    /**
     * @return FixedLink[]
     */
    public function get(?string $origin_crs, ?string $destination_crs) : array {
        $query = [];
        if ($origin_crs !== null) {
            $query['origin.crsCode'] = $origin_crs;
        }
        if ($destination_crs !== null) {
            $query['destination.crsCode'] = $destination_crs;
        }
        return $this->collection->find($query)->toArray();
    }

    public function addIndexes() : void {
        $this->collection->createIndexes(
            [
                ['key' => ['origin.crsCode' => 1]],
                ['key' => ['destination.crsCode' => 1]],
            ]
        );
    }
}