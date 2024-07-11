<?php
declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateData extends Command
{
    private const TIMETABLE_FILE = 'timetable.zip';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-data {--skip-download}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the timetable database';

    /**
     * Execute the console command.
     */
    public function handle() {
        $storage = Storage::disk('local');
        $path = $storage->path(self::TIMETABLE_FILE);
        if (!$this->option('skip-download')) {
            $this->info('Obtaining token...');
            $token = Http::asForm()->post(
                'https://opendata.nationalrail.co.uk/authenticate',
                [
                    'username' => config('services.national_rail.username'),
                    'password' => config('services.national_rail.password')
                ]
            )->object()->token;
            $this->info("Token: $token", OutputInterface::VERBOSITY_VERY_VERBOSE);
            $this->info('Downloading timetable...');
            $storage->put(
                self::TIMETABLE_FILE,
                Http::timeout(0)->withHeader('X-Auth-Token', $token)
                    ->get('https://opendata.nationalrail.co.uk/api/staticfeeds/3.0/timetable')
                    ->toPsrResponse()
                    ->getBody()
            );
            $this->info("Timetable downloaded to $path");
        }
        $result = Process::timeout(0)->tty()
            ->env([
                'DATABASE_HOSTNAME' => config('database.connections.mysql.host'),
                'DATABASE_PORT' => config('database.connections.mysql.port'),
                'DATABASE_NAME' => config('database.connections.mysql.database'),
                'DATABASE_USERNAME' => config('database.connections.mysql.username'),
                'DATABASE_PASSWORD' => config('database.connections.mysql.password'),
            ])
            ->run("yarn run dtd2mysql --timetable $path");
        if ($result->failed()) {
            throw new RuntimeException("Failed to run dtd2mysql with exit code {$result->exitCode()}");
        }
    }
}
