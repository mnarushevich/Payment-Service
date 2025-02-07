<?php

namespace App\Models\DynamoDB;

use BaoPham\DynamoDb\DynamoDbModel;

class StripeEventLog extends DynamoDbModel
{
    protected $table = 'StripeEventLogs';  // DynamoDB table name

    protected $primaryKey = 'Id';  // Primary key attribute

    public $timestamps = false;  // DynamoDB doesn't have automatic timestamps

    protected $fillable = [
        'Id', 'TransactionId', // Define fillable fields
    ];
}
