<?php

declare(strict_types=1);

/*
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * @author Sébastien Barré <sebastien@sheub.eu>
 */

namespace Geocoder\Provider\Here\Tests;

use Geocoder\IntegrationTest\BaseTestCase;
use Geocoder\Location;
use Geocoder\Provider\Here\Here;
use Geocoder\Provider\Here\Model\HereAddress;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;

class HereTest extends BaseTestCase
{
    protected function getCacheDir(): ?string
    {
        if (isset($_SERVER['USE_CACHED_RESPONSES']) && true === $_SERVER['USE_CACHED_RESPONSES']) {
            return __DIR__.'/.cached_responses';
        }

        return null;
    }

    public function testGeocodeWithRealAddress(): void
    {
        if (!isset($_SERVER['HERE_API_KEY'])) {
            $this->markTestSkipped('You need to configure the HERE_API_KEY value in phpunit.xml');
        }

        $provider = Here::createUsingApiKey($this->getHttpClient($_SERVER['HERE_API_KEY']), $_SERVER['HERE_API_KEY'], false, '6.2');

        $results = $provider->geocodeQuery(GeocodeQuery::create('10 avenue Gambetta, Paris, France')->withLocale('fr-FR'));

        $this->assertInstanceOf(\Geocoder\Model\AddressCollection::class, $results);
        $this->assertCount(1, $results);

        /** @var Location $result */
        $result = $results->first();
        $this->assertInstanceOf(\Geocoder\Model\Address::class, $result);
        $this->assertEqualsWithDelta(48.8653, $result->getCoordinates()->getLatitude(), 0.01);
        $this->assertEqualsWithDelta(2.39844, $result->getCoordinates()->getLongitude(), 0.01);
        $this->assertNotNull($result->getBounds());
        $this->assertEqualsWithDelta(48.8664242, $result->getBounds()->getSouth(), 0.01);
        $this->assertEqualsWithDelta(2.3967311, $result->getBounds()->getWest(), 0.01);
        $this->assertEqualsWithDelta(48.8641758, $result->getBounds()->getNorth(), 0.01);
        $this->assertEqualsWithDelta(2.4001489, $result->getBounds()->getEast(), 0.01);
        $this->assertEquals(10, $result->getStreetNumber());

        $this->assertEquals('Avenue Gambetta', $result->getStreetName());
        $this->assertEquals(75020, $result->getPostalCode());
        $this->assertEquals('Paris', $result->getLocality());
        $this->assertEquals('France', $result->getCountry()->getName());
        $this->assertEquals('FRA', $result->getCountry()->getCode());
    }

    /**
     * @throws \Geocoder\Exception\Exception
     */
    public function testGeocodeWithDefaultAdditionalData(): void
    {
        if (!isset($_SERVER['HERE_API_KEY'])) {
            $this->markTestSkipped('You need to configure the HERE_API_KEY value in phpunit.xml');
        }

        $provider = Here::createUsingApiKey($this->getHttpClient($_SERVER['HERE_API_KEY']), $_SERVER['HERE_API_KEY'], false, '6.2');

        $results = $provider->geocodeQuery(GeocodeQuery::create('Sant Roc, Santa Coloma de Cervelló, Espanya')->withLocale('ca'));

        $this->assertInstanceOf(\Geocoder\Model\AddressCollection::class, $results);
        $this->assertCount(1, $results);

        /** @var HereAddress $result */
        $result = $results->first();

        $this->assertInstanceOf(\Geocoder\Model\Address::class, $result);
        $this->assertEqualsWithDelta(41.37854, $result->getCoordinates()->getLatitude(), 0.01);
        $this->assertEqualsWithDelta(2.01196, $result->getCoordinates()->getLongitude(), 0.01);
        $this->assertNotNull($result->getBounds());
        $this->assertEqualsWithDelta(41.36505, $result->getBounds()->getSouth(), 0.01);
        $this->assertEqualsWithDelta(1.99398, $result->getBounds()->getWest(), 0.01);
        $this->assertEqualsWithDelta(41.39203, $result->getBounds()->getNorth(), 0.01);
        $this->assertEqualsWithDelta(2.02994, $result->getBounds()->getEast(), 0.01);

        $this->assertEquals('08690', $result->getPostalCode());
        $this->assertEquals('Sant Roc', $result->getSubLocality());
        $this->assertEquals('Santa Coloma de Cervelló', $result->getLocality());
        $this->assertEquals('Espanya', $result->getCountry()->getName());
        $this->assertEquals('ESP', $result->getCountry()->getCode());

        $this->assertEquals('Espanya', $result->getAdditionalDataValue('CountryName'));
        $this->assertEquals('Catalunya', $result->getAdditionalDataValue('StateName'));
        $this->assertEquals('Barcelona', $result->getAdditionalDataValue('CountyName'));
    }

    /**
     * Validation of some AdditionalData filters.
     * https://developer.here.com/documentation/geocoder/topics/resource-params-additional.html.
     *
     * @throws \Geocoder\Exception\Exception
     */
    public function testGeocodeWithAdditionalData(): void
    {
        if (!isset($_SERVER['HERE_API_KEY'])) {
            $this->markTestSkipped('You need to configure the HERE_API_KEY value in phpunit.xml');
        }

        $provider = Here::createUsingApiKey($this->getHttpClient($_SERVER['HERE_API_KEY']), $_SERVER['HERE_API_KEY'], false, '6.2');

        $results = $provider->geocodeQuery(GeocodeQuery::create('Sant Roc, Santa Coloma de Cervelló, Espanya')
            ->withData('Country2', 'true')
            ->withData('IncludeShapeLevel', 'country')
            ->withData('IncludeRoutingInformation', 'true')
            ->withLocale('ca'));

        $this->assertInstanceOf(\Geocoder\Model\AddressCollection::class, $results);
        $this->assertCount(1, $results);

        /** @var HereAddress $result */
        $result = $results->first();
        $this->assertInstanceOf(\Geocoder\Model\Address::class, $result);
        $this->assertEqualsWithDelta(41.37854, $result->getCoordinates()->getLatitude(), 0.01);
        $this->assertEqualsWithDelta(2.01196, $result->getCoordinates()->getLongitude(), 0.01);
        $this->assertNotNull($result->getBounds());
        $this->assertEqualsWithDelta(41.36505, $result->getBounds()->getSouth(), 0.01);
        $this->assertEqualsWithDelta(1.99398, $result->getBounds()->getWest(), 0.01);
        $this->assertEqualsWithDelta(41.39203, $result->getBounds()->getNorth(), 0.01);
        $this->assertEqualsWithDelta(2.02994, $result->getBounds()->getEast(), 0.01);

        $this->assertEquals('08690', $result->getPostalCode());
        $this->assertEquals('Sant Roc', $result->getSubLocality());
        $this->assertEquals('Santa Coloma de Cervelló', $result->getLocality());
        $this->assertEquals('Espanya', $result->getCountry()->getName());
        $this->assertEquals('ESP', $result->getCountry()->getCode());

        $this->assertEquals('ES', $result->getAdditionalDataValue('Country2'));
        $this->assertEquals('Espanya', $result->getAdditionalDataValue('CountryName'));
        $this->assertEquals('Catalunya', $result->getAdditionalDataValue('StateName'));
        $this->assertEquals('Barcelona', $result->getAdditionalDataValue('CountyName'));
        $this->assertEquals('district', $result->getAdditionalDataValue('routing_address_matchLevel'));
        $this->assertEquals('NT_TzyupfxmTFN0Rh1TXEMqSA', $result->getAdditionalDataValue('routing_locationId'));
        $this->assertEquals('address', $result->getAdditionalDataValue('routing_result_type'));
        $this->assertEquals('WKTShapeType', $result->getShapeValue('_type'));
        $this->assertMatchesRegularExpression('/^MULTIPOLYGON/', $result->getShapeValue('Value'));
    }

    /**
     * Search for a specific city in a different country.
     *
     * @throws \Geocoder\Exception\Exception
     */
    public function testGeocodeWithExtraFilterCountry(): void
    {
        if (!isset($_SERVER['HERE_API_KEY'])) {
            $this->markTestSkipped('You need to configure the HERE_API_KEY value in phpunit.xml');
        }

        $provider = Here::createUsingApiKey($this->getHttpClient($_SERVER['HERE_API_KEY']), $_SERVER['HERE_API_KEY'], false, '6.2');

        $queryBarcelonaFromSpain = GeocodeQuery::create('Barcelona')->withData('country', 'ES')->withLocale('ca');
        $queryBarcelonaFromVenezuela = GeocodeQuery::create('Barcelona')->withData('country', 'VE')->withLocale('ca');

        $resultsSpain = $provider->geocodeQuery($queryBarcelonaFromSpain);
        $resultsVenezuela = $provider->geocodeQuery($queryBarcelonaFromVenezuela);

        $this->assertInstanceOf(\Geocoder\Model\AddressCollection::class, $resultsSpain);
        $this->assertInstanceOf(\Geocoder\Model\AddressCollection::class, $resultsVenezuela);
        $this->assertCount(1, $resultsSpain);
        $this->assertCount(1, $resultsVenezuela);

        $resultSpain = $resultsSpain->first();
        $resultVenezuela = $resultsVenezuela->first();

        $this->assertEquals('Barcelona', $resultSpain->getLocality());
        $this->assertEquals('Barcelona', $resultVenezuela->getLocality());
        $this->assertEquals('Espanya', $resultSpain->getCountry()->getName());
        $this->assertEquals('República Bolivariana De Venezuela', $resultVenezuela->getCountry()->getName());
        $this->assertEquals('ESP', $resultSpain->getCountry()->getCode());
        $this->assertEquals('VEN', $resultVenezuela->getCountry()->getCode());
    }

    /**
     * Search for a specific street in different towns in the same country.
     *
     * @throws \Geocoder\Exception\Exception
     */
    public function testGeocodeWithExtraFilterCity(): void
    {
        if (!isset($_SERVER['HERE_API_KEY'])) {
            $this->markTestSkipped('You need to configure the HERE_API_KEY value in phpunit.xml');
        }

        $provider = Here::createUsingApiKey($this->getHttpClient($_SERVER['HERE_API_KEY']), $_SERVER['HERE_API_KEY'], false, '6.2');

        $queryStreetCity1 = GeocodeQuery::create('Carrer de Barcelona')->withData('city', 'Sant Vicenç dels Horts')->withLocale('ca')->withLimit(1);
        $queryStreetCity2 = GeocodeQuery::create('Carrer de Barcelona')->withData('city', 'Girona')->withLocale('ca')->withLimit(1);
        $queryStreetCity3 = GeocodeQuery::create('Carrer de Barcelona')->withData('city', 'Pallejà')->withLocale('ca')->withLimit(1);

        $resultsCity1 = $provider->geocodeQuery($queryStreetCity1);
        $resultsCity2 = $provider->geocodeQuery($queryStreetCity2);
        $resultsCity3 = $provider->geocodeQuery($queryStreetCity3);

        $this->assertInstanceOf(\Geocoder\Model\AddressCollection::class, $resultsCity1);
        $this->assertInstanceOf(\Geocoder\Model\AddressCollection::class, $resultsCity2);
        $this->assertInstanceOf(\Geocoder\Model\AddressCollection::class, $resultsCity3);

        $resultCity1 = $resultsCity1->first();
        $resultCity2 = $resultsCity2->first();
        $resultCity3 = $resultsCity3->first();

        $this->assertEquals('Carrer de Barcelona', $resultCity1->getStreetName());
        $this->assertEquals('Carrer de Barcelona', $resultCity2->getStreetName());
        $this->assertEquals('Carrer de Barcelona', $resultCity3->getStreetName());
        $this->assertEquals('Sant Vicenç dels Horts', $resultCity1->getLocality());
        $this->assertEquals('Girona', $resultCity2->getLocality());
        $this->assertEquals('Pallejà', $resultCity3->getLocality());
        $this->assertEquals('Espanya', $resultCity1->getCountry()->getName());
        $this->assertEquals('Espanya', $resultCity2->getCountry()->getName());
        $this->assertEquals('Espanya', $resultCity3->getCountry()->getName());
        $this->assertEquals('ESP', $resultCity1->getCountry()->getCode());
        $this->assertEquals('ESP', $resultCity2->getCountry()->getCode());
        $this->assertEquals('ESP', $resultCity3->getCountry()->getCode());
    }

    public function testGeocodeWithExtraFilterCounty(): void
    {
        if (!isset($_SERVER['HERE_API_KEY'])) {
            $this->markTestSkipped('You need to configure the HERE_API_KEY value in phpunit.xml');
        }

        $provider = Here::createUsingApiKey($this->getHttpClient($_SERVER['HERE_API_KEY']), $_SERVER['HERE_API_KEY'], false, '6.2');

        $queryCityRegion1 = GeocodeQuery::create('Cabanes')->withData('county', 'Girona')->withLocale('ca')->withLimit(1);
        $queryCityRegion2 = GeocodeQuery::create('Cabanes')->withData('county', 'Castelló')->withLocale('ca')->withLimit(1);

        $resultsRegion1 = $provider->geocodeQuery($queryCityRegion1);
        $resultsRegion2 = $provider->geocodeQuery($queryCityRegion2);

        $this->assertInstanceOf(\Geocoder\Model\AddressCollection::class, $resultsRegion1);
        $this->assertInstanceOf(\Geocoder\Model\AddressCollection::class, $resultsRegion2);

        /** @var HereAddress $resultRegion1 */
        $resultRegion1 = $resultsRegion1->first();
        /** @var HereAddress $resultRegion2 */
        $resultRegion2 = $resultsRegion2->first();

        $this->assertEquals('Cabanes', $resultRegion1->getLocality());
        $this->assertEquals('Cabanes', $resultRegion2->getLocality());
        $this->assertEquals('Girona', $resultRegion1->getAdditionalDataValue('CountyName'));
        $this->assertEquals('Castelló', $resultRegion2->getAdditionalDataValue('CountyName'));
        $this->assertEquals('Catalunya', $resultRegion1->getAdditionalDataValue('StateName'));
        $this->assertEquals('Comunitat Valenciana', $resultRegion2->getAdditionalDataValue('StateName'));
        $this->assertEquals('Espanya', $resultRegion1->getCountry()->getName());
        $this->assertEquals('Espanya', $resultRegion2->getCountry()->getName());
        $this->assertEquals('ESP', $resultRegion1->getCountry()->getCode());
        $this->assertEquals('ESP', $resultRegion2->getCountry()->getCode());
    }

    public function testReverseWithRealCoordinates(): void
    {
        if (!isset($_SERVER['HERE_API_KEY'])) {
            $this->markTestSkipped('You need to configure the HERE_API_KEY value in phpunit.xml');
        }

        $provider = Here::createUsingApiKey($this->getHttpClient($_SERVER['HERE_API_KEY']), $_SERVER['HERE_API_KEY'], false, '6.2');

        $results = $provider->reverseQuery(ReverseQuery::fromCoordinates(48.8632156, 2.3887722));

        $this->assertInstanceOf(\Geocoder\Model\AddressCollection::class, $results);
        $this->assertCount(1, $results);

        /** @var Location $result */
        $result = $results->first();
        $this->assertInstanceOf(\Geocoder\Model\Address::class, $result);
        $this->assertEqualsWithDelta(48.8632147, $result->getCoordinates()->getLatitude(), 0.001);
        $this->assertEqualsWithDelta(2.3887722, $result->getCoordinates()->getLongitude(), 0.001);
        $this->assertNotNull($result->getBounds());
        $this->assertEqualsWithDelta(48.86315, $result->getBounds()->getSouth(), 0.001);
        $this->assertEqualsWithDelta(2.38853, $result->getBounds()->getWest(), 0.001);
        $this->assertEqualsWithDelta(48.8632147, $result->getBounds()->getNorth(), 0.001);
        $this->assertEqualsWithDelta(2.38883, $result->getBounds()->getEast(), 0.001);
        $this->assertEquals('Avenue Gambetta', $result->getStreetName());
        $this->assertEquals(75020, $result->getPostalCode());
        $this->assertEquals('Paris', $result->getLocality());
        $this->assertEquals('France', $result->getCountry()->getName());
        $this->assertEquals('FRA', $result->getCountry()->getCode());
    }

    public function testGetBaseUrlVersion6(): void
    {
        $provider = Here::createUsingApiKey($this->getMockedHttpClient(), 'apiKey', false, '6.2');
        $query = GeocodeQuery::create('Paris');
        $this->assertEquals(Here::GEOCODE_ENDPOINT_URL_API_KEY, $provider->getBaseUrl($query));

        $revQuery = ReverseQuery::fromCoordinates(48.8, 2.3);
        $this->assertEquals(Here::REVERSE_ENDPOINT_URL_API_KEY, $provider->getBaseUrl($revQuery));
    }

    public function testGetBaseUrlVersion7(): void
    {
        $provider = Here::createUsingApiKey($this->getMockedHttpClient(), 'apiKey', false, '7');
        $query = GeocodeQuery::create('Paris');
        $this->assertEquals(Here::GS7_GEOCODE_ENDPOINT_URL, $provider->getBaseUrl($query));

        $revQuery = ReverseQuery::fromCoordinates(48.8, 2.3);
        $this->assertEquals(Here::GS7_REVERSE_ENDPOINT_URL, $provider->getBaseUrl($revQuery));
    }

    public function testGetBaseUrlCIT(): void
    {
        $provider = Here::createUsingApiKey($this->getMockedHttpClient(), 'apiKey', true, '6.2');
        $query = GeocodeQuery::create('Paris');
        $this->assertEquals(Here::GEOCODE_CIT_ENDPOINT_API_KEY, $provider->getBaseUrl($query));
    }

    public function testGeocodeGS7Mapping(): void
    {
        $json = '{
            "items": [
                {
                    "title": "Avenue Gambetta, 75020 Paris, France",
                    "id": "here:af:streetsection:9k8l",
                    "resultType": "street",
                    "address": {
                        "label": "Avenue Gambetta, 75020 Paris, France",
                        "countryCode": "FRA",
                        "countryName": "France",
                        "state": "Île-de-France",
                        "county": "Paris",
                        "city": "Paris",
                        "district": "20e Arrondissement",
                        "street": "Avenue Gambetta",
                        "postalCode": "75020",
                        "houseNumber": "10"
                    },
                    "position": {
                        "lat": 48.8653,
                        "lng": 2.39844
                    },
                    "mapView": {
                        "west": 2.39673,
                        "south": 48.86417,
                        "east": 2.40015,
                        "north": 48.86642
                    }
                }
            ]
        }';

        $provider = Here::createUsingApiKey($this->getMockedHttpClient($json), 'apiKey');
        $results = $provider->geocodeQuery(GeocodeQuery::create('10 avenue Gambetta, Paris, France'));

        $this->assertCount(1, $results);
        /** @var HereAddress $result */
        $result = $results->first();

        $this->assertEquals(48.8653, $result->getCoordinates()->getLatitude());
        $this->assertEquals(2.39844, $result->getCoordinates()->getLongitude());
        $this->assertEquals(48.86417, $result->getBounds()->getSouth());
        $this->assertEquals(2.39673, $result->getBounds()->getWest());
        $this->assertEquals(48.86642, $result->getBounds()->getNorth());
        $this->assertEquals(2.40015, $result->getBounds()->getEast());
        $this->assertEquals('10', $result->getStreetNumber());
        $this->assertEquals('Avenue Gambetta', $result->getStreetName());
        $this->assertEquals('75020', $result->getPostalCode());
        $this->assertEquals('Paris', $result->getLocality());
        $this->assertEquals('20e Arrondissement', $result->getSubLocality());
        $this->assertEquals('FRA', $result->getCountry()->getCode());
        $this->assertEquals('France', $result->getCountry()->getName());
        $this->assertEquals('here:af:streetsection:9k8l', $result->getLocationId());
        $this->assertEquals('street', $result->getLocationType());
        $this->assertEquals('Avenue Gambetta, 75020 Paris, France', $result->getLocationName());
        $this->assertEquals('France', $result->getAdditionalDataValue('CountryName'));
        $this->assertEquals('Île-de-France', $result->getAdditionalDataValue('StateName'));
        $this->assertEquals('Paris', $result->getAdditionalDataValue('CountyName'));
    }

    public function testReverseGS7Mapping(): void
    {
        $json = '{
            "items": [
                {
                    "title": "Avenue Gambetta, 75020 Paris, France",
                    "id": "here:af:streetsection:9k8l",
                    "resultType": "street",
                    "address": {
                        "label": "Avenue Gambetta, 75020 Paris, France",
                        "countryCode": "FRA",
                        "countryName": "France",
                        "state": "Île-de-France",
                        "county": "Paris",
                        "city": "Paris",
                        "district": "20e Arrondissement",
                        "street": "Avenue Gambetta",
                        "postalCode": "75020"
                    },
                    "position": {
                        "lat": 48.8632,
                        "lng": 2.3888
                    },
                    "mapView": {
                        "west": 2.3885,
                        "south": 48.8631,
                        "east": 2.3889,
                        "north": 48.8633
                    }
                }
            ]
        }';

        $provider = Here::createUsingApiKey($this->getMockedHttpClient($json), 'apiKey');
        $results = $provider->reverseQuery(ReverseQuery::fromCoordinates(48.8632, 2.3888));

        $this->assertCount(1, $results);
        /** @var HereAddress $result */
        $result = $results->first();

        $this->assertEquals(48.8632, $result->getCoordinates()->getLatitude());
        $this->assertEquals(2.3888, $result->getCoordinates()->getLongitude());
        $this->assertEquals('Avenue Gambetta', $result->getStreetName());
        $this->assertEquals('75020', $result->getPostalCode());
        $this->assertEquals('Paris', $result->getLocality());
    }

    public function testParseGS7ResponseWithoutMapView(): void
    {
        $json = '{
            "items": [
                {
                    "title": "Avenue Gambetta, 75020 Paris, France",
                    "id": "here:af:streetsection:9k8l",
                    "resultType": "street",
                    "address": {
                        "label": "Avenue Gambetta, 75020 Paris, France",
                        "countryCode": "FRA"
                    },
                    "position": {
                        "lat": 48.8653,
                        "lng": 2.39844
                    }
                }
            ]
        }';

        $provider = Here::createUsingApiKey($this->getMockedHttpClient($json), 'apiKey');
        $results = $provider->geocodeQuery(GeocodeQuery::create('10 avenue Gambetta, Paris, France'));

        $this->assertCount(1, $results);
        /** @var HereAddress $result */
        $result = $results->first();
        $this->assertNull($result->getBounds());
    }

    public function testParseV6ResponseWithDisplayPosition(): void
    {
        $json = '{
            "Response": {
                "View": [
                    {
                        "Result": [
                            {
                                "Location": {
                                    "LocationId": "NT_lP.Bf9f-N7Y.I.M.V.I.M.V",
                                    "LocationType": "street",
                                    "DisplayPosition": {
                                        "Latitude": 48.8653,
                                        "Longitude": 2.39844
                                    },
                                    "MapView": {
                                        "TopLeft": {"Latitude": 48.86642, "Longitude": 2.39673},
                                        "BottomRight": {"Latitude": 48.86417, "Longitude": 2.40015}
                                    },
                                    "Address": {
                                        "Country": "FRA"
                                    }
                                }
                            }
                        ]
                    }
                ]
            }
        }';

        $provider = new Here($this->getMockedHttpClient($json), 'appId', 'appCode');
        $results = $provider->geocodeQuery(GeocodeQuery::create('10 avenue Gambetta, Paris, France'));

        $this->assertCount(1, $results);
        /** @var HereAddress $result */
        $result = $results->first();
        $this->assertEquals(48.8653, $result->getCoordinates()->getLatitude());
    }

    public function testParseV6ResponseWithoutAdditionalData(): void
    {
        $json = '{
            "Response": {
                "View": [
                    {
                        "Result": [
                            {
                                "Location": {
                                    "LocationId": "NT_lP.Bf9f-N7Y.I.M.V.I.M.V",
                                    "LocationType": "street",
                                    "DisplayPosition": {"Latitude": 48.8653, "Longitude": 2.39844},
                                    "MapView": {
                                        "TopLeft": {"Latitude": 48.86642, "Longitude": 2.39673},
                                        "BottomRight": {"Latitude": 48.86417, "Longitude": 2.40015}
                                    },
                                    "Address": {
                                        "Country": "FRA"
                                    }
                                }
                            }
                        ]
                    }
                ]
            }
        }';

        $provider = new Here($this->getMockedHttpClient($json), 'appId', 'appCode');
        $results = $provider->geocodeQuery(GeocodeQuery::create('10 avenue Gambetta, Paris, France'));

        $this->assertCount(1, $results);
        /** @var HereAddress $result */
        $result = $results->first();
        $this->assertNull($result->getCountry()->getName());
    }

    public function testGeocodeV6WithAllStructuredParams(): void
    {
        $provider = new Here($this->getMockedHttpClient('{"Response": {"View": []}}'), 'appId', 'appCode');
        $query = GeocodeQuery::create('Paris')
            ->withData('country', 'FRA')
            ->withData('state', 'IDF')
            ->withData('county', 'Paris')
            ->withData('city', 'Paris')
            ->withLocale('fr-FR');
        
        $provider->geocodeQuery($query);
        $this->addToAssertionCount(1);
    }

    public function testGeocodeGS7WithAllStructuredParams(): void
    {
        $provider = Here::createUsingApiKey($this->getMockedHttpClient('{"items": []}'), 'apiKey');
        $query = GeocodeQuery::create('Paris')
            ->withData('country', 'FRA')
            ->withData('state', 'IDF')
            ->withData('county', 'Paris')
            ->withData('city', 'Paris')
            ->withLocale('fr-FR');
        
        $provider->geocodeQuery($query);
        $this->addToAssertionCount(1);
    }

    public function testGeocodeWithInvalidData(): void
    {
        $this->expectException(\Geocoder\Exception\InvalidServerResponse::class);

        $provider = new Here($this->getMockedHttpClient(), 'appId', 'appCode');
        $provider->geocodeQuery(GeocodeQuery::create('foobar'));
    }

    public function testGeocodeIpv4(): void
    {
        $this->expectException(\Geocoder\Exception\UnsupportedOperation::class);
        $this->expectExceptionMessage('The Here provider does not support IP addresses, only street addresses.');

        $provider = $this->getProvider();
        $provider->geocodeQuery(GeocodeQuery::create('127.0.0.1'));
    }

    public function testGeocodeWithLocalhostIPv6(): void
    {
        $this->expectException(\Geocoder\Exception\UnsupportedOperation::class);
        $this->expectExceptionMessage('The Here provider does not support IP addresses, only street addresses.');

        $provider = $this->getProvider();
        $provider->geocodeQuery(GeocodeQuery::create('::1'));
    }

    public function testGeocodeInvalidApiKey(): void
    {
        $this->expectException(\Geocoder\Exception\InvalidCredentials::class);
        $this->expectExceptionMessage('Invalid or missing api key.');

        $provider = new Here(
            $this->getMockedHttpClient(
                '{
					"type": {
						"subtype": "InvalidCredentials"
					}
                }'
            ),
            'appId',
            'appCode'
        );
        $provider->geocodeQuery(GeocodeQuery::create('New York'));
    }

    public function testGeocodeGS7InvalidApiKey(): void
    {
        $this->expectException(\Geocoder\Exception\InvalidCredentials::class);
        $this->expectExceptionMessage('Invalid or missing api key.');

        $provider = Here::createUsingApiKey(
            $this->getMockedHttpClient(
                '{
                    "error": "Unauthorized"
                }'
            ),
            'apiKey'
        );
        $provider->geocodeQuery(GeocodeQuery::create('New York'));
    }

    public function testGeocodeWithInvalidInputData(): void
    {
        $this->expectException(\Geocoder\Exception\InvalidArgument::class);
        $this->expectExceptionMessage('Input parameter validation failed.');

        $provider = new Here(
            $this->getMockedHttpClient(
                '{
					"type": {
						"subtype": "InvalidInputData"
					}
                }'
            ),
            'appId',
            'appCode'
        );
        $provider->geocodeQuery(GeocodeQuery::create('New York'));
    }

    public function testGeocodeWithQuotaExceeded(): void
    {
        $this->expectException(\Geocoder\Exception\QuotaExceeded::class);
        $this->expectExceptionMessage('Valid request but quota exceeded.');

        $provider = new Here(
            $this->getMockedHttpClient(
                '{
					"type": {
						"subtype": "QuotaExceeded"
					}
                }'
            ),
            'appId',
            'appCode'
        );
        $provider->geocodeQuery(GeocodeQuery::create('New York'));
    }

    public function testGeocodeWithNoCredentials(): void
    {
        $this->expectException(\Geocoder\Exception\InvalidCredentials::class);
        $this->expectExceptionMessage('Invalid or missing api key.');

        $provider = new Here($this->getMockedHttpClient());
        $provider->geocodeQuery(GeocodeQuery::create('New York'));
    }

    public function testGeocodeGS7WithNoResults(): void
    {
        $provider = Here::createUsingApiKey($this->getMockedHttpClient('{"items": []}'), 'apiKey');
        $results = $provider->geocodeQuery(GeocodeQuery::create('New York'));

        $this->assertCount(0, $results);
    }

    public function testGeocodeV6WithNoResponse(): void
    {
        $provider = new Here($this->getMockedHttpClient('{}'), 'appId', 'appCode');
        $results = $provider->geocodeQuery(GeocodeQuery::create('New York'));

        $this->assertCount(0, $results);
    }

    public function testGeocodeV6WithEmptyView(): void
    {
        $provider = new Here($this->getMockedHttpClient('{"Response": {"View": []}}'), 'appId', 'appCode');
        $results = $provider->geocodeQuery(GeocodeQuery::create('New York'));

        $this->assertCount(0, $results);
    }

    public function testGeocodeWithRealIPv6(): void
    {
        $this->expectException(\Geocoder\Exception\UnsupportedOperation::class);
        $this->expectExceptionMessage('The Here provider does not support IP addresses, only street addresses.');

        $provider = $this->getProvider();
        $provider->geocodeQuery(GeocodeQuery::create('::ffff:88.188.221.14'));
    }

    public function getProvider(): Here
    {
        if (!isset($_SERVER['HERE_API_KEY'])) {
            $this->markTestSkipped('You need to configure the HERE_API_KEY value in phpunit.xml');
        }

        return Here::createUsingApiKey($this->getHttpClient(), $_SERVER['HERE_API_KEY'], false, '6.2');
    }
}
