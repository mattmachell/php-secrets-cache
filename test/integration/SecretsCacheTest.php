<?php 

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SecretsCache\SecretsCache;
use SecretsCache\APCUCache;
use Aws\SecretsManager\SecretsManagerClient;
use Aws\Credentials\CredentialProvider;
use SecretsCache\Exceptions\RemoteSecretNotFoundException;

class SecretsCacheTest extends TestCase {

    protected const FAKE_SECRET = "Th15IsAF4kET3sTs3Cr3t!";

    /**
     * @before
     */
    public static function setupTestEnv(): void
    {
    }

    protected function getClient(){
        return new SecretsManagerClient(
            [
            'version' => '2017-10-17',
            'region' => 'eu-west-1',
            'endpoint' => 'http://localstack:4566',
            'credentials' => CredentialProvider::defaultProvider()
            ]
        );
    }

    public function testGetSecretUncached(){
        apcu_clear_cache();

        $cache = new APCUCache();

        $client = self::getClient();

        $secretCache = new SecretsCache($cache, $client);

        $secret_value = $secretCache->get('local/test-secret');

        $this->assertEquals(self::FAKE_SECRET, $secret_value);
    }

  
    public function testUnknownSecretThrowsException()
    {
        apcu_clear_cache();

        $cache = new APCUCache();

        $client = self::getClient();

        $secretCache = new SecretsCache($cache, $client);

        $this->expectException(RemoteSecretNotFoundException::class);
        $secret_value = $secretCache->get('unknownsecret');

    }

    public function testGetCachedSecret(){

        apcu_clear_cache();

        $cache = new APCUCache();

        $client = self::getClient();

        $secretCache = new SecretsCache($cache, $client);

        $secretCache->set('local/test-secret', 'banana');

        $secret_value = $secretCache->get('local/test-secret');

        $this->assertEquals('banana',$secret_value);
    }

}