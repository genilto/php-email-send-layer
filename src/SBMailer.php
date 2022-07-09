<?php

class SBMailer {

    /**
     * Line break
     */
    private const LB = "\n";
    /**
     * Test Environment control
     */
    private $isTestEnv = false;
    private $testAddress = null;
    private $testAddressName = '';
    private $bodyToAppend = '';

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
    private $tag = '';

    // To validate duplicates
    private $allRecipients = array(
        "reply_to" => array(),
        "to" => array(),
        "cc" => array(),
        "bcc" => array()
    );

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
        $this->testAddress = SBMailerUtils::cleanAddress($testAddress);
        $this->testAddressName = SBMailerUtils::cleanName($testAddressName);
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
     *          'env' => getenv('ENV'), // 'prod' or 'test'
     *          'test_address' => getenv('TEST_ADDRESS'), // Required when env == 'test'
     *          'test_address_name' => getenv('TEST_ADDRESS_NAME'),
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
    /**
     * Add an address to the list
     * Do not add again in case it already exists
     */
    private function addAnAddress ($kind, $address, $name = '') {
        $fixedAddress = SBMailerUtils::cleanAddress($address);
        $fixedName = SBMailerUtils::cleanName($name);
        $key = strtolower($fixedAddress);
        if (array_key_exists($key, $this->allRecipients[$kind])) {
            return false;
        }
        $this->allRecipients[$kind][$key] = array("address" => $fixedAddress, "name" => $fixedName);
        return true;
    }
    public function addReplyTo($address, $name = '') {
        return $this->addAnAddress ("reply_to", $address, $name);
    }
    public function addAddress ($address, $name = '') {
        return $this->addAnAddress ("to", $address, $name);
    }
    public function addCC($address, $name = '') {
        return $this->addAnAddress ("cc", $address, $name);
    }
    public function addBcc($address, $name = '') {
        return $this->addAnAddress ("bcc", $address, $name);
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
    public function setTag($tag) {
        $this->tag = SBMailerUtils::cleanName($tag);
    }
    private function appendExtraBody ($content, $addLineBreak = true) {
        $this->bodyToAppend .= $content;
        if ($addLineBreak) {
            $this->bodyToAppend .= self::LB;
        }
    }
    private function appendAddressesToExtraBody ($address, $name, $duplicated) {
        if ($this->isTestEnv) {
            $this->appendExtraBody(" - ", false);
            if (!empty($name)) {
                $this->appendExtraBody($name . " ", false);
            }
            $this->appendExtraBody("[ " . $address . " ]", false);
            if ($duplicated) {
                $this->appendExtraBody(" - DUPLICATED", false);
            }
            $this->appendExtraBody("");
        }
    }
    private function initializeTestEnv () {
        if ($this->isTestEnv) {
            $this->mailAdapter->addAddress($this->testAddress, $this->testAddressName);
            $this->appendExtraBody(self::LB . self::LB);
            
            $this->appendExtraBody("----------------------------------------------------------------------------");
            $tag = empty($this->tag) ? "" : " TAG: " . $this->tag;
            $this->appendExtraBody("Sent from a TEST Environment." . $tag);
            $this->appendExtraBody("----------------------------------------------------------------------------");

            $testEmail = $this->testAddress;
            if (!empty($this->testAddressName)) {
                $testEmail = $this->testAddressName . "( " . $this->testAddress . " )";
            }
            $this->appendExtraBody("All messages are being redirected to: " . $testEmail);
            $this->appendExtraBody("Below are the recipients who would receive this message:");
        }
    }
    /**
     * Check if the address exists
     * 
     * @param string $address Address to check
     * @param array $listKeysToCheck keys of recipient list to check
     * 
     * @return bool if the address exists in some list
     */
    private function addressExistsInList ($address, $listKeysToCheck) {
        if (count($listKeysToCheck) > 0) {
            foreach ($listKeysToCheck as $listKey) {
                $keyAddress = strtolower($address);
                if (array_key_exists($keyAddress, $this->allRecipients[$listKey])) {
                    return true;
                }
            }
        }
        return false;
    }
    private function addAddressListToAdapter($listKey, $method, $listKeysToCheckDuplicates = array(), $ignoreTest = false) {
        if (!empty($this->allRecipients[$listKey])) {
            if ($this->isTestEnv) $this->appendExtraBody(self::LB . strtoupper($listKey) . ":");
            foreach ($this->allRecipients[$listKey] as $email) {
                $duplicated = $this->addressExistsInList ($email["address"], $listKeysToCheckDuplicates);
                
                if ((!$this->isTestEnv || $ignoreTest) && !$duplicated) {
                    $this->mailAdapter->$method($email["address"], $email["name"]);
                }

                $this->appendAddressesToExtraBody ($email["address"], $email["name"], $duplicated);
            }
        }
    }
    private function handleRecipients () {
        $this->initializeTestEnv();
        $this->addAddressListToAdapter("reply_to", "addReplyTo", array(), true);
        $this->addAddressListToAdapter("to", "addAddress") ;
        $this->addAddressListToAdapter("cc", "addCC", array("to"));
        $this->addAddressListToAdapter("bcc", "addBcc", array("to", "cc")) ;
    }
    /**
     * Sets Email body to the adapter
     */
    private function handleBody () {
        $addText = true;

        if (!empty($this->Body)) {
            if ($this->isHTMLMessage) {
                if (!empty($this->bodyToAppend)) {
                    $this->bodyToAppend = "<pre>" . $this->bodyToAppend . "</pre>";
                }
                $this->mailAdapter->setHtmlBody($this->Body . $this->bodyToAppend);
            } else {
                $this->mailAdapter->setTextBody($this->Body . $this->bodyToAppend);
                $addText= false;
            }
        }

        if ($addText) {
            if (!empty($this->AltBody)) {
                $this->mailAdapter->setTextBody($this->AltBody . $this->bodyToAppend);
            }
        }
    }
    public function send () {
        $this->handleRecipients();
        $this->mailAdapter->setSubject( $this->Subject );
        $this->handleBody();

        if (!empty($this->tag)) {
            $this->mailAdapter->setTag($this->tag);
        }

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
