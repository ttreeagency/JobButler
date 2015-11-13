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
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Media\Domain\Model\AssetCollection;
use TYPO3\Media\Domain\Model\Document;
use TYPO3\Media\Domain\Repository\AssetCollectionRepository;
use TYPO3\Media\Domain\Repository\DocumentRepository;

/**
 * A Trait to give utility methods to work with document
 */
trait DocumentJobTrait
{

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
        $collectionName = $this->getSettings()['defaultAssetCollection'];
        $collection = $this->assetCollectionRepository->findOneByTitle($collectionName);
        if ($collection === null) {
            $collection = new AssetCollection($collectionName);
            $this->assetCollectionRepository->add($collection);
        }
        return $collection;
    }
}
