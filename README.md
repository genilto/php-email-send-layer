# SBMailer
A small layer created to abstract the ways to send emails with php.


# Email Adapters

This project contains a small abstraction for email sending process. It uses adapters to allow the implementation of any email sending provider. Currently, there are some adapters implemented, as below.

To configure a default adapter, you can define a constant named SBMAILER and set the default parameters. As the examples.


## PHPMailer

Send emails using the PHPMailer library. This particular adapter allow you to not inform params, so PHPMailer will use php mail() function to send emails.

Send Using mail() function

```php
define('SBMAILER', array(
    'default' => 'phpmailer'
));
```

Send Using SMTP

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


## Sendgrid

Send emails using the Sendgrid API library.

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

## Postmark

Send emails using the Postmark API library.

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


# How to implement new Adapters

You can implement new providers for email sending, just implementing the interface iSBMailerAdapter giving it a unique name and class name.


# Setup before run the examples

Before run the examples, you need to install the dependencies of each adapter you intend to use.

This project install the dependencies using composer. Make sure you have composer installed.

To do that, navigate to the directory of the desired adapter and run the command below in terminal

```
composer install
```

The command above will install the corresponding adapter dependencies.


# How to run the example

You can run directly using some local server, as apache and php, just pointing the server to html folder of the project.

Or you can run it using docker.
There is a docker-composer.yml in the project that creates a container, pointing the server to the html folder of the project.

Before run the composer, duplicate the file env.sample and rename to .env. After that configure the environment variables there with the correct values.

To create the container just use the command below in your terminal:

```
docker-compose up -d
```

To test the example, just go to http://localhost:85 in your browser. If you have configured the correct informations, you can use the form to send a test email.


# Manually Install and use in production

Download the latest release file (zip) from github releases. Unzip in your server and import sbmailer/SBMailer.php in your code. It would require all it needs to run.


# For production

You must remove the html folder from the final version that goes into production. It contains a form to test email sendings, and it is not good let it it public in your server.

By the way, the suggestion is keeping this library out of public directory, and just requiring sbmailer/SBMailer.php in your code.


# Create new Releases

To generate a new release:

First you need to install composer dependencies of desired adapters, as mentioned above, and then, generate the package:

```
composer archive --format=zip --file=dist/sbmailer
```
