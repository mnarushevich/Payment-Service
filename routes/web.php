<?php

use Aws\DynamoDb\DynamoDbClient;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

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

Route::get('/test-dynamodb-orm', function () {
//    \App\Models\DynamoDB\User::create([
//        'id' => '999',
//        'name' => 'Maksim Test ORM 333',
//    ]);

    \App\Models\DynamoDB\Payment::create([
        'Id' => '999',
        'TransactionId' => Str::uuid(),
    ]);

    return response()->json(['message' => 'Payment created']);
});
