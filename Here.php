<?php

declare(strict_types=1);

/*
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Geocoder\Provider\Here;

use Geocoder\Collection;
use Geocoder\Exception\InvalidArgument;
use Geocoder\Exception\InvalidCredentials;
use Geocoder\Exception\QuotaExceeded;
use Geocoder\Exception\UnsupportedOperation;
use Geocoder\Http\Provider\AbstractHttpProvider;
use Geocoder\Model\AddressBuilder;
use Geocoder\Model\AddressCollection;
use Geocoder\Provider\Here\Model\HereAddress;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\Query;
use Geocoder\Query\ReverseQuery;
use Psr\Http\Client\ClientInterface;

/**
 * @author Sébastien Barré <sebastien@sheub.eu>
 */
final class Here extends AbstractHttpProvider implements Provider
{
    /**
     * @var string
     */
    public const GS7_GEOCODE_ENDPOINT_URL = 'https://geocode.search.hereapi.com/v1/geocode';

    /**
     * @var string
     */
    public const GS7_REVERSE_ENDPOINT_URL = 'https://revgeocode.search.hereapi.com/v1/revgeocode';

    /**
     * @var string
     */
    public const GEOCODE_ENDPOINT_URL_API_KEY = 'https://geocoder.ls.hereapi.com/6.2/geocode.json';

    /**
     * @var string
     */
    public const GEOCODE_ENDPOINT_URL_APP_CODE = 'https://geocoder.api.here.com/6.2/geocode.json';

    /**
     * @var string
     */
    public const GEOCODE_CIT_ENDPOINT_API_KEY = 'https:/geocoder.sit.ls.hereapi.com/6.2/geocode.json';

    /**
     * @var string
     */
    public const GEOCODE_CIT_ENDPOINT_APP_CODE = 'https://geocoder.cit.api.here.com/6.2/geocode.json';

    /**
     * @var string
     */
    public const REVERSE_ENDPOINT_URL_API_KEY = 'https://reverse.geocoder.ls.hereapi.com/6.2/reversegeocode.json';

    /**
     * @var string
     */
    public const REVERSE_ENDPOINT_URL_APP_CODE = 'https://reverse.geocoder.api.here.com/6.2/reversegeocode.json';

    /**
     * @var string
     */
    public const REVERSE_CIT_ENDPOINT_URL_API_KEY = 'https://reverse.geocoder.sit.ls.hereapi.com/6.2/reversegeocode.json';

    /**
     * @var string
     */
    public const REVERSE_CIT_ENDPOINT_URL_APP_CODE = 'https://reverse.geocoder.cit.api.here.com/6.2/reversegeocode.json';

    /**
     * @var string[]
     */
    public const GEOCODE_ADDITIONAL_DATA_PARAMS = [
        'CrossingStreets',
        'PreserveUnitDesignators',
        'Country2',
        'IncludeChildPOIs',
        'IncludeRoutingInformation',
        'AdditionalAddressProvider',
        'HouseNumberMode',
        'FlexibleAdminValues',
        'IntersectionSnapTolerance',
        'AddressRangeSqueezeOffset',
        'AddressRangeSqueezeFactor',
        'AddressRangeSqueezeOffset',
        'IncludeShapeLevel',
        'RestrictLevel',
        'SuppressStreetType',
        'NormalizeNames',
        'IncludeMicroPointAddresses',
    ];

    /**
     * @var string|null
     */
    private ?string $appId;

    /**
     * @var string|null
     */
    private ?string $appCode;

    /**
     * @var bool
     */
    private bool $useCIT;

    /**
     * @var string|null
     */
    private ?string $apiKey = null;

    /**
     * @var string
     */
    private string $version;

    /**
     * @param ClientInterface $client  an HTTP adapter
     * @param string|null     $appId   an App ID
     * @param string|null     $appCode an App code
     * @param bool            $useCIT  use Customer Integration Testing environment (CIT) instead of production
     * @param string          $version version of the API to use ('6.2' or '7')
     */
    public function __construct(ClientInterface $client, ?string $appId = null, ?string $appCode = null, bool $useCIT = false, string $version = '6.2')
    {
        $this->appId = $appId;
        $this->appCode = $appCode;
        $this->useCIT = $useCIT;
        $this->version = $version;

        parent::__construct($client);
    }

    public static function createUsingApiKey(ClientInterface $client, string $apiKey, bool $useCIT = false, string $version = '7'): self
    {
        $client = new self($client, null, null, $useCIT, $version);
        $client->apiKey = $apiKey;

        return $client;
    }

    public function geocodeQuery(GeocodeQuery $query): Collection
    {
        // This API doesn't handle IPs
        if (filter_var($query->getText(), FILTER_VALIDATE_IP)) {
            throw new UnsupportedOperation('The Here provider does not support IP addresses, only street addresses.');
        }

        if ('7' === $this->version && null !== $this->apiKey) {
            return $this->geocodeQueryGS7($query);
        }

        $queryParams = $this->withApiCredentials([
            'searchtext' => $query->getText(),
            'gen' => 9,
            'additionaldata' => $this->getAdditionalDataParam($query),
        ]);

        if (null !== $query->getData('country')) {
            $queryParams['country'] = $query->getData('country');
        }

        if (null !== $query->getData('state')) {
            $queryParams['state'] = $query->getData('state');
        }

        if (null !== $query->getData('county')) {
            $queryParams['county'] = $query->getData('county');
        }

        if (null !== $query->getData('city')) {
            $queryParams['city'] = $query->getData('city');
        }

        if (null !== $query->getLocale()) {
            $queryParams['language'] = $query->getLocale();
        }

        return $this->executeQuery(sprintf('%s?%s', $this->getBaseUrl($query), http_build_query($queryParams)), $query->getLimit());
    }

    private function geocodeQueryGS7(GeocodeQuery $query): Collection
    {
        $queryParams = $this->withApiCredentials([
            'q' => $query->getText(),
            'limit' => $query->getLimit(),
        ]);

        $qq = [];
        if (null !== $country = $query->getData('country')) {
            $qq[] = 'country='.$country;
        }
        if (null !== $state = $query->getData('state')) {
            $qq[] = 'state='.$state;
        }
        if (null !== $county = $query->getData('county')) {
            $qq[] = 'county='.$county;
        }
        if (null !== $city = $query->getData('city')) {
            $qq[] = 'city='.$city;
        }

        if (!empty($qq)) {
            $queryParams['qq'] = implode(';', $qq);
        }

        if (null !== $query->getLocale()) {
            $queryParams['lang'] = $query->getLocale();
        }

        return $this->executeQuery(sprintf('%s?%s', $this->getBaseUrl($query), http_build_query($queryParams)), $query->getLimit());
    }

    public function reverseQuery(ReverseQuery $query): Collection
    {
        if ('7' === $this->version && null !== $this->apiKey) {
            return $this->reverseQueryGS7($query);
        }

        $coordinates = $query->getCoordinates();

        $queryParams = $this->withApiCredentials([
            'gen' => 9,
            'mode' => 'retrieveAddresses',
            'prox' => sprintf('%s,%s', $coordinates->getLatitude(), $coordinates->getLongitude()),
            'maxresults' => $query->getLimit(),
        ]);

        return $this->executeQuery(sprintf('%s?%s', $this->getBaseUrl($query), http_build_query($queryParams)), $query->getLimit());
    }

    private function reverseQueryGS7(ReverseQuery $query): Collection
    {
        $coordinates = $query->getCoordinates();

        $queryParams = $this->withApiCredentials([
            'at' => sprintf('%s,%s', $coordinates->getLatitude(), $coordinates->getLongitude()),
            'limit' => $query->getLimit(),
        ]);

        if (null !== $query->getLocale()) {
            $queryParams['lang'] = $query->getLocale();
        }

        return $this->executeQuery(sprintf('%s?%s', $this->getBaseUrl($query), http_build_query($queryParams)), $query->getLimit());
    }

    private function executeQuery(string $url, int $limit): Collection
    {
        $content = $this->getUrlContents($url);

        $json = json_decode($content, true);

        if (isset($json['error'])) {
            if ('Unauthorized' === $json['error']) {
                throw new InvalidCredentials('Invalid or missing api key.');
            }
        }

        if (isset($json['type'])) {
            switch ($json['type']['subtype']) {
                case 'InvalidInputData':
                    throw new InvalidArgument('Input parameter validation failed.');
                case 'QuotaExceeded':
                    throw new QuotaExceeded('Valid request but quota exceeded.');
                case 'InvalidCredentials':
                    throw new InvalidCredentials('Invalid or missing api key.');
            }
        }

        if (isset($json['items'])) {
            return $this->parseGS7Response($json['items'], $limit);
        }

        if (!isset($json['Response']) || empty($json['Response'])) {
            return new AddressCollection([]);
        }

        if (!isset($json['Response']['View'][0])) {
            return new AddressCollection([]);
        }

        $locations = $json['Response']['View'][0]['Result'];

        return $this->parseV6Response($locations, $limit);
    }

    private function parseGS7Response(array $items, int $limit): Collection
    {
        $results = [];

        foreach ($items as $item) {
            $builder = new AddressBuilder($this->getName());

            $position = $item['position'];
            $builder->setCoordinates($position['lat'], $position['lng']);

            if (isset($item['mapView'])) {
                $mapView = $item['mapView'];
                $builder->setBounds($mapView['south'], $mapView['west'], $mapView['north'], $mapView['east']);
            }

            $address = $item['address'];
            $builder->setStreetNumber($address['houseNumber'] ?? null);
            $builder->setStreetName($address['street'] ?? null);
            $builder->setPostalCode($address['postalCode'] ?? null);
            $builder->setLocality($address['city'] ?? null);
            $builder->setSubLocality($address['district'] ?? null);
            $builder->setCountryCode($address['countryCode'] ?? null);
            $builder->setCountry($address['countryName'] ?? null);

            /** @var HereAddress $hereAddress */
            $hereAddress = $builder->build(HereAddress::class);
            $hereAddress = $hereAddress->withLocationId($item['id'] ?? null);
            $hereAddress = $hereAddress->withLocationType($item['resultType'] ?? null);
            $hereAddress = $hereAddress->withLocationName($item['title'] ?? null);

            $additionalData = [];
            if (isset($address['countryName'])) {
                $additionalData[] = ['key' => 'CountryName', 'value' => $address['countryName']];
            }
            if (isset($address['state'])) {
                $additionalData[] = ['key' => 'StateName', 'value' => $address['state']];
            }
            if (isset($address['county'])) {
                $additionalData[] = ['key' => 'CountyName', 'value' => $address['county']];
            }

            $hereAddress = $hereAddress->withAdditionalData($additionalData);
            $results[] = $hereAddress;

            if (count($results) >= $limit) {
                break;
            }
        }

        return new AddressCollection($results);
    }

    private function parseV6Response(array $locations, int $limit): Collection
    {
        $results = [];

        foreach ($locations as $loc) {
            $location = $loc['Location'];
            $builder = new AddressBuilder($this->getName());
            $coordinates = isset($location['NavigationPosition'][0]) ? $location['NavigationPosition'][0] : $location['DisplayPosition'];
            $builder->setCoordinates($coordinates['Latitude'], $coordinates['Longitude']);
            $bounds = $location['MapView'];

            $builder->setBounds($bounds['BottomRight']['Latitude'], $bounds['TopLeft']['Longitude'], $bounds['TopLeft']['Latitude'], $bounds['BottomRight']['Longitude']);
            $builder->setStreetNumber($location['Address']['HouseNumber'] ?? null);
            $builder->setStreetName($location['Address']['Street'] ?? null);
            $builder->setPostalCode($location['Address']['PostalCode'] ?? null);
            $builder->setLocality($location['Address']['City'] ?? null);
            $builder->setSubLocality($location['Address']['District'] ?? null);
            $builder->setCountryCode($location['Address']['Country'] ?? null);

            // The name of the country can be found in the AdditionalData.
            $additionalData = $location['Address']['AdditionalData'] ?? null;
            if (!empty($additionalData)) {
                $builder->setCountry($additionalData[array_search('CountryName', array_column($additionalData, 'key'))]['value'] ?? null);
            }

            // There may be a second AdditionalData. For example if "IncludeRoutingInformation" parameter is added
            $extraAdditionalData = $loc['AdditionalData'] ?? [];

            /** @var HereAddress $address */
            $address = $builder->build(HereAddress::class);
            $address = $address->withLocationId($location['LocationId'] ?? null);
            $address = $address->withLocationType($location['LocationType']);
            $address = $address->withAdditionalData(array_merge($additionalData ?? [], $extraAdditionalData));
            $address = $address->withShape($location['Shape'] ?? null);
            $results[] = $address;

            if (count($results) >= $limit) {
                break;
            }
        }

        return new AddressCollection($results);
    }

    public function getName(): string
    {
        return 'Here';
    }

    /**
     * Get serialized additional data param.
     */
    private function getAdditionalDataParam(GeocodeQuery $query): string
    {
        $additionalDataParams = [
            'IncludeShapeLevel' => 'country',
        ];

        foreach (self::GEOCODE_ADDITIONAL_DATA_PARAMS as $paramKey) {
            if (null !== $query->getData($paramKey)) {
                $additionalDataParams[$paramKey] = $query->getData($paramKey);
            }
        }

        return $this->serializeComponents($additionalDataParams);
    }

    /**
     * Add API credentials to query params.
     *
     * @param array<string, string> $queryParams
     *
     * @return array<string, string>
     */
    private function withApiCredentials(array $queryParams): array
    {
        if (null !== $this->apiKey) {
            $queryParams['apiKey'] = $this->apiKey;
        } elseif (!empty($this->appId) && !empty($this->appCode)) {
            $queryParams['app_id'] = $this->appId;
            $queryParams['app_code'] = $this->appCode;
        } else {
            throw new InvalidCredentials('Invalid or missing api key.');
        }

        return $queryParams;
    }

    public function getBaseUrl(Query $query): string
    {
        $usingApiKey = null !== $this->apiKey;

        if ('7' === $this->version && $usingApiKey) {
            return ($query instanceof ReverseQuery) ? self::GS7_REVERSE_ENDPOINT_URL : self::GS7_GEOCODE_ENDPOINT_URL;
        }

        if ($query instanceof ReverseQuery) {
            if ($this->useCIT) {
                return $usingApiKey ? self::REVERSE_CIT_ENDPOINT_URL_API_KEY : self::REVERSE_CIT_ENDPOINT_URL_APP_CODE;
            }

            return $usingApiKey ? self::REVERSE_ENDPOINT_URL_API_KEY : self::REVERSE_ENDPOINT_URL_APP_CODE;
        }

        if ($this->useCIT) {
            return $usingApiKey ? self::GEOCODE_CIT_ENDPOINT_API_KEY : self::GEOCODE_CIT_ENDPOINT_APP_CODE;
        }

        return $usingApiKey ? self::GEOCODE_ENDPOINT_URL_API_KEY : self::GEOCODE_ENDPOINT_URL_APP_CODE;
    }

    /**
     * Serialize the component query parameter.
     *
     * @param array<string, string> $components
     */
    private function serializeComponents(array $components): string
    {
        return implode(';', array_map(function ($name, $value) {
            return sprintf('%s,%s', $name, $value);
        }, array_keys($components), $components));
    }
}
