<?php
namespace Ttree\JobButler\Controller\Module\Management;

/*
 * This file is part of the Ttree.JobButler package.
 *
 * (c) Contributors of the project and the ttree team - www.ttree.ch
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Cocur\Slugify\Slugify;
use Ttree\JobButler\Domain\Model\DocumentJobTrait;
use Ttree\JobButler\Domain\Model\JobConfigurationInterface;
use Ttree\JobButler\Domain\Model\JobConfigurationOptions;
use Ttree\JobButler\Domain\Repository\JobConfigurationRepository;
use Ttree\JobButler\Domain\Service\JobRunnerServiceInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Message;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Utility\Files;

/**
 * Controller for asset handling
 *
 * @Flow\Scope("singleton")
 */
class JobController extends ActionController
{

    /**
     * @var JobConfigurationRepository
     * @Flow\Inject
     */
    protected $jobConfigurationRepository;

    /**
     * @var JobRunnerServiceInterface
     * @Flow\Inject
     */
    protected $jobRunnerService;

    /**
     * Show available jobs
     */
    public function indexAction()
    {
        $jobs = $this->jobConfigurationRepository->findAll();
        $tags = [];
        foreach ($jobs as $job) {
            $tags = array_merge($tags, $job->getTags());
        }
        $slugify = new Slugify();
        $tags = array_map(function($tag) use ($slugify) {
            return $slugify->slugify(mb_strtolower($tag));
        }, array_unique($tags));
        $this->view->assignMultiple([
            'jobs' => $jobs,
            'tags' => $tags,
        ]);
    }

    /**
     * @param string $action
     * @param string $jobIdentifier
     * @param array $options
     */
    public function redirectAction($action, $jobIdentifier, array $options = [])
    {
        $action = trim($action);
        $jobIdentifier = trim($jobIdentifier);
        $settings = array_filter($this->settings['allowedActionRedirect'] ?: []);
        $allowedActionRedirect = array_keys($settings);
        if (!in_array($action, $allowedActionRedirect)) {
            $this->addFlashMessage(sprintf('Disallowed action (%s)', $action), '', Message::SEVERITY_ERROR);

            $this->redirect('index');
        }

        if (!isset($settings[$action])) {
            $settings[$action] = 'forward';
        }

        switch ($settings[$action]) {
            case 'redirect':
                $this->redirect($action, null, null, [
                    'jobIdentifier' => $jobIdentifier,
                    'options' => $options
                ]);
                break;
            case 'forward':
                $this->forward($action, null, null, [
                    'jobIdentifier' => $jobIdentifier,
                    'options' => $options
                ]);
                break;
            case 'downloadCenter':
                $this->forward($action, null, null, [
                    'jobIdentifier' => $jobIdentifier
                ]);
                break;
            default:
                $this->addFlashMessage(sprintf('Illegal action (%s), check your settings.', $action), '', Message::SEVERITY_ERROR);

                $this->redirect('index');
        }
    }

    /**
     * @param string $jobIdentifier
     */
    public function downloadCenterAction($jobIdentifier)
    {
        /** @var DocumentJobTrait $jobConfiguration */
        $jobConfiguration = $this->jobConfigurationRepository->findOneByIdentifier($jobIdentifier);
        if ($jobConfiguration->getShowFileBrowser() !== true) {
            $this->addFlashMessage(sprintf('The current job (%s) does not implement "DocumentJobTrait"', $jobIdentifier), '', Message::SEVERITY_ERROR);
            $this->redirect('index');
        }
        $files = Files::readDirectoryRecursively($jobConfiguration->getDocumentAbsolutePath());
        $files = array_map(function($file) {
            return [
                'path' => $file,
                'creationDate' => \DateTime::createFromFormat('s', filemtime($file)),
                'filesize' => filesize($file),
                'name' => basename($file)
            ];
        }, $files);
        usort($files, function($a, $b) {
            return strnatcmp($a['name'], $b['name']);
        });
        $this->view->assignMultiple([
            'files' => $files,
            'jobConfiguration' => $jobConfiguration
        ]);
    }

    /**
     * @param string $jobIdentifier
     * @param string $path
     */
    public function downloadAction($jobIdentifier, $path)
    {
        /** @var DocumentJobTrait $jobConfiguration */
        $jobConfiguration = $this->jobConfigurationRepository->findOneByIdentifier($jobIdentifier);
        $dirname = rtrim(dirname($path));
        if (rtrim($jobConfiguration->getDocumentAbsolutePath(), '/') !== $dirname) {
            $this->addFlashMessage(sprintf('The current job (%s) path does not match the requested path (%s)', $jobIdentifier, $path), '', Message::SEVERITY_ERROR);
            $this->redirect('index');
        }

        header('Content-Type: application/octet-stream');
        header('Content-Transfer-Encoding: Binary');
        header('Content-disposition: attachment; filename="' . basename($path) . '"');
        readfile($path); // do the double-download-dance (dirty but worky)
    }

    /**
     * @param string $jobIdentifier
     * @param array $options
     */
    public function executeAction($jobIdentifier, array $options = [])
    {
        ini_set('memory_limit', -1);
        $jobConfiguration = $this->jobConfigurationRepository->findOneByIdentifier($jobIdentifier);
        try {
            if ($jobConfiguration === null) {
                $this->addFlashMessage(sprintf('Unable to find a Job Configuration with identifier "%s"', $jobIdentifier), '', Message::SEVERITY_ERROR);
                $this->redirect('index');
            }
            if (isset($this->settings['maximumExecutionTime'])) {
                set_time_limit((integer)$this->settings['maximumExecutionTime']);
            }
            $startTime = microtime(true);
            $options = new JobConfigurationOptions($options);
            if ($this->jobRunnerService->execute($jobConfiguration, $options)) {
                if ($jobConfiguration->isAsynchronous()) {
                    $this->addFlashMessage(sprintf('Job "%s" queued with success', $jobConfiguration->getName()), '', Message::SEVERITY_OK);
                } else {
                    $duration = round(microtime(true) - $startTime, 2);
                    if ($duration > 0) {
                        $message = sprintf('Job "%s" exectued with success in %s sec.', $jobConfiguration->getName(), $duration);
                    } else {
                        $message = sprintf('Job "%s" exectued with success.', $jobConfiguration->getName());
                    }
                    $this->addFlashMessage($message, '', Message::SEVERITY_OK);
                }
            }
        } catch (\Exception $exception) {
            $this->systemLogger->logException($exception);
            $this->addFlashMessage(sprintf('Failed to execute job "%s" with message: %s', $jobConfiguration->getName(), $exception->getMessage()), '', Message::SEVERITY_ERROR);
        }
        $this->redirect('index');
    }

    /**
     * @param string $jobIdentifier
     */
    public function configurationWizardAction($jobIdentifier)
    {
        $jobConfiguration = $this->jobConfigurationRepository->findOneByIdentifier($jobIdentifier);
        $this->view->assign('jobConfiguration', $jobConfiguration);
    }

    /**
     * @param string $jobIdentifier
     * @return JobConfigurationInterface
     */
    protected function loadJobConfiguration($jobIdentifier)
    {
        $jobConfiguration = $this->jobConfigurationRepository->findOneByIdentifier($jobIdentifier);
        if ($jobConfiguration === null) {
            $this->addFlashMessage(sprintf('Unable to find a Job Configuration with identifier "%s"', $jobIdentifier), '', Message::SEVERITY_ERROR);
            $this->redirect('index');
        }

        return $jobConfiguration;
    }
}
