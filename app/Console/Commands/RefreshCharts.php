<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\vtcController;
use Illuminate\Contracts\Logging\Log;
use Illuminate\Support\Facades\DB;
class RefreshCharts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh:charts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'refreshes Chart';

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
     * @return mixed
     */
    public function handle()
    {
        $vtcControl = new vtcController();
        $LastData = $vtcControl->ReadLastLiveChartData();
        $lastLiveDate = str_replace("T", " ", $LastData[0]['T']);

        if ($vtcControl->ReadlastDBChartData()->datetime != $lastLiveDate){
            $vtcControl->WriteLastChartData();
            \Log::info("DB vtcbtc Last Data updated from Live!");
        } 

        if($vtcControl->checkOrdersForBuy()){
            $bought = $vtcControl->calculateBuy();
            $bought ? \Log::info("order bought!") : \Log::info("nothing!");
        } else {
            $sold = $vtcControl->calculateSell();
            $sold ? \Log::info("order sold!") : \Log::info("nothing!");
        }
        //echo 'testing cron';
    }
}
