# SBMailer

A small layer created to abstract the ways to send emails with php.

## Email Adapters

This project contains a small abstraction for email sending process. It uses adapters to allow the implementation of any email sending provider. Currently, there are some adapters implemented, as below.

The first step is require the sbmailer package to your project:

```shell
composer require genilto/sbmailer
```

After that, you must install the corresponding SDK according to the adapter you are going to use, and then configure as default adapter. 

To do that, you must define a constant named SBMAILER and set the default parameters.

See the examples bellow.

### Postmark

Send emails using the Postmark API library.

To use this adapter you must require the following dependencies in your project:

```shell
composer require wildbit/postmark-php
```

And then configure the default adapter like bellow:

```php
define('SBMAILER', array(
    'default' => 'postmark',
    'params' => array (
        'postmark' => array (
            'api_key' => getenv('POSTMARK_API_KEY')
        )
    )
));
```

### PHPMailer

Send emails using the PHPMailer library. This particular adapter allow you to not inform params, so PHPMailer will use php mail() function to send emails.

To use this adapter you must require the following dependencies in your project:

```shell
composer require phpmailer/phpmailer
```

And then configure the default adapter like bellow:

#### Send Using mail() function

```php
define('SBMAILER', array(
    'default' => 'phpmailer'
));
```

#### Send Using SMTP

```php
define('SBMAILER', array(
    'default' => 'phpmailer',
    'params' => array (
        'phpmailer' => array ( // Using SMTP function
            'smtp_server'   => getenv('MAIL_SMTP_SERVER'),
            'smtp_port'     => getenv('MAIL_SMTP_PORT'),
            'smtp_user'     => getenv('MAIL_SMTP_USER'),
            'smtp_password' => getenv('MAIL_SMTP_PASSWORD')
        ),
    )
));
```

### Sendgrid

Send emails using the Sendgrid API library.

To use this adapter you must require the following dependencies in your project:

```shell
composer require sendgrid/sendgrid
```

And then configure the default adapter like bellow:

```php
define('SBMAILER', array(
    'default' => 'sendgrid',
    'params' => array (
        'sendgrid' => array (
            'api_key' => getenv('SENDGRID_API_KEY')
        ),
    )
));
```

### Mailersend

Send emails using the Mailersend API library.

To use this adapter you must require the following dependencies in your project:

```shell
composer require php-http/guzzle7-adapter nyholm/psr7
composer require mailersend/mailersend
```

And then configure the default adapter like bellow:

```php
define('SBMAILER', array(
    'default' => 'mailersend',
    'params' => array (
        'mailersend' => array (
            'api_key' => getenv('MAILERSEND_API_KEY')
        )
    )
));
```

### Sendinblue

Send emails using the Sendinblue API library.

To use this adapter you must require the following dependencies in your project:

```shell
composer require sendinblue/api-v3-sdk
```

And then configure the default adapter like bellow:

```php
define('SBMAILER', array(
    'default' => 'sendinblue',
    'params' => array (
        'sendinblue' => array (
            'api_key' => getenv('SENDINBLUE_API_KEY')
        )
    )
));
```

### Microsoft Graph

Send emails using the Microsoft Graph API library.

To use this adapter you must require the following dependencies in your project:

```shell
composer require microsoft/microsoft-graph
```

And then configure the default adapter like bellow:

```php
define('SBMAILER', array(
    'default' => 'microsoft-graph',
    'params' => array (
        'microsoft-graph' => array (
            'tenant_id' => getenv('MS_GRAPH_TENTANT_ID'),
            'client_id' => getenv('MS_GRAPH_CLIENT_ID'),
            'client_secret' => getenv('MS_GRAPH_CLIENT_SECRET'),
            'save_to_sent_items' => true,
        )
    )
));
```

## Test environment

The library allow you to set the environment as test. That means that you can define a default email address where the emails will be redirected when in test environment.

Below is an example of an entire configuration, with multiple email providers and defining postmark as the default email. Also, defining the environment as test, with a default email and name where all the test emails will be delivered.

```php
define('SBMAILER', array (
    'default' => getenv('DEFAULT_ADAPTER'),
    //'log_location' => '',
    'params'  => array (
        'postmark' => array (
            'api_key' => getenv('POSTMARK_API_KEY')
        ),
        'sendgrid' => array (
            'api_key' => getenv('SENDGRID_API_KEY')
        ),
        'mailersend' => array (
            'api_key' => getenv('MAILERSEND_API_KEY')
        ),
        'sendinblue' => array (
            'api_key' => getenv('SENDINBLUE_API_KEY')
        ),
        // 'phpmailer' => array (), // Using mail function
        'phpmailer' => array ( // Using SMTP function
            'smtp_server'   => getenv('MAIL_SMTP_SERVER'),
            'smtp_port'     => getenv('MAIL_SMTP_PORT'),
            'smtp_user'     => getenv('MAIL_SMTP_USER'),
            'smtp_password' => getenv('MAIL_SMTP_PASSWORD')
        ),
        'microsoft-graph' => array (
            'tenant_id' => getenv('MS_GRAPH_TENTANT_ID'),
            'client_id' => getenv('MS_GRAPH_CLIENT_ID'),
            'client_secret' => getenv('MS_GRAPH_CLIENT_SECRET'),
            'save_to_sent_items' => true,
        ),
    ),
    'log_level' => getenv('LOG_LEVEL'), // 0 - Off | 1 - Error only | 2 - Full
    'env' => getenv('ENV'), // 'prod' or 'test'
    'test_address' => getenv('TEST_ADDRESS'), // Required when env == 'test'
    'test_address_name' => getenv('TEST_ADDRESS_NAME'),
));
```

Setting 'env' as 'test' it will be required that you set test_address as well.
All messages will be redirected to the test_address and a message with all recipients will be appended to the message body.

## How to implement new Adapters

You can implement new adapters just implementing the interface iSBMailerAdapter giving it a unique name and class name. See the existing adapters to a better undertanding.

## Setup before run the examples

Before run the examples, you need to install the dependencies of each adapter you intend to use.

This project install the dependencies using composer. Make sure you have composer installed.

Install all the dependencies as described above.

In developer environments, just run:

```shell
composer install
```

All the dependencies for all the adapters will be installed.

## How to run the example

You can run directly using some local server, as apache and php, just pointing the server to html folder of the project.

Or you can run it using docker.
There is a docker-composer.yml in the project that creates a container, pointing the server to the html folder of the project.

Before run the composer, duplicate the file env.sample and rename to .env. After that configure the environment variables there with the correct values.

To create the container just use the command below in your terminal:

```shell
docker-compose up -d
```

To test the example, just go to <http://localhost:85> in your browser. If you have configured the correct informations, you can use the form to send a test email.

## Manually Install and use in production

Download the latest release file (zip) from github releases. Unzip in your server and import sbmailer/SBMailer.php in your code. It would require all it needs to run.

## For production

For production, you must run:

```shell
composer install --no-dev
```

Just basic dependencies will be installed, and you must require the desired dependency as described above.

You must remove the html folder from the final version that goes into production case it is there. It contains a form to test email sendings, and it is not good let it it public in your server.

By the way, the suggestion is keeping this library out of public directory, and just requiring sbmailer/SBMailer.php in your code.

## Create new Releases

To generate a new release:

First you need to install composer dependencies of desired adapters, as mentioned above, and then, generate the package:

```shell
composer archive --format=zip --file=dist/sbmailer
```
