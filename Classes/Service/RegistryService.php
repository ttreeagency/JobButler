<?php
namespace Ttree\JobButler\Service;

/*
 * This file is part of the Ttree.JobButler package.
 *
 * (c) Contributors of the project and the ttree team - www.ttree.ch
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\Frontend\StringFrontend;

/**
 * Job Configuration Repository
 *
 * @Flow\Scope("singleton")
 */
class RegistryService
{

    /**
     * @var StringFrontend
     */
    protected $cache;

    /**
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        return $this->cache->has($key);
    }

    /**
     * @param string $key
     * @return boolean
     */
    public function get($key)
    {
        return $this->cache->get($key);
    }

    /**
     * @param string $key
     * @param string $value
     * @return boolean
     */
    public function set($key, $value)
    {
        return $this->cache->set($key, $value);
    }
}
