<?php
namespace Ttree\JobButler\Form\Finishers;

/*
 * This file is part of the Ttree.JobButler package.
 *
 * (c) Contributors of the project and the ttree team - www.ttree.ch
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Ttree\JobButler\Domain\Repository\JobConfigurationRepository;
use Ttree\JobButler\Domain\Service\JobRunnerServiceInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Message;
use TYPO3\Flow\Mvc\Exception\ForwardException;
use TYPO3\Flow\Mvc\FlashMessageContainer;
use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\Form\Core\Model\AbstractFinisher;

/**
 * This finisher redirects to another Controller.
 */
class ExecuteJobFinisher extends AbstractFinisher
{

    /**
     * @var UriBuilder
     * @Flow\Inject
     */
    protected $uriBuilder;

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
     * @var FlashMessageContainer
     * @Flow\Inject
     */
    protected $flashMessageContainer;

    /**
     * @var array
     */
    protected $defaultOptions = [
        'path' => null,
        'action' => '',
        'format' => 'html',
        'statusCode' => 303,
    ];

    /**
     * @return void
     * @throws ForwardException
     */
    public function executeInternal()
    {
        $formRuntime = $this->finisherContext->getFormRuntime();
        $request = $formRuntime->getRequest()->getMainRequest();
        $this->uriBuilder->setRequest($request);

        $uri = null;
        $path = $this->parseOption('path');
        $action = $this->parseOption('action');
        $format = $this->parseOption('format');

        $modifiedArguments = ['module' => $path];
        if ($action !== null) {
            $modifiedArguments['moduleArguments']['@action'] = $action;
        }

        $jobIdentifier = $formRuntime->getRequest()->getArgument('jobIdentifier');
        $options = $formRuntime->getRequest()->getArguments();
        unset($options['jobIdentifier']);

        $this->executeJob($jobIdentifier, $options);

        $uri = $this->uriBuilder
            ->reset()
            ->setCreateAbsoluteUri(true)
            ->setFormat($format)
            ->uriFor('index', $modifiedArguments, 'Backend\Module', 'TYPO3.Neos');

        $this->redirect($uri);
    }

    /**
     * @param string $jobIdentifier
     * @param array $options
     */
    protected function executeJob($jobIdentifier, array $options = []) {
        $jobConfiguration = $this->jobConfigurationRepository->findOneByIdentifier($jobIdentifier);
        $startTime = microtime(true);
        if ($this->jobRunnerService->execute($jobConfiguration, $options)) {
            if ($jobConfiguration->isAsynchronous()) {
                $this->flashMessageContainer->addMessage(new Message(sprintf('Job "%s" queued with success', $jobConfiguration->getName())));
            } else {
                $duration = round(microtime(true) - $startTime, 2);
                $this->flashMessageContainer->addMessage(new Message(sprintf('Job "%s" exectued with success in %s sec.', $jobConfiguration->getName(), $duration)));
            }
        }
    }

    /**
     * @param string $uri
     */
    protected function redirect($uri) {
        $formRuntime = $this->finisherContext->getFormRuntime();

        $response = $formRuntime->getResponse();
        $mainResponse = $response;
        while ($response = $response->getParentResponse()) {
            $mainResponse = $response;
        };
        $mainResponse->setStatus($this->parseOption('statusCode'));
        $mainResponse->setHeader('Location', (string)$uri);
    }
}
