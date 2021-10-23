<?php

namespace ThomasMarinissen\LaravelVercel\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Class Commands
 */
class InstallCommands extends Command
{
    /**
     * The command's signature.
     *
     * @var string
     */
    public $signature = 'vercel:install {--php-version= : PHP version (supported version 7.4 and 8.0)}';

    /**
     * The command's description.
     *
     * @var string
     */
    public $description = 'Install Vercel';

    /**
     * @var null|Filesystem
     */
    private $disk = null;

    /**
     * @var Repository
     */
    private $config;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Repository $config)
    {
        // call the parent
        parent::__construct();

        // set the config repository
        $this->config = $config;
    }

    /**
     * Handle the installation command
     */
    public function handle(): void
    {
        // get the PHP runtime to install
        $runtime = $this->choice('What PHP runtime would you like to use?', $this->config->get('vercel.runtimes'), '8.0');

        // load the stub
        $vercelJsonStub = $this->disk()->get($this->stub('vercel.json'));

        // set the correct vercel build
        $vercelJsonStub = str_replace('{{ runtime }}', $runtime, $vercelJsonStub);

        // store the vercel.json file
        $this->disk()->put('vercel.json', $vercelJsonStub);

        // make sure that the other stubs are available
        $this->addStubs();
    }

    /**
     * Add the stubs
     *
     * @throws FileNotFoundException
     */
    private function addStubs(): void
    {
        // get access to the disk
        $disk = $this->disk();

        // make sure that the api directory is available
        if (!$disk->exists('api')) {
            $disk->makeDirectory('api');
        }

        // add the files
        $disk->put('api/index.php', $disk->get($this->stub('api/index.php')));
        $disk->put('.vercelignore', $disk->get($this->stub('.vercelignore')));
    }

    /**
     * Get the disk
     *
     * @return Filesystem               The file system class
     */
    private function disk(): Filesystem
    {
        // if the disk has been requested before, return it
        if (!is_null($this->disk)) {
            return $this->disk;
        }

        // create the disk, set it and return it
        return $this->disk = Storage::build([
            'driver' => 'local',
            'root' => $this->laravel->basePath(),
        ]);
    }

    /**
     * Get the path to a stub
     *
     * @param string            $path The stub to get the path for
     * @return string           The full stub path
     */
    private function stub(string $path): string
    {
        return $this->config->get('vercel.stubs_path') . $path;
    }
}
