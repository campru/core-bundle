<?php

namespace Smartbox\CoreBundle\Tests\Serializer\Handler;

use JMS\Serializer\SerializerInterface;
use Smartbox\CoreBundle\Serializer\Handler\CachedObjectHandler;
use Smartbox\CoreBundle\Tests\BaseTestCase;
use JMS\Serializer\SerializationContext;
use Smartbox\CoreBundle\Tests\Fixtures\Entity\CacheableEntity;
use Smartbox\CoreBundle\Tests\Fixtures\Entity\SerializableThing;
use Smartbox\CoreBundle\Tests\Utils\Cache\FakeCacheService;
use Smartbox\CoreBundle\Tests\Utils\Cache\FakeCacheServiceSpy;
use Smartbox\CoreBundle\Type\Date;
use Smartbox\CoreBundle\Utils\Cache\CacheServiceInterface;

class CachedObjectHandlerTest extends BaseTestCase
{
    /** @var CacheServiceInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $cacheServiceMock;

    /** @var FakeCacheServiceSpy */
    private $cacheServiceSpy;

    public function setUp()
    {
        parent::setUp();

        $this->cacheServiceSpy = new FakeCacheServiceSpy();
        $this->cacheServiceMock = $this->getMock(FakeCacheService::class, null, [$this->cacheServiceSpy]);
        $this->getContainer()->get('smartcore.serializer.subscriber.cache')->setCacheService($this->cacheServiceMock);
        $this->getContainer()->get('smartcore.serializer.handler.cache')->setCacheService($this->cacheServiceMock);
    }

    public function dataProviderForSerializationFormat()
    {
        return [
            ['json'],
            ['array'],
            ['mongo_array'],
        ];
    }

    /**
     * @dataProvider dataProviderForSerializationFormat
     *
     * @param $format
     */
    public function testSerializationWithCache($format)
    {
        /** @var SerializerInterface $serializer */
        $serializer = $this->getContainer()->get('serializer');
        $cacheData = $this->createCacheableEntity('title 1');
        $cacheDataArray = [
            'type' => 'Smartbox\\CoreBundle\\Tests\\Fixtures\\Entity\\CacheableEntity',
            'title' => 'title 1',
        ];

        $entity = new SerializableThing();
        $entity->setIntegerValue(10);
        $entity->setStringValue('test');
        $entity->setDoubleValue(17.17);
        $entity->setArrayOfDates(
            [
                new Date(),
                new Date(),
            ]
        );
        $entity->setNestedEntity(clone $cacheData);
        $entity->setArrayOfEntities(
            [
                clone $cacheData,
                clone $cacheData,
            ]
        );

        $context = new SerializationContext();

        $serializedEntity = $serializer->serialize($entity, $format, $context);
        $cacheKey = CachedObjectHandler::getDataCacheKey($cacheData, $context);
        $deserializedEntity = $serializer->deserialize($serializedEntity, SerializerInterface::class, $format);

        $this->assertEquals($entity, $deserializedEntity);
        $expectedSpyLog = [
            [
                'method' => 'exists',
                'arguments' =>[$cacheKey],
                'result' => false,
            ],
            [
                'method' => 'set',
                'arguments' => [$cacheKey, $cacheDataArray, null],
                'result' => true,
            ],
            [
                'method' => 'exists',
                'arguments' => [$cacheKey],
                'result' => true,
            ],
            [
                'method' => 'get',
                'arguments'=> [$cacheKey],
                'result' => $cacheDataArray,
            ],
            [
                'method' => 'exists',
                'arguments' => [$cacheKey],
                'result' => true,
            ],
            [
                'method' => 'get',
                'arguments' => [$cacheKey],
                'result' => $cacheDataArray,
            ],
        ];
        $this->assertEquals($expectedSpyLog, $this->cacheServiceSpy->getLog(), 'Methods of cache service were not executed with proper order or arguments.');
    }

    protected function createCacheableEntity($title)
    {
        $testEntity = new CacheableEntity();
        $testEntity->setTitle($title);

        return $testEntity;
    }

    public function testSerializationWithoutCacheForXML()
    {
        /** @var SerializerInterface $serializer */
        $serializer = $this->getContainer()->get('serializer');
        $cacheData = $this->createCacheableEntity('title 1');

        $entity = new SerializableThing();
        $entity->setIntegerValue(10);
        $entity->setStringValue('test');
        $entity->setDoubleValue(17.17);
        $entity->setArrayOfDates(
            [
                new Date(),
                new Date(),
            ]
        );
        $entity->setNestedEntity(clone $cacheData);
        $entity->setArrayOfEntities(
            [
                clone $cacheData,
                clone $cacheData,
            ]
        );

        $context = new SerializationContext();

        $serializedEntity = $serializer->serialize($entity, 'xml', $context);
        $deserializedEntity = $serializer->deserialize($serializedEntity, SerializerInterface::class, 'xml');

        $this->assertEquals($entity, $deserializedEntity);
    }
}
