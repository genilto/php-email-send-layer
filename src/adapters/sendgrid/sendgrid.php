<?php

require_once ( __DIR__ . "/vendor/autoload.php");

class SBSendgridAdapter implements iSBMailerAdapter {

    private $apiKey;
    private $email;

    private $htmlBody;
    private $textBody;

    /**
     * Create a sendgrid Adapter
     *
     * @param array $params
     */
    public function __construct ($params) {
        $this->apiKey = $params['api_key'];
        $this->email = new \SendGrid\Mail\Mail();
    }
    public function getMailerName () {
        return 'SendGrid';
    }
    public function setFrom($address, $name = '') {
        $this->email->setFrom($address, $name);
    }
    public function addReplyTo($address, $name = '') {
        $this->email->setReplyTo($address, $name);
        return true;
    }
    public function addAddress ($address, $name = '') {
        $this->email->addTo($address, $name);
        return true;
    }
    public function addCC($address, $name = '') {
        $this->email->addCc($address, $name);
        return true;
    }
    public function addBcc($address, $name = '') {
        $this->email->addBcc($address, $name);
        return true;
    }
    public function addAttachment($path, $name = '') {
        $contents = SBMailerUtils::getFileContents($path);
        $file_encoded = base64_encode( $contents );
        $type = SBMailerUtils::filenameToType($path);
        if ('' === $name) {
            $name = (string) SBMailerUtils::mb_pathinfo($path, PATHINFO_BASENAME);
        }
        $this->email->addAttachment(
                $file_encoded,
                $type,
                $name
            );
        return true;
    }
    public function setSubject($subject) {
        $this->email->setSubject( $subject );
    }
    public function setHtmlBody($body) {
        $this->htmlBody = $body;
    }
    public function setTextBody($body) {
        $this->textBody = $body;
    }
    public function setTag($tagName) {
        $this->email->addCategory($tagName);
    }
    public function send () {
        if (!empty($this->htmlBody)) {
            $this->email->addContent(SBMailerUtils::CONTENT_TYPE_TEXT_HTML, $this->htmlBody);
        }
        if (!empty($this->textBody)) {
            $this->email->addContent(SBMailerUtils::CONTENT_TYPE_PLAINTEXT, $this->textBody);
        }

        $sendgrid = new \SendGrid($this->apiKey);
        $response = $sendgrid->send($this->email);

        // echo "RESULT: <pre>";
        // print_r($response);
        // echo "</pre>";

        // Verify the response code from Sendgrid
        if ($response->statusCode() < 200 || $response->statusCode() > 299) {
            $errorMessage = "Status Code returned by Sendgrid: " . $response->statusCode() . ". Details: ";
            
            if (!empty($response->body())) {
                $data = json_decode($response->body());
                
                if (isset($data->errors) && is_array($data->errors)) {
                    foreach($data->errors as $error) {
                        if (isset($error->message) && !empty($error->message)) {
                            $errorMessage .= $error->message;
                        }
                    }
                }
            } else {
                $errorMessage .= 'No details found';
            }
            throw new Exception($errorMessage);
        }
        return true;
    }
    public function deferToQueue() {
        throw new Exception("Batch not implemented");
    }
    public function shouldSendQueue() {
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
SBMailerUtils::registerAdapter('sendgrid', 'SBSendgridAdapter');
