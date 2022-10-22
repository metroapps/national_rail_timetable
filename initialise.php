<?php
declare(strict_types = 1);

use DI\ContainerBuilder;
use Http\Factory\Guzzle\ResponseFactory;
use Http\Factory\Guzzle\StreamFactory;
use Miklcct\NationalRailTimetable\Config\Config;
use Miklcct\NationalRailTimetable\Models\Date;
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
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Teapot\HttpException;
use Whoops\Handler\Handler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use function DI\autowire;

/**
 * Return the 2 databases defined in the application
 * 
 * The database at index 0 is the one currently used, while the one at index 1 is available for importing new data
 * 
 * @return Database[]
 */
function get_databases() : array {
    $container = get_container();
    /** @var Config $config */
    $config = $container->get(Config::class);
    $databases = array_map(
        static fn (string $name) => $container->get(Client::class)->selectDatabase($name)
        , [$config->databaseName, $config->alternativeDatabaseName]
    );
    /** @var (Date|null)[] $generated_dates */
    $generated_dates = array_map(
        static fn (Database $database) => $database->selectCollection('metadata')->findOne(['generated' => ['$exists' => true]])?->generated
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
                Config::class => static fn() : Config => require __DIR__ . '/config.php',
                Database::class => static function() {
                    return get_databases()[0];
                },
                CacheInterface::class => 
                    static fn() => new Psr16Cache(new PhpFilesAdapter('', 0, __DIR__ . '/var/cache', true)),
                LocationRepositoryInterface::class => autowire(MongodbLocationRepository::class),
                ServiceRepositoryFactoryInterface::class => 
                    static fn(ContainerInterface $container) => new MongodbServiceRepositoryFactory($container->get(Database::class), $container->get(CacheInterface::class)),
                FixedLinkRepositoryInterface::class => autowire(MongodbFixedLinkRepository::class),
                ViewResponseFactoryInterface::class => autowire(ViewResponseFactory::class),
                ResponseFactoryInterface::class => autowire(ResponseFactory::class),
                StreamFactoryInterface::class => autowire(StreamFactory::class),
            ]
        )
        ->build();
    }
    return $container;
}

require_once __DIR__ . '/vendor/autoload.php';

$whoops = new Run;
$pretty_page_handler = new PrettyPageHandler;
$pretty_page_handler->setEditor(PrettyPageHandler::EDITOR_PHPSTORM);
$whoops->pushHandler($pretty_page_handler);
$whoops->pushHandler(
    new class extends Handler {
        public function handle() : void {
            $exception = $this->getException();
            if ($exception instanceof HttpException) {
                $this->getRun()->sendHttpCode($exception->getCode());
            }
        }
    }
);
$whoops->register();

error_reporting(E_ALL);
set_time_limit(300);
ini_set('memory_limit', '4G');
date_default_timezone_set('Europe/London');