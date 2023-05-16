<?php

declare(strict_types=1);

use Aws\Exception\AwsException;
use PHPUnit\Framework\TestCase;
use SecretsCache\SecretsCache;
use Aws\SecretsManager\SecretsManagerClient;
use SecretsCache\APCUCache;

class SecretsCacheTest extends TestCase
{

    public function testGetFromCache()
    {

        $cache_stub = $this->createStub(APCUCache::class);
        $cache_stub->method('get')->willReturn('my-secret-value');
        $cache_stub->method('has')->willReturn(true);

        $client_stub = $this->createStub(SecretsManagerClient::class);
        $cache = new SecretsCache($cache_stub, $client_stub);

        $this->assertEquals($cache->get('my-secret'), 'my-secret-value');
    }

    public function testGetFromRemoteString()
    {
        $cache_stub = $this->createStub(APCUCache::class);
        $cache_stub->method('has')->willReturn(false);


        $client_stub = $this->createStub(SecretsManagerClient::class);
        $client_stub->method('__call')->willReturn(['SecretString' => 'my-secret-value']);
        $cache = new SecretsCache($cache_stub, $client_stub);

        $this->assertEquals($cache->get('my-secret'), 'my-secret-value');
    }

    public function testNoSecretReturned()
    {
        $cache_stub = $this->createStub(APCUCache::class);
        $cache_stub->method('has')->willReturn(false);


        $client_stub = $this->createStub(SecretsManagerClient::class);
        $client_stub->method('__call')->willReturn([]);
        $cache = new SecretsCache($cache_stub, $client_stub);
        
        $this->expectException(\SecretsCache\Exceptions\NoRemoteSecretReturned::class);
        $cache->get('my-secret');
    }

    public function testSet()
    {
        $cache_stub = $this->createStub(APCUCache::class);
        $cache_stub->method('set')->willReturn(true);

        $client_stub = $this->createStub(SecretsManagerClient::class);
        $cache = new SecretsCache($cache_stub, $client_stub);

        $this->assertTrue($cache->set('my-key','my-value'));
    }

    public function testGetRemoteStringFailure()
    {
        $cache_stub = $this->createStub(APCUCache::class);
        $cache_stub->method('has')->willReturn(false);


        $client_stub = $this->createStub(SecretsManagerClient::class);
        $exception_stub= $this->createStub(AwsException::class);
        $client_stub->method('__call')->will($this->throwException($exception_stub));
   
        $cache = new SecretsCache($cache_stub, $client_stub);

        $this->expectException(\SecretsCache\Exceptions\RemoteRequestFailedException::class);
        $cache->get('my-secret');

    }

    public function testGetFromRemoteBinary()
    {
        $cache_stub = $this->createStub(APCUCache::class);
        $cache_stub->method('has')->willReturn(false);


        $client_stub = $this->createStub(SecretsManagerClient::class);
        $client_stub->method('__call')->willReturn(['SecretBinary' => base64_encode('my-secret-value')]);
        $cache = new SecretsCache($cache_stub, $client_stub);

        $this->assertEquals($cache->get('my-secret'), 'my-secret-value');
    }

    public function testOutBoundHook()
    {

        $cache_stub = $this->createStub(APCUCache::class);
        $cache_stub->method('get')->willReturn('my-secret-value');
        $cache_stub->method('has')->willReturn(true);

        $client_stub = $this->createStub(SecretsManagerClient::class);
        $cache = new SecretsCache($cache_stub, $client_stub, 1, 2);

        $cache->setOutBoundHook(function ($i) {
            return '--' . $i;
        });

        $this->assertEquals($cache->get('my-secret-value'), '--my-secret-value');
    }

    public function testInBoundHook()
    {

        $cache_stub = $this->createMock(APCUCache::class);
        $cache_stub->expects($this->once())->method('set')->with($this->equalTo('my-secret-key'), $this->equalTo('--my-secret-value'));


        $client_stub = $this->createStub(SecretsManagerClient::class);
        $cache = new SecretsCache($cache_stub, $client_stub, 1, 2);

        $cache->setInboundHook(function ($i) {
            return '--' . $i;
        });

        $cache->set('my-secret-key', 'my-secret-value');
    }
}
