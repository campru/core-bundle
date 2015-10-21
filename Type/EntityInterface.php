<?php

namespace Smartbox\CoreBundle\Type;

interface EntityInterface extends SerializableInterface
{
    const GROUP_PUBLIC = 'public';
    const GROUP_METADATA = 'metadata';
    const GROUP_DEFAULT = 'Default';

    public function __construct();

    /**
     * @return string
     */
    public function getVersion();

    /**
     * @param string $version
     */
    public function setVersion($version);

    /**
     * @return string
     */
    public function getGroup();

    /**
     * @param string $group
     */
    public function setGroup($group);
}