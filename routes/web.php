<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::group(['prefix' => 'dashboard'], function () {
    Voyager::routes();
});

Route::get('/dashboard/vtc', 'vtcController@getData');

Route::get('/dashboard/vtcbtctest', function () {
	$jsonData = "https://bittrex.com/Api/v2.0/pub/market/GetTicks?marketName=BTC-VTC&tickInterval=oneMin";
	$jsonDecoded = json_decode(file_get_contents($jsonData), true);
	$lastClose = array_values(array_slice($jsonDecoded["result"], -1))[0]['C'];
    return ['value' => $lastClose];
})->name('/dashboard/vtcbtctest');
