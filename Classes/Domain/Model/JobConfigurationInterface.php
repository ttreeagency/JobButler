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

/**
 * Job Configuration Interface
 */
interface JobConfigurationInterface
{
    /**
     * Job Identifier
     */
    public function getIdentifier();

    /**
     * Job Short Identifier
     *
     * @return string
     */
    public function getShortIdentifier();

    /**
     * Job Package Key
     *
     * @return string
     */
    public function getPackageKey();

    /**
     * Job Package Translation Source
     *
     * @return string
     */
    public function getTranslationSource();

    /**
     * Check if the current job is asynchronous
     *
     * @return boolean
     */
    public function isAsynchronous();

    /**
     * @return string
     */
    public function getIcon();

    /**
     * Job Name
     *
     * @return string
     */
    public function getName();

    /**
     * Job Tags
     *
     * @return array
     */
    public function getTags();

    /**
     * Job Description
     *
     * @return string
     */
    public function getDescription();

    /**
     * Job Privilege Target
     *
     * @return string|null
     */
    public function getPrivilegeTarget();

    /**
     * If the current provide a configuration wizard
     *
     * @return string|null
     */
    public function getWizardFactoryClass();

    /**
     * Single execution of the current job
     *
     * @param array $options List of options
     * @return boolean true on job success, always true for async job
     */
    public function execute(array $options = []);

}
