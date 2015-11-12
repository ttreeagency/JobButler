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
            default:
                $this->addFlashMessage(sprintf('Illegal action (%s), check your settings.', $action), '', Message::SEVERITY_ERROR);

                $this->redirect('index');
        }
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
        if ($jobConfiguration->isAsynchronous() && isset($this->settings['maximumExecutionTime'])) {
            set_time_limit((integer)$this->settings['maximumExecutionTime']);
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
     */
    public function configurationWizardAction($jobIdentifier) {
        $jobConfiguration = $this->jobConfigurationRepository->findOneByIdentifier($jobIdentifier);
        $this->view->assign('jobConfiguration', $jobConfiguration);
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
