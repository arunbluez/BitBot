<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\vtcController;
use ConsoleTVs\Charts\Facades\Charts;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Logging\Log;

class vtcController extends Controller
{
	public $chart;


    public function getData(){
    	$vtcdata = $this->getJsonData();
    	$portfolio = $this->displayOrders();

    	$chart = Charts::realtime(route('/dashboard/vtcbtctest'), 60000, 'line', 'highcharts')
    	->maxValues(100);



    	return view('/vendor/voyager/vtcbtc/browse', array('vtcdata' => $vtcdata,'portfolio'=>$portfolio));
    }

    public function getJsonData(){
    	$jsonData = "https://bittrex.com/Api/v2.0/pub/market/GetTicks?marketName=BTC-VTC&tickInterval=fiveMin";
		return $jsonDecoded = $this->formatDataChart(json_decode(file_get_contents($jsonData), true));
    }

    public function formatDataChart($data){
    	$fData[]=array();
		for($j=0;$j<count($data["result"]);$j++){
			$fData[$j]["C"] = $data["result"][$j]["C"];
			$fData[$j]["T"] = $data["result"][$j]["T"];
			$fData[$j]["L"] = substr($data["result"][$j]["T"], 11, 5);
		}
		$formatedData = array_values(array_slice($fData, -100, 100, true));
    	return $formatedData;
    }
    public function WriteLastChartData(){

    	$lastLiveData = $this->ReadLastLiveChartData();
    	$LastDBData = $this->ReadLastDBChartData();
		$Last25DBData = DB::table('vtcbtc')->orderBy('id', 'desc')->take(29)->get();
		$macdSum = 0;
    	$lastma1 = $LastDBData->ma1;
    	$lastma2 = $LastDBData->ma2;
    	$lastsignal = $LastDBData->sm_signal;
    	$lastLiveCloseData = $lastLiveData[0]['C'];

    	for($i=0;$i<29;$i++){
    		$macdSum = $macdSum + $Last25DBData[$i]->macd;
    	}

    	$ma1 = $this->ema(20,$lastLiveCloseData,$lastma1);
		$ma2 = $this->ema(25,$lastLiveCloseData,$lastma2);
		$macd = $ma1 - $ma2;
		$macdSum = $macdSum + $macd;
		$signal = $macdSum/30;

    	    DB::table('vtcbtc')->insert(array(
                'open'=>$lastLiveData[0]["O"],
                'high'=>$lastLiveData[0]["H"],
                'low'=>$lastLiveData[0]["L"],
                'close'=>$lastLiveData[0]["C"],
                'volume'=>$lastLiveData[0]["V"],
                'datetime'=>$lastLiveData[0]["T"],
                'sm_signal'=>$signal,
                'ma1'=>$ma1,
                'ma2'=>$ma2,
                'macd'=>$macd,
                'created_at'=>date('Y-m-d H:m:s')
            ));

    }
    public function ReadLastDBChartData(){
    	return DB::table('vtcbtc')->orderBy('id', 'desc')->first();
    }
    public function ReadLastLiveChartData(){
    	$jsonData = "https://bittrex.com/Api/v2.0/pub/market/GetTicks?marketName=BTC-VTC&tickInterval=thirtyMin";
    	$fgc5 = json_decode(file_get_contents($jsonData), true);
        $lastData = array_values(array_slice($fgc5["result"], -1, 1, true));
    	return $lastData;
    }



    public function calculateBuy(){
    	$lastDBData = $this->ReadLastDBChartData();
    	$last2DBData = DB::table('vtcbtc')->orderBy('id', 'desc')->skip(1)->take(1)->get();

    	$macdLive = $lastDBData->macd;
    	$macdDB = $last2DBData[0]->macd;

    	$signalDB = $last2DBData[0]->sm_signal;
    	$signalLive = $lastDBData->sm_signal;
    	
	    if($macdLive>=$signalLive){
			if($macdDB < $signalDB){	
				$this->BuyOrder();
				return true;
			}else{
				return false;
			}
		} else {
				return false;
		}
    }

    public function calculateSell(){
    	$lastDBData = $this->ReadLastDBChartData();
    	$last2DBData = DB::table('vtcbtc')->orderBy('id', 'desc')->skip(1)->take(1)->get();

    	$macdLive = $lastDBData->macd;
    	$macdDB = $last2DBData[0]->macd;

    	$signalDB = $last2DBData[0]->sm_signal;
    	$signalLive = $lastDBData->sm_signal;


    	if($macdLive < $macdDB){
    		$this->SellOrder();
			return true;
		} else {
			return false;
		}
    }


    public function checkOrdersForBuy(){
    	$order = DB::table('order')->where('open', '=', 1)->first();
    	if($order){
    		return false;
    	} else {
    		return true;
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
        $Lastsum = DB::table('vtcbtc')->where('id', '=', $id)->sum('balance');
        for($i=0;$i<$limit;$i++){
            if ($k-$i<0){
                $sum = $sum + 0;
            }else{
                $sum = $sum + $array[$k-$i];
            }
        }
        $SMA = $sum / $limit;
    return $SMA;
    }

	private function BuyOrder(){

		$BTCEUR = 0;

		if($BTCEUR != 0){
			$btceurrate = $BTCEUR;
		}else{
			$jsonBTCUSDT = "https://bittrex.com/api/v1.1/public/getticker?market=USDT-BTC";
			$jsonEURUSD = "https://api.fixer.io/latest?symbols=USD,EUR";
			$btceurrate = json_decode(file_get_contents($jsonBTCUSDT), true)['result']['Last'] / json_decode(file_get_contents($jsonEURUSD), true)['rates']['USD'];
		}

		$jsonVTCBTC = "https://bittrex.com/api/v1.1/public/getticker?market=BTC-VTC";
		$fgc = json_decode(file_get_contents($jsonVTCBTC), true);
    	$rate = $fgc['result']['Last'];
		DB::table('order')->insert(array(
                'buysell'=>'BUY',
                'units'=>50,
                'price_per_unit'=>$rate+(0.0025*$rate),
                'btceur'=>$btceurrate,
                'open'=>1,
                'created_at'=>date('Y-m-d H:m:s')
            ));
		\Log::info("VTC|BTC = BUY @" . $rate+(0.0025*$rate) . " Units @50 BTCEUR @" . var_dump($btceurrate));

	}

	private function SellOrder(){
		$BTCEUR = 0;

		if($BTCEUR != 0){
			$btceurrate = $BTCEUR;;
		}else{
			$jsonBTCUSDT = "https://bittrex.com/api/v1.1/public/getticker?market=USDT-BTC";
			$jsonEURUSD = "https://api.fixer.io/latest?symbols=USD,EUR";
			$btceurrate = json_decode(file_get_contents($jsonBTCUSDT), true)['result']['Last'] / json_decode(file_get_contents($jsonEURUSD), true)['rates']['USD'];
		}

		$jsonVTCBTC = "https://bittrex.com/api/v1.1/public/getticker?market=BTC-VTC";
		$fgc = json_decode(file_get_contents($jsonVTCBTC), true);
    	$rate = $fgc['result']['Last'];
		DB::table('order')->insert(array(
                'buysell'=>'SELL',
                'units'=>50,
                'price_per_unit'=>$rate-(0.0025*$rate),
                'btceur'=>$btceurrate,
                'open'=>0,
                'created_at'=>date('Y-m-d H:m:s')
            ));

		DB::table('order')->where('open', '=', '1')->update(array(
			'open'=>0,
		));

		\Log::info("VTC|BTC = SELL @" . $rate-(0.0025*$rate) . " Units @50 BTCEUR @" . var_dump($btceurrate));
	}


	private function displayOrders(){
		$totalProfit = 0;
		$orderBuy = DB::table('order')->where('buysell', '=', 'BUY')->where('open', '=', '0')->get();
		$orderSell = DB::table('order')->where('buysell', '=', 'SELL')->get();
		
		if($orderBuy){
			for($i=0;$i<count($orderBuy);$i++){
				$portfolio['orders'][$i]['unit'] = 50;
				$portfolio['orders'][$i]['buy'] = $orderBuy[$i]->price_per_unit*$orderBuy[$i]->btceur;
				$portfolio['orders'][$i]['sell'] = $orderSell[$i]->price_per_unit*$orderSell[$i]->btceur;
				$portfolio['orders'][$i]['profit'] = ($portfolio['orders'][$i]['sell']*50)-($portfolio['orders'][$i]['buy']*50);
				$portfolio['orders'][$i]['profitPercent'] = ($portfolio['orders'][$i]['profit']/($portfolio['orders'][$i]['buy']*50))*100;
				$totalProfit = $totalProfit + $portfolio['orders'][$i]['profit'];
			}
			$portfolio['total'][0]['total'] = $totalProfit;
		} else {
			$portfolio['orders'][0]['unit'] = 0;
			$portfolio['orders'][0]['buy'] = 0;
			$portfolio['orders'][0]['sell'] = 0;
			$portfolio['orders'][0]['profit'] = 0;
			$portfolio['orders'][0]['profitPercent'] = 0;
			$portfolio['total'][0]['total'] = 0;
		}
		return $portfolio;
	}

}
