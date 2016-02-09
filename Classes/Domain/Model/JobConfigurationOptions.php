<?php
namespace Ttree\JobButler\Domain\Model;

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
use TYPO3\Flow\Utility\Arrays;

/**
 * Job Configuration Options
 */
class JobConfigurationOptions
{
    /**
     * @var array
     */
    protected $options;

    /**
     * JobConfigurationOptions constructor.
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * @param string $path
     * @return mixed
     */
    public function getValueByPath($path)
    {
        return Arrays::getValueByPath($this->options, $path);
    }
}
