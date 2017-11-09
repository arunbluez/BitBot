<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Contracts\Logging\Log;

class AddVtcbtcLast100 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $cnmkt5 = "https://bittrex.com/Api/v2.0/pub/market/GetTicks?marketName=BTC-VTC&tickInterval=thirtyMin";
        $fgc5 = json_decode(file_get_contents($cnmkt5), true);
        $result5 = array_values(array_slice($fgc5["result"], -1000, 1000, true));

        $ma1[0]=$result5[0]["C"]*(2/(8+1));
        $ma2[0]=$result5[0]["C"]*(2/(20+1));
        $macd[0] = $ma1[0] - $ma2[0];
        $signal[0]=$result5[0]["C"]/25;

        for($k=0;$k<count($result5);$k++){
            if($k > 0){
                $ma1[$k] = $this->ema(20,$result5[$k]["C"],$ma1[$k-1]);
                $ma2[$k] = $this->ema(25,$result5[$k]["C"],$ma2[$k-1]);
                
                $macd[$k] = $ma1[$k] - $ma2[$k];
                
                $signal[$k] = $this->sma(30,$macd,$k);
                }
        }


        for($i=0;$i<count($result5);$i++){

            DB::table('vtcbtc')->insert(array(
                'open'=>$result5[$i]["O"],
                'high'=>$result5[$i]["H"],
                'low'=>$result5[$i]["L"],
                'close'=>$result5[$i]["C"],
                'volume'=>$result5[$i]["V"],
                'datetime'=>$result5[$i]["T"],
                'sm_signal'=>$signal[$i],
                'ma1'=>$ma1[$i],
                'ma2'=>$ma2[$i],
                'macd'=>$macd[$i],
                'created_at'=>date('Y-m-d H:m:s')
            ));

        }



    }


    private function ema($limit,$currentday,$previousEMA){
        $EMA_previous_day = $previousEMA;
        $multiplier1 = (2/($limit+1));
        $Close= $currentday;
        $EMA = ($Close - $EMA_previous_day) * $multiplier1 + $EMA_previous_day;
    return $EMA;
    }

    private function sma($limit,$array,$k){
        $sum = 0;
       // \Log::info('start:'.$array[$k]);
        for($i=0;$i<$limit;$i++){
            // \Log::info('k:'.$k . ' i:'. $i);
            if ($k-$i<0){
                $sum = $sum + 0;
            }else{
               // \Log::info('array:'.$array[$k-$i]);
                $sum = $sum + $array[$k-$i];
            }
        }
      //  \Log::info('sum:'.$sum);
        $SMA = $sum / $limit;
    return $SMA;
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('vtcbtc')->truncate();
    }
}
