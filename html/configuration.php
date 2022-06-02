<?php
/**
 * For PHPMailer you must define the adapter like below
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
 * For Sendgrid you must define the adapter like below
 */
function DEFAULT_EMAIL_ADAPTER () {
    return new SBSendgridAdapter(
        getenv('MAIL_API_KEY')
    );
};
