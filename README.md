entity-logger
=============

*By Sébastien Garcia*

This bundle allows you to log every modification made to your entities, just by implementing the ``EntityLoggerInterface`` interface.

## Installation

Using Composer:

    php composer.phar require meyfarth/entity-logger dev-master

Enable the bundle in your kernel :

```php
// app/AppKernel.php

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new \Meyfarth\EntityLoggerBundle\MeyfarthEntityLoggerBundle(),
        );
        
        ...
        
        return $bundles;
    }
```

Update your database :

    php app/console doctrine:schema:update --force
    
This will create a ``meyfarth_entity_log`` table. You can dump your database first using `--dump-sql` option if you want to know the table structure.

## Usage

To log an entity, implement the ``EntityLoggerInterface`` interface in the entities you want to log :
```php
<?php

namespace MyApp\MyBundle\Entity;

use Meyfarth\EntityLoggerBundle\Entity\EntityLoggerInterface;

/**
 * MyEntity
 */
class MyEntity implements EntityLoggerInterface
{
  // Your code
}
```

## Configuration
```yml
# app/config/config.yml
meyfarth_entity_logger:
    enable: true # default false. If not enabled, nothing will be logged
    log:
        create: true  # log on new entities
        update: true  # log on updates, new entities will not be considered as updates
        delete: true  # log on deletion
    user_class: false # log the current user (see below for explainations)
    nb_logs_by_page: 50 # number of logs by page on listing page, default is 50
```


If you use the security component with your own user entity (managed by Doctrine in your database), you can automatically log the current user by specifying the user_class parameter:

```yml
# app/config/config.yml
meyfarth_entity_logger:
    user_class: MyApp\MyBundle\Entity\MyUser
```

## Interfaces

Upcoming : interface to compare the entity between 2 logs.

You can now list all the logs. Simply add to your routing :
```yml
# app/config.routing.yml
#...
meyfarth_entity_log:
    resource: "@MeyfarthEntityLoggerBundle/Resources/config/routing.yml"
    prefix:   /entity-log

```

You can now access the log list by going to ``yourserver/entity-log/list/{page}`` and see the ugliest table you'll ever see. Note that the ``{page}`` token is by default 1.

To override the default template, add your own template named ``list.html.twig`` in ``app/Resouces/MeyfarthEntityLoggerBundle/views/Log/list.html.twig``. 

The controller pass those parameters to the view :
```yml
'logs' # the database result of the current page.
'page' # the current page
'nbByPage' # the number of elements by page
'nbPages' # the total number of pages
```

To access the EntityLog data in twig :
```twig
log.id      {# the EntityLog id #}
log.dateLog {# DateTime of the log #}
log.typeLog {# type of log. Possibles values are defined in EntityLoggerService #}
log.data    {# the data : an array of array #}
```

Note : to compare the values in ``log.typeLog``, you can use the following class constants :
* ``Meyfarth\EntityLoggerBundle\Service\EntityLoggerService::TYPE_INSERT`` 
* ``Meyfarth\EntityLoggerBundle\Service\EntityLoggerService::TYPE_UPDATE`` 
* ``Meyfarth\EntityLoggerBundle\Service\EntityLoggerService::TYPE_DELETE`` 

The ``log.data`` is defined as following :
```php

/* Note that only the fields modified will be in the data. 
It means that if you only modify the "string" field of your entity, only the "string" field 
will be present in the log.data
*/
array(
    '[FIELDNAME]' => array(
        0 => previousData,
        1 => currentData,
    ),
    '[FIELDNAME2]' => array(
        0 => previousData,
        1 => currentData,
    ),
);

```
