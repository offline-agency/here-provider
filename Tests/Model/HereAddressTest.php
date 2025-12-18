<?php

declare(strict_types=1);

namespace Geocoder\Provider\Here\Tests\Model;

use Geocoder\Model\AdminLevelCollection;
use Geocoder\Provider\Here\Model\HereAddress;
use PHPUnit\Framework\TestCase;

class HereAddressTest extends TestCase
{
    private function createAddress(): HereAddress
    {
        return new HereAddress('Here', new AdminLevelCollection());
    }

    public function testGettersAndWithers(): void
    {
        $address = $this->createAddress();

        $address = $address->withLocationId('id123');
        $this->assertEquals('id123', $address->getLocationId());

        $address = $address->withLocationType('street');
        $this->assertEquals('street', $address->getLocationType());

        $address = $address->withLocationName('Paris');
        $this->assertEquals('Paris', $address->getLocationName());
    }

    public function testAdditionalData(): void
    {
        $address = $this->createAddress();
        
        $data = [
            ['key' => 'foo', 'value' => 'bar'],
            ['key' => 'baz', 'value' => 123],
        ];

        $address = $address->withAdditionalData($data);
        
        $this->assertTrue($address->hasAdditionalDataValue('foo'));
        $this->assertEquals('bar', $address->getAdditionalDataValue('foo'));
        $this->assertEquals(123, $address->getAdditionalDataValue('baz'));
        $this->assertFalse($address->hasAdditionalDataValue('missing'));
        $this->assertEquals('default', $address->getAdditionalDataValue('missing', 'default'));
    }

    public function testShape(): void
    {
        $address = $this->createAddress();

        $shape = [
            'type' => 'Point',
            'coordinates' => [1.2, 3.4],
        ];

        $address = $address->withShape($shape);

        $this->assertTrue($address->hasShapeValue('type'));
        $this->assertEquals('Point', $address->getShapeValue('type'));
        $this->assertEquals([1.2, 3.4], $address->getShapeValue('coordinates'));
        $this->assertFalse($address->hasShapeValue('missing'));
        $this->assertEquals('default', $address->getShapeValue('missing', 'default'));
    }

    public function testEmptyShape(): void
    {
        $address = $this->createAddress();
        $address = $address->withShape([]);
        $this->assertNull($address->getShapeValue('any'));
    }
}

