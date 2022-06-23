<?php

class SBSendinblueAdapter implements iSBMailerAdapter {

    private $apiKey;
    private $email;
    
    /**
     * Create a sendinblue Adapter
     *
     * @param string $apiKey
     */
    public function __construct ($apiKey) {
        $this->apiKey = $apiKey;
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
    public function isHTML($isHtml = true) {
        if ($isHtml) {
            $this->contentType = SBMailerUtils::CONTENT_TYPE_TEXT_HTML;
        } else {
            $this->contentType = SBMailerUtils::CONTENT_TYPE_PLAINTEXT;
        }
    }
    public function setBody($body) {
        $this->email->addContent($this->contentType, $body);
    }
    public function setAltBody($altBody) {
        $this->email->addContent(SBMailerUtils::CONTENT_TYPE_PLAINTEXT, $altBody);
    }
    public function send () {


        $config = SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->apiKey);

        $apiInstance = new SendinBlue\Client\Api\TransactionalEmailsApi(
            new GuzzleHttp\Client(),
            $config
        );
        $sendSmtpEmail = new \SendinBlue\Client\Model\SendSmtpEmail();
        $sendSmtpEmail['subject'] = 'Testing sendingblue';
        $sendSmtpEmail['htmlContent'] = '<html><body><h1>This is a transactional email</h1></body></html>';
        $sendSmtpEmail['sender'] = array('name' => 'Fragmentoweb', 'email' => 'contato@fragmentoweb.com');
        $sendSmtpEmail['to'] = array(
            array('email' => 'genilto.vanzin@gmail.com', 'name' => 'Genilto Vanzin')
        );
        // $sendSmtpEmail['cc'] = array(
        //     array('email' => 'example2@example2.com', 'name' => 'Janice Doe')
        // );
        // $sendSmtpEmail['bcc'] = array(
        //     array('email' => 'example@example.com', 'name' => 'John Doe')
        // );
        //$sendSmtpEmail['replyTo'] = array('email' => 'replyto@domain.com', 'name' => 'John Doe');
        //$sendSmtpEmail['headers'] = array('Some-Custom-Name' => 'unique-id-1234');
        //$sendSmtpEmail['params'] = array('parameter' => 'My param value', 'subject' => 'New Subject');
        
        try {
            $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
            echo "<pre>";
            print_r($result);
            echo "</pre>";
            return true;
        } catch (Exception $e) {
            throw $e;
        }




        // $credentials = SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->apiKey);
        // $apiInstance = new SendinBlue\Client\Api\TransactionalEmailsApi(new GuzzleHttp\Client(), $credentials);
        
        // $sendSmtpEmail = new \SendinBlue\Client\Model\SendSmtpEmail([
        //      'subject' => 'from the PHP SDK!',
        //      'sender' => ['name' => 'Fragmentoweb', 'email' => 'contato@fragmentoweb.com'],
        //     // 'replyTo' => ['name' => 'Sendinblue', 'email' => 'contact@sendinblue.com'],
        //      'to' => [[ 'name' => 'Genilto Vanzin', 'email' => 'genilto.vanzin@gmail.com']],
        //      'htmlContent' => '<html><body><h1>This is a transactional email</h1><div>topzera</div></body></html>'
        // ]);
        
        // try {
        //     $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
        //     echo "<pre>";
        //     print_r($result);
        //     echo "</pre>";
        //     return true;
        // } catch (Exception $e) {
        //     throw $e;
        // }



        // $sendgrid = new \SendGrid($this->apiKey);
        // $response = $sendgrid->send($this->email);

        // // echo "<pre>";
        // // print $response->statusCode() . "\n";
        // // print_r($response->headers());
        // // print_r( $response->body() ) . "\n";
        // // echo "</pre>";

        // // Verify the response code from Sendgrid
        // if ($response->statusCode() < 200 || $response->statusCode() > 299) {
        //     $errorMessage = "Status Code returned by Sendgrid: " . $response->statusCode() . ". Details: ";
            
        //     if (!empty($response->body())) {
        //         $response = json_decode($response->body());
                
        //         if (isset($response->errors) && is_array($response->errors)) {
        //             // echo "<pre>";
        //             // print_r($response->errors);
        //             // echo "</pre>";
        //             foreach($response->errors as $error) {
        //                 if (isset($error->message) && !empty($error->message)) {
        //                     $errorMessage .= $error->message;
        //                 }
        //             }
        //         }
        //     } else {
        //         $errorMessage .= 'No details found';
        //     }
        //     throw new Exception($errorMessage);
        // }
    }
}
