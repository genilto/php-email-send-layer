<?php

// Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Import the configurations
require_once ( __DIR__ . '/configuration.php' );
require_once ( __DIR__ . '/sbmailer/SBMailer.php' );

// Creates the default mailer instance as configurations
$mailer = SBMailer::createDefault();

// Set the From fields of email
$mailer->setFrom("emailfrom@domain.com", "From Name");
$mailer->addReplyTo("replyto@domain.com", "Reply To Name");

// Add recipients
$mailer->addAddress ("to@domain.com", "To Name");
$mailer->addCC ("cc@domain.com", "CC Name");
$mailer->addBcc("bcc@domain.com", "BCC Name");

// Add attachments
$mailer->addAttachment( __DIR__ . "/att/attachment.jpeg", "image.jpeg");

// Set the subject and the email body
// Always HTML body
$mailer->setSubject("Test E-mail at " . date("Y-m-d H:i:s"));
$mailer->setBody("HTML body <b>bold</b>");
//$mailer->setAltBody("Alternative Body when reader does not support HTML");

// Sends the email
try {
    $mailer->send ();
    echo "Email sent.";
} catch (Exception $e) {
    echo $e->getMessage();
}
