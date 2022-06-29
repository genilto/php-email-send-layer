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

    private $graph;
    private $email;

    /**
     * Create a Microsoft Graph Adapter
     *
     * @param string $accessToken
     */
    public function __construct ($accessToken) {
        $this->graph = new Graph();
        $this->graph->setAccessToken($accessToken);
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
    public function addReplyTo($address, $name = '') {
        $this->email->setReplyTo( $this->createRecipient($address, $name) );
        return true;
    }
    public function addAddress ($address, $name = '') {
        return $this->addAnAddress('toRecipients', $address, $name);
    }
    public function addCC($address, $name = '') {
        return $this->addAnAddress('ccRecipients', $address, $name);
    }
    public function addBcc($address, $name = '') {
        return $this->addAnAddress('bccRecipients', $address, $name);
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
        $this->email['subject'] = $subject;
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
            "saveToSentItems" => true
        );
        $this->graph->createRequest("POST", "/me/sendMail")
                    ->attachBody($mailBody)
                    ->execute();
        return true;
    }
}
