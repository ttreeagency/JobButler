<?php
namespace Ttree\JobButler\ViewHelpers;

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
use Ttree\JobButler\Form\Finishers\ExecuteJobFinisher;
use Ttree\JobButler\Form\Finishers\ModuleRedirectFinisher;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Validation\Validator\NotEmptyValidator;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Form\Core\Model\FormDefinition;
use TYPO3\Form\Core\Model\Page;
use TYPO3\Form\Persistence\FormPersistenceManagerInterface;

/**
 * Main Entry Point to render a Form into a Fluid Template
 *
 * Usage
 * =====
 *
 * <pre>
 * {namespace form=TYPO3\Form\ViewHelpers}
 * <form:render factoryClass="NameOfYourCustomFactoryClass" />
 * </pre>
 *
 * The factory class must implement {@link TYPO3\Form\Factory\FormFactoryInterface}.
 *
 * @api
 */
class RenderViewHelper extends AbstractViewHelper
{

    /**
     * @var boolean
     */
    protected $escapeOutput = FALSE;

    /**
     * @Flow\Inject
     * @var FormPersistenceManagerInterface
     */
    protected $formPersistenceManager;

    /**
     * @param JobConfigurationInterface $jobConfiguration
     * @param string $persistenceIdentifier the persistence identifier for the form.
     * @param string $factoryClass The fully qualified class name of the factory (which has to implement \TYPO3\Form\Factory\FormFactoryInterface)
     * @param string $presetName name of the preset to use
     * @param array $overrideConfiguration factory specific configuration
     * @return string the rendered form
     */
    public function render(JobConfigurationInterface $jobConfiguration, $persistenceIdentifier = NULL, $factoryClass = 'TYPO3\Form\Factory\ArrayFormFactory', $presetName = 'default', array $overrideConfiguration = array())
    {
        if (isset($persistenceIdentifier)) {
            $overrideConfiguration = Arrays::arrayMergeRecursiveOverrule($this->formPersistenceManager->load($persistenceIdentifier), $overrideConfiguration);
        }

        $factory = $this->objectManager->get($factoryClass);
        /** @var FormDefinition $formDefinition */
        $formDefinition = $factory->build($overrideConfiguration, $presetName);
        ObjectAccess::setProperty($formDefinition, 'identifier', 'options', TRUE);
        $this->postProcessFormDefinition($jobConfiguration, $formDefinition);

        $response = new Response($this->controllerContext->getResponse());
        $form = $formDefinition->bind($this->controllerContext->getRequest(), $response);
        $form->getRequest()->setArgumentNamespace('--options');

        return $form->render();
    }

    /**
     * @param JobConfigurationInterface $jobConfiguration
     * @param FormDefinition $formDefinition
     */
    protected function postProcessFormDefinition(JobConfigurationInterface $jobConfiguration, FormDefinition $formDefinition) {
        $redirectFinisher = new ExecuteJobFinisher();
        $redirectFinisher->setOptions([
            'path' => 'management/jobsbutler',
            'action' => 'index'
        ]);
        $formDefinition->addFinisher($redirectFinisher);

        /** @var Page $firstPage */
        $page = $formDefinition->getPages()[0];

        $jobIdentifier = $page->createElement('jobIdentifier', 'TYPO3.Form:HiddenField');
        $jobIdentifier->setDefaultValue($jobConfiguration->getIdentifier());
        $jobIdentifier->addValidator(new NotEmptyValidator());
    }
}
