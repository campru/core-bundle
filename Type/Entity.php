<?php

namespace Smartbox\CoreBundle\Type;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Serializer\Cache\SerializerCacheableInterface;
use Smartbox\CoreBundle\Type\Traits\HasGroup;
use Smartbox\CoreBundle\Type\Traits\HasType;
use Smartbox\CoreBundle\Type\Traits\HasVersion;

class Entity implements EntityInterface //, SerializerCacheableInterface
{
    use HasGroup;
    use HasVersion;
    use HasType;

    public function __construct()
    {
    }
}