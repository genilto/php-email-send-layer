<?php

class SBMailer implements iSBMailerAdapter {

    /**
     * Keep straight compatibility to PHPMailer
     * When migrating from PHPMailer to SBMailer, We can just change the 
     * imports and the instance of the object, everithing else would work
     * like a charm
     */
    public $ErrorInfo = '';

    /**
     * The Subject of the message.
     *
     * @var string
     */
    public $Subject = '';

    /**
     * An HTML or plain text message body.
     * If HTML then call isHTML(true).
     *
     * @var string
     */
    public $Body = '';

    /**
     * The plain-text message body.
     * This body can be read by mail clients that do not have HTML email
     * capability such as mutt & Eudora.
     * Clients that can read HTML will view the normal Body.
     *
     * @var string
     */
    public $AltBody = '';

    private $mailAdapter;
    private $enableExcetions;

    // To validate duplicates
    private $replyToList = [];
    private $allRecipients = [];

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
        $this->mailAdapter->setFrom(
            SBMailerUtils::cleanAddress($address), 
            SBMailerUtils::cleanName($name));
    }
    private function hasDuplicates (&$list, $kind, $address) {
        // Validate if it is already added
        $a = strtolower($address);
        if (array_key_exists($a, $list)) {
            return true;
        }
        $list[$a] = array("kind" => $kind, "address" => $address);
        return false;
    }
    public function addReplyTo($address, $name = '') {
        $fixedAddress = SBMailerUtils::cleanAddress($address);
        if ($this->hasDuplicates ($this->replyToList, "replyTo", $fixedAddress)) {
            return false;
        }
        return $this->mailAdapter->addReplyTo($fixedAddress, SBMailerUtils::cleanName($name));
    }
    public function addAddress ($address, $name = '') {
        $fixedAddress = SBMailerUtils::cleanAddress($address);
        if ($this->hasDuplicates ($this->allRecipients, "to", $fixedAddress)) {
            return false;
        }
        return $this->mailAdapter->addAddress($fixedAddress, SBMailerUtils::cleanName($name));
    }
    public function addCC($address, $name = '') {
        $fixedAddress = SBMailerUtils::cleanAddress($address);
        if ($this->hasDuplicates ($this->allRecipients, "cc", $fixedAddress)) {
            return false;
        }
        return $this->mailAdapter->addCC($fixedAddress, SBMailerUtils::cleanName($name));
    }
    public function addBcc($address, $name = '') {
        $fixedAddress = SBMailerUtils::cleanAddress($address);
        if ($this->hasDuplicates ($this->allRecipients, "bcc", $fixedAddress)) {
            return false;
        }
        return $this->mailAdapter->addBcc($fixedAddress, SBMailerUtils::cleanName($name));
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
    public function isHTML($isHtml = true) {
        $this->mailAdapter->isHTML($isHtml);
    }
    public function setBody($body) {
        $this->mailAdapter->setBody($body);
    }
    public function setAltBody($altBody) {
        $this->mailAdapter->setAltBody($altBody);
    }
    /**
     * Adjust for PHPMailer compatibility
     */
    private function adjustCompatibility () {
        if (!empty($this->Subject)) {
            $this->setSubject( $this->Subject );
        }
        if (!empty($this->Body)) {
            $this->setBody( $this->Body );
        }
        if (!empty($this->AltBody)) {
            $this->setAltBody( $this->AltBody );
        }
    }

    public function send () {
        
        $this->adjustCompatibility();

        try {
            $this->mailAdapter->send();
            return true;
        } catch (\Exception $e) {
            $this->ErrorInfo = $e->getMessage();
            if (empty($this->ErrorInfo)) {
                $this->ErrorInfo = "Email was not sent! No details found!";
            }
            if ($this->enableExcetions) {
                throw new \Exception( $this->ErrorInfo );
            }
        }
        return false;
    }
    public function getErrorInfo() {
        return $this->ErrorInfo;
    }
}
