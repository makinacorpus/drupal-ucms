Getting started
***************

Requirements
============
To setup µCMS, you will need :

* A *composer based* Drupal 7 project 
* A twig based theme
* `Badm <https://github.com/makinacorpus/drupal-badm>`_ as admin theme
* At least 2 domains (one for your admin site & one for your first site)
* JQuery in version 1.9 or higher (or the `JQuery Update module <https://www.drupal.org/project/jquery_update/releases/7.x-3.0-alpha3>`_)
* Elastic Search (version >=2.3 and <3) with the mapper-attachments plugin **<- won't be required in µCMS v2**

Installation
============

Install µCMS Module
-------------------
This module works with composer, and should be installed using it, go in your 
project repository and just type the following line in your terminal :

.. code-block:: sh

    composer require makinacorpus/drupal-ucms

Please refer to this `Composer template for Drupal projects <https://github.com/drupal-composer/drupal-project/tree/7.x/>`_
to have a nice exemple for doing this.

Configure Drupal 7 - Symfony - Dependency Injection
---------------------------------------------------
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

Configure Elastic Search
------------------------
.. note::

   Elastic Search won't be needed in the version 2 of µCMS

You must then tell this module your Elastic Search hostname by adding the 
following variable to your ``settings.php`` file :

.. todo::

   Note sure this is the righ variable
   
.. code-block:: php
   
   <? php
   
   $conf['ucms_search.elastic.config']['hosts'] = ['localhost:9200'];
   
Configure the master site hostname
----------------------------------
In order to administrate your multi-site factory, µCMS provides you an administration
site.

To let µCMS know your master hostname, add the following variable to your 
``settings.php`` file :

.. code-block:: php
   
   <? php
   
   $conf['ucms_site_master_hostname'] = 'YOUR_MASTER_HOSTNAME';
   
Add also the following variable needed to perform multi-site redirection :

.. code-block:: php
   
   <? php
   
   $conf['site_frontpage']='home';

Setup of µCMS
=============
Before you can create your fisrt website, you need to configure :

* your µCMS roles & users;
* themes & categories available for your futur sites;
* the workflow for a site creation.

But first, enable the following µCMS modules in Drupal :

* ``ucms_site`` : provides the core module
* ``ucms_dashboard`` : provides an administration dashboard
* ``ucms_contrib`` : provides several tools for the admin panel
* ``ucms_user`` : provides an user management dashboard

Configure Roles & Users
-----------------------
µCMS uses Drupal to configure differents *Roles* for your factory. You can attach 
*Permissions* to each *Roles*. Then a *Role* will be given to each *Users*. 

In the exact same way as in Drupal.

Creating Roles
^^^^^^^^^^^^^^
As an example, we suggest you create at least these two roles :

* ``fadmin`` : can manage the site-factory
* ``webmaster`` : can only manage his sites

To set these two *Roles* go to *People* pannel ``YOUR_MASTER_HOSTNAME/admin/people``,
then, go in the *Permissions* tab and in the *Roles* sub-tab.

Or go directly to ``YOUR_MASTER_HOSTNAME/admin/people/permissions/roles``.

.. note:: 
   The *People* dashboard is not accesible via the main dashboard but only
   by the url : ``MASTER_HOSTNAME/admin/people``.

You can now create your two *Roles* : ``fadmin`` & ``webmaster``.

Setting up Permissions
^^^^^^^^^^^^^^^^^^^^^^
Then, go to the permission sub-tab ``YOUR_MASTER_HOSTNAME/admin/people/permissions/``
and give the following *Permissions* :

.. csv-table::
   :header: *Permissions*, ``fadmin``, ``webmaster``
   :widths: 50, 10, 10

   **UCMS - Contribution**                                  
   Access the favorites feature, yes, yes
   Access the UCMS content overview page, yes, yes
   **UCMS - Dashboard**
   Use contextual pane  , yes, yes
   **UCMS - Site**
   Content god mode, no, no 
   Manage global content, yes, no
   Manage group content, yes, no
   Manage starred content  , yes, no
   Flag content as inappropriate, no, no
   Unflag content flagged as inappropriate, no, no
   Transfer content ownership to another user, no, no
   View all content no matter where it stands, yes, no   
   View global published content, yes, no
   View group published content, no, yes
   View other site content, no, yes
   Site god mode, no, yes
   Request new site, yes, yes
   Access to site dashboard, yes, yes
   Manage all sites no matter their state is, yes, no
   **UCMS - User management**
   Manage all users, yes, no

Creating Users
^^^^^^^^^^^^^^
Now, let's create an *User* for each *Role* :

* an *User* ``FactoryAdmin`` with the *Role* ``fadmin``
* an *User* ``Webmaster`` with the *Role* ``webmaster``

Go to the *Dashboard* and for each *User* click on ``Create user``, fill the form, 
enable your *User* and set a passaword.

.. Note::

   The *Role* admin for µCMS is different from the drupal admin

Configure site template
-----------------------
µCMS let you choose themes and categories available when someone wants to create a
new site. To set this up, go to ``YOUR_MASTER_HOSTNAME/admin/structure/`` and the click 
on ``Site factory configuration``.

Or go directly to ``YOUR_MASTER_HOSTNAME/admin/structure/site``.

Here you can choose the default node type for site home page and allowed themes 
for a new site.

Configure the site workflow
---------------------------
µCMS provides a complete customizable *Workflow* to securize a site life-cycle - 
from request to archive passing by publication. Here is the different *States* a 
site can be in µCMS :

.. csv-table::
   :header: States, Description, Published ?
   :widths: 10, 500, 5
 
   *Requested*, Someone asked for a new site : beginning of the site life-cycle, no
   *Rejected*, A requested site has been rejected by someone, no
   *Creation*, A requested site has been accepted and is now in creation, no
   *Initialization*, First contents is adding in a create site, no
   *On*, Site is published, YES
   *Off*, Site is Off-line, no
   *Archive*, The site no-longer needed : end of the site life-cycle, no

To setup this workflow, go to the *Transitions* pannel : ``YOUR_MASTER_HOSTNAME/admin/structure/``,
click on ``Site factory configuration`` and go to the ``Transitions`` tab.
 
Or go directly to ``YOUR_MASTER_HOSTNAME/admin/structure/site/transitions``.

Here you can choose for each trio *'StateA/RoleA/StateB'* if the *Role* ``RoleA``
can put a site from the *State* ``StateA`` to ``StateB``.

For example : 

* Can an ``admin`` put a site from *Requested* to *Rejected* ?
* Can a ``webmaster`` put a site from *Off* to *Archive* ?
* Can an ``xxx`` directly put a site to *On* from *Requested* ?
* Can an ``xxx`` put a site from *aaa* to *bbb* ?
* ...

Creation of your first site
---------------------------
Ok... that's *it*, let's create your first site !

Log in with an *User* attached to a *Role* with the ability to request a new site and go to the 
*Dashboard* : ``YOUR_MASTER_HOSTNAME/admin/dashboard``.

Then, just follow the workflow you have setted up.
