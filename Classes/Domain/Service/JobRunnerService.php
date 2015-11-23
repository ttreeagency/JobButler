<?php
namespace Ttree\JobButler\Domain\Service;

/*
 * This file is part of the Ttree.JobButler package.
 *
 * (c) Contributors of the project and the ttree team - www.ttree.ch
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Ttree\JobButler\Domain\Model\JobConfigurationInterface;
use Ttree\JobButler\Domain\Model\JobConfigurationOptions;
use Ttree\JobButler\Domain\Repository\JobConfigurationRepository;
use TYPO3\Flow\Annotations as Flow;

/**
 * Job Configuration Repository
 *
 * @Flow\Scope("singleton")
 */
class JobRunnerService implements JobRunnerServiceInterface
{

    /**
     * @var JobConfigurationRepository
     * @Flow\Inject
     */
    protected $jobConfigurationRepository;

    /**
     * {@inheritdoc}
     */
    public function execute(JobConfigurationInterface $jobConfiguration, JobConfigurationOptions $options)
    {
        $status = $jobConfiguration->execute($options);
        $this->emitJobExecuted($status, $jobConfiguration, $options);
        return $status;
    }

    /**
     * @Flow\Signal
     * @param boolean $status
     * @param JobConfigurationInterface $jobConfiguration
     * @param JobConfigurationOptions $options
     * @return void
     */
    protected function emitJobExecuted($status, JobConfigurationInterface $jobConfiguration, JobConfigurationOptions $options)
    {
    }
}
