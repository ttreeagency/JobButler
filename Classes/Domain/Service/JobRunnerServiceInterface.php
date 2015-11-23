<?php
/*
 * This file is part of the Ttree.JobButler package.
 *
 * (c) Contributors of the project and the ttree team - www.ttree.ch
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
namespace Ttree\JobButler\Domain\Service;

use Ttree\JobButler\Domain\Model\JobConfigurationInterface;
use Ttree\JobButler\Domain\Model\JobConfigurationOptions;

/**
 * Job Runner Service
 */
interface JobRunnerServiceInterface
{
    /**
     * Execute the given job configuration
     *
     * @param JobConfigurationInterface $jobConfiguration
     * @param JobConfigurationOptions $options
     * @return boolean
     */
    public function execute(JobConfigurationInterface $jobConfiguration, JobConfigurationOptions $options);
}
