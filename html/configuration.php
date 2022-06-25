<?php

// Define the function which instantiate the default Email Adapter that would be
// used when sending new emails
if (!function_exists('DEFAULT_EMAIL_ADAPTER')) {
    /**
     * For PHPMailer using mail function you must define the adapter like below
     */
    // function DEFAULT_EMAIL_ADAPTER () {
    //     sbmailer_load_adapter ('phpmailer');
    //     return new SBPHPMailerAdapter();
    // };

    /**
     * For PHPMailer using SMTP configuration you must define the adapter like below
     * Using SMTP
     */
    // function DEFAULT_EMAIL_ADAPTER () {
    //     sbmailer_load_adapter ('phpmailer');
    //     return new SBPHPMailerAdapter(
    //         getenv('MAIL_SMTP_SERVER'),
    //         getenv('MAIL_SMTP_PORT'),
    //         getenv('MAIL_SMTP_USER'),
    //         getenv('MAIL_SMTP_PASSWORD')
    //     );
    // };

    /**
     * For Sendgrid you must define the adapter like below
     */
    // function DEFAULT_EMAIL_ADAPTER () {
    //     sbmailer_load_adapter ('sendgrid');
    //     return new SBSendgridAdapter(
    //         getenv('SENDGRID_API_KEY')
    //     );
    // };

    /**
     * For Mailersend you must define the adapter like below
     */
    // function DEFAULT_EMAIL_ADAPTER () {
    //     sbmailer_load_adapter ('mailersend');
    //     return new SBMailersendAdapter(
    //         getenv('MAILERSEND_API_KEY')
    //     );
    // };

    /**
     * For Sendgrid you must define the adapter like below
     */
    function DEFAULT_EMAIL_ADAPTER () {
        sbmailer_load_adapter ('postmark');
        return new SBPostmarkAdapter(
            getenv('POSTMARK_API_KEY')
        );
    };
}
