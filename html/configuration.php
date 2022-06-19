<?php

// Define the function which instantiate the default Email Adapter that would be
// used when sending new emails
if (!function_exists('DEFAULT_EMAIL_ADAPTER')) {
    /**
     * For PHPMailer using SMTP configuration you must define the adapter like below
     * Using SMTP
     */
    // function DEFAULT_EMAIL_ADAPTER () {
    //     return new SBPHPMailerAdapter(
    //         getenv('MAIL_SMTP_SERVER'),
    //         getenv('MAIL_SMTP_PORT'),
    //         getenv('MAIL_SMTP_USER'),
    //         getenv('MAIL_SMTP_PASSWORD')
    //     );
    // };

    /**
     * For PHPMailer using mail function you must define the adapter like below
     */
    // function DEFAULT_EMAIL_ADAPTER () {
    //     return new SBPHPMailerAdapter();
    // };

    /**
     * For Sendgrid you must define the adapter like below
     */
    function DEFAULT_EMAIL_ADAPTER () {
        return new SBSendgridAdapter(
            getenv('MAIL_API_KEY')
        );
    };
}