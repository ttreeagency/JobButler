Ttree.JobButler
===============

[![StyleCI](https://styleci.io/repos/45913813/shield)](https://styleci.io/repos/45913813)

Introduction
------------

Neos CMS package to help Editors and Administrators to manage Jobs. Jobs can be anything:

- Import data from external source
- Export data (CSV, ...)
- ETL
- Analytics
- ...

Works with Neos CMS 1.2-2.0+. This package is Composer ready, [PSR-2] and [PSR-4] compliant.

**Currently this package is under development and version 1.0 is not out, breaking change can happen**

Features
--------

The package provide a backend module with a simple interface where Editors or Administrators can:

- [x] View all available Jobs
- [x] Manual Job execution
- [x] Pass options to a Job before manual execution
- [x] Filter job list by searching
- [x] Filter job list by tagging
- [x] Job can produce files (export), use the ```DocumentJobTrait```
- [ ] Create web hook to trigger job execution from external source
- [ ] Schedule a Job (integrate with Ttree.Scheduler)
- [ ] View the execution history for a given Job

![Backend Module](http://g.recordit.co/N0U74HQIY7.gif)

Check the issue tracker to follow ongoing features.

Installation
------------

```
composer require "ttree/jobbutler"
```

How to register a new Job
-------------------------

A Job is a PHP class based on ```JobConfigurationInterface```. By default you can use the ```AbstractJobConfiguration```
abstract class that offer nice defaults and helpers for general usage.

You can create a simple class like this one:

```php
<?php
namespace Your\Package\JobConfiguration;

/**
 * Export Document Job
 */
class ExportDocumentJob extends AbstractJobConfiguration
{
    use DocumentJobTrait;
    
    public function getIcon()
    {
        return 'print';
    }

    public function execute(array $options = [])
    {
        $context = $this->createContext('live');
        $sideNode = $context->getNode('/sites/yoursite');
        $flowQuery = new FlowQuery(array($sideNode));
        $flowQuery = $flowQuery->find('[instanceof TYPO3.Neos.NodeTypes:Page]');
        $writer = Writer::createFromFileObject(new \SplTempFileObject());
        $writer->insertOne([
            'identifier' => 'Identifier',
            'title' => 'Page Title'
        ]);

        foreach ($flowQuery as $node) {
            /** @var NodeInterface $node */
            $writer->insertOne([
                'identifier' => $node->getIdentifier(),
                'title' => $node->getProperty('title')
            ]);
        }

        $this->writeDocument($this->getOption('document', 'export.csv'), $writer);
        
        return true;
    }

}

```

The method ```DocumentJobTrait::writeDocument``` will automatically create a new AssetCollection name "Export"
(check [Settings.yaml](Settings.yaml) to change the asset collection name). Generated document are stored outside
of the public resource folder, check [Settings.yaml](Settings.yaml) to change the default path. 

Now create a XLIFF file name ```Jobs.xlf``` in the same package, with the following content:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file original="" product-name="Your.Package" source-language="en" datatype="plaintext">
        <body>
            <trans-unit id="your.package.jobconfiguration.exportdocumentjob.name" xml:space="preserve">
				<source>Export all Pages</source>
			</trans-unit>
			<trans-unit id="your.package.jobconfiguration.exportdocumentjob.description" xml:space="preserve">
				<source>Export a single CSV file with all Pages identifier and title.</source>
			</trans-unit>
        </body>
    </file>
</xliff>
```

You can extend this Job to get data from Google Analytics and you have a nice spreadsheet to work on Content Inventory ...

Now go to the backend module, you should see your Job, ready for execution.

How to configure a new Job
--------------------------

```yaml
Ttree:
  JobButler:
    jobSettings:
      'Your\Package\JobConfiguration\ExportDocumentJob':
        'icon': 'circle-arrow-down'
        'wizardFactoryClass': 'Your\Package\JobConfiguration\Wizard\ExportProfileByReportWizard'
```

Currently the following settings are supported by ```AbstractJobConfiguration```:

- icon (string), default 'task'
- tags (array), default emtpy array
- wizardFactoryClass (string), default null
- privilegeTarget (string), default null
- asynchronous (boolean), default false

Adding a configuration Wizard before executing a Job
----------------------------------------------------

Your Job need to provide a Form factory to render the form:

```xml
    ...
    
    /**
     * {@inheritdoc}
     */
    public function getWizardFactoryClass()
    {
        return 'Your\Package\JobConfiguration\ExportDocumentWizard';
    }
    
    ...
```

Provide a simple Factor:

```php
<?php
namespace Your\Package\JobConfiguration;

/**
 * Export Profile Index Job
 */
class ExportDocumentWizard extends AbstractFormFactory {

    /**
     * @param array $factorySpecificConfiguration
     * @param string $presetName
     * @return \TYPO3\Form\Core\Model\FormDefinition
     */
    public function build(array $factorySpecificConfiguration, $presetName) {
        $formConfiguration = $this->getPresetConfiguration($presetName);
        $form = new FormDefinition('options', $formConfiguration);

        $page = $form->createPage('page');

        $reportIdentifier = $page->createElement('reportIdentifier', 'TYPO3.Form:SingleLineText');
        $reportIdentifier->setLabel('Report Identifier');
        $reportIdentifier->addValidator(new NotEmptyValidator());

        return $form;
    }
}

``` 

The ``RenderViewHelper``` take care for the finisher configuration and arguments processing.

Settings
--------

- ```maximumExecutionTime```: Override the system maximum excution time from the php.ini (default: 300)

Acknowledgments
---------------

Development sponsored by [ttree ltd - neos solution provider](http://ttree.ch).

We try our best to craft this package with a lots of love, we are open to sponsoring, support request, ... just contact us.

License
-------

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[PSR-2]: http://www.php-fig.org/psr/psr-2/
[PSR-4]: http://www.php-fig.org/psr/psr-4/
