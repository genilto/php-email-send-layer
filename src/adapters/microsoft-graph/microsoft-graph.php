<?php

require_once ( __DIR__ . "/vendor/autoload.php");

use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\Attachment;
use Microsoft\Graph\Model\BodyType;
use Microsoft\Graph\Model\EmailAddress;
use Microsoft\Graph\Model\InternetMessageHeader;
use Microsoft\Graph\Model\ItemBody;
use Microsoft\Graph\Model\Message;
use Microsoft\Graph\Model\Recipient;

class SBMicrosoftGraphAdapter implements iSBMailerAdapter {

    private $params;
    private $email;
    
    /**
     * Create a Microsoft Graph Adapter
     *
     * @param string $accessToken
     */
    public function __construct ($params) {
        $this->params = $params;
        $this->email = new Message();
    }
    public function getMailerName () {
        return 'Microsoft Graph';
    }
    private function createRecipient ($address, $name) {
        $emailAddress = new EmailAddress();
        $emailAddress->setAddress($address);
        if (!empty($name)) {
            $emailAddress->setName(htmlentities($name));
        }
        $recipient = new Recipient();
        $recipient->setEmailAddress($emailAddress);
        return $recipient;
    }
    public function setFrom($address, $name = '') {
        $this->email->setFrom( $this->createRecipient($address, $name) );
        $this->email->setSender( $this->email->getFrom() );
    }
    private function addRecipientToList ($recipientList, $address, $name) {
        if ($recipientList === null) {
            $recipientList = array();
        }
        $recipientList[] = $this->createRecipient($address, $name);
        return $recipientList;
    }
    public function addReplyTo($address, $name = '') {
        $this->email->setReplyTo( $this->addRecipientToList ($this->email->getReplyTo(), $address, $name) );
        return true;
    }
    public function addAddress ($address, $name = '') {
        $this->email->setToRecipients( $this->addRecipientToList ($this->email->getToRecipients(), $address, $name) );
        return true;
    }
    public function addCC($address, $name = '') {
        $this->email->setCcRecipients( $this->addRecipientToList ($this->email->getCcRecipients(), $address, $name) );
        return true;
    }
    public function addBcc($address, $name = '') {
        $this->email->setBccRecipients( $this->addRecipientToList ($this->email->getBccRecipients(), $address, $name) );
        return true;
    }
    public function addAttachment($path, $name = '') {
        $contents = SBMailerUtils::getFileContents($path);
        $file_encoded = base64_encode( $contents );
        $type = SBMailerUtils::filenameToType($path);
        if (empty($name)) {
            $name = (string) SBMailerUtils::mb_pathinfo($path, PATHINFO_BASENAME);
        }
        $attachment = new Attachment(array(
            "contentBytes" => $file_encoded
        ));
        $attachment->setODataType("#microsoft.graph.fileAttachment");
        $attachment->setName($name);
        $attachment->setContentType($type);

        $atts = $this->email->getAttachments();
        if ($atts === null) {
            $atts = array();
        }
        $atts[] = $attachment;
        $this->email->setAttachments($atts);
        $this->email->setHasAttachments(true);
        return true;
    }
    public function setSubject($subject) {
        $this->email->setSubject( $subject );
    }
    private function setBody ( $bodyType, $body ) {
        $itemBody = new ItemBody();
        $itemBody->setContentType( $bodyType );
        $itemBody->setContent( $body );
        $this->email->setBody( $itemBody );
    }
    public function setHtmlBody($body) {
        $this->setBody( BodyType::HTML, $body );
    }
    public function setTextBody($body) {
        $this->setBody( BodyType::TEXT, $body );
    }
    public function setTag($tagName) {
        $headers = $this->email->getInternetMessageHeaders();
        if ($headers === null) {
            $headers = array();
        }

        $h = new InternetMessageHeader();
        $h->setName("x-custom-tag-name");
        $h->setValue($tagName);
        
        $headers[] = $h;
        $this->email->setInternetMessageHeaders( $headers );
    }
    public function send () {
        $mailBody = array(
            "Message" => $this->email,
            "saveToSentItems" => !empty($this->params["save_to_sent_items"]) && $this->params["save_to_sent_items"] == true ? true : false,
        );
        $accessToken = $this->getAuthToken ();
        $graph = new Graph();
        $graph->setAccessToken($accessToken);
        
        try {
            $sendResult = $graph->createRequest("POST", "/users" . "/" . $this->email->getFrom()->getEmailAddress()->getAddress() . "/sendMail")
                        ->attachBody($mailBody)
                        ->execute();
            // echo "<pre>";
            // print_r($sendResult);
            // echo "</pre>";
            return true;
        
        } catch (Exception $e) {
            // echo "<pre>";
            // print_r($e);
            // echo "</pre>";
            throw $e;
        }
        return false;
    }

    private function getAuthToken () {
        $guzzle = new \GuzzleHttp\Client();
        $url = 'https://login.microsoftonline.com/' . $this->params["tenant_id"] . '/oauth2/v2.0/token';
        $token = json_decode($guzzle->post($url, [
            'form_params' => [
                'client_id' => $this->params["client_id"],
                'client_secret' => $this->params["client_secret"],
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ],
        ])->getBody()->getContents());
        return $token->access_token;
    }
}

// Register the new adapter
SBMailerUtils::registerAdapter('microsoft-graph', 'SBMicrosoftGraphAdapter');
