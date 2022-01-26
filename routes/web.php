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

//Route::get('ussd/{msisdn}/{sessionid}/{shortcode}/000', 'UssdController@logout')->where('response', '[A-Za-z0-9]+');
Route::get('ussd/{msisdn}/{sessionid}/{shortcode}/0', 'UssdController@mainMenu')->where('response', '[A-Za-z0-9]+');
Route::get('ussd/{msisdn}/{sessionid}/{shortcode}/00', 'UssdController@back')->where('response', '[A-Za-z0-9]+');
Route::get('ussd/{msisdn}/{sessionid}/{shortcode}/000', 'UssdController@logout')->where('response', '[A-Za-z0-9]+');
Route::get('ussd/{msisdn}/{sessionid}/{shortcode}/{response}', 'UssdController@index')->where('response', '[A-Za-z0-9]+');
Route::get('ussd/{msisdn}/{sessionid}/{shortcode}', 'UssdController@index');
Route::get('ussd', 'UssdController@index');
Route::get('ussd/msisdn={msisdn}&sessionid={sessionid}&shortcode={shortcode}&request={request}', 'UssdController@index');


Route::get('/clear-cache', function() {
    Artisan::call('cache:clear');
    return "Cache is cleared";
});