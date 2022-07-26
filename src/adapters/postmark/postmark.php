<?php

require_once ( __DIR__ . "/vendor/autoload.php");

use Postmark\PostmarkClient;
use Postmark\Models\PostmarkAttachment;

class SBPostmarkAdapter implements iSBMailerAdapter {

    private $apiKey;
    private $email;

    /**
     * Create a Postmark Adapter
     *
     * @param array $params
     */
    public function __construct ($params) {
        $this->apiKey = $params['api_key'];
        $this->resetEmail ();
    }
    public function getMailerName () {
        return 'Postmark';
    }
    private function resetEmail () {
        $this->email = array(
            'From' => NULL,
            'ReplyTo' => NULL,
            'To' => NULL,
            'Cc' => NULL,
            'Bcc' => NULL,
            'Subject' => NULL,
            'TextBody' => NULL,
            'HtmlBody' => NULL,
            "Attachments" => NULL, // array
            'Tag' => NULL,
            'Metadata' =>  NULL, // array
            'Headers' => NULL, // array
            'TrackOpens' => NULL,
            "TrackLinks" => NULL,
            'MessageStream' => NULL
        );
    }
    private function createAddress ($address, $name) {
        if (empty($name)) {
            return $address;
        }
        $fixedName = htmlentities($name);
        return "\"$fixedName\" <$address>";
    }
    private function addAnAddress ($kind, $address, $name) {
        $newAddress = $this->createAddress($address, $name);
        if (!empty($this->email[$kind])) {
            $this->email[$kind] .= "," . $newAddress;
            return true;
        }
        $this->email[$kind] = $newAddress;
        return true;
    }
    public function setFrom($address, $name = '') {
        $this->email['From'] = $this->createAddress($address, $name);
    }
    public function addReplyTo($address, $name = '') {
        return $this->addAnAddress('ReplyTo', $address, $name);
    }
    public function addAddress ($address, $name = '') {
        return $this->addAnAddress('To', $address, $name);
    }
    public function addCC($address, $name = '') {
        return $this->addAnAddress('Cc', $address, $name);
    }
    public function addBcc($address, $name = '') {
        return $this->addAnAddress('Bcc', $address, $name);
    }
    public function addAttachment($path, $name = '') {
        $contents = SBMailerUtils::getFileContents($path);
        $type = SBMailerUtils::filenameToType($path);
        if (empty($name)) {
            $name = (string) SBMailerUtils::mb_pathinfo($path, PATHINFO_BASENAME);
        }
        $attachment = PostmarkAttachment::fromRawData($contents, $name, $type);
        
        $key = "Attachments";
        if ($this->email[$key] === NULL) {
            $this->email[$key] = [];
        }

        $this->email[$key][] = $attachment;
        return true;
    }
    public function setSubject($subject) {
        $this->email['Subject'] = $subject;
    }
    public function setHtmlBody($body) {
        $this->email['HtmlBody'] = $body;
    }
    public function setTextBody($altBody) {
        $this->email['TextBody'] = $altBody;
    }
    public function setTag($tagName) {
        $this->email['Tag'] = $tagName;
    }
    public function send () {
        $client = new PostmarkClient($this->apiKey);
        $sendResult = $client->sendEmail(
            $this->email['From'],
            $this->email['To'],
            $this->email['Subject'],
            $this->email['HtmlBody'],
            $this->email['TextBody'],
            $this->email['Tag'],
            $this->email['TrackOpens'],
            $this->email['ReplyTo'],
            $this->email['Cc'],
            $this->email['Bcc'],
            $this->email['Headers'],
            $this->email['Attachments'],
            $this->email['TrackLinks'],
            $this->email['Metadata'],
            $this->email['MessageStream']
        );

        // echo "<pre>";
        // print_r($sendResult);
        // echo "</pre>";

        return true;
    }
}

// Register the new adapter
SBMailerUtils::registerAdapter('postmark', 'SBPostmarkAdapter');
