<?php

use Psr\Log\LoggerInterface;
use Analog\Logger;

class SBMailer {

    /**
     * The adapter
     * 
     * @var iSBMailerAdapter
     */
    private $mailAdapter;

    /**
     * The Tag. Usefull to classify the messages
     * 
     * @var string
     */
    private $tag = '';

    /**
     * Logging adapter
     * 
     * @var LoggerInterface
     */
    private $logger = null;
    
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
     * 
     * @var string
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
     * When the body is in HTML or not
     * 
     * @var boolean
     */
    private $isHTMLMessage = true;

    /**
     * The plain-text message body.
     * This body can be read by mail clients that do not have HTML email
     * capability such as mutt & Eudora.
     * Clients that can read HTML will view the normal Body.
     *
     * @var string
     */
    public $AltBody = '';

    /**
     * From email
     * 
     * @var array
     */
    private $from = null;

    /**
     * Keeps all the recipients by type
     * Uses the lists to control the duplicates
     * 
     * @var array
     */
    private $allRecipients = array(
        "reply_to" => array(),
        "to" => array(),
        "cc" => array(),
        "bcc" => array()
    );

    /**
     * Instantiate the class
     * 
     * @param iSBMailerAdapter $mailAdapter
     * @param LoggerInterface $logger (optional)
     */
    public function __construct ($mailAdapter, $logger = null) {
        $this->mailAdapter = $mailAdapter;
        $this->logger = $logger;
    }
    
    /**
     * Defines the env as Test or Not
     * When true, testAddress must be set to redirect all messages to it
     * 
     * @param boolean $isTestEnv
     */
    public function isTestEnv ($isTestEnv = true) {
        $this->isTestEnv = $isTestEnv;
    }

    /**
     * When isTestEnv = true
     * testAddress is required and can be set here
     * 
     * @param string $testAddress Email address
     * @param string $testAddressName (optional) Name
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
     *          'debug_level' => 1, // 0 - Off | 1 - Error only | 2 - Full
     *          'env' => getenv('ENV'), // 'prod' or 'test'
     *          'test_address' => getenv('TEST_ADDRESS'), // Required when env == 'test'
     *          'test_address_name' => getenv('TEST_ADDRESS_NAME'),
     *      )
     * ));
     * </pre>
     * 
     * @throws \Exception if SBMAILER contant or configurations not defined
     */
    public static function createDefault () {
        if (!defined('SBMAILER') || empty(SBMAILER['default'])) {
            throw new \Exception('Default configurations for SBMailer not defined in SBMAILER constant.');
        }
        $adapterName = SBMAILER['default'];
        $adapterParams = array();
        if (!empty(SBMAILER['params']) && !empty(SBMAILER['params'][$adapterName])) {
            $adapterParams = SBMAILER['params'][$adapterName];
        }
        $mailer = self::includeAndCreateByName($adapterName, $adapterParams);

        // Validate the test environment
        if (!empty(SBMAILER['env']) && strtolower(SBMAILER['env']) == 'test') {
            $mailer->isTestEnv();
            if (empty(SBMAILER['test_address'])) {
                throw new \Exception('"test_address" not found for test environment in SBMAILER default configuration.');
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
    public static function createByName ($adapterName, $adapterParams) {
        $adapterConfiguration = SBMailerUtils::getAdapter($adapterName);
        if (!$adapterConfiguration) {
            throw new \Exception("SBMailer Adapter $adapterName not found!");
        }
        
        // Instatiate the adapter
        $mailAdapterClass = new ReflectionClass($adapterConfiguration['className']);
        $mailAdapter = $mailAdapterClass->newInstanceArgs( array ( $adapterParams ) );

        // Create Default Logger
        $currentDate = date("Y-m-d");
        $logger = new Logger();
        $logger->handler (__DIR__ . "/../logs/$currentDate-$adapterName.log");

        // Create the SBMailer instance
        $mailer = new SBMailer( $mailAdapter, $logger );
        return $mailer;
    }

    /**
     * Creates a instance of SBMailer by Adapter Name
     * Include the internal adapter file if needed
     * 
     * @throws \Exception if SBMailer Adapter not found
     */
    private static function includeAndCreateByName ($adapterName, $adapterParams) {
        if (!SBMailerUtils::existsAdapter($adapterName)) {
            @include_once ( __DIR__ . "/adapters/$adapterName/$adapterName.php" );
        }
        return self::createByName($adapterName, $adapterParams);
    }

    /**
     * Gets the instantiated mailer adapter name
     * 
     * @return string
     */
    public function getMailerName () {
        return $this->mailAdapter->getMailerName();
    }

    /**
     * Sets the from field of the email
     *
     * @param string $address
     * @param string $name (optional)
     */
    public function setFrom($address, $name = '') {
        $this->from = array(
            "address" => SBMailerUtils::cleanAddress($address), 
            "name" => SBMailerUtils::cleanName($name)
        );
    }

    /**
     * Add an address to the list
     * Do not add again in case it already exists
     * 
     * @param string $kind (reply_to, to, cc, bcc)
     * @param string $address
     * @param string $name (optional)
     * 
     * @return boolean true when success, false when not added
     */
    private function addAnAddress ($kind, $address, $name = '') {
        $fixedAddress = SBMailerUtils::cleanAddress($address);
        if (empty($fixedAddress)) {
            return false;
        }
        $fixedName = SBMailerUtils::cleanName($name);
        $key = strtolower($fixedAddress);
        if (array_key_exists($key, $this->allRecipients[$kind])) {
            return false;
        }
        $this->allRecipients[$kind][$key] = array("address" => $fixedAddress, "name" => $fixedName);
        return true;
    }

    /**
     * Sets the Reply to field of the email
     *
     * @param string $address
     * @param string $name  (optional)
     * 
     * @return bool true on success, false if address already used or invalid in some way
     */
    public function addReplyTo($address, $name = '') {
        return $this->addAnAddress ("reply_to", $address, $name);
    }

    /**
     * Add recipient to the TO field of the email
     *
     * @param string $address
     * @param string $name (optional)
     * 
     * @return bool true on success, false if address already used or invalid in some way
     */
    public function addAddress ($address, $name = '') {
        return $this->addAnAddress ("to", $address, $name);
    }

    /**
     * Add recipient to the CC field of the email
     *
     * @param string $address
     * @param string $name (optional)
     * 
     * @return bool true on success, false if address already used or invalid in some way
     */
    public function addCC($address, $name = '') {
        return $this->addAnAddress ("cc", $address, $name);
    }

    /**
     * Add recipient to the BCC field of the email
     *
     * @param string $address
     * @param string $name (optional)
     * 
     * @return bool true on success, false if address already used or invalid in some way
     */
    public function addBcc($address, $name = '') {
        return $this->addAnAddress ("bcc", $address, $name);
    }

    /**
     * Add an attachment from a path on the filesystem.
     * Never use a user-supplied path to a file!
     * Returns false if the file could not be found or read.
     * Explicitly *does not* support passing URLs; It is not an HTTP client.
     * If you need to do that, fetch the resource yourself and pass it in via a local file
     *
     * @param string $path Path of the file in server filesystem
     * @param string $name (optional) Name to display the attachment in email
     * 
     * @throws Exception
     *
     * @return bool true when success, false when some error occurred and ErrorInfo will have details of the error
     */
    public function addAttachment($path, $name = '') {
        try {
            return $this->mailAdapter->addAttachment(
                    $path,
                    $name
                );
        } catch (Exception $e) {
            $this->ErrorInfo = $e->getMessage();
            $this->logError ('addAttachment', $this->ErrorInfo, array('exception' => $e));
            return false;
        }
    }

    /**
     * Sets the email subject
     *
     * @param string $subject
     */
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

    /**
     * Sets the email body
     *
     * @param string $body of the email
     */
    public function setBody($body) {
        $this->Body = $body;
    }

    /**
     * Sets the email alternative body
     * Displayed when email reader doenst support HTML
     *
     * @param string $body Text body
     */
    public function setAltBody($body) {
        $this->AltBody = $body;
    }

    /**
     * Sets a tag for better message classification
     *
     * @param string $tagName
     */
    public function setTag($tag) {
        $this->tag = SBMailerUtils::cleanName($tag);
    }

    /**
     * Append an extra content to the body of message
     * Used in test environment to idicates to whom the message
     * would be sent
     *
     * @param string $content The content to be appended to Body
     * @param boolean $addLineBreak (optional) default to add a line break at the end of content
     */
    private function appendExtraBody ($content, $addLineBreak = true) {
        $this->bodyToAppend .= $content;
        if ($addLineBreak) {
            $this->bodyToAppend .= "\n";
        }
    }

    /**
     * When in test environment, append all the recipients that would
     * receive the message to the body of the email
     *
     * @param string $address The email address
     * @param string $name Name
     * @param boolean $duplicated Indicates when the address is duplicated
     */
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

    /**
     * Initializes the Test Environment configuration body
     */
    private function initializeTestEnv () {
        if ($this->isTestEnv) {
            $this->mailAdapter->addAddress($this->testAddress, $this->testAddressName);
            $this->appendExtraBody("");
            $this->appendExtraBody("");
            $this->appendExtraBody("");
            
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

    /**
     * Add the email address to corresponding method on adapter
     * It checks if the address is duplicated and not add it twice
     * 
     * @param string $listKey The field to be added (reply_to, to, cc, bcc)
     * @param string $method The method to be called on adapter (eg. 'addAddress')
     * @param array $listKeysToCheckDuplicates Array with the lists to check for duplicated
     * @param boolean $ignoreTest (optional) When add even in test environment. Defaults to false
     */
    private function addAddressListToAdapter($listKey, $method, $listKeysToCheckDuplicates = array(), $ignoreTest = false) {
        if (!empty($this->allRecipients[$listKey])) {
            if ($this->isTestEnv) {
                $this->appendExtraBody("");
                $this->appendExtraBody( strtoupper($listKey) . ":");
            }
            foreach ($this->allRecipients[$listKey] as $email) {
                $duplicated = $this->addressExistsInList ($email["address"], $listKeysToCheckDuplicates);
                
                if ($duplicated) {
                    $this->logWarning("addAddressListToAdapter", "Duplicated Address", array("list" => $listKey, "email" => $email));
                }

                if ((!$this->isTestEnv || $ignoreTest) && !$duplicated) {
                    $this->mailAdapter->$method($email["address"], $email["name"]);
                }

                $this->appendAddressesToExtraBody ($email["address"], $email["name"], $duplicated);
            }
        }
    }

    /**
     * Handle the recipients. Check duplicates and add each to corresponding method 
     * in current adapter
     */
    private function handleRecipients () {
        $this->initializeTestEnv();
        $this->addAddressListToAdapter("reply_to", "addReplyTo", array(), true);
        $this->addAddressListToAdapter("to", "addAddress");
        $this->addAddressListToAdapter("cc", "addCC", array("to"));
        $this->addAddressListToAdapter("bcc", "addBcc", array("to", "cc"));
    }

    /**
     * Sets the Email body to the adapter
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

    /**
     * Log the message ocurred using the current Log Handler
     * 
     * @param string $method
     * @param string $where
     * @param string $errorMessage
     * @param array $context
     */
    private function log ($method, $where, $errorMessage, $context) {
        if ($this->logger === null) {
            return;
        }
        $error = $errorMessage;
        if (!empty($this->tag)) {
            $context["tag"] = $this->tag;
        }
        if (count($context) > 0) {
            $error .= ' | CONTEXT: {data}';
        }
        $this->logger->$method( "($where) $error", array("data" => $context) );
    }

    /**
     * Log the error ocurred using the current Log Handler
     * 
     * @param string $where
     * @param string $errorMessage
     * @param array $context
     */
    private function logError ($where, $errorMessage, array $context = array()) {
        $this->log("error", $where, $errorMessage, $context );
    }

    /**
     * Log the warning ocurred using the current Log Handler
     * 
     * @param string $where
     * @param string $errorMessage
     * @param array $context
     */
    private function logWarning ($where, $errorMessage, array $context = array()) {
        $this->log("warning", $where, $errorMessage, $context );
    }

    /**
     * Do a basic validation to the email created
     * before send it to adapter
     * 
     * @throws \Exception when validation fails
     */
    private function basicValidation () {
        
        // From Email must be set
        if ($this->from == null || empty($this->from['address'])) {
            throw new Exception("From address must be informed!");
        }

        // At least one TO recipient must be set
        if (empty($this->allRecipient) || empty($this->allRecipients["to"])) {
            throw new Exception("At least one TO recipient must be informed!");
        }
    }

    /**
     * Sends the email
     * 
     * @return bool false on error - See the ErrorInfo property or getErrorInfo() method for details of the error
     */
    public function send () {
        $this->handleRecipients();
        $this->mailAdapter->setSubject( $this->Subject );
        $this->handleBody();

        if (!empty($this->tag)) {
            $this->mailAdapter->setTag($this->tag);
        }

        try {
            // Do some basic validations before send
            $this->basicValidation();

            // Add the FROM to the adapter
            $this->mailAdapter->setFrom(
                $this->from['address'], 
                $this->from['name']
            );

            // Sends the email
            $this->mailAdapter->send();
            return true;
            
        } catch (\Exception $e) {
            $this->ErrorInfo = $e->getMessage();
            if (empty($this->ErrorInfo)) {
                $this->ErrorInfo = "Email was not sent! No details found!";
            }
            // Exception logging
            $this->logError('send', $this->ErrorInfo, array('exception' => $e) );
        }
        return false;
    }

    /**
     * Get the Last error ocurred
     * 
     * @return string The error
     */
    public function getErrorInfo() {
        return $this->ErrorInfo;
    }
}
