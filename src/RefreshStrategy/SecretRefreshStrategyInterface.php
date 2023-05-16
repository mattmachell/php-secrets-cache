<?php

namespace SecretsCache\RefreshStrategy;

interface SecretRefreshStrategyInterface {

    public function refreshCredentials(): bool;

}