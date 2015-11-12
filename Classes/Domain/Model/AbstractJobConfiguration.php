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
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Utility\Unicode\Functions;
use TYPO3\Media\Domain\Model\AssetCollection;
use TYPO3\Media\Domain\Model\Document;
use TYPO3\Media\Domain\Repository\AssetCollectionRepository;
use TYPO3\Media\Domain\Repository\DocumentRepository;

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
     * @var AssetCollectionRepository
     * @Flow\Inject
     */
    protected $assetCollectionRepository;

    /**
     * @var DocumentRepository
     * @Flow\Inject
     */
    protected $documentRepository;

    /**
     * @var ResourceManager
     * @Flow\Inject
     */
    protected $resourceManager;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var array
     */
    protected $defaultOptions = [];

    /**
     * Lazy load settings
     */
    public function initializeSettings()
    {
        if (!is_array($this->settings)) {
            $this->settings = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Ttree.JobButler');
        }
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
        $this->initializeSettings();
        return Arrays::getValueByPath($this->settings, array('jobSettings', $this->getIdentifier(), 'icon')) ?: 'tasks';
    }

    /**
     * {@inheritdoc}
     */
    public function getTags()
    {
        $this->initializeSettings();
        return Arrays::getValueByPath($this->settings, array('jobSettings', $this->getIdentifier(), 'tags')) ?: [];
    }

    /**
     * {@inheritdoc}
     */
    public function getWizardFactoryClass()
    {
        $this->initializeSettings();
        return Arrays::getValueByPath($this->settings, array('jobSettings', $this->getIdentifier(), 'wizardFactoryClass')) ?: null;
    }

    /**
     * {@inheritdoc
     */
    public function isAsynchronous()
    {
        $this->initializeSettings();
        return Arrays::getValueByPath($this->settings, array('jobSettings', $this->getIdentifier(), 'asynchronous')) ?: false;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->translate($this->getNormalizedIdentifier() . '.name') ?: sprintf('Undefined (%s)', $this->getNormalizedIdentifier());
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
        $this->initializeSettings();
        return Arrays::getValueByPath($this->settings, array('jobSettings', $this->getIdentifier(), 'privilegeTarget')) ?: null;
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
     * @param string $content
     * @param string $filename
     * @return Document
     */
    protected function storeDocument($content, $filename)
    {
        $cacheKey = md5(get_called_class() . '::' . $filename);
        $createDocument = function ($cacheKey, $content, $filename) {
            $resource = $this->resourceManager->importResourceFromContent($content, $filename);
            $this->registry->set($cacheKey, $resource->getSha1());
            $document = new Document($resource);
            return $document;
        };

        if (!$this->registry->has($cacheKey)) {
            $document = $createDocument($cacheKey, (string)$content, $filename);
            $this->documentRepository->add($document);
        } else {
            $document = $this->documentRepository->findOneByResourceSha1($this->registry->get($cacheKey));
            if ($document === null) {
                $document = $createDocument($cacheKey, (string)$content, $filename);
                $this->documentRepository->add($document);
            } else {
                $this->documentRepository->update($document);
            }
            $resource = $this->resourceManager->importResourceFromContent((string)$content, $filename);
            $this->registry->set($cacheKey, $resource->getSha1());
            $document->setResource($resource);
        }

        $this->updateAssetCollection($document);

        return $document;
    }

    /**
     * @param string $filename
     */
    protected function deleteDocument($filename)
    {
        $cacheKey = md5(get_called_class() . '::' . $filename);
        $document = $this->documentRepository->findOneByResourceSha1($this->registry->get($cacheKey));
        if ($document === null) {
            return;
        }
        $this->documentRepository->remove($document);
    }

    /**
     * @param Document $document
     * @return void
     */
    protected function updateAssetCollection(Document $document)
    {
        $assetCollections = $document->getAssetCollections();
        $collection = $this->createAssetCollectionIfMissing();
        if (!$assetCollections->contains($collection)) {
            $assetCollections->add($collection);
            $document->setAssetCollections($assetCollections);
            $this->documentRepository->update($document);
        }
    }

    /**
     * @return AssetCollection
     */
    protected function createAssetCollectionIfMissing()
    {
        $this->initializeSettings();
        $collectionName = $this->settings['defaultAssetCollection'];
        $collection = $this->assetCollectionRepository->findOneByTitle($collectionName);
        if ($collection === null) {
            $collection = new AssetCollection($collectionName);
            $this->assetCollectionRepository->add($collection);
        }
        return $collection;
    }
}
