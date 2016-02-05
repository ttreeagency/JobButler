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

use Ttree\JobButler\Exception;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Files;

/**
 * A Trait to give utility methods to work with document
 */
trait DocumentJobTrait
{
    /**
     * @var string
     * @Flow\InjectConfiguration(package="Ttree.JobButler",path="temporaryDirectoryBase")
     */
    protected $temporaryDirectoryBase;

    /**
     * @return boolean
     */
    public function getShowFileBrowser()
    {
        return true;
    }

    /**
     * @param string $filename
     * @param string $content
     * @return void
     * @throws Exception
     */
    public function writeDocument($filename, $content)
    {
        $bytes = file_put_contents($this->getDocumentAbsolutePath($filename), (string)$content);
        if ($bytes === false) {
            throw new Exception(sprintf('Unable to write document (%s)', $filename), 1448311912);
        }
    }

    /**
     * @param string $filename
     */
    public function removeDocument($filename)
    {
        $document = $this->getDocumentAbsolutePath($filename);
        if (!is_file($document)) {
            return;
        }
        unlink($document);
    }

    /**
     * @param string $filename
     */
    public function downloadDocument($filename)
    {

    }

    /**
     * @param string $filename
     * @return string
     * @throws \TYPO3\Flow\Utility\Exception
     */
    public function getDocumentAbsolutePath($filename = null)
    {
        $path = str_replace('\\', '/', get_called_class());
        $documentAbsolutePath = $this->temporaryDirectoryBase . $path . '/';
        Files::createDirectoryRecursively($documentAbsolutePath);
        return Files::getNormalizedPath($documentAbsolutePath . $filename);
    }
}
