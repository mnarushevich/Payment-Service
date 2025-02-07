<?php

namespace App\Models\DynamoDB;

use BaoPham\DynamoDb\DynamoDbModel;

class User extends DynamoDbModel
{
    protected $table = 'Users';  // DynamoDB table name
    protected $primaryKey = 'id';  // Primary key attribute
    public $timestamps = false;  // DynamoDB doesn't have automatic timestamps

    protected $fillable = [
        'id', 'name', // Define fillable fields
    ];
}
