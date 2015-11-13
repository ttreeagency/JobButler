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
use Ttree\JobButler\Domain\Repository\JobConfigurationRepository;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;

/**
 * Job Configuration Service
 *
 * @Flow\Scope("singleton")
 */
class JobConfigurationService
{
    const JOB_CONFIGURATION_INTERFACE = 'Ttree\JobButler\Domain\Model\JobConfigurationInterface';

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $runtimeCache = [];

    /**
     * @return array
     */
    public function getServiceConfiguration()
    {
        if ($this->runtimeCache !== []) {
            return $this->runtimeCache;
        }
        $configurations = self::getAvailableJobConfigurations($this->objectManager);
        $this->runtimeCache = array_map(function ($item) {
            $item['implementation'] = $this->objectManager->get($item['implementation']);
            return $item;
        }, $configurations);

        return $this->runtimeCache;
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
}
