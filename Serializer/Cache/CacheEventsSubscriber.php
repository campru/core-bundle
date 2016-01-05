<?php

namespace Smartbox\CoreBundle\Serializer\Cache;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\GenericSerializationVisitor;
use Smartbox\CoreBundle\Serializer\Handler\CachedObjectHandler;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;

class CacheEventsSubscriber implements EventSubscriberInterface
{
    use CacheServiceAwareTrait;

    /** @var \ReflectionProperty */
    private $dataProperty;

    public function __construct()
    {
        $this->dataProperty = new \ReflectionProperty(GenericSerializationVisitor::class, 'data');
        $this->dataProperty->setAccessible(true);
    }

    public static function getSubscribedEvents()
    {
        return array(
            array('event' => 'serializer.pre_serialize', 'method' => 'onPreSerialize'),
            array('event' => 'serializer.post_serialize', 'method' => 'onPostSerialize'),
        );
    }

    public function onPreSerialize(PreSerializeEvent $event)
    {
        $data = $event->getObject();
        if ($data instanceof SerializerCacheableInterface) {
            if ($this->getCacheService()->exists(CachedObjectHandler::getDataCacheKey($data))) {
                $event->setType(CachedObjectHandler::TYPE);
            }
        }
    }

    public function onPostSerialize(ObjectEvent $event)
    {
        $visitor = $event->getVisitor();
        $object = $event->getObject();
        $type = $event->getType();
        $cacheData = $this->getDataFromVisitor($visitor);

        if ($type['name'] !== CachedObjectHandler::TYPE && $object instanceof SerializerCacheableInterface){
            // save to cache
            $this->cacheService->set(CachedObjectHandler::getDataCacheKey($object), $cacheData);
        }
    }

    /**
     * @param GenericSerializationVisitor $visitor
     * @return array
     */
    public function getDataFromVisitor(GenericSerializationVisitor $visitor)
    {
        return $this->dataProperty->getValue($visitor);
    }
}