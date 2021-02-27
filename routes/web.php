<?php
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->post('/webhook', 'Webhook');

$router->group(['prefix' => 'api'], function () use ($router) {
    // Matkul
    $router->post('matkul', 'MatkulController@store');
    $router->get('matkul', 'MatkulController@getMatkul');
    $router->get('matkul/{id}', 'MatkulController@showMatkul');
    $router->put('matkul/{id}', 'MatkulController@update');
    $router->delete('matkul/{id}', 'MatkulController@destroy');

    // Tugas
    $router->post('tugas', 'TugasController@store');
    $router->get('tugas', 'TugasController@getAllTugas');
    $router->get('tugas/{id}', 'TugasController@getTugas');
    $router->put('tugas/{id}', 'TugasController@update');
    $router->delete('tugas/{id}', 'TugasController@destroy');
});
