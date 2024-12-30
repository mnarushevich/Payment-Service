Docs » [LocalStack DynamoDB](https://docs.localstack.cloud/user-guide/aws/dynamodb/)

To set up DynamoDB locally using LocalStack in a Laravel application with Docker, follow these steps:

1. Install LocalStack

LocalStack is a fully functional local AWS cloud stack. Add it to your docker-compose.yml file.

Example docker-compose.yml:
```yaml
    localstack:
        image: localstack/localstack:latest
        container_name: localstack
        platform: linux/x86_64
        ports:
            - "4566:4566" # Gateway
            - "8011:8000" # DynamoDB
        environment:
            SERVICES: dynamodb
            DEBUG: 1

        volumes:
            - "${LOCALSTACK_VOLUME_DIR:-./volume}:/var/lib/localstack"
            - "/var/run/docker.sock:/var/run/docker.sock"
        networks:
            - sbc
```

2. Update Laravel’s Dependencies

Install the AWS SDK for PHP, which Laravel uses for working with AWS services like DynamoDB:

```bash
composer require aws/aws-sdk-php
```

3. Configure Laravel for DynamoDB
Update your .env file with the LocalStack credentials and DynamoDB configuration:

```dotenv
AWS_ACCESS_KEY_ID=test
AWS_SECRET_ACCESS_KEY=test
AWS_DEFAULT_REGION=us-east-1
AWS_DYNAMODB_ENDPOINT=http://localstack:4566
```

Add these configurations to config/services.php:
```php
return [
    // Other configurations...
    'dynamodb' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'endpoint' => env('AWS_DYNAMODB_ENDPOINT'),
    ],
];
````

4. Start Docker Containers

Start the Docker containers:

```bash
docker-compose up -d
```

Verify LocalStack is running by checking the logs:

```bash
docker logs localstack
```

5. Create DynamoDB Table

Use the AWS CLI to create a DynamoDB table in LocalStack:
1.	Install the AWS CLI if you don’t have it installed:

```bash
pip install awscli
```

2.	Configure the AWS CLI to use LocalStack:

```bash
aws configure
```

Use the following:
```yaml
•	Access Key ID: test
•	Secret Access Key: test
•	Region: us-east-1
•	Output Format: json
```

3.	Create a DynamoDB table:

```bash
aws --endpoint-url=http://localhost:4566 dynamodb create-table  --table-name Users --attribute-definitions AttributeName=id,AttributeType=S --key-schema AttributeName=id,KeyType=HASH  --provisioned-throughput ReadCapacityUnits=5,WriteCapacityUnits=5
```

6. Test DynamoDB in Laravel

Use the AWS SDK in Laravel to interact with DynamoDB:

Example Usage:
```php
use Aws\DynamoDb\DynamoDbClient;

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
            'id' => ['S' => '123'],
            'name' => ['S' => 'John Doe'],
        ],
    ]);

    return response()->json($result);
});
```

7. Debugging
###### To inspect the data, use the AWS CLI:

```bash
aws --endpoint-url=http://localhost:4566 dynamodb scan --table-name Users
```


Check the LocalStack dashboard (if installed) at http://localhost:4566.
