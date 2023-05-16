<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SecretsCache\APCUCache;

class APCUCacheTest extends TestCase
{

    const ITEM_KEY = 'testitem';
    const ITEM_KEY2 = 'testitem2';

    /**
     * @before
     */
    public static function setupTestEnv(): void
    {
        apcu_clear_cache();
    }

    public function testCanSetCacheItem()
    {

        $cache = new APCUCache();
        $this->assertEquals(true, $cache->set(self::ITEM_KEY, 'testvalue', 3600));

        $this->assertEquals('testvalue', $cache->get(self::ITEM_KEY));
    }

    public function testGetItemWithDefault()
    {
        $cache = new APCUCache();
        $this->assertEquals('testdefault', $cache->get('unknownkey', 'testdefault'));
    }

    public function testDeleteCacheItem()
    {
        $cache = new APCUCache();
        $cache->set(self::ITEM_KEY, 'testvalue', 3600);

        $this->assertEquals('testvalue', $cache->get(self::ITEM_KEY));

        $cache->delete(self::ITEM_KEY);

        $this->assertEquals(null, $cache->get(self::ITEM_KEY));
    }

    public function testGetMultiple()
    {
        $cache = new APCUCache();
        $cache->set(self::ITEM_KEY, 'testvalue', 3600);
        $cache->set(Self::ITEM_KEY2, 'testvalue2', 3600);

        $this->assertEquals(
            [
                self::ITEM_KEY => 'testvalue',
                self::ITEM_KEY2 => 'testvalue2'
            ],
            $cache->getMultiple([self::ITEM_KEY, self::ITEM_KEY2])
        );
    }

    public function testGetMultipleWithDefault()
    {
        $cache = new APCUCache();
        $cache->set(self::ITEM_KEY, 'testvalue', 3600);

        $expected = [
            self::ITEM_KEY => 'testvalue',
            self::ITEM_KEY2 => 'testdefault'
        ];
        $this->assertEquals(
            $expected,
            $cache->getMultiple(
                [self::ITEM_KEY, self::ITEM_KEY2],
                'testdefault'
            )
        );
    }

    public function testTimeCOnversion()
    {
        $cache = new APCUCache(200);

        $this->assertEquals(200, $cache->convertToSeconds(null));

        $this->assertEquals(20, $cache->convertToSeconds(20));

        $dateInterval =  DateInterval::createFromDateString('3600 seconds');
        $this->assertEquals(3600, $cache->convertToSeconds($dateInterval));

        $dateInterval = DateInterval::createFromDateString('1 hour');
        $this->assertEquals(3600, $cache->convertToSeconds($dateInterval));
    }

    public function testSetMultiple()
    {
        $cache = new APCUCache();

        $expected = [
            self::ITEM_KEY => 'testvalue',
            self::ITEM_KEY2 => 'testdefault'
        ];

        $cache->setMultiple($expected, 3600);

        $this->assertEquals($expected, $cache->getMultiple([self::ITEM_KEY, self::ITEM_KEY2]));
    }

    public function deleteMultiple()
    {
        $cache = new APCUCache();
        $another_cache = new APCUCache(prefix: 'anothercache');


        $expected = [
            self::ITEM_KEY => 'testvalue',
            self::ITEM_KEY2 => 'testvalue2'
        ];

        $cache->setMultiple($expected, 3600);
        $another_cache->setMultiple($expected, 3600);

        $this->assertEquals(
            $expected,
            $cache->getMultiple([self::ITEM_KEY, self::ITEM_KEY2])
        );

        $this->deleteMultiple([self::ITEM_KEY, self::ITEM_KEY2]);

        $this->assertEquals([], $cache->getMultiple([self::ITEM_KEY, self::ITEM_KEY2]));
        $this->assertEquals($expected, $another_cache->getMultiple([self::ITEM_KEY, self::ITEM_KEY2]));

    }

    public function testPrefixes()
    {

        $my_cache = new APCUCache(prefix: 'mycache');
        $my_cache->set(self::ITEM_KEY, 'test1');

        $another_cache = new APCUCache(prefix: 'anothercache');
        $another_cache->set(self::ITEM_KEY, 'test2');

        $this->assertNotEquals('test2', $my_cache->get(self::ITEM_KEY));
        $this->assertNotEquals('test1', $another_cache->get(self::ITEM_KEY));
    }

    public function testOnlyClearsOwnNameSpace()
    {
        $my_cache = new APCUCache(prefix: 'mycache');
        $my_cache->set(self::ITEM_KEY, 'test1');

        $another_cache = new APCUCache(prefix: 'anothercache');
        $another_cache->set(self::ITEM_KEY, 'test2');

        $my_cache->clear();

        $this->assertEquals('test2', $another_cache->get(self::ITEM_KEY));
        $this->assertFalse($my_cache->has(self::ITEM_KEY));
    }

    public function testSerialise()
    {
        $cache = new APCUCache();

        $cache->set('array', ['one', 'two']);
        $this->assertEquals($cache->get('array'), ['one', 'two']);
    }

    public function testHas()
    {
       
        $my_cache = new APCUCache(prefix: 'mycache');
        $my_cache->set(self::ITEM_KEY, 'test1');

        $another_cache = new APCUCache(prefix: 'anothercache');
        $another_cache->set(self::ITEM_KEY, 'test2');

        $this->assertTrue($my_cache->has(self::ITEM_KEY));

        $this->assertTrue($another_cache->has(self::ITEM_KEY));

        $my_cache->delete(self::ITEM_KEY);
        $this->assertFalse($my_cache->has(self::ITEM_KEY));
        $this->assertTrue($another_cache->has(self::ITEM_KEY));
    }
}
