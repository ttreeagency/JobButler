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

use Ttree\JobButler\Domain\Model\JobConfigurationInterface;
use Ttree\JobButler\Domain\Repository\JobConfigurationRepository;
use Ttree\JobButler\Domain\Service\JobRunnerServiceInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Message;
use TYPO3\Flow\Mvc\Controller\ActionController;

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
    public function indexAction() {
        $this->view->assign('jobs', $this->jobConfigurationRepository->findAll());
    }

    /**
     * @param string $action
     * @param string $jobIdentifier
     * @param array $options
     */
    public function redirectAction($action, $jobIdentifier, array $options = []) {
        if (!in_array($action, ['execute', 'history', 'schedule'])) {
            $this->addFlashMessage(sprintf('Disallowed action (%s)', $action), '', Message::SEVERITY_ERROR);
            $this->redirect('index');
        }
        $this->forward($action, null, null, [
            'jobIdentifier' => $jobIdentifier,
            'options' => $options
        ]);
    }

    /**
     * @param string $jobIdentifier
     * @param array $options
     */
    public function executeAction($jobIdentifier, array $options = []) {
        $jobConfiguration = $this->jobConfigurationRepository->findOneByIdentifier($jobIdentifier);
        if ($jobConfiguration === NULL) {
            $this->addFlashMessage(sprintf('Unable to find a Job Configuration with identifier "%s"', $jobIdentifier), '', Message::SEVERITY_ERROR);
            $this->redirect('index');
        }
        $startTime = microtime(true);
        if ($this->jobRunnerService->execute($jobConfiguration, $options)) {
            if ($jobConfiguration->isAsynchronous()) {
                $this->addFlashMessage(sprintf('Job "%s" queued with success', $jobConfiguration->getName()), '', Message::SEVERITY_OK);
            } else {
                $duration = round(microtime(true) - $startTime, 2);
                $this->addFlashMessage(sprintf('Job "%s" exectued with success in %s sec.', $jobConfiguration->getName(), $duration), '', Message::SEVERITY_OK);
            }
        }
        $this->redirect('index');
    }

    /**
     * @param string $jobIdentifier
     * @param array $options
     */
    public function historyAction($jobIdentifier, array $options = []) {
        $jobConfiguration = $this->loadJobConfiguration($jobIdentifier);
        $this->addFlashMessage('This action is currently not implemented', '', Message::SEVERITY_WARNING);
        $this->redirect('index');
    }

    /**
     * @param string $jobIdentifier
     * @param array $options
     */
    public function scheduleAction($jobIdentifier, array $options = []) {
        $jobConfiguration = $this->loadJobConfiguration($jobIdentifier);
        $this->addFlashMessage('This action is currently not implemented', '', Message::SEVERITY_ERROR);
        $this->redirect('index');
    }

    /**
     * @param string $jobIdentifier
     * @return JobConfigurationInterface
     */
    protected function loadJobConfiguration($jobIdentifier) {
        $jobConfiguration = $this->jobConfigurationRepository->findOneByIdentifier($jobIdentifier);
        if ($jobConfiguration === NULL) {
            $this->addFlashMessage(sprintf('Unable to find a Job Configuration with identifier "%s"', $jobIdentifier), '', Message::SEVERITY_ERROR);
            $this->redirect('index');
        }

        return $jobConfiguration;
    }

}
