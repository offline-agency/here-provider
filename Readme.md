# Here Geocoder provider
[![Build Status](https://github.com/geocoder-php/here-provider/actions/workflows/provider.yml/badge.svg)](https://github.com/geocoder-php/here-provider/actions)
[![Latest Stable Version](https://poser.pugx.org/geocoder-php/here-provider/v/stable)](https://packagist.org/packages/geocoder-php/here-provider)
[![Total Downloads](https://poser.pugx.org/geocoder-php/here-provider/downloads)](https://packagist.org/packages/geocoder-php/here-provider)
[![Monthly Downloads](https://poser.pugx.org/geocoder-php/here-provider/d/monthly.png)](https://packagist.org/packages/geocoder-php/here-provider)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/geocoder-php/here-provider.svg?style=flat-square)](https://scrutinizer-ci.com/g/geocoder-php/here-provider)
[![Quality Score](https://img.shields.io/scrutinizer/g/geocoder-php/here-provider.svg?style=flat-square)](https://scrutinizer-ci.com/g/geocoder-php/here-provider)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

This is the Here provider from the PHP Geocoder. This is a **READ ONLY** repository. See the
[main repo](https://github.com/geocoder-php/Geocoder) for information and documentation.

### Features

- Support for **HERE Geocoding & Search API v7 (GS7)** via API Key.
- Backward compatibility for **Geocoder API v6.2** (via API Key or App ID/Code).
- Geocoding (Address to Coordinates).
- Reverse Geocoding (Coordinates to Address).
- Support for Structured Geocoding Queries.
- Support for CIT (Customer Integration Testing) environment.
- Normalized response mapping to `HereAddress` model.

You can find the [documentation for the provider here](https://developer.here.com/documentation/geocoder/dev_guide/topics/resources.html).


### Install

```bash
composer require geocoder-php/here-provider
```

## Using

New applications on the Here platform use the `api_key` authentication method. This provider uses the **HERE Geocoding & Search API v7 (GS7)** by default when an API Key is provided via `createUsingApiKey`.

```php
$httpClient = new \Http\Discovery\Psr18Client();

// By default, this uses GS7 v1 endpoints
$provider = \Geocoder\Provider\Here\Here::createUsingApiKey($httpClient, 'your-api-key');

$result = $geocoder->geocodeQuery(GeocodeQuery::create('Buckingham Palace, London'));
```

### Using Legacy Geocoder API v6.2 with API Key

If you need to continue using the legacy v6.2 API with an API Key (e.g., for specific parameters or response shapes), you can specify the version:

```php
$httpClient = new \Http\Discovery\Psr18Client();

// Force use of Geocoder API v6.2 with an API Key
$provider = \Geocoder\Provider\Here\Here::createUsingApiKey($httpClient, 'your-api-key', false, '6.2');

$result = $geocoder->geocodeQuery(GeocodeQuery::create('Buckingham Palace, London'));
```

If you're using the legacy `app_code` authentication method, use the constructor on the provider like so. This will continue to use the **Geocoder API v6.2** endpoints.

```php
$httpClient = new \Http\Discovery\Psr18Client();

// You must provide both the app_id and app_code - This will use v6.2 endpoints
$provider = new \Geocoder\Provider\Here\Here($httpClient, 'app-id', 'app-code');

$result = $geocoder->geocodeQuery(GeocodeQuery::create('Buckingham Palace, London'));
```

### Migrating to GS7 (v7)

The transition to GS7 is automatic when using `createUsingApiKey`. Note that some response fields and parameters may differ slightly from v6.2, but the provider maps them to the same `HereAddress` model for backward compatibility.

Key changes in GS7:
- New base URLs: `*.search.hereapi.com/v1/*`
- Authentication via `apiKey` query parameter.
- Free-form queries use the `q` parameter instead of `searchtext`.
- Reverse geocoding uses the `at` parameter instead of `prox`.

### Language parameter

Define the preferred language of address elements in the result. Without a preferred language, the Here geocoder will return results in an official country language or in a regional primary language so that local people will understand. Language code must be provided according to RFC 4647 standard.

### Contribute

Contributions are very welcome! Send a pull request to the [main repository](https://github.com/geocoder-php/Geocoder) or
report any issues you find on the [issue tracker](https://github.com/geocoder-php/Geocoder/issues).
