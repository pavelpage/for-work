<?php

namespace App\Console\Commands;

use App\Services\NormalizedStringService;
use Illuminate\Console\Command;

class CheckString extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:string {string}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checking if string is normalized or not';
    /**
     * @var NormalizedStringService
     */
    private $normalizedStringService;

    /**
     * Create a new command instance.
     * @param NormalizedStringService $normalizedStringService
     */
    public function __construct(NormalizedStringService $normalizedStringService)
    {
        parent::__construct();
        $this->normalizedStringService = $normalizedStringService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle()
    {
        //
        $res = $this->normalizedStringService->isStringNormalized($this->argument('string'));

        if ($res) {
            $this->info(PHP_EOL . 'string is normalized'. PHP_EOL);
        }
        else {
            $this->error(PHP_EOL . 'string is not normalized'. PHP_EOL);
        }

    }
}
