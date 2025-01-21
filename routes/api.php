<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\WeatherController;
use App\Http\Controllers\ImmediateController;
use App\Http\Controllers\FactoryController;
use App\Http\Controllers\ToxicityController;
use App\Http\Controllers\PollutionController;
use App\Http\Controllers\VolatilityController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\AccessTokenController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AlohaController;
use App\Http\Middleware\TokenController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// 第三方資料取得
Route::post('/Weather', [WeatherController::class, 'getWeather']); //天氣
Route::post('/CEMS', [WeatherController::class, 'newCEMS']); // CEMS
Route::post('/AQI', [WeatherController::class, 'getAQI']); //空氣
Route::post('/Opacity', [WeatherController::class, 'getOpacity']); //不透光度
Route::post('/Flare', [WeatherController::class, 'getFlare']); //Flare

// 取得資料
Route::post('/nowweather', [ImmediateController::class, 'nowweather']); //單日最新天氣資訊
Route::post('/nowair', [ImmediateController::class, 'nowair']); //單日最新空氣資訊
Route::post('/nowcems', [ImmediateController::class, 'nowcems']); //單日最新CEMS資訊
Route::post('/nowopacity', [ImmediateController::class, 'nowopacity']); //單日最新不透光度資訊
Route::post('/nowflare', [ImmediateController::class, 'nowflare']); //單日最新Flare資訊

// 廠區管理
Route::middleware(TokenController::class)->group(function (){
    Route::post('/createfactory', [FactoryController::class, 'createfactory']); //新增廠區資料
    Route::post('/updatefactory', [FactoryController::class, 'updatefactory']); //更新廠區資料
    Route::post('/backend/factories', [FactoryController::class, 'allfactories']); //後台廠區列表
    Route::post('/backend/factory', [FactoryController::class, 'factory']); //後台廠區資料
});
Route::post('/factories', [FactoryController::class, 'factories']); //廠區列表
Route::post('/factory', [FactoryController::class, 'factory']); //單一廠區資料

// 毒性化學物質
Route::middleware(TokenController::class)->group(function (){
    Route::post('/createtoxicity', [ToxicityController::class, 'createtoxicity']); //新增毒性化學物質(單日)
    Route::post('/updatetoxicity', [ToxicityController::class, 'updatetoxicity']); //更新毒性化學物質
    Route::post('/backend/toxicity', [ToxicityController::class, 'toxicity']); //後台毒性化學物質資料
});
Route::post('/toxicity', [ToxicityController::class, 'toxicity']); //毒性化學物質歷史資料查詢(單日)
Route::post('/toxic/history', [ToxicityController::class, 'historymonthtoxicity']); //毒性化學物質歷史資料查詢(區間)

// 有害空氣污染物
Route::middleware(TokenController::class)->group(function (){
    Route::post('/createpollution', [PollutionController::class, 'createpollution']); //新增有害空氣污染物(單日)
    Route::post('/updatepollution', [PollutionController::class, 'updatepollution']); //更新有害空氣污染物
    Route::post('/backend/pollution', [PollutionController::class, 'pollution']); //後台有害空氣污染物資料
});
Route::post('/pollution', [PollutionController::class, 'pollution']); //有害空氣污染物歷史資料查詢(單日)
Route::post('/pollution/history', [PollutionController::class, 'historymonthpollution']); //有害空氣污染物歷史資料查詢(區間)

// 揮發性有機物質
Route::middleware(TokenController::class)->group(function (){
    Route::post('/createvolatility', [VolatilityController::class, 'createvolatility']); //新增揮發性有機物質(單日)
    Route::post('/updatevolatility', [VolatilityController::class, 'updatevolatility']); //更新揮發性有機物質
    Route::post('/backend/volatility', [VolatilityController::class, 'volatility']); //後台揮發性有機物質資料
});
Route::post('/nowvolatility', [VolatilityController::class, 'nowvolatility']); //揮發性有機物質歷史資料查詢(單日)
Route::post('/volatility', [VolatilityController::class, 'volatility']); //揮發性有機物質歷史資料查詢(單日)
Route::post('/volatility/history', [VolatilityController::class, 'historymonthvolatility']); //揮發性有機物質歷史資料查詢(區間)

// 第三方歷史資料查詢
Route::post('/Weather/history', [HistoryController::class, 'weatherhistory']); //氣象歷史資料查詢
Route::post('/aqi/history', [HistoryController::class, 'aqihistory']); //空氣歷史資料查詢
Route::post('/opacity/history', [HistoryController::class, 'opacityhistory']); //不透光度歷史資料查詢
Route::post('/cems/history', [HistoryController::class, 'cemshistory']); //CEMS歷史資料查詢
Route::post('/flare/history', [HistoryController::class, 'flarehistory']); //Flare歷史資料查詢

// 廠區資料查詢
Route::post('/flare/select', [HistoryController::class, 'selectflare']); //廠區Flare編號查詢
Route::post('/cems/select', [HistoryController::class, 'selectcems']); //廠區CEMS編號查詢
Route::post('/opacity/select', [HistoryController::class, 'selectopacity']); //廠區不透光度編號查詢

// 帳號管理
Route::middleware(TokenController::class)->group(function (){
    Route::post('/register', [UserController::class, 'createaccount']); //新增帳號
    Route::post('/update', [UserController::class, 'updateaccount']); //更新帳號資料
    Route::post('/account/list', [UserController::class, 'selectaccount']); //查詢帳號列表
    Route::post('/account', [UserController::class, 'oneaccount']); //查詢單一帳號
});
Route::post('/login', [AccessTokenController::class, 'issueToken']); //登入

// Aloha資料
Route::post('/Aloha/insert', [AlohaController::class, 'insertdata']); //新增Aloha資料
Route::post('/Aloha/select', [AlohaController::class, 'selectdata']); //查詢Aloha資料