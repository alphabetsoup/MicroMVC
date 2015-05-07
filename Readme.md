#MicroMVC#
*A lightweight model-view-controller framework for PHP 5*
---

This code was eventually brought into the video compression and queueing system used by Imageination.com. An exampe of the magic methods __get() as applied to model object calls such as student.pencils() as specified by the .belongsTo() and .hasMany() initialisation calls are in [base/base.php](base/base.php)

There is also a neat workaround for the lack of late static binding in PHP in [base/lib-phpextensions.php](base/lib-phpextensions.php) in the form of a slow-but-useable get_called_class() method that simulates the behavior of get_called_class() in PHP>=5.3.
