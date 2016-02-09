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

use Ttree\JobButler\Service\RegistryService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\I18n\Translator;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Utility\Unicode\Functions;

/**
 * Abstract Job Configuration
 */
abstract class AbstractJobConfiguration implements JobConfigurationInterface
{

    /**
     * @var ConfigurationManager
     * @Flow\Inject
     */
    protected $configurationManager;

    /**
     * @var Translator
     * @Flow\Inject
     */
    protected $translator;

    /**
     * @var RegistryService
     * @Flow\Inject
     */
    protected $registry;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var array
     */
    protected $defaultOptions = [];

    /**
     * @var string
     */
    protected $defaultIcon = 'tasks';

    /**
     * Get package settings
     */
    public function getSettings()
    {
        if (!is_array($this->settings)) {
            $this->settings = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Ttree.JobButler');
        }
        return $this->settings;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return get_called_class();
    }

    /**
     * {@inheritdoc}
     */
    public function getShortIdentifier()
    {
        return md5($this->getIdentifier());
    }

    /**
     * @return string
     */
    protected function getNormalizedIdentifier()
    {
        return str_replace("\\", ".", Functions::strtolower($this->getIdentifier()));
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon()
    {
        return $this->getOption('icon', $this->defaultIcon);
    }

    /**
     * {@inheritdoc}
     */
    public function getTags()
    {
        $tags = [];
        foreach ($this->getOption('tags', []) as $tag => $status) {
            if ($status !== true) {
                continue;
            }
            if (strpos($tag, ':') !== false) {
                list($packageKey, $identifier) = explode(':', $tag);
            } else {
                $packageKey = 'Ttree.JobButler';
                $identifier = $tag;
            }
            $translation = $this->translator->translateById($identifier, [], null, null, 'JobTags', $packageKey);
            $tags[md5($translation)] = $translation;
        }
        natsort($tags);
        return array_values($tags);
    }

    /**
     * {@inheritdoc}
     */
    public function getWizardFactoryClass()
    {
        return $this->getOption('wizardFactoryClass', null);
    }

    /**
     * {@inheritdoc
     */
    public function isAsynchronous()
    {
        return $this->getOption('asynchronous', false);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->translate($this->getNormalizedIdentifier() . '.name') ?: sprintf('Undefined (%s)', $this->getNormalizedIdentifier() . '.name');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->translate($this->getNormalizedIdentifier() . '.description');
    }

    /**
     * {@inheritdoc}
     */
    public function getPrivilegeTarget()
    {
        return $this->getOption('privilegeTarget', null);
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslationSource()
    {
        return 'Jobs';
    }

    /**
     * @param array $options
     * @return array
     */
    protected function mergeDefaultOptions(array $options)
    {
        return Arrays::arrayMergeRecursiveOverrule($this->defaultOptions, $options);
    }

    /**
     * @param string $identifier
     * @return string
     */
    protected function translate($identifier)
    {
        $translation = $this->translator->translateById($identifier, [], null, null, $this->getTranslationSource(), $this->getPackageKey());
        if ($translation === $identifier) {
            return null;
        }
        return $translation;
    }

    /**
     * @param string $option
     * @param mixed $default
     * @return mixed
     */
    protected function getOption($option, $default)
    {
        $settings = $this->getSettings();
        $defaultSettings = Arrays::getValueByPath($settings, array('jobSettings', '*')) ?: [];
        $currentSettings = array_merge($defaultSettings, Arrays::getValueByPath($settings, array('jobSettings', $this->getIdentifier())) ?: []);
        return Arrays::getValueByPath($currentSettings, $option) ?: $default;
    }
}
