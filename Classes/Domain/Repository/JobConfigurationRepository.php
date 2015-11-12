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
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Tests\Unit\Utility\PositionalArraySorterTest;
use TYPO3\Flow\Utility\PositionalArraySorter;
use TYPO3\Flow\Utility\Unicode\Functions;

/**
 * Job Configuration Repository
 *
 * @Flow\Scope("singleton")
 */
class JobConfigurationRepository
{

    const JOB_CONFIGURATION_INTERFACE = 'Ttree\JobButler\Domain\Model\JobConfigurationInterface';

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @return array
     */
    public function findAll()
    {
        $jobConfigurations = [];

        foreach (self::getAvailableJobConfigurations($this->objectManager) as $jobConfiguration) {
            /** @var JobConfigurationInterface $implementation */
            $implementation = $this->objectManager->get($jobConfiguration['implementation']);
            $jobConfigurations[] = [
                'name' => $implementation->getName(),
                'implementation' => $implementation
            ];
        }

        return $this->orderJobs($jobConfigurations);
    }

    /**
     * @param string
     * @return JobConfigurationInterface
     */
    public function findOneByIdentifier($identifier)
    {
        $jobs = $this->findAll();
        foreach ($jobs as $job) {
            /** @var JobConfigurationInterface $job */
            if ($job->getIdentifier() !== $identifier) {
                continue;
            }
            return $job;
        }
    }

    /**
     * Returns a map of all available jobs configuration
     *
     * @param ObjectManagerInterface $objectManager
     * @return array Array of available jobs configuration
     * @Flow\CompileStatic
     */
    public static function getAvailableJobConfigurations($objectManager)
    {
        $reflectionService = $objectManager->get('TYPO3\Flow\Reflection\ReflectionService');

        $result = [];

        foreach ($reflectionService->getAllImplementationClassNamesForInterface(self::JOB_CONFIGURATION_INTERFACE) as $implementation) {
            /** @var JobConfigurationInterface $jobConfiguration */
            $jobConfiguration = $objectManager->get($implementation);
            $result[$jobConfiguration->getIdentifier()] = [
                'implementation' => $implementation,
                'identifier' => $jobConfiguration->getIdentifier()
            ];
        }

        return $result;
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
