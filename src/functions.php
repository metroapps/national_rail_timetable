<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable;

use Psr\Container\ContainerInterface;
use DI\ContainerBuilder;
use Http\Factory\Guzzle\ResponseFactory;
use Miklcct\NationalRailTimetable\Config\Config;
use Miklcct\NationalRailTimetable\Repositories\FixedLinkRepositoryInterface;
use Miklcct\NationalRailTimetable\Repositories\LocationRepositoryInterface;
use Miklcct\NationalRailTimetable\Repositories\MongodbFixedLinkRepository;
use Miklcct\NationalRailTimetable\Repositories\MongodbLocationRepository;
use Miklcct\NationalRailTimetable\Repositories\MongodbServiceRepositoryFactory;
use Miklcct\NationalRailTimetable\Repositories\ServiceRepositoryFactoryInterface;
use Miklcct\ThinPhpApp\Response\ViewResponseFactory;
use Miklcct\ThinPhpApp\Response\ViewResponseFactoryInterface;
use MongoDB\Client;
use MongoDB\Database;
use Psr\Http\Message\ResponseFactoryInterface;

use function DI\autowire;
use function array_slice;

/**
 * Rotate an array
 *
 * @param array $array
 * @param int $offset positive to rotate to the left, negative to the right
 * @return void
 */
function array_rotate(array $array, int $offset) : array {
    return array_merge(
        array_slice($array, $offset)
        , array_slice($array, 0, $offset)
    );
}

/**
 * Get the list of all TOCs in code => name format
 */
function get_all_tocs() : array {
    static $result;
    $result ??= json_decode(file_get_contents(__DIR__ . '/../resource/toc.json'), true);
    return $result;
}

/**
 * Get the full version of truncated station name
 */
function get_full_station_name(string $name) : string {
    static $mapping;
    $mapping ??= json_decode(file_get_contents(__DIR__ . '/../resource/long_station_names.json'), true);
    return $mapping[$name] ?? $name;
}

function get_container() : ContainerInterface {
    static $container;
    if ($container === null) {
        $container = (new ContainerBuilder())->addDefinitions(
            [
                Config::class => static fn() : Config => require __DIR__ . '/../config.php',
                Database::class => 
                    static function(ContainerInterface $container) {
                        $config = $container->get(Config::class);
                        return (new Client(uri: $config->mongodbUri ?? 'mongodb://127.0.0.1/', uriOptions: $config->mongodbUriOptions ?? [], driverOptions: ['typeMap' => ['array' => 'array']]))->selectDatabase($config->databaseName);
                    },
                LocationRepositoryInterface::class => 
                    static fn(ContainerInterface $container) => new MongodbLocationRepository($container->get(Database::class)->selectCollection('locations')),
                ServiceRepositoryFactoryInterface::class =>
                    static function(ContainerInterface $container) {
                        /** @var Database */
                        $database = $container->get(Database::class);
                        return new MongodbServiceRepositoryFactory($database->selectCollection('services'), $database->selectCollection('associations'));
                    },
                FixedLinkRepositoryInterface::class =>
                    static fn(ContainerInterface $container) => new MongodbFixedLinkRepository($container->get(Database::class)->selectCollection('fixed_links')),
                ViewResponseFactoryInterface::class => autowire(ViewResponseFactory::class),
                ResponseFactoryInterface::class => autowire(ResponseFactory::class),
            ]
        )
        ->build();
    }
    return $container;
}
