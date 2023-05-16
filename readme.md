# PHP Secrets Cache

Experimental AWS secrets cache for PHP. *This is a demo and not production code.*

Solves the problem where you have secrets stored in environment variables, but need to refresh them more dynamically and don't want to tear down a host of containers to do so. Instead of storing secrets in environment variable, store them in the secrets cache and use a client that can refresh the stored secret if it gets an HTTP response that indicates it isn't authenticated.

Stores secrets in APCU, so you'll need to have apcu enabled.

Features a set of hooks for if you need to run code on a secret on the way in or out of the cache.

## Example



## Running tests

```docker-compose up unit-tests```

```docker-compose up integration-tests```