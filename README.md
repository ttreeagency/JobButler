Ttree.JobButler
===============

Introduction
------------

Neos CMS package that all Editors and Administrators to manage Jobs. Jobs can be anything:

- Import data from external source
- Export data (CSV, ...)
- ETL
- Analytics
- ...

Works with Neos CMS 1.2-2.0+. This package is Composer ready, [PSR-2] and [PSR-4] compliant.

Features
--------

The package provide a backend module with a simple interface where Editors or Administrators can:

1. View all available Jobs
2. Execute a Job
3. Schedule a Job (TODO, integrate with Ttree.Scheduler)
4. View the execution history for a given Job (TODO)

Check the issue tracker to follow ongoing features.

Installation
------------

```composer require "ttree/jobbutler"```

Acknowledgments
---------------

Development sponsored by [ttree ltd - neos solution provider](http://ttree.ch).

We try our best to craft this package with a lots of love, we are open to sponsoring, support request, ... just contact us.

License
-------

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[PSR-2]: http://www.php-fig.org/psr/psr-2/
[PSR-4]: http://www.php-fig.org/psr/psr-4/
