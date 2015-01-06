PHPMongo
========
[![Build Status](https://travis-ci.org/sokil/php-mongo.png?branch=master&1)](https://travis-ci.org/sokil/php-mongo)
[![Latest Stable Version](https://poser.pugx.org/sokil/php-mongo/v/stable.png)](https://packagist.org/packages/sokil/php-mongo)
[![Coverage Status](https://coveralls.io/repos/sokil/php-mongo/badge.png)](https://coveralls.io/r/sokil/php-mongo)
[![Gitter](https://badges.gitter.im/Join Chat.svg)](https://gitter.im/sokil/php-mongo?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![Documentation Status](https://readthedocs.org/projects/phpmongo/badge/?version=latest)](https://readthedocs.org/projects/phpmongo/?badge=latest)
[![Total Downloads](http://img.shields.io/packagist/dt/sokil/php-mongo.svg)](https://packagist.org/packages/sokil/php-mongo)

#### PHP ODM for MongoDB.

Why to use this library? You can easily work with document data through comfortable getters and setters instead of array and don't check if key exist in array. Access to subdocument use dot-syntax. You can validate data passed to document before save. We give you  events, which you can handle in different moments of document's life, and more things which make you life easier.

#### Requirements

* PHP 5.3 or above
* PHP MongoDB Extension 0.9 or above (Some features require >= 1.5)
* [Symfony Event Dispatcher](http://symfony.com/doc/current/components/event_dispatcher/introduction.html)
* [GeoJson version ~1.0](https://github.com/jmikola/geojson)
* [PSR-3 logger interface](https://github.com/php-fig/log)

#### Table of contents

* [Installation](#installation)
* [Connecting](#connecting)
* [Mapping](#mapping)
  * [Selecting database and collection](#selecting-database-and-collection)
  * [Custom collections](#custom-collections)
  * [Document schema and validating](#document-schema-and-validating)
* [Getting documents by id](#getting-documents-by-id)
* [Create new document](#create-new-document)
* [Get and set data in document](#get-and-set-data-in-document)
* [Storing document](#storing-document)
* [Querying documents](#querying-documents)
  * [Query Builder](#query-builder)
  * [Extending Query Builder](#extending-query-builder)
  * [Identity Map](#identity-map)
  * [Comparing queries](#comparing-queries)
* [Geospatial queries](#geospatial-queries)
* [Pagination](#pagination)
* [Embedded documents](#embedded-documents)
* [Batch operations](#batch-operations)
  * [Batch insert](#batch-insert)
  * [Batch update](#batch-update)
  * [Moving data between collections](#moving-data-between-collections)
* [Persistence (Unit of Work)](#persistence-unit-of-work)
* [Document validation](#document-validation)
* [Deleting collections and documents](#deleting-collections-and-documents)
* [Aggregation framework](#aggregation-framework)
* [Events](#events)
* [Behaviors](#behaviors)
* [Relation](#relations)
  * [One-to-one relation](#one-to-one-relation)
  * [One-to-many relation](#one-to-many-relation)
  * [Many-to-many relation](#many-to-many-relation)
  * [Add relation](#add-relation)
  * [Remove relation](#remove-relation)
* [Read preferences](#read-preferences)
* [Write concern](#write-concern)
* [Capped collections](#capped-collections)
* [Executing commands](#executing-commands)
* [Queue](#queue)
* [Migrations](#migrations)
* [GridFS](#gridfs)
* [Versioning](#versioning)
* [Indexes](#indexes)
* [Caching and documents with TTL](#caching-and-documents-with-ttl)
* [Debugging](#debugging)
  * [Logging](#logging)
  * [Profiling](#profiling)

Installation
------------

You can install library through Composer:
```javascript
{
    "require": {
        "sokil/php-mongo": "dev-master"
    }
}
```

If you use Symfony framework, you can use [Symfony MongoDB Bundle](https://github.com/sokil/php-mongo-bundle) which wraps this library

```javascript
{
    "require": {
        "sokil/php-mongo-bundle": "dev-master"
    }
}
```

If you use Yii Framework, you can use [Yii Adapter](https://github.com/sokil/php-mongo-yii) which wraps this library

```javascript
{
    "require": {
        "sokil/php-mongo-yii": "dev-master"
    }
}
```

If you require migrations, you can add dependency to "[sokil/php-mongo-migrator](https://github.com/sokil/php-mongo-migrator)", based on this library:

```javascript
{
    "require": {
        "sokil/php-mongo-migrator": "dev-master"
    }
}
```

Download latest release:
[Latest sources from GitHub](https://github.com/sokil/php-mongo/releases/latest)

Connecting
----------

#### Single connection

Connecting to MongoDB server made through `\Sokil\Mongo\Client` class:

```php
<?php
$client = new Client($dsn);
```

Format of DSN used to connect to server described in [PHP manual](http://www.php.net/manual/en/mongo.connecting.php).
To connect to localhost use next DSN:
```
mongodb://127.0.0.1
```
To connect to replica set use next DSN:
```
mongodb://server1.com,server2.com/?replicaSet=replicaSetName
```

#### Pool of connections

If you have few connections you may prefer connection pool instead of managing different connections. Use `\Sokil\Mongo\ClientPool` instance to initialize pool object:

```php
<?php

$pool = new ClientPool(array(
    'connect1' => array(
        'dsn' => 'mongodb://127.0.0.1',
        'defaultDatabase' => 'db2',
        'connectOptions' => array(
            'connectTimeoutMS' => 1000,
            'readPreference' => \MongoClient::RP_PRIMARY,
        ),
        'mapping' => array(
            'db1' => array(
                'col1' => '\Collection1',
                'col2' => '\Collection2',
            ),
            'db2' => array(
                'col1' => '\Collection3',
                'col2' => '\Collection4',
            )
        ),
    ),
    'connect2' => array(
        'dsn' => 'mongodb://127.0.0.1',
        'defaultDatabase' => 'db2',
        'mapping' => array(
            'db1' => array(
                'col1' => '\Collection5',
                'col2' => '\Collection6',
            ),
            'db2' => array(
                'col1' => '\Collection7',
                'col2' => '\Collection8',
            )
        ),
    ),
));

$connect1Client = $pool->get('connect1');
$connect2Client = $pool->get('connect2');
```

Mapping
-------

### Selecting database and collection

You can get instances of databases and collections by its name.

To get instance of database class `\Sokil\Mongo\Database`:
```php
<?php
$database = $client->getDatabase('databaseName');
// or simply
$database = $client->databaseName;
```

To get instance of collection class `\Sokil\Mongo\Collection`:
```php
<?php
$collection = $database->getCollection('collectionName');
// or simply
$collection = $database->collectionName;
```

Default database may be specified to get collection directly from `\Sokil\Mongo\Client` object:
```php
<?php
$client->useDatabase('databaseName');
$collection = $client->getCollection('collectionName');
```

### Custom collections

Custom collections used to add some collection-specific fetures in related class. First you need to create class extended from `\Sokil\Mongo\Collection`:
```php
<?php

// define class of collection
class CustomCollection extends \Sokil\Mongo\Collection
{

}
```

This class must be then mapped to collection name in order to return object of this class when collection requested. Custom collection referenced in standart way:

```php
<?php
/**
 * @var \CustomCollection
 */
$collection = $client
    ->getDatabase('databaseName')
    ->getCollection('collectionName');
```

#### Mapping of collection name to collection class

Any collection name may be mapped to class name directly:

```php
<?php

// map class to collection name
$client->map([
    'databaseName'  => [
        'collectionName' => '\CustomCollection'
    ],
]);

/**
 * @var \CustomCollection
 */
$collection = $client->getDatabase('databaseName')->getCollection('collectionName');
```

#### Mapping with class preffix

We can specify collection class prefix so any collection may be mapped to class without enumerating every collection name:

```php
<?php
$client->map([
    'databaseName'  => '\Class\Prefix',
]);

/**
 * @var \Class\Prefix\CollectionName
 */
$collection = $client->getDatabase('databaseName')->getCollection('collectionName');

/**
 * @var \Class\Prefix\CollectionName\SubName
 */
$collection = $client->getDatabase('databaseName')->getCollection('collectionName.subName');
```

#### Collection definition options

If you want to pass some options to collection's constructor, you also can
configure them in mapping definition:

```php
<?php
$client->map([
    'databaseName'  => [
        'collectionName' => [
            'class' => '\Some\Custom\Collection\Classname',
            'collectionOption1' => 'value1',
            'collectionOption2' => 'value2',
        ]
    ],
]);
```

All options later may be accessed by `Collection::getOption()` method:

```php
<?php
// will return 'value1'
$client
    ->getDatabase('databaseName')
    ->getCollection('collectionName')
    ->getOption('collectionOption1');
```

Predefined options are:

| Option           | Default value            | Description                                                |
| ---------------- | ------------------------ | ---------------------------------------------------------- |
| class            | \Sokil\Mongo\Collection  | Fully qualified collectin class                            |
| documentClass    | \Sokil\Mongo\Document    | Fully qualified document class                             |
| versioning       | false                    | Using document versioning                                  |
| index            | null                     | Index definition                                           |
| expressionClass  | \Sokil\Mongo\Expression  | Fully qualified expression class for custom query builder  |
| behaviors        | null                     | List of behaviors, attached to every document              |

If `class` omitted, then used standart `\Sokil\Mongo\Collection` class.

To override default document class use `documentClass` option of collection:
```php
<?php
$client->map([
    'databaseName'  => [
        'collectionName' => [
            'documentClass' => '\Some\Document\Class',
        ]
    ],
]);

// is instance of \Some\Document\Class
$document = $client
    ->getDatabase('databaseName')
    ->getCollection('collectionName')
    ->createDocument();
```

#### Regexp mapping

Collection name in mapping may be defined as RegExp pattern. Pattern must start from symbol `/`:

```php
<?php
$database->map(array(
    '/someCollection\d/' => '\Some\Collection\Class',
));
```

Any collection with name matched to pattern will be instance of `\Some\Collection\Class`:
```php
<?php
$col1 = $database->getCollection('someCollection1');
$col2 = $database->getCollection('someCollection2');
$col4 = $database->getCollection('someCollection4');
```

### Document schema and validating

Custom document class may be useful when required some processing of date on load, getting or save. Custom document class must extend `\Sokil\Mongo\Document`.

```php
<?php
class CustomDocument extends \Sokil\Mongo\Document
{

}
```

Now you must configure its name in collection's class by overriding method `Collection::getDocumentClassName()`:

```php
<?php
class CustomCollection extends \Sokil\Mongo\Collection
{
    public function getDocumentClassName(array $documentData = null) {
        return '\CustomDocument';
    }
}
```

You may flexibly configure document's class in `\Sokil\Mongo\Collection::getDocumentClassName()` relatively to concrete document's data:

```php
<?php
class CustomCollection extends \Sokil\Mongo\Collection
{
    public function getDocumentClassName(array $documentData = null) {
        return '\Custom' . ucfirst(strtolower($documentData['type'])) . 'Document';
    }
}
```

Also document class may be defined in collection mapping:

```php
<?php
$client->map([
    'databaseName'  => [
        'collectionName1' => [
            'documentClass' => '\CustomDocument',
        ],
        'collectionName2' => function(array $documentData = null) {
            return '\Custom' . ucfirst(strtolower($documentData['type'])) . 'Document';
        }
    ],
]);
```

In example above class `\CustomVideoDocument` related to `{"_id": "45..", "type": "video"}`, and `\CustomAudioDocument` to `{"_id": "45..", type: "audio"}`

#### Document schema

Document's scheme is completely not required. If field is required and has default value, it can be defined in special property of document class:
```php
<?php
class CustomDocument extends \Sokil\Mongo\Document
{
    protected $_data = [
        'requiredField' => 'defaultValue',
        'someField'     => [
            'subDocumentField' => 'value',
        ],
    ];
}
```

#### Document validation

Document can be validated before save. To set validation rules method `\Sokil\Mongo\Document::rules()` must be override with validation rules. Supported rules are:
```php
<?php
class CustomDocument extends \Sokil\Mongo\Document
{
    public function rules()
    {
        return array(
            array('email,password', 'required'),
            array('role', 'equals', 'to' => 'admin'),
            array('role', 'not_equals', 'to' => 'guest'),
            array('role', 'in', 'range' => array('admin', 'manager', 'user')),
            array('contract_number', 'numeric', 'message' => 'Custom error message, shown by getErrors() method'),
            array('contract_number' ,'null', 'on' => 'SCENARIO_WHERE_CONTRACT_MUST_BE_NULL'),
            array('code' ,'regexp', '#[A-Z]{2}[0-9]{10}#')
        );
    }
}
```

Document can have validation state, based on scenario. Scenarion can be specified by method `Document::setScenario($scenario)`.
```php
<?php
$document->setScenario('register');
```

If some validation rule applied only for some scenarios, this scenarios must be passed on `on` key, separated by comma.
```php
<?php
public function rules()
    {
        return array(
            array('field' ,'null', 'on' => 'register,update'),
        );
    }
```

If some validation rule applied to all except some scenarios, this scenarios must be passed on `except` key, separated by comma.

```php
<?php
public function rules()
    {
        return array(
            array('field' ,'null', 'except' => 'register,update'),
        );
    }
```

If document invalid, `\Sokil\Mongo\Document\Exception\Validate` will trigger and errors may be accessed through `Document::getErrors()` method of document object. This document may be get from exception method:
```php
<?php
try {

} catch(\Sokil\Mongo\Document\Exception\Validate $e) {
    $e->getDocument()->getErrors();
}
```

Error may be triggered manually by calling method `triggerError($fieldName, $rule, $message)`
```php
<?php
$document->triggerError('someField', 'email', 'E-mail must be at domain example.com');
```

You may add you custom validation rule just adding method to document class and defining method name as rule:
```php
<?php
class CustomDocument extends \Sokil\Mongo\Document
{
    punlic function rules()
    {
        return array(
            array(
                'email',
                'uniqueFieldValidator',
                'message' => 'E-mail must be unique in collection'
            ),
        );
    }

    /**
     * Validator
     */
    public function uniqueFieldValidator($fieldName, $params)
    {
        // Some logic of checking unique mail.
        //
        // Before version 1.7 this method must return true if validator passes,
        // and false otherwise.
        //
        // Since version 1.7 this method return no values and must call
        // Document::addError() method to add error into stack.
    }
}
```

You may create your own validator class, if you want to use validator in few classes.
Just extend your class from abstract validator class `\Sokil\Mongo\Validator` and register your own validator namespace:

```php
<?php
namespace Vendor\Mongo\Validator;

/**
 * Validator class
 */
class MyOwnEqualsValidator extends \Sokil\Mongo\Validator
{
    public function validateField(\Sokil\Mongo\Document $document, $fieldName, array $params)
    {
        if (!$document->get($fieldName)) {
            return;
        }

        if ($document->get($fieldName) === $params['to']) {
            return;
        }

        if (!isset($params['message'])) {
            $params['message'] = 'Field "' . $fieldName . '" must be equals to "' . $params['to'] . '" in model ' . get_called_class();
        }

        $document->addError($fieldName, $this->getName(), $params['message']);
    }
}

/**
 * Registering validator in document
 */

class SomeDocument extends \Sokil\Mongo\Document
{
    public function beforeConstruct()
    {
        $this->addValidatorNamespace('Vendor\Mongo\Validator');
    }

    public function rules()
    {
        return array(
            // 'my_own_equals_validator' converts to 'MyOwnEqualsValidator' class name
            array('field', 'my_own_equals_validator', 'to' => 42, 'message' => 'Not equals'),
        );
    }
}
```

Getting documents by id
-----------------------

To get document from collection by its id:
```php
<?php
$document = $collection->getDocument('5332d21b253fe54adf8a9327');
```

Create new document
-------------------

Create new empty document object:

```php
<?php
$document = $collection->createDocument();
```

Or with pre-defined values:

```php
<?php
$document = $collection->createDocument([
    'param1' => 'value1',
    'param2' => 'value2'
]);
```

Get and set data in document
----------------------------

To get value of document's field you may use one of following ways:
```php
<?php
$document->requiredField; // defaultValue
$document->get('requiredField'); // defaultValue
$document->getRequiredField(); // defaultValue

$document->someField; // ['subDocumentField' => 'value']
$document->get('someField'); // ['subDocumentField' => 'value']
$document->getSomeField(); // ['subDocumentField' => 'value']
$document->get('someField.subDocumentField'); // 'value'

$document->get('some.unexisted.subDocumentField'); // null
```
If field not exists, null value returned.

To set value you may use following ways:
```php
<?php
$document->someField = 'someValue'; // {someField: 'someValue'}
$document->set('someField', 'someValue'); // {someField: 'someValue'}
$document->set('someField.sub.document.field', 'someValue'); // {someField: {sub: {document: {field: {'someValue'}}}}}
$document->setSomeField('someValue');  // {someField: 'someValue'}
```

Storing document
----------------

To store document in database just save it.
```php
<?php
$document = $collection->createDocument(['param' => 'value'])->save();

$document = $collection->getDocument('23a4...')->set('param', 'value')->save();
```

Querying documents
------------------

### Query Builder

To query documents, which satisfy some conditions you need to use query builder:
```php
<?php
$cursor = $collection
    ->find()
    ->fields(['name', 'age'])
    ->where('name', 'Michael')
    ->whereGreater('age', 29)
    ->whereIn('interests', ['php', 'snowboard', 'traveling'])
    ->skip(20)
    ->limit(10)
    ->sort([
        'name'  => 1,
        'age'   => -1,
    ]);
```

All "where" conditions added with logical AND. To add condition with logical OR:
```php
<?php
$cursor = $collection
    ->find()
    ->whereOr(
        $collection->expression()->where('field1', 50),
        $collection->expression()->where('field2', 50),
    );
```

Result of the query is iterator `\Sokil\Mongo\QueryBuilder`, which you can then iterate:
```php
<?php
foreach($cursor as $documentId => $document) {
    echo $document->get('name');
}
```

Or you can get result array:
```php
<?php
$result = $cursor->findAll();
```

To get only one result:
```php
<?php
$document = $cursor->findOne();
```

To get only one random result:
```php
<?php
$document = $cursor->findRandom();
```

To get values from a single field in the result set of documents:
```php
<?php
$columnValues = $cursor->pluck('some.field.name');
```

### Extending Query Builder

For extending standart query builder class with custom condition methods you need to override property `Collection::$_queryExpressionClass` with class, which extends `\Sokil\Mongo\Expression`:

```php
<?php

// define expression in collection
class UserCollection extends \Sokil\Mongo\Collection
{
    protected $_queryExpressionClass = 'UserExpression';
}

// define expression
class UserExpression extends \Sokil\Mongo\Expression
{
    public function whereAgeGreaterThan($age)
    {
        $this->whereGreater('age', (int) $age);
    }
}

// use custom method for searching
$collection = $db->getCollection('user'); // instance of UserCollection
$queryBuilder = $collection->find(); // instance of UserExpression

$queryBuilder->whereAgeGreaterThan(18)->fetchRandom();

// or since v.1.3.2 configure query builder through callable:
$collection
    ->find(function(UserExpression $e) {
        $e->whereAgeGreaterThan(18);
    })
    ->fetchRandom();

```
### Identity Map

Imagine that you have two different query builders and they are both
return same document. Identity map helps us to get same instance of object
from different queries, so if we made changes to document from first query,
that changes will be in document from second query:

```php
<?php

$document1 = $collection->find()->whereGreater('age' > 18)->findOne();
$document2 = $collection->find()->where('gender', 'male')->findOne();

$document1->name = 'Mary';
echo $document2->name; // Mary
```

This two documents referenced same object. Collection by
default store all requested documents to identity map and return same objects
for different requests. But if we know that documents never be reused, we
can disable storing documents to identity map:

```php
<?php

$collection->disableDocumentPool();
```

To enable identity mapping:
```php
<?php

$collection->enableDocumentPool();
```

To check if identity mapping enabled:
```php
<?php

$collection->isDocumentPoolEnabled();
```

To clear pool identity map from previously stored documents:
```php
<?php

$collection->clearDocumentPool();
```

To check if there are documents in map already:
```php
<?php

$collection->isDocumentPoolEmpty();
```

If document already loaded, but it may be changed from another proces in db,
then your copy is not fresh. You can manually refresh document state
syncing it with db:
```php
<?php

$document->refresh();
```

### Comparing queries

If you want to cache your search results or want to compare two queries, you need some
identifier which unambiguously identify query. You can use `Cursor::getHash()` for
that reason. This hash uniquely identify just query parameners rather
than result set of documents, because it calculated from all query parameters:

```php
<?php

$queryBuilder = $this->collection
    ->find()
    ->field('_id')
    ->field('interests')
    ->sort(array(
        'age' => 1,
        'gender' => -1,
    ))
    ->limit(10, 20)
    ->whereAll('interests', ['php', 'snowboard']);

$hash = $queryBuilder->getHash(); // will return 508cc93b371c222c53ae90989d95caae

if($cache->has($hash)) {
    return $cache->get($hash);
}

$result = $queryBuilder->findAll();

$cache->set($hash, $result);
```

Geospatial queries
------------------

Before querying geospatial coordinates we need to create geospatial index
and add some data.

Index 2dsphere available since MongoDB version 2.4 and may be created in few ways:
```php

<?php
// creates index on location field
$collection->ensure2dSphereIndex('location');
// cerate compound index
$collection->ensureIndex(array(
    'location' => '2dsphere',
    'name'  => -1,
));
```

Geo data can be added as array in [GeoJson](http://geojson.org/) format or
using GeoJson objects of library [GeoJson](https://github.com/jmikola/geojson):

Add data as GeoJson object
```php
<?php

$document->setGeometry(
    'location',
    new \GeoJson\Geometry\Point(array(30.523400000000038, 50.4501))
);

$document->setGeometry(
    'location',
    new \GeoJson\Geometry\Polygon(array(
        array(24.012228, 49.831485), // Lviv
        array(36.230376, 49.993499), // Harkiv
        array(34.174927, 45.035993), // Simferopol
        array(24.012228, 49.831485), // Lviv
    ))
);

```

Data may be set througn array:
```php
<?php

// Point
$document->setPoint('location', 30.523400000000038, 50.4501);
// LineString
$document->setLineString('location', array(
    array(30.523400000000038, 50.4501),
    array(36.230376, 49.993499),
));
// Polygon
$document->setPolygon('location', array(
    array(
        array(24.012228, 49.831485), // Lviv
        array(36.230376, 49.993499), // Harkiv
        array(34.174927, 45.035993), // Simferopol
        array(24.012228, 49.831485), // Lviv
    ),
));
// MultiPoint
$document->setMultiPoint('location', array(
    array(24.012228, 49.831485), // Lviv
    array(36.230376, 49.993499), // Harkiv
    array(34.174927, 45.035993), // Simferopol
));
// MultiLineString
$document->setMultiLineString('location', array(
    // line string 1
    array(
        array(34.551416, 49.588264), // Poltava
        array(35.139561, 47.838796), // Zaporizhia
    ),
    // line string 2
    array(
        array(24.012228, 49.831485), // Lviv
        array(34.174927, 45.035993), // Simferopol
    )
));
// MultiPolygon
$document->setMultyPolygon('location', array(
    // polygon 1
    array(
        array(
            array(24.012228, 49.831485), // Lviv
            array(36.230376, 49.993499), // Harkiv
            array(34.174927, 45.035993), // Simferopol
            array(24.012228, 49.831485), // Lviv
        ),
    ),
    // polygon 2
    array(
        array(
            array(24.012228, 49.831485), // Lviv
            array(36.230376, 49.993499), // Harkiv
            array(34.174927, 45.035993), // Simferopol
            array(24.012228, 49.831485), // Lviv
        ),
    ),
));
// GeometryCollection
$document->setGeometryCollection('location', array(
    // point
    new \GeoJson\Geometry\Point(array(30.523400000000038, 50.4501)),
    // line string
    new \GeoJson\Geometry\LineString(array(
        array(30.523400000000038, 50.4501),
        array(24.012228, 49.831485),
        array(36.230376, 49.993499),
    )),
    // polygon
    new \GeoJson\Geometry\Polygon(array(
        // line ring 1
        array(
            array(24.012228, 49.831485), // Lviv
            array(36.230376, 49.993499), // Harkiv
            array(34.174927, 45.035993), // Simferopol
            array(24.012228, 49.831485), // Lviv
        ),
        // line ring 2
        array(
            array(34.551416, 49.588264), // Poltava
            array(32.049226, 49.431181), // Cherkasy
            array(35.139561, 47.838796), // Zaporizhia
            array(34.551416, 49.588264), // Poltava
        ),
    )),
));

```

Query documents near point on flat surface, defined by latitude 49.588264 and
longitude 34.551416 and distance 1000 meters from this point:

```php
<?php
$collection->find()->nearPoint('location', 34.551416, 49.588264, 1000);
```

This query require `2dsphere` or `2d` indexes.

Distance may be specified as array `[minDistance, maxDistance]`. This
feature allowed for MongoDB version 2.6 and greater. If some value
empty, only existed value applied.

```php
<?php
// serch distance less 100 meters
$collection->find()->nearPoint('location', 34.551416, 49.588264, array(null, 1000));
// search distabce between 100 and 1000 meters
$collection->find()->nearPoint('location', 34.551416, 49.588264, array(100, 1000));
// search distabce greater than 1000 meters
$collection->find()->nearPoint('location', 34.551416, 49.588264, array(1000, null));
```

To search on spherical surface:
```php
<?php
$collection->find()->nearPointSpherical('location', 34.551416, 49.588264, 1000);
```

To find geometries, which intersect specified:
```php
<?php
$this->collection
    ->find()
    ->intersects('link', new \GeoJson\Geometry\LineString(array(
        array(30.5326905, 50.4020355),
        array(34.1092134, 44.946798),
    )))
    ->findOne();
```

To select documents with geospatial data that exists entirely within a specified shape:
```php
<?php
$point = $this->collection
    ->find()
    ->within('point', new \GeoJson\Geometry\Polygon(array(
        array(
            array(24.0122356, 49.8326891), // Lviv
            array(24.717129, 48.9117731), // Ivano-Frankivsk
            array(34.1092134, 44.946798), // Simferopol
            array(34.5572385, 49.6020445), // Poltava
            array(24.0122356, 49.8326891), // Lviv
        )
    )))
    ->findOne();
```

Search documents within flat circle:
```php
<?php
$this->collection
    ->find()
    ->withinCircle('point', 28.46963, 49.2347, 0.001)
    ->findOne();
```

Search document within spherical circle:
```php
<?php
$point = $this->collection
    ->find()
    ->withinCircleSpherical('point', 28.46963, 49.2347, 0.001)
    ->findOne();
```

Search documents with points (stored as legacy coordinates) within box:
```php
<?php
$point = $this->collection
    ->find()
    ->withinBox('point', array(0, 0), array(10, 10))
    ->findOne();
```

Search documents with points (stored as legacy coordinates) within polygon:
```php
<?php
$point = $this->collection
    ->find()
    ->withinPolygon(
        'point',
        array(
            array(0, 0),
            array(0, 10),
            array(10, 10),
            array(10, 0),
        )
    )
    ->findOne();
```

Pagination
----------

Query builder allows you to create pagination.
```php
<?php
$paginator = $collection->find()->where('field', 'value')->paginate(3, 20);
$totalDocumentNumber = $paginator->getTotalRowsCount();
$totalPageNumber = $paginator->getTotalPagesCount();

// iterate through documents
foreach($paginator as $document) {
    echo $document->getId();
}
```

Embedded documents
------------------

### Embedded document

Imagine that you have document, whicj represent `User` model:

```javascript
{
    "login": "beebee",
    "email": "beebee@gmail.com",
    "profile": {
        "birthday": "1984-08-11",
        "gender": "female",
        "country": "Ukraine",
        "city": "Kyiv"
    }
}
```

You can define embedded `profile` document as standalone class:
```php
<?php

/**
 * Profile class
 */
class Profile extends \Sokil\Mongo\Structure
{
    public function getBirthday() { return $this->get('birthday'); }
    public function getGender() { return $this->get('gender'); }
    public function getCountry() { return $this->get('country'); }
    public function getCity() { return $this->get('city'); }
}

/**
 * User model
 */
class User extends \Sokil\Mongo\Document
{
    public function getProfile()
    {
        return $this->getObject('profile', '\Profile');
    }
}
```

Now you are able to get profile params:

```php
<?php
$birthday = $user->getProfile()->getBirthday();
```

### Embedded list of documents

Imagine that you have stored post data in collection 'posts', and post document has
embedded comment documents:

```javascript
{
    "title": "Storing embedded documents",
    "text": "MongoDb allows to atomically modify embedded documents",
    "comments": [
        {
            "author": "MadMike42",
            "text": "That is really cool",
            "date": ISODate("2015-01-06T06:49:41.622Z"
        },
        {
            "author": "beebee",
            "text": "Awesome!!!11!",
            "date": ISODate("2015-01-06T06:49:48.622Z"
        },
    ]
}
```

So we can create `Comment` model, which extends `\Sokil\Mongo\Structure`:

```php
<?php
class Comment extends \Sokil\Mongo\Structure
{
    public function getAuthor() { return $this->get('author'); }
    public function getText() { return $this->get('text'); }
    public function getDate() { return $this->get('date')->sec; }
}

```

Now we can create `Post` model with access to embedded `Comment` models:

```php
<?php

class Post extends \Sokil\Mongo\Document
{
    public function getComments()
    {
        return $this->getObjectList('comments', '\Comment');
    }
}
```

Method `Post::getComments()` allows you to get all of embedded document. To
paginate embedded documents you can use `\Sokil\Mongo\Cursor::slice()` functionality.

```php
<?php
$collection->find()->slice('comments', $limit, $offset)->findAll();
```

If you get `Document` instance through `Collection::getDocument()` you can define
additional expressions for loading it:

```php
<?php
$document = $collection->getDocument('54ab8585c90b73d6949d4159', function(Cursor $cursor) {
    $cursor->slice('comments', $limit, $offset);
});

```


Batch operations
----------------

### Batch insert

To insert many documents at once with validation of inserted document:
```php
<?php
$collection->insertMultiple(array(
    array('i' => 1),
    array('i' => 2),
));
```

### Batch update

Making changes in few documents:

```php
<?php

$collection->updateMultiple(function(\Sokil\Mongo\Expression $expression) {
    return $expression->where('field', 'value');
}, array('field' => 'new value'));
```

To update all documents:
```php
<?php
$collection->updateAll(array('field' => 'new value'));
```

### Moving data between collections

To copy documents from one collection to another according to expression:

```php
<?php
// to new collection of same database
$collection
    ->find()
    ->where('condition', 1)
    ->copyToCollection('newCollection');

// to new collection in new database
$collection
    ->find()
    ->where('condition', 1)
    ->copyToCollection('newCollection', 'newDatabase');
```

To move documents from one collection to another according to expression:

```php
<?php
// to new collection of same database
$collection
    ->find()
    ->where('condition', 1)
    ->moveToCollection('newCollection');

// to new collection in new database
$collection
    ->find()
    ->where('condition', 1)
    ->moveToCollection('newCollection', 'newDatabase');
```

Important to note that there is no transactions so if error will occur
during process, no changes will rollback.

Persistence (Unit of Work)
--------------------------

Instead of saving and removing objects right now, we can queue this job and execute all changes at once. This may be done through well-known pattern Unit of Work.

Lets create persistance manager
```php
<?php
$persistence = $client->createPersistence();
```

Now we can add some documents to be saved or removed later
```php
<?php
$persistence->persist($document1);
$persistence->persist($document2);

$persistence->remove($document3);
$persistence->remove($document4);
```

If later we decice do not save or remove document, we may detach it from persistence manager
```php
<?php
$persistence->detach($document1);
$persistence->detach($document3);
```

Or we even may remove them all:
```php
<?php
$persistence->clear();
```

Note that after detaching document from persistence manager, it's changes do not removed and document still may be saved directly or by adding to persistence manager.

If we decide to store changes to databasae we may flush this changes:
```php
<?php
$persistence->flush();
```

Note that persisted documents do not deleted from persistence manager after flush, but removed will be deleted.

Deleting collections and documents
-----------------------------------

Deleting of collection:
```php
<?php
$collection->delete();
```

Deleting of document:
```php
<?php
$document = $collection->getDocument($documentId);
$collection->deleteDocument($document);
// or simply
$document->delete();
```

Deleting of few documents:
```php
<?php
$collection->deleteDocuments($collection->expression()->where('param', 'value'));
```

Aggregation framework
--------------------------------

Create aggregator:
```php
<?php
$aggregator = $collection->createAggregator();
````

Than you need to configure aggregator by pipelines.
```php
<?php
// through array
$aggregator->match(array(
    'field' => 'value'
));
// through callable
$aggregator->match(function($expression) {
    $expression->whereLess('date', new \MongpDate);
});
```

To get results of aggregation after configuring pipelines:
```php
<?php
/**
 * @var array list of aggregation results
 */
$result = $aggregator->aggregate();
// or
$result = $collection->aggregate($aggregator);
```

You can execute aggregation without previously created aggregator:

```php
<?php
// by array
$collection->aggregate(array(
    array(
        '$match' => array(
            'field' => 'value',
        ),
    ),
));
// or callable
$collection->aggregate(function($aggregator) {
    $aggregator->match(function($expression) {
        $expression->whereLess('date', new \MongpDate);
    });
});
```

Using of Collection::createPipeline() is deprecated since 1.10.10. Use
Collection::createAggregator(), callable or array in Collection::aggregate().

Events
-------
Event support based on Symfony's Event Dispatcher component. Events can be attached in class while initialusing object or any time to the object. To attach events in Document class you need to override `Document::beforeConstruct()` method:
```php
<?php
class CustomDocument extends \Sokil\Mongo\Document
{
    public function beforeConstruct()
    {
        $this->onBeforeSave(function() {
            $this->set('date' => new \MongoDate);
        });
    }
}
```

Or you can attach event handler to document object:
```php
<?php
$document->onBeforeSave(function() {
    $this->set('date' => new \MongoDate);
});
```
To cancel operation execution on some condition use event handling cancel:
```php
<?php
$document
    ->onBeforeSave(function(\Sokil\Mongo\Event $event) {
        if($this->get('field') === 42) {
            $event->cancel();
        }
    })
    ->save();
```


Behaviors
----------

Behavior is a posibility to extend functionality of document object and reuse code among documents of different class.
Behavior is a class extended from `\Sokil\Mongo\Behavior`:
```php
<?php
class SomeBehavior extends \Sokil\Mongo\Behavior
{
    public function return42()
    {
        return 42;
    }
}
```

To get instance of object, to which behavior is attached, call `Behavior::getOwner()` method:
```php
<?php
class SomeBehavior extends \Sokil\Mongo\Behavior
{
    public function getOwnerParam($selector)
    {
        return $this->getOwner()->get($selector);
    }
}
```

You can add behavior in document class:
```php
<?php
class CustomDocument extends \Sokil\Mongo\Document
{
    public function behaviors()
    {
        return [
            '42behavior' => '\SomeBehavior',
        ];
    }
}
```

You can attach behavior in runtime too:
```php
<?php
$document->attachBehavior(new \SomeBehavior);
```

Then you can call any methods of behaviors. This methods searches in order of atraching behaviors:
```php
<?php
echo $document->return42();
```

Relations
-------------

You can define relations between different documents, which helps you to load related doluments. Library supports relations one-to-one and one-to-many

To define relation to other document you need to override `Document::relations()` method and returl array of relations in format `[relationName => [relationType, targetCollection, reference], ...]`

### One-to-one relation

We have to classes User and Profile. User has one profile, and profile belongs to User.

```php
<?php
class User extends \Sokil\Mongo\Document
{
    protected $_data = [
        'email'     => null,
        'password'  => null,
    ];

    public function relations()
    {
        return [
            'profileRelation' => [self::RELATION_HAS_ONE, 'profile', 'user_id'],
        ];
    }
}

class Profile extends \Sokil\Mongo\Document
{
    protected $_data = [
        'name' => [
            'last'  => null,
            'first' => null,
        ],
        'age'   => null,
    ];

    public function relations()
    {
        return [
            'userRelation' => [self::RELATION_BELONGS, 'user', 'user_id'],
        ];
    }
}
```

Now we can lazy load related documnts just calling relation name:
```php
<?php
$user = $userColletion->getDocument('234...');
echo $user->profileRelation->get('age');

$profile = $profileCollection->getDocument('234...');
echo $pfofile->userRelation->get('email');
```

### One-to-many relation

One-to-many relation helps you to load all related documents. Class User has few posts of class Post:

```php
<?php
class User extends \Sokil\Mongo\Document
{
    protected $_data = [
        'email'     => null,
        'password'  => null,
    ];

    public function relations()
    {
        return [
            'postsRelation' => [self::RELATION_HAS_MANY, 'posts', 'user_id'],
        ];
    }
}

class Posts extends \Sokil\Mongo\Document
{
    protected $_data = [
        'user_id' => null,
        'message'   => null,
    ];

    public function relations()
    {
        return [
            'userRelation' => [self::RELATION_BELONGS, 'user', 'user_id'],
        ];
    }

    public function getMessage()
    {
        return $this->get('message');
    }
}
```

Now you can load related posts of document:
```php
<?php
foreach($user->postsRelation as $post) {
    echo $post->getMessage();
}
```

### Many-to-many relation

Many-to-many relation in relational databases uses intermediate table with stored ids of related rows from both tables. In mongo this table equivalent embeds to one of two related documents. Element of relation definition at position 3 must be set to true in this document.


```php
<?php

// this document contains field 'driver_id' where array of ids stored
class CarDocument extends \Sokil\Mongo\Document
{
    protected $_data = [
        'brand' => null,
    ];

    public function relations()
    {
        return array(
            'drivers'   => array(self::RELATION_MANY_MANY, 'drivers', 'driver_id', true),
        );
    }
}

class DriverDocument extends \Sokil\Mongo\Document
{
    protected $_data = [
        'name' => null,
    ];

    public function relations()
    {
        return array(
            'cars'    => array(self::RELATION_MANY_MANY, 'cars', 'driver_id'),
        );
    }
}
```

Now you can load related documents:
```php
<?php
foreach($car->drivers as $driver) {
    echo $driver->name;
}
```

### Add relation

There is helper to add related document, if you don't
want modify relation field directly:

```php
<?php
$car->addRelation('drivers', $driver);
```

This helper automatically resolves collection and field
where to store relation data.

### Remove relation

There is helper to remove related document, if you don't
want modify relation field directly:

```php
<?php
$car->removeRelation('drivers', $driver);
```

This helper automatically resolves collection and field
where to remove relation data. If relation type is `HAS_MANY` or `BELONS_TO`,
second parameter wich defined related object may be omitted.


Read preferences
----------------
[Read preference](http://docs.mongodb.org/manual/core/read-preference/) describes how MongoDB clients route read operations to members of a replica set. You can configure read preferences at any level:

```php
<?php
// in constructor
$client = new Client($dsn, array(
    'readPreference' => 'nearest',
));

// by passing to \Sokil\Mongo\Client instance
$client->readNearest();

// by passing to database
$database = $client->getDatabase('databaseName')->readPrimaryOnly();

// by passing to collection
$collection = $database->getCollection('collectionName')->readSecondaryOnly();
```

Write concern
-------------
[Write concern](http://docs.mongodb.org/manual/core/write-concern/) describes the guarantee that MongoDB provides when reporting on the success of a write operation. You can configure write concern at any level:

```php
<?php

// by passing to \Sokil\Mongo\Client instance
$client->setMajorityWriteConcern(10000);

// by passing to database
$database = $client->getDatabase('databaseName')->setMajorityWriteConcern(10000);

// by passing to collection
$collection = $database->getCollection('collectionName')->setWriteConcern(4, 1000);
```

Capped collections
------------------

To use capped collection you need previously to create it:
```php
<?php
$numOfElements = 10;
$sizeOfCollection = 10*1024;
$collection = $database->createCappedCollection('capped_col_name', $numOfElements, $sizeOfCollection);
```

Now you can add only 10 documents to collection. All old documents will ve rewritted ny new elements.

Executing commands
------------------

Command is universal way to do anything with mongo. Let's get stats of collection:
```php
<?php
$collection = $database->createCappedCollection('capped_col_name', $numOfElements, $sizeOfCollection);
$stats = $database->executeCommand(['collstat' => 'capped_col_name']);
```

Result in $stats:
```
array(13) {
  'ns' =>  string(29) "test.capped_col_name"
  'count' =>  int(0)
  'size' =>  int(0)
  'storageSize' =>  int(8192)
  'numExtents' =>  int(1)
  'nindexes' =>  int(1)
  'lastExtentSize' =>  int(8192)
  'paddingFactor' =>  double(1)
  'systemFlags' =>  int(1)
  'userFlags' =>  int(1)
  'totalIndexSize' =>  int(8176)
  'indexSizes' =>  array(1) {
    '_id_' =>    int(8176)
  }
  'ok' =>  double(1)
}
```

Queue
-----

Queue gives functionality to send messages from one process and get them in another process. Messages can be send to different channels.

Sending message to queue with default priority:
```php
<?php
$queue = $database->getQueue('channel_name');
$queue->enqueue('world');
$queue->enqueue(['param' => 'value']);
```

Send message with priority
```php
<?php
$queue->enqueue('hello', 10);
```

Reading messages from channel:
```php
<?php
$queue = $database->getQueue('channel_name');
echo $queue->dequeue(); // hello
echo $queue->dequeue(); // world
echo $queue->dequeue()->get('param'); // value
```

Number of messages in queue
```php
<?php
$queue = $database->getQueue('channel_name');
echo count($queue);
```

Migrations
----------

Migrations allows you easily change schema and data versions. This functionality implemented in packet https://github.com/sokil/php-mongo-migrator and can be installed through composer:
```javascript
{
    "require": {
        "sokil/php-mongo-migrator": "dev-master"
    }
}
```

GridFS
------

GridFS allows you to store binary data in mongo database. Details at http://docs.mongodb.org/manual/core/gridfs/.

First get instance of GridFS. You can specify prefix for partitioning filesystem:

```php
<?php
$imagesFS = $database->getGridFS('image');
$cssFS = $database->getGridFS('css');
```

Now you can store file, located on disk:
```php
<?php
$id = $imagesFS->storeFile('/home/sokil/images/flower.jpg');
```

You can store file from binary data:
```php
<?php
$id1 = $imagesFS->storeBytes('some text content');
$id2 = $imagesFS->storeBytes(file_get_contents('/home/sokil/images/flower.jpg'));
```

You are able to store some metadata with every file:
```php
<?php
$id1 = $imagesFS->storeFile('/home/sokil/images/flower.jpg', [
    'category'  => 'flower',
    'tags'      => ['flower', 'static', 'page'],
]);

$id2 = $imagesFS->storeBytes('some text content', [
    'category' => 'books',
]);
```

Get file by id:
```php
<?php
$imagesFS->getFileById('6b5a4f53...42ha54e');
```

Find file by metadata:
```php
<?php
foreach($imagesFS->find()->where('category', 'books') as $file) {
    echo $file->getFilename();
}
```

Deleting files by id:
```php
<?php
$imagesFS->deleteFileById('6b5a4f53...42ha54e');
```

If you want to use your own `GridFSFile` classes, you need to define mapping, as it does with collections:
```php
<?php
// define mapping of prefix to GridFS class
$database->map([
    'GridFSPrefix' => '\GridFSClass',
]);

// define GridFSFile class
class GridFSClass extends \Sokil\Mongo\GridFS
{
    public function getFileClassName(\MongoGridFSFile $fileData = null)
    {
        return '\GridFSFileClass';
    }
}

// define file class
class GridFSFileClass extends \Sokil\Mongo\GridFSFile
{
    public function getMetaParam()
    {
        return $this->get('meta.param');
    }
}

// get file as instance of class \GridFSFileClass
$database->getGridFS('GridFSPrefix')->getFileById($id)->getMetaParam();
```

Versioning
----------

To enable versioning of documents in collection, you can set protected
property `Collection::$versioning` to `true`, or call `Collection::enableVersioning()`
method.

```php
<?php
// througn protected property
class MyCollection extends \Sokil\Mongo\Collection
{
    protected $versioning = true;
}

// througn method
$collection = $database->getCollection('my');
$collection->enableVersioning();
```

To check if documents in collections is versioned call:

```php
<?php
if($collection->isVersioningEnabled()) {}
```

Revision is an instance of class `\Sokil\Mongo\Revision` and inherits `\Sokil\Mongo\Document`,
so any methods of document may be applied to revision. Revisions may be accessed:
```php
<?php
// get all revisions
$document->getRevisions();

// get slice of revisions
$limit = 10;
$offset = 15;
$document->getRevisions($limit, $offset);
```

To get one revision by id use:
```php
<?php
$revision = $document->getRevision($revisionKey);
```

To get count of revisions:
```php
<?php
$count = $document->getRevisionsCount();
```

To clear all revisions:
```php
<?php
$document->clearRevisions();
```

Revisions stored in separate collection, named `{COLLECTION_NAME}.revisions`
To obtain original document of collection `{COLLECTION_NAME}` from revision,
which is document of collection `{COLLECTION_NAME}.revisions`,
use `Revision::getDocument()` method:

```php
<?php
$document->getRevision($revisionKey)->getDocument();
```

Properties of document from revision may be accessed directly:
```
echo $document->property;
echo $document->getRevision($revisionKey)->property;
```

Also date of creating revison may be obtained from document:
```php
<?php
// return timestamp
echo $document->getRevision($revisionKey)->getDate();
// return formatted date string
echo $document->getRevision($revisionKey)->getDate('d.m.Y H:i:s');
```

Indexes
-------

Create index with custom options (see options in http://php.net/manual/en/mongocollection.ensureindex.php):
```php
<?php
$collection->ensureIndex('field', [ 'unique' => true ]);
```

Create unique index:
```php
<?php
$collection->ensureUniqueIndex('field');
```

Create sparse index (see http://docs.mongodb.org/manual/core/index-sparse/ for details about sparse indexes):
```php
<?php
$collection->ensureSparseIndex('field');
```

Create TTL index (see http://docs.mongodb.org/manual/tutorial/expire-data/ for details about TTL indexes):
```php
<?php
$collection->ensureTTLIndex('field');
```

You may define field as array where key is field name and value is direction:
```php
<?php
$collection->ensureIndex(['field' => 1]);
```

Also you may define compound indexes:
```php
<?php
$collection->ensureIndex(['field1' => 1, 'field2' => -1]);
```

You may define all collection indexes in property `Collection::$_index`
as array, where each item is an index definition.
Every index definition must contain key `keys` with list of fields and orders,
and optional options, as described in http://php.net/manual/en/mongocollection.createindex.php.

```php
<?php
class MyCollection extends \Sokil\Mongo\Collection
{
    protected $_index = array(
        array(
            'keys' => array('field1' => 1, 'field2' => -1),
            'unique' => true
        ),
    );
}
```

Then you must create this indexes by call of `Collection::initIndexes()`:

```php
<?php
$collection = $database->getCollection('myCollection')->initIndexes();
```

You may use [Mongo Migrator](https://github.com/sokil/php-mongo-migrator) package
to ensure indexes in collections from migration scripts.

[Query optimiser](http://docs.mongodb.org/manual/core/query-plans/#read-operations-query-optimization)
 automatically choose which index to use, but you can manuallty define it:

```php
<?php
$collection->find()->where('field', 1)->hind(array('field' => 1));
```

Caching and documents with TTL
------------------------------

If you want to get collection where documents will expire after some specified time, just add special index to this collection.

```php
<?php
$collection->ensureTTLIndex('createDate', 1000);
```

You can do this also in migration script, using [Mongo Migrator](https://github.com/sokil/php-mongo-migrator).
For details see readme on than pakage's page.

Or you can use `\Sokil\Mongo\Cache` class, which already implement this functionality.

```php
<?php
// Get cache instance
$cache = $document->getCache('some_namespace');
```
Before using cache must be inititalised by calling method `Cache:init()`:
```php
<?php
$cahce->init();
```

This operation creates index with `expireAfterSecond` key in collection `some_namespace`.

This operation may be done in some console command or migration script, or
you can create manually in mongo console:

```javascript
db.some_namespace.ensureIndex('e', {expireAfterSeconds: 0});
```

Now you can store new value with:
```php
<?php
// this store value for 10 seconds by defininc concrete timestamp when cached value expired
$cache->setByDate('key', 'value', time() + 10);
// same but expiration defined relatively to current time
$cache->set('key', 'value', 10);
```

You can devine value which never expired and must be deleted manually:
```php
<?php
$cache->setNeverExpired('key', 'value');
```

You can define some tags defined with key:
```php
<?php
$cache->set('key', 'value', 10, ['php', 'c', 'java']);
$cache->setNeverExpired('key', 'value', ['php', 'c', 'java']);
$cache->setDueDate('key', 'value', time() + 10, ['php', 'c', 'java']);
```

To get value
```php
<?php
$value = $cache->get('key');
```

To delete cached value by key:
```php
<?php
$cache->delete('key');
```

Delete few values by tags:
```php
<?php
// delete all values with tag 'php'
$cache->deleteMatchingTag('php');
// delete all values without tag 'php'
$cache->deleteNotMatchingTag('php');
// delete all values with tags 'php' and 'java'
$cache->deleteMatchingAllTags(['php', 'java']);
// delete all values which don't have tags 'php' and 'java'
$cache->deleteMatchingNoneOfTags(['php', 'java']);
// Document deletes if it contains any of passed tags
$cache->deleteMatchingAnyTag(['php', 'elephant']);
// Document deletes if it contains any of passed tags
$cache->deleteNotMatchingAnyTag(['php', 'elephant']);
```

Debugging
---------

### Logging

Library suports logging of queries. To configure logging, you need to pass logger object to instance of `\Sokil\Mongo\Client`. Logger must implement `\Psr\Log\LoggerInterface` due to [PSR-3](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md):

```php
<?php
$client = new Client($dsn);
$client->setLogger($logger);
```

### Profiling

Mode details about profiling at [Analyze Performance of Database Operations](http://docs.mongodb.org/manual/tutorial/manage-the-database-profiler/)
profiler data stores to `system.profile` collection, which you can query through query builder:

```php
<?php

$qb = $database
    ->findProfilerRows()
    ->where('ns', 'social.users')
    ->where('op', 'update');
```

Structure of document described in article [Database Profiler Output](http://docs.mongodb.org/manual/reference/database-profiler/)

There is three levels of profiling, described in article [Profile command](http://docs.mongodb.org/manual/reference/command/profile/).
Switching between then may be done by calling methods:

```php
<?php

// disable profiles
$database->disableProfiler();

// profile slow queries slower than 100 milliseconds
$database->profileSlowQueries(100);

// profile all queries
$database->profileAllQueries();
```

To get current level of profiling, call:
```php
<?php
$params = $database->getProfilerParams();
echo $params['was'];
echo $params['slowms'];

// or directly
$level = $database->getProfilerLevel();
$slowms = $database->getProfilerSlowMs();
```



<hr/>
<br/>
Pull requests, bug reports and feature requests is welcome. Add new to [issues](https://github.com/sokil/php-mongo/issues)
