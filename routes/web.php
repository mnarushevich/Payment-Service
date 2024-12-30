<?php

use Aws\DynamoDb\DynamoDbClient;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/test-dynamodb', function () {
    $client = new DynamoDbClient([
        'region' => config('services.dynamodb.region'),
        'version' => 'latest',
        'endpoint' => config('services.dynamodb.endpoint'),
        'credentials' => [
            'key' => config('services.dynamodb.key'),
            'secret' => config('services.dynamodb.secret'),
        ],
    ]);

    $result = $client->putItem([
        'TableName' => 'Users',
        'Item' => [
            'id' => ['S' => '456'],
            'name' => ['S' => 'Maksim Test'],
        ],
    ]);

    return response()->json($result);
});
