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
