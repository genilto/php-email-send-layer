<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class SBPHPMailerAdapter implements iSBMailerAdapter {

    private $mailer;
    
    /**
     * Create a PHPMailer Adapter
     *
     * @param string $apiKey
     */
    public function __construct ($smtpServer = '', $smtpPort = '', $smtpUser = '', $smtpPassword = '') {
        $this->mailer = new PHPMailer(true); // Enable Exceptions
        $this->mailer->isHTML(true); // Defaults to HTML Body

        // Server settings
        // $mailer->SMTPDebug = SMTP::DEBUG_SERVER;                        //Enable verbose debug output
        if (!empty($smtpServer)) {
            $this->mailer->isSMTP();                                    //Send using SMTP
            $this->mailer->Host       = $smtpServer;                    //Set the SMTP server to send through
            $this->mailer->SMTPAuth   = true;                           //Enable SMTP authentication
            $this->mailer->Username   = $smtpUser;                      //SMTP username
            $this->mailer->Password   = $smtpPassword;                  //SMTP password
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; //Enable implicit TLS encryption
            $this->mailer->Port       = $smtpPort;                      //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
        }
    }
    public function getMailerName () {
        return 'PHPMailer (' . $this->mailer->Mailer . ')';
    }
    public function setFrom($address, $name = '') {
        $this->mailer->setFrom($address, $name);
    }
    public function addReplyTo($address, $name = '') {
        return $this->mailer->addReplyTo($address, $name);
    }
    public function addAddress ($address, $name = '') {
        return $this->mailer->addAddress($address, $name);
    }
    public function addCC($address, $name = '') {
        return $this->mailer->addCc($address, $name);
    }
    public function addBcc($address, $name = '') {
        return $this->mailer->addBcc($address, $name);
    }
    public function addAttachment($path, $name = '') {
        return $this->mailer->addAttachment($path, $name);
    }
    public function setSubject($subject) {
        $this->mailer->Subject = $subject;
    }
    public function setHtmlBody($body) {
        $this->mailer->isHTML(true);
        $this->mailer->Body = $body;
    }
    public function setTextBody($body) {
        $this->mailer->AltBody = $body;
    }
    public function setTag($tagName) {}
    public function send () {
        try {
            return $this->mailer->send();
        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}