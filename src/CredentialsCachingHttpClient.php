<?php

namespace SecretsCache;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Client\ClientInterface;
use SecretsCache\Refreshstrategy\SecretRefreshStrategyInterface;


class CredentialsCachingHttpClient implements ClientInterface{

    public function __construct(private ClientInterface $client, private SecretRefreshStrategyInterface $refresh_strategy) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface {

        $response = $this->client->sendRequest($request);

        if($response->getStatusCode() === 401 || $response->getStatusCode() === 403){
            
            $this->refresh_strategy->refreshCredentials();
            $response = $this->client->sendRequest($request);
            
        }

        return $response;
    }

}