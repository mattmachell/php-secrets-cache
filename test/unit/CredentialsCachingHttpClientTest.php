<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use SecretsCache\RefreshStrategy\SecretRefreshStrategyInterface;
use SecretsCache\CredentialsCachingHttpClient;
use SecretsCache\RefreshStrategy\ClearAndReRequestAPIKeyStrategy;
use SecretsCache\SecretsCache;

class CredentialsCachingHttpClientTest extends TestCase
{
    public function testRequestNewCredsOn401Fail(){
        $response = $this->createMock(Response::class);
        $response->method('getStatusCode')->willReturn(401);

        $response2 = $this->createMock(Response::class);
        $response2->method('getStatusCode')->willReturn(200);

        $client = $this->createMock(Client::class);
        $client->expects($this->exactly(2))->method('sendRequest')->willReturnOnConsecutiveCalls($response, $response2);

        $request = $this->createMock(RequestInterface::class);

        $refresh_strategy = $this->createMock(SecretRefreshStrategyInterface::class);
        $refresh_strategy->expects($this->once())->method('refreshCredentials')->willReturn(true);

        $wrapper_client = new CredentialsCachingHttpClient($client, $refresh_strategy);

        $result = $wrapper_client->sendRequest($request);

        $this->assertEquals($response2, $result);

    }

    public function testRequestNewCredsOn403Fail(){

        $response = $this->createMock(Response::class);
        $response->method('getStatusCode')->willReturn(403);

        $response2 = $this->createMock(Response::class);
        $response2->method('getStatusCode')->willReturn(200);

        $client = $this->createMock(Client::class);
        $client->expects($this->exactly(2))->method('sendRequest')->willReturnOnConsecutiveCalls($response, $response2);

        $request = $this->createMock(RequestInterface::class);

        $refresh_strategy = $this->createMock(SecretRefreshStrategyInterface::class);
        $refresh_strategy->expects($this->once())->method('refreshCredentials')->willReturn(true);

        $wrapper_client = new CredentialsCachingHttpClient($client, $refresh_strategy);

        $result = $wrapper_client->sendRequest($request);

        $this->assertEquals($response2, $result);

    }

    public function testAPIKeyClearStrategy(){

        $request = $this->createMock(RequestInterface::class);
        $request_decorated = $this->createMock(RequestInterface::class);
        $request->expects($this->once())->method('withHeader')->willReturn($request_decorated);
        $cache = $this->createMock(SecretsCache::class);
        $cache->expects($this->atLeastOnce())->method('get')->willReturn('my-key-value');
        $cache->expects($this->once())->method('delete');

        $response = $this->createMock(Response::class);
        $response->expects($this->once())->method('getStatusCode')->willReturn(401);

        $response2 = $this->createMock(Response::class);

        $client = $this->createMock(Client::class);
        $client->expects($this->exactly(2))->method('sendRequest')->willReturnOnConsecutiveCalls($response, $response2);

        $refresh_strategy = new ClearAndReRequestAPIKeyStrategy('API_KEY', 'bearer', $cache, $request);

        $wrapper_client = new CredentialsCachingHttpClient($client, $refresh_strategy);

        $result = $wrapper_client->sendRequest($request);

        $this->assertEquals($result, $response2);
        



    }
}