<?php
declare(strict_types=1);

namespace Miklcct\NationalRailTimetable;

use DateTimeImmutable;
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
use Miklcct\NationalRailTimetable\Models\Date;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Psr16Cache;

use function DI\autowire;
use function array_slice;
use function Miklcct\ThinPhpApp\Escaper\html;

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

/**
 * Return the 2 databases defined in the application
 * 
 * The database at index 0 is the one currently used, while the one at index 1 is available for importing new data
 * 
 * @return Database[]
 */
function get_databases() : array {
    $container = get_container();
    /** @var Config */
    $config = $container->get(Config::class);
    $databases = array_map(
        fn (string $name) => $container->get(Client::class)->selectDatabase($name)
        , [$config->databaseName, $config->alternativeDatabaseName]
    );
    /** @var (Date|null)[] */
    $generated_dates = array_map(
        fn (Database $database) => $database->selectCollection('metadata')->findOne(['generated' => ['$exists' => true]])?->generated
        , $databases
    );
    if ($generated_dates[0]?->toDateTimeImmutable() < $generated_dates[1]?->toDateTimeImmutable()) {
        return [$databases[1], $databases[0]];
    }
    return $databases;
}

function get_container() : ContainerInterface {
    static $container;
    if ($container === null) {
        $container = (new ContainerBuilder())->addDefinitions(
            [
                Client::class => static function(ContainerInterface $container) : Client {
                    $config = $container->get(Config::class);
                    return new Client(uri: $config->mongodbUri ?? 'mongodb://127.0.0.1/', uriOptions: $config->mongodbUriOptions ?? [], driverOptions: ['typeMap' => ['array' => 'array']]);
                },
                Config::class => static fn() : Config => require __DIR__ . '/../config.php',
                Database::class => static function() {
                    return get_databases()[0];
                },
                CacheInterface::class => 
                    static fn() => new Psr16Cache(new PhpFilesAdapter('', 0, __DIR__ . '/../var/cache', true)),
                LocationRepositoryInterface::class => autowire(MongodbLocationRepository::class),
                ServiceRepositoryFactoryInterface::class => 
                    static fn(ContainerInterface $container) => new MongodbServiceRepositoryFactory($container->get(Database::class), $container->get(CacheInterface::class)),
                FixedLinkRepositoryInterface::class => autowire(MongodbFixedLinkRepository::class),
                ViewResponseFactoryInterface::class => autowire(ViewResponseFactory::class),
                ResponseFactoryInterface::class => autowire(ResponseFactory::class),
            ]
        )
        ->build();
    }
    return $container;
}
