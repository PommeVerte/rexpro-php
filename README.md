This is a rexpro client for PHP. It's main purpose was for it to be integrated into frameworks, and therefore it will fail silently and not throw any exceptions. See Error handling section 


Installation
============

### MsgPack (optional)

rexpro-php does not require MsgPack anymore as it can also serialize using json. If you wish you can still use it though: [MsgPack](http://msgpack.org/) .

Install MsgPack from PEAR:

```bash
sudo pecl install msgpack-beta
sudo sh -c 'echo "extension=msgpack.so" > /etc/php5/mods-available/msgpack.ini'
sudo php5enmod msgpack
```

### PHP Rexster Client

##### For TP3 and a Gremlin-Server version of this client go here :

https://github.com/PommeVerte/gremlin-php

##### For Rexster 2.4+

Prefered method is trough composer. Add the following to your **composer.json** file:

```json
{
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/PommeVerte/rexpro-php.git"
        }
    ],
    "require": {
        "brightzone/rexpro": "master"
    }
}
```
If you just want to pull and use the library do:

```bash
git clone https://github.com/PommeVerte/rexpro-php.git
cd rexpro-php
composer install --no-dev # required to set autoload files
```

##### For Rexster 2.3

Prefered method is through composer. Add the following to your **composer.json** file:

```json
{
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/PommeVerte/rexpro-php.git"
        }
    ],
    "require": {
        "brightzone/rexpro": "2.3"
    }
}
```
If you just want to pull and use the library do:

```bash
git clone https://github.com/PommeVerte/rexpro-php.git -b 2.3
cd rexpro-php
composer install --no-dev # required to set autoload files
```


Error Handling
==============

The PHP Client does not throw Exceptions. It was built with the goal of being wrapped into a PHP framework and therefore fails silently (you can still get errors by checking method return values).

For instance:

```php
if($db->open('localhost:8184','tinkergraph',null,null) === false)
  throw new Exception($db->error->code . ' : ' . $db->error->description);
$db->script = 'g.v(2)';
$result = $db->runScript();
if($result === false)
   throw new Exception($db->error->code . ' : ' . $db->error->description);
//do something with result
```

Namespace
=========

The Connection class exists within the `rexpro` namespace. This means that you have to do either of the two following:

```php
require_once 'rexpro-php/src/Connection.php';
use \brightzone\rexpro\Connection;
 
$db = new Connection;
```

Or

```php
require_once 'rexpro-php/src/Connection.php';

$db = new \brightzone\rexpro\Connection;
```

Serializer
==========
rexpro-php will use the pecl msgpack extention by default. But if it isn't installed on the system it will automatically revert to using JSON.

If you wish to force a specific serializer type you may do so like this:

```php
$db = new Connection;
echo $db->getSerializer(); // will echo 'MSGPACK'
$db->setSerializer(Messages::SERIALIZER_JSON);
echo $db->getSerializer(); // will echo 'JSON'
// do something with $db Connection Object.
```

Examples
========

You can find more information by reading the API in the wiki. 

Here are a few basic usages.

Example 1:

```php
$db = new Connection;
//you can set $db->timeout = 0.5; if you wish
$db->open('localhost:8184','tinkergraph',null,null);
$db->script = 'g.v(2)';
$result = $db->runScript();
//do something with result
$db->close();
```

Example 2 (with bindings):

```php
$db = new Connection;
$db->open('localhost:8184','tinkergraph','username','password');

$db->script = 'g.v(CUSTO_BINDING)';
$db->bindValue('CUSTO_BINDING',2);
$result = $db->runScript();
//do something with result
$db->close();
```

Example 3 (sessionless):

```php
$db = new Connection;
$db->open('localhost:8184');
$db->script = 'g.v(2).map()';
$db->graph = 'tinkergraph'; //need to provide graph
$result = $db->runScript(false);
//do something with result
$b->close();
```

Example 4 (transaction):

```php
$db = new Connection;
$db->open('localhost:8184','neo4jsample',null,null);
  	
$db->transactionStart();

$db->script = 'g.addVertex([name:"michael"])';
$result = $db->runScript();
$db->script = 'g.addVertex([name:"john"])';
$result = $db->runScript();

$db->transactionStop(true);//accept commit of changes. set to false if you wish to cancel changes
$db->close();
```

Unit testing
============

If your test rexster server uses credentials for loging in you will need to run the following to set up proper credentials for tests:

```bash
DBUSER=<username> DBPASS=<password> phpunit src/tests/
```

Using env variables allows us to pass these arguments to a CI environment if needed.
