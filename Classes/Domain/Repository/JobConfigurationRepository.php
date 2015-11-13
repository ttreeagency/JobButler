<?php
namespace Ttree\JobButler\Domain\Repository;

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
use Ttree\JobButler\Domain\Service\JobConfigurationService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Unicode\Functions;

/**
 * Job Configuration Repository
 *
 * @Flow\Scope("singleton")
 */
class JobConfigurationRepository
{

    /**
     * @var JobConfigurationService
     * @Flow\Inject
     */
    protected $jobConfigurationService;

    /**
     * Return all Jobs grouped by tags
     *
     * A single Job can be attached to multiple Tags.
     *
     * @return array
     */
    public function findAll()
    {
        $jobConfigurations = [];

        foreach ($this->jobConfigurationService->getServiceConfiguration() as $jobConfiguration) {
            /** @var JobConfigurationInterface $implementation */
            $implementation = $jobConfiguration['implementation'];
            $job = [
                'name' => $implementation->getName(),
                'implementation' => $implementation
            ];
            foreach ($implementation->getTags() as $tag) {
                if (!isset($jobConfigurations[$tag])) {
                    $jobConfigurations[$tag] = [];
                }
                $jobConfigurations[$tag][] = $job;
            }
        }

        foreach ($jobConfigurations as $tag => $jobConfigurationsByTag) {
            $jobConfigurations[$tag] = $this->orderJobs($jobConfigurationsByTag);
        }

        return $jobConfigurations;
    }

    /**
     * @param string
     * @return JobConfigurationInterface
     */
    public function findOneByIdentifier($identifier)
    {
        $jobs = $this->jobConfigurationService->getServiceConfiguration();
        foreach ($jobs as $job) {
            /** @var JobConfigurationInterface $job */
            if ($job['identifier'] !== $identifier) {
                continue;
            }
            return $job['implementation'];
        }
    }

    /**
     * Order current job list, order by name by default
     *
     * @param array $jobConfigurations
     * @param string $orderBy
     * @return array
     */
    protected function orderJobs(array $jobConfigurations, $orderBy = 'name')
    {
        usort($jobConfigurations, function ($a, $b) use ($orderBy) {
            $a = Functions::strtolower(trim($a[$orderBy]));
            $b = Functions::strtolower(trim($b[$orderBy]));
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        });

        $result = [];
        foreach ($jobConfigurations as $jobConfiguration) {
            $result[] = $jobConfiguration['implementation'];
        }

        return $result;
    }
}
