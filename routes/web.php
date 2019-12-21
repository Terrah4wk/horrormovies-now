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

Route::get('/', [
    'as' => 'tv.index',
    'uses' => 'TvController@index'
]);
Route::get('/netflix/neu-erschienen', 'NetflixController@new')->name('netflix.new');
Route::get('/netflix/aktuell', 'NetflixController@current')->name('netflix.current');
Route::get('/netflix/auslaufend', 'NetflixController@expire')->name('netflix.expire');
Route::get('/imprint', 'SiteController@imprint')->name('page.imprint');
