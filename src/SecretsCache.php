<?php

declare(strict_types=1);

namespace SecretsCache;

use Aws\SecretsManager\SecretsManagerClient;
use Aws\Exception\AwsException;
use SecretsCache\APCUCache;
use SecretsCache\Exceptions\RemoteRequestFailedException;
use SecretsCache\Exceptions\RemoteSecretNotFoundException;

class SecretsCache
{

    public function __construct(
        public APCUCache $cache,
        public SecretsManagerClient $client,
        protected int $retry_delay = 1,
        protected int $backoff_factor = 2,
        protected mixed $inBoundHook = null,
        protected mixed $outBoundHook = null
    ) {
    }

    public function get(string $secret_name): mixed
    {

        $value = null;

        if ($this->cache->has($secret_name)) {
            $value = $this->cache->get($secret_name);
        } else {
            $value =  $this->getFromAWSAndStore($secret_name);
        }

        if (isset($this->outBoundHook)) {
            $value = ($this->outBoundHook)($value);
        }
        return $value;
    }

    public function set(string $secret_name, mixed $value): bool
    {
        if (isset($this->inBoundHook)) {
            $value = ($this->inBoundHook)($value);
        }
        return $this->cache->set($secret_name, $value);
    }

    public function setOutBoundHook(callable $callable): void
    {
        $this->outBoundHook = $callable;
    }

    public function setInboundHook(callable $callable): void
    {
        $this->inBoundHook = $callable;
    }

    protected function requestSecretFromAWS(string $secret_name): mixed
    {
        try {
            $result = $this->client->getSecretValue([
                'SecretId' => $secret_name,
            ]);
        } catch (AwsException $e) {
            $error = $e->getStatusCode() ?? 500;

            if($error == 404){
                throw new RemoteSecretNotFoundException('No such secret configured');
            }
            

            throw new RemoteRequestFailedException('Failed request with error : ' . $error);
        }
        return $result;
    }

    public function getFromAWSAndStore(string $secret_name): mixed
    {
        $result = $this->requestSecretFromAWS($secret_name);

        if (isset($result['SecretString'])) {
            $secret = $result['SecretString'];
        } elseif (isset($result['SecretBinary'])) {
            $secret = base64_decode($result['SecretBinary']);
        } else {
            throw new \SecretsCache\Exceptions\NoRemoteSecretReturned('No value returned for requested key '.$secret_name);
        }

        $this->set($secret_name, $secret);
        return $secret;
    }

    public function delete(string $secret_name): void{
        $this->cache->delete($secret_name);
    }
}
