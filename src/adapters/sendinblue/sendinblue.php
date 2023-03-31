<?php

use \genilto\sbmailer\iSBMailerAdapter;
use \genilto\sbmailer\SBMailerUtils;
use \SendinBlue\Client\ApiException;
use \SendinBlue\Client\Model\SendSmtpEmail;
use \SendinBlue\Client\Model\SendSmtpEmailAttachment;
use \SendinBlue\Client\Model\SendSmtpEmailBcc;
use \SendinBlue\Client\Model\SendSmtpEmailCc;
use \SendinBlue\Client\Model\SendSmtpEmailReplyTo;
use \SendinBlue\Client\Model\SendSmtpEmailSender;
use \SendinBlue\Client\Model\SendSmtpEmailTo;

class SBSendinblueAdapter implements iSBMailerAdapter {

    private $apiKey;
    private $email;
    
    /**
     * Create a sendinblue Adapter
     *
     * @param array $params
     */
    public function __construct ($params) {
        $this->apiKey = $params['api_key'];
        $this->email = new SendSmtpEmail();
    }
    public function getMailerName () {
        return 'Sendinblue';
    }
    public function setFrom($address, $name = '') {
        $sender = new SendSmtpEmailSender();
        $sender->setEmail($address);
        if (!empty($name)) {
            $sender->setName($name);
        }
        $this->email->setSender($sender);
    }
    public function addReplyTo($address, $name = '') {
        $recipient = new SendSmtpEmailReplyTo();
        $recipient->setEmail($address);
        if (!empty($name)) {
            $recipient->setName($name);
        }
        $this->email->setReplyTo($recipient);
        return true;
    }
    public function addAddress ($address, $name = '') {
        $recipient = new SendSmtpEmailTo();
        $recipient->setEmail($address);
        if (!empty($name)) {
            $recipient->setName($name);
        }
        $recipients = $this->email->getTo();
        if ($recipients == null) {
            $recipients = array();
        }
        $recipients[] = $recipient;
        $this->email->setTo($recipients);
        return true;
    }
    public function addCC($address, $name = '') {
        $recipient = new SendSmtpEmailCc();
        $recipient->setEmail($address);
        if (!empty($name)) {
            $recipient->setName($name);
        }
        $recipients = $this->email->getCc();
        if ($recipients == null) {
            $recipients = array();
        }
        $recipients[] = $recipient;
        $this->email->setCc($recipients);
        return true;
    }
    public function addBcc($address, $name = '') {
        $recipient = new SendSmtpEmailBcc();
        $recipient->setEmail($address);
        if (!empty($name)) {
            $recipient->setName($name);
        }
        $recipients = $this->email->getBcc();
        if ($recipients == null) {
            $recipients = array();
        }
        $recipients[] = $recipient;
        $this->email->setBcc($recipients);
        return true;
    }
    public function addAttachment($path, $name = '') {
        $contents = SBMailerUtils::getFileContents($path);
        $file_encoded = base64_encode( $contents );
        if ('' === $name) {
            $name = (string) SBMailerUtils::mb_pathinfo($path, PATHINFO_BASENAME);
        }
        
        $att = new SendSmtpEmailAttachment();
        $att->setName($name);
        $att->setContent($file_encoded);

        $atts = $this->email->getAttachment();
        if ($atts == null) {
            $atts = array();
        }
        $atts[] = $att;
        $this->email->setAttachment( $atts );
        return true;
    }
    public function setSubject($subject) {
        $this->email->setSubject( $subject );
    }
    public function setHtmlBody($body) {
        $this->email->setHtmlContent($body);
    }
    public function setTextBody($body) {
        $this->email->setTextContent($body);
    }
    public function setTag($tagName) {
        $tags = $this->email->getTags();
        if ($tags == null) {
            $tags = array();
        }
        $tags[] = $tagName;
        $this->email->setTags($tags);
    }
    public function send () {
        $config = SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->apiKey);

        $apiInstance = new SendinBlue\Client\Api\TransactionalEmailsApi(
            new GuzzleHttp\Client(),
            $config
        );

        try {
            $result = $apiInstance->sendTransacEmail($this->email);
            // echo "RESULT: <pre>";
            // print_r($result);
            // echo "</pre>";
            return array("status" => "SUCCESS");
        } catch (ApiException $e) {
            $responseBody = $e->getResponseBody();
            if (empty($responseBody)) {
                throw $e;
            }
            $data = json_decode($responseBody);
            if (empty($data) || empty($data->code)) {
                throw $e;
            }
            $errorMessage = "Status Code returned by Sendinblue: " . $data->code . ". Details: " . $data->message;
            throw new \Exception($errorMessage);
        }
        return array("status" => "ERROR");
    }
    public function deferToQueue() {
        throw new Exception("Batch not implemented");
    }
    public function shouldSendQueueBeforeAdd() {
        return false;
    }
    public function sendQueue () {
        throw new Exception("Batch not implemented");
    }
    public function couldRetryOnError ($exception) {
        return false;
    }
}

// Register the new adapter
SBMailerUtils::registerAdapter('sendinblue', 'SBSendinblueAdapter');