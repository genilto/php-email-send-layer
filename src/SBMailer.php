<?php
require_once ( __DIR__ . '/SBMailerUtils.php' );
require_once ( __DIR__ . '/iSBMailerAdapter.php' );
require_once ( __DIR__ . '/SBSendgridAdapter.php' );
require_once ( __DIR__ . '/SBPHPMailerAdapter.php' );

class SBMailer implements iSBMailerAdapter {

    private $mailAdapter;
    private $enableExcetions;

    public $ErrorInfo;

    public function __construct ($mailAdapter, $enableExcetions = false) {
        $this->mailAdapter = $mailAdapter;
        $this->enableExcetions = $enableExcetions;
    }

    /**
     * Creates the default instance of SBMailer
     * adding the default adapter as configured 
     * in DEFAULT_EMAIL_ADAPTER function
     * 
     * @throws \Exception if DEFAULT_EMAIL_ADAPTER is not defined
     */
    public static function createDefault ($enableExcetions = false) {
        if (function_exists('DEFAULT_EMAIL_ADAPTER')) {
            $mailer = new SBMailer( DEFAULT_EMAIL_ADAPTER(), $enableExcetions );
            return $mailer;
        }
        throw new \Exception('DEFAULT_EMAIL_ADAPTER not defined.');
    }

    public function setFrom($address, $name = '') {
        $this->mailAdapter->setFrom($address, $name);
    }
    public function addReplyTo($address, $name = '') {
        $this->mailAdapter->addReplyTo($address, $name);
    }
    public function addAddress ($address, $name = '') {
        $this->mailAdapter->addAddress($address, $name);
    }
    public function addCC($address, $name = '') {
        $this->mailAdapter->addCC($address, $name);
    }
    public function addBcc($address, $name = '') {
        $this->mailAdapter->addBcc($address, $name);
    }
    public function addAttachment($path, $name = '') {
        $this->mailAdapter->addAttachment(
                $path,
                $name
            );
    }
    public function setSubject($subject) {
        $this->mailAdapter->setSubject( $subject );
    }
    public function setBody($body) {
        $this->mailAdapter->setBody($body);
    }
    public function setAltBody($altBody) {
        $this->mailAdapter->setAltBody($altBody);
    }
    public function send () {
        try {
            $this->mailAdapter->send();
            return true;
        } catch (\Exception $e) {
            $this->ErrorInfo = "Email was NOT sent. Error: " . $e->getMessage();
            if ($this->enableExcetions) {
                throw new \Exception( $this->ErrorInfo );
            }
        }
        return false;
    }
}
