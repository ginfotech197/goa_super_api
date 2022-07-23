<?php

namespace App\Console\Commands;

use App\Http\Controllers\CentralController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class generateFatafatResult extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generateFatafat:result';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // LOG::info('Fatafat');
        // LOG::info(Carbon::today());
        $centralControllerObj = new CentralController();
        $centralControllerObj->createResult(1);
    }
}
