# php-email-send-layer
A small layer created to abstract the ways to send emails with php.

# Mail Adapters

This project contains a small abstraction for email sending process. It uses adapters to allow the implementation of any email sending provider. Currently, there are some adapters implemented, as below.

To configure a default adapter, you can define a function named DEFAULT_EMAIL_ADAPTER and return the adapter. As the examples.

## PHPMailer

Send emails throw the PHPMailer library. To use this adapter you can just declare the DEFAULT_EMAIL_ADAPTER as below, informing the required arguments.

```
function DEFAULT_EMAIL_ADAPTER () {
    return new SBPHPMailerAdapter(
        getenv('MAIL_SMTP_SERVER'),
        getenv('MAIL_SMTP_PORT'),
        getenv('MAIL_SMTP_USER'),
        getenv('MAIL_SMTP_PASSWORD')
    );
};
```

## Sendgrid

Send emails throw the Sendgrid API library. To use this adapter you can just declare the DEFAULT_EMAIL_ADAPTER as below informing the required arguments.

```
function DEFAULT_EMAIL_ADAPTER () {
    return new SBSendgridAdapter(
        getenv('MAIL_API_KEY')
    );
};
```

# How to implement new Adapters

We can implement new providers for email sending, just implementing the interface sbmailer/iSBMailerAdapter, instantiate it and pass as argument to SBMailer class, or defining it in your DEFAULT_EMAIL_ADAPTER function. 

# Setup before run the examples

Before run the examples, you need to install its dependencies. 
This project install the dependencies using composer. 

```
composer install
```

The command above will install PHPMailer library and Sendgrid library.

After that, you need to configure the informations in the example in index.php file.
Just take a look at the code and change de from, to and other informations before run.

Don't forget to 

# How to run the example

You can run directly using some local server, as apache and php, or you can run it using docker.

There is a docker-composer.yml in the project that creates a container, pointing the server to the html folder of the project.

Before run the composer, duplicate the file env.example and rename to .env. After that configure the environment variables there with the correct values.

To create the container just use the command below in your terminal:

```
docker-compose up -d
```

To test the example, just go to http://localhost:85 in your browser. If you have configured the correct informations, the recipients filled there, will receive the email correctly.
