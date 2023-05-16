<?php

namespace SecretsCache\RefreshStrategy;

use Exception;
use Psr\Http\Message\RequestInterface;
use SecretsCache\Exceptions\CredentialRefreshFailed;
use SecretsCache\SecretsCache;

class ClearAndReRequestAPIKeyStrategy implements SecretRefreshStrategyInterface{


    public function __construct(protected String $api_key_secret_name, protected $api_secret_header, protected SecretsCache $cache, protected RequestInterface $request){
    }

    public function refreshCredentials() : bool {

        try{
            $this->cache->delete($this->api_key_secret_name);
            $secret = $this->cache->get($this->api_key_secret_name);
            $this->request = $this->request->withHeader($this->api_secret_header,$secret);
        }
        catch(Exception $e){
            throw new CredentialRefreshFailed();
        }
        return true;
    }
}