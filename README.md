Ttree.JobButler
===============

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

1. View all available Jobs
2. Execute a Job
3. Schedule a Job (TODO, integrate with Ttree.Scheduler)
4. View the execution history for a given Job (TODO)


![Backend Module](https://dl.dropboxusercontent.com/s/uu805iihinsjooz/2015-11-10%20at%2014.53.png?dl=0)

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

        $this->storeDocument($writer, 'myexport.csv');

        return true;
    }

}

```

The method ```AbstractJobConfiguration::storeDocument``` will automatically create a new AssetCollection name "Export"
(check [Settings.yaml](Settings.yaml) to change the asset collection name), and use a persistent cache to keep track of
created file. If you execute multiple time the same Job, it will resuse the same Document to store the result (this need
to be improved later on, but currently work correctly).

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

Acknowledgments
---------------

Development sponsored by [ttree ltd - neos solution provider](http://ttree.ch).

We try our best to craft this package with a lots of love, we are open to sponsoring, support request, ... just contact us.

License
-------

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[PSR-2]: http://www.php-fig.org/psr/psr-2/
[PSR-4]: http://www.php-fig.org/psr/psr-4/
