<?php
class SBSendgridAdapter implements iSBMailerAdapter {

    private $apiKey;
    private $email;
    
    /**
     * Create a sendgrid Adapter
     *
     * @param string $apiKey
     */
    public function __construct ($apiKey) {
        $this->apiKey = $apiKey;
        $this->email = new \SendGrid\Mail\Mail();
    }
    public function setFrom($address, $name = '') {
        $this->email->setFrom($address, $name);
    }
    public function addReplyTo($address, $name = '') {
        $this->email->setReplyTo($address, $name);
    }
    public function addAddress ($address, $name = '') {
        $this->email->addTo($address, $name);
    }
    public function addCC($address, $name = '') {
        $this->email->addCc($address, $name);
    }
    public function addBcc($address, $name = '') {
        $this->email->addBcc($address, $name);
    }
    public function addAttachment($path, $name = '') {
        $file_encoded = base64_encode(file_get_contents($path));

        // Try to work it out from the file name
        $type = SBMailerUtils::filenameToType($path);

        if ('' === $name) {
            $name = (string) SBMailerUtils::mb_pathinfo($path, PATHINFO_BASENAME);
        }

        $this->email->addAttachment(
                $file_encoded,
                $type,
                $name
            );
    }
    public function setSubject($subject) {
        $this->email->setSubject( $subject );
    }
    public function setBody($body) {
        $this->email->addContent("text/html", $body);
    }
    public function setAltBody($altBody) {
        $this->email->addContent("text/plain", $altBody);
    }
    public function send () {
        $sendgrid = new \SendGrid($this->apiKey);
        $response = $sendgrid->send($this->email);

        // echo "<pre>";
        // print $response->statusCode() . "\n";
        // print_r($response->headers());
        // print_r( $response->body() ) . "\n";
        // echo "</pre>";

        if ($response->statusCode() < 200 || $response->statusCode() > 299) {
            $errorMessage = "Sendgrid returned Status code: " . $response->statusCode() . ". Details: ";
            
            if (!empty($response->body())) {
                $response = json_decode($response->body());
                
                if (isset($response->errors) && is_array($response->errors)) {
                    // echo "<pre>";
                    // print_r($response->errors);
                    // echo "</pre>";
                    foreach($response->errors as $error) {
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
    }
}
