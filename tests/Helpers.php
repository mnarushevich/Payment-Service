<?php

declare(strict_types=1);

function getUrl(string $route, array $params = []): string
{
    return route($route, $params);
}

function getAuthorizationHeader(string $access_token): array
{
    return ['Authorization' => sprintf('Bearer %s', $access_token)];
}

function generateJWTToken(string $internalUserId): string
{
    $fake = Faker\Factory::create();
    $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64_encode(json_encode([
        'sub' => $internalUserId,
        'iat' => time(),
        'exp' => time() + 3600,
        'internal_user_id' => $internalUserId,
    ]));

    $signature = base64_encode(hash_hmac('sha256', sprintf('%s.%s', $header, $payload), $fake->uuid(), true));

    return sprintf('%s.%s.%s', $header, $payload, $signature);
}

function getMockData(string $fileName, bool $isResponse = true): array
{
    $mockDataFolder = $isResponse ? 'Responses' : 'Requests';
    $json = file_get_contents(__DIR__.sprintf('/Integration/Mocks/%s/%s.json', $mockDataFolder, $fileName));

    return json_decode($json, true);
}
