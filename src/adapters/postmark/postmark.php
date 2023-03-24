<?php

require_once ( __DIR__ . "/vendor/autoload.php");

use Postmark\PostmarkClient;
use Postmark\Models\PostmarkAttachment;

class SBPostmarkAdapter implements iSBMailerAdapter {

    public const MAX_MESSAGES_IN_QUEUE = 500;
    public const MAX_QUEUE_SIZE = 35000000; // Postmark limits to 50MB. Defining less than the limit

    private $apiKey;
    private $email;

    private $queueSize = 0;
    private $deferedList = [];

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
            'MessageStream' => NULL,
            'size' => 0
        );
    }
    private function createAddress ($address, $name) {
        if (empty($name)) {
            return $address;
        }
        $fixedName = quoted_printable_encode($name);
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

        return array("status" => "SUCCESS");
    }
    public function deferToQueue() {
        if ($this->email['size'] == 0)
            $this->email['size'] = SBMailerUtils::getMemoryUsage($this->email);
        $this->queueSize += $this->email['size'];
        $this->deferedList[] = $this->email;
        $this->resetEmail ();
    }
    public function shouldSendQueueBeforeAdd() {
        $this->email['size'] = SBMailerUtils::getMemoryUsage($this->email);
        // When reach the max messages in queue
        // Or the maximum amount of memory usage
        return (count($this->deferedList) >= self::MAX_MESSAGES_IN_QUEUE) || 
            (($this->queueSize + $this->email['size']) >= self::MAX_QUEUE_SIZE);
    }
    private function sendMailBatch ($client, &$chunckedList, &$response, $totalSize) {
        $startTime = time();
        
        $sendResult = $client->sendEmailBatch($chunckedList);
        if (!empty($sendResult)) {
            while ($sendResult->valid()) {
                $current = $sendResult->current();
                $errorCode = $current->offsetGet("errorcode");
                $response[] = array(
                    "status" => ($errorCode == 0) ? "SUCCESS" : "ERROR",
                    "errorcode" => $errorCode,
                    "message" => $current->offsetGet("message")
                );
                $sendResult->next();
            }
        }
        
        // foreach($chunckedList as $index => $email) {
        //     $response[] = array(
        //                     "status" => "SUCCESS",
        //                     "errorcode" => 0,
        //                     "message" => $index,
        //                     "size" => $email["size"]
        //                 );
        // }
        $endTime = time();
        
        // Add a last object with some stats
        $response[] = array(
                            "status" => "STATS",
                            "size" => $totalSize,
                            "startTime" => $startTime,
                            "endTime" => $endTime,
                            "time" => ($endTime - $startTime)
                        );
    }
    public function sendQueue () {
        if (count($this->deferedList) == 0) {
            //throw new Exception("There is no email messages on sending queue!");
            return [];
        }
        $client = new PostmarkClient($this->apiKey);
        $response = array();

        $totalMessages = 0;
        $totalSize = 0;
        $chunckedList = [];

        foreach($this->deferedList as $email) {
            if (($totalMessages >= self::MAX_MESSAGES_IN_QUEUE) || (($totalSize + $email['size']) >= self::MAX_QUEUE_SIZE)) {
                //echo " Sending (Chuncked): " . count($chunckedList) . " emails " . ($totalSize / 1024 / 1024) . "MB <br>";
                $this->sendMailBatch ($client, $chunckedList, $response, $totalSize);
                $totalMessages = 0;
                $totalSize = 0;
                $chunckedList = [];
            }
            $totalMessages++;
            $totalSize += $email['size'];
            $chunckedList[] = $email;
        }

        if (count($chunckedList) > 0) {
            //echo " Sending: " . count($chunckedList) . " emails " . ($totalSize / 1024 / 1024) . "KB <br>";
            $this->sendMailBatch ($client, $chunckedList, $response, $totalSize);
        }

        // // Send the emails in chunks of 500
        // while ($chunckedList = array_splice($this->deferedList, 0, self::MAX_MESSAGES_IN_QUEUE)) {
        //     $sendResult = $client->sendEmailBatch($chunckedList);
        //     if (!empty($sendResult)) {
        //         while ($sendResult->valid()) {
        //             $current = $sendResult->current();
        //             $errorCode = $current->offsetGet("errorcode");
        //             $response[] = array(
        //                 "status" => ($errorCode == 0) ? "SUCCESS" : "ERROR",
        //                 "errorcode" => $errorCode,
        //                 "message" => $current->offsetGet("message")
        //             );
        //             $sendResult->next();
        //         }
        //     }
        //     // echo "Sending " . count($chunckedList) . " emails... ";
        //     // $totalSize = 0;
        //     // foreach($chunckedList as $index => $email) {
        //     //     $response[] = array(
        //     //                     "status" => "SUCCESS",
        //     //                     "errorcode" => 0,
        //     //                     "message" => $index,
        //     //                     "size" => $email["size"]
        //     //                 );
        //     //     $totalSize += $email["size"];
        //     // }
        //     // echo " Total sent: " . $totalSize . " Remaining in array: " . count($this->deferedList) . " <br>";
        // }
        $this->deferedList = [];
        $this->queueSize = 0;
        return $response;
    }
    public function couldRetryOnError ($exception) {
        $message = $exception->getMessage();
        // cURL error 28: Connection timed out after xxxx milliseconds (see https://curl.haxx.se/libcurl/c/libcurl-errors.html)
        return !empty($message) && strpos($message, "cURL error 28") !== false;
    }
}

// Register the new adapter
SBMailerUtils::registerAdapter('postmark', 'SBPostmarkAdapter');
