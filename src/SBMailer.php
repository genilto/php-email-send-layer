<?php

class SBMailer {

    /**
     * Test Environment control
     */
    private $isTestEnv = false;
    private $testAddress = null;
    private $testAddressName = '';

    /**
     * Keep straight compatibility to PHPMailer
     * When migrating from PHPMailer to SBMailer, We can just change the 
     * imports and the instance of the object, everything else must work
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
    private $isHTMLMessage = true;

    // To validate duplicates
    private $replyToList = [];
    private $allRecipients = [];

    public function __construct ($mailAdapter, $enableExcetions = false) {
        $this->mailAdapter = $mailAdapter;
        $this->enableExcetions = $enableExcetions;
    }

    /**
     * Defines the env as Test
     * testAddress must be set to redirect all messages to it
     * 
     * @param $isTestEnv
     */
    public function isTestEnv ($isTestEnv = true) {
        $this->isTestEnv = $isTestEnv;
    }
    /**
     * When isTestEnv = true
     * testAddress is required and can be set here
     * 
     * @param $isTestEnv
     */
    public function setTestAddress ($testAddress, $testAddressName = '') {
        $this->testAddress = $testAddress;
        $this->testAddressName = $testAddressName;
    }

    /**
     * Creates the default instance of SBMailer
     * adding the default adapter as configured 
     * in SBMAILER constant
     * 
     * Example: 
     * <pre>
     * define('SBMAILER', array(
     *      'default' => 'postmark',
     *      'params' => array (
     *          'postmark' => array (
     *              'api_key' => getenv('POSTMARK_API_KEY')
     *          ),
     *          'phpmailer' => array ( // Using SMTP function
     *              'smtp_server'   => getenv('MAIL_SMTP_SERVER'),
     *              'smtp_port'     => getenv('MAIL_SMTP_PORT'),
     *              'smtp_user'     => getenv('MAIL_SMTP_USER'),
     *              'smtp_password' => getenv('MAIL_SMTP_PASSWORD')
     *          ),
     *      )
     * ));
     * </pre>
     * 
     * @throws \Exception if SBMAILER contant or configurations not defined
     */
    public static function createDefault ($enableExcetions = false) {
        if (!defined('SBMAILER') || empty(SBMAILER['default'])) {
            throw new \Exception('Default configurations for SBMailer not defined in SBMAILER constant.');
        }
        $adapterName = SBMAILER['default'];
        $adapterParams = array();
        if (!empty(SBMAILER['params']) && !empty(SBMAILER['params'][$adapterName])) {
            $adapterParams = SBMAILER['params'][$adapterName];
        }
        $mailer = self::includeAndCreateByName($adapterName, $adapterParams, $enableExcetions);

        // Validate the test environment
        if (!empty(SBMAILER['env']) && strtolower(SBMAILER['env']) == 'test') {
            $mailer->isTestEnv();
            if (empty(SBMAILER['test_address'])) {
                throw new \Exception('"test_address" not found for test environment in SBMAILER constant.');
            }
            $testAddressName = !empty(SBMAILER['test_address_name']) ? SBMAILER['test_address_name'] : null;
            $mailer->setTestAddress(SBMAILER['test_address'], $testAddressName);
        }
        return $mailer;
    }
    /**
     * Creates a instance of SBMailer by Adapter Name
     * The adapter must be previously loaded
     * 
     * @throws \Exception if SBMailer Adapter not found
     */
    public static function createByName ($adapterName, $adapterParams, $enableExcetions = false) {
        $adapterConfiguration = SBMailerUtils::getAdapter($adapterName);
        if (!$adapterConfiguration) {
            throw new \Exception("SBMailer Adapter $adapterName not found!");
        }
        $mailAdapterClass = new ReflectionClass($adapterConfiguration['className']);
        $mailAdapter = $mailAdapterClass->newInstanceArgs( array ( $adapterParams ) );
        $mailer = new SBMailer( $mailAdapter, $enableExcetions );
        return $mailer;
    }
    /**
     * Creates a instance of SBMailer by Adapter Name
     * Include the adapter file if needed
     * 
     * @throws \Exception if SBMailer Adapter not found
     */
    public static function includeAndCreateByName ($adapterName, $adapterParams, $enableExcetions = false) {
        if (!SBMailerUtils::existsAdapter($adapterName)) {
            @include_once ( __DIR__ . "/adapters/$adapterName/$adapterName.php" );
        }
        return self::createByName($adapterName, $adapterParams, $enableExcetions);
    }
    public function getMailerName () {
        return $this->mailAdapter->getMailerName();
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
        try {
            return $this->mailAdapter->addAttachment(
                    $path,
                    $name
                );
        } catch (Exception $e) {
            $this->ErrorInfo = $e->getMessage();
            return false;
        }
    }
    public function setSubject($subject) {
        $this->Subject = $subject;
    }
    /**
     * Sets message type to HTML or plain.
     *
     * @param bool $isHtml True for HTML mode
     */
    public function isHTML($isHtml = true) {
        $this->isHTMLMessage = $isHtml;
    }
    public function setBody($body) {
        $this->Body = $body;
    }
    public function setAltBody($altBody) {
        $this->AltBody = $altBody;
    }
    public function setTag($tagName) {
        $this->mailAdapter->setTag($tagName);
    }
    /**
     * Adjust for PHPMailer compatibility
     */
    private function handleCompatibility () {
        $this->mailAdapter->setSubject( $this->Subject );
        
        if (!empty($this->AltBody)) {
            $this->mailAdapter->setTextBody($this->AltBody);
        }

        if (!empty($this->Body)) {
            if ($this->isHTMLMessage) {
                $this->mailAdapter->setHtmlBody($this->Body);
            } else {
                $this->mailAdapter->setTextBody($this->Body);
            }
        }
    }
    public function send () {
        $this->handleCompatibility();
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
