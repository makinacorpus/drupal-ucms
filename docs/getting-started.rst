Getting started
===============

Installation
------------

Easy way : if your Drupal 7 project is composer based
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
This module works with composer, and should be installed using it, go in your 
project repository and just type the following line in your terminal :

.. code-block:: sh

    composer require makinacorpus/drupal-ucms

Please refer to this `Composer template for Drupal projects <https://github.com/drupal-composer/drupal-project/tree/7.x/>`_
to have a nice exemple for doing this.

Hard way : if your Drupal 7 project is not composer based
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
You may use the ``Composer Manager`` module although it's untested, or if it's 
not too late you probably should provide a global ``composer.json`` for your 
Drupal site.

Configuration
-------------

Step 1 : Configure Drupal 7 - Symfony - Dependency Injection
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
`Drupal-sf-dic <https://github.com/makinacorpus/dupral-ucms/>`_  is a Drupal 7 
module that serves the purpose of bringing the Symfony 3 dependency injection 
container to Drupal 7 along with a limited Drupal 8 API compatibility layer.

Drupal-µCMS needs drupal-sf-dic to be configured in order to bring Symfony 3 Fullstack 
into Drupal 7 and to use twig.

First, you need to configure drupal-sf-dic, to do that, you may follow :

* `Getting Started with Drupal-sf-dic 7 <http://drupal-sf-dic.readthedocs.io/en/latest/getting-started.html>`_

Then, you may follow these documentations :

* `Bringing Symfony 3 Fullstack into Drupal 7 <http://drupal-sf-dic.readthedocs.io/en/latest/bundles.html>`_
* `Using twig in Drupal 7 <http://drupal-sf-dic.readthedocs.io/en/latest/twig.html>`_

Step 2: Setting the µCMS Master Site Hostname
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
You must then tell this module your master site hostname by adding the following 
variable to your ``settings.php`` file :

.. code-block:: php
   
   <? php
   
   $conf['ucms_site_master_hostname'] = 'YOUR_HOSTNAME';
