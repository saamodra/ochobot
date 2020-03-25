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

$router->get('/asd/{matkulId}', 'ExampleController@getTugasMatkul');

// $router->get('/asde/{matkulId}', function($matkulId) {
//     $matkul = app('db')->table('matkul')
//             ->where('id', $matkulId)
//             ->first();
 
//         if ($matkul) {
//             return (array) $matkul;
//         }
 
//         return null;
// });
