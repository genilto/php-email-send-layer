<?php

namespace genilto\sbmailer;

interface iSBMailerAdapter
{
    /**
     * Gets the instantiated mailer adapter name
     * 
     * @return string
     */
    public function getMailerName ();

    /**
     * Sets the from field of the email
     *
     * @param string $address
     * @param string $name (optional)
     */
    public function setFrom($address, $name = '');

    /**
     * Sets the Reply to field of the email
     *
     * @param string $address
     * @param string $name  (optional)
     * 
     * @return bool true on success, false if address already used or invalid in some way
     */
    public function addReplyTo($address, $name = '');

    /**
     * Add recipient to the TO field of the email
     *
     * @param string $address
     * @param string $name (optional)
     * 
     * @return bool true on success, false if address already used or invalid in some way
     */
    public function addAddress ($address, $name = '');

    /**
     * Add recipient to the CC field of the email
     *
     * @param string $address
     * @param string $name (optional)
     * 
     * @return bool true on success, false if address already used or invalid in some way
     */
    public function addCC($address, $name = '');

    /**
     * Add recipient to the BCC field of the email
     *
     * @param string $address
     * @param string $name (optional)
     * 
     * @return bool true on success, false if address already used or invalid in some way
     */
    public function addBcc($address, $name = '');

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
     * @return bool
     */
    public function addAttachment($path, $name = '');

    /**
     * Sets the email subject
     *
     * @param string $subject
     */
    public function setSubject($subject);

    /**
     * Sets the email body
     *
     * @param string $body of the email
     */
    public function setHtmlBody($body);

    /**
     * Sets the email alternative body
     * Displayed when email reader doenst support HTML
     *
     * @param string $body Text body
     */
    public function setTextBody($body);

    /**
     * Sets a tag for better message classification
     *
     * @param string $tagName
     */
    public function setTag($tagName);

    /**
     * Sends the email
     * 
     * @throws \Exception
     * 
     * @return array
     */
    public function send ();

    /**
     * Defer the message to a queue to be sent later in Batch
     */
    public function deferToQueue ();

    /**
     * Verify when the quantity of messages in the queue is greater than the max messages to send in batch
     * 
     * @return bool true when the quantity of messages in the queue is greater than the max messages to send in batch
     */
    public function shouldSendQueueBeforeAdd ();

    /**
     * Send all the defered emails in batch
     * 
     * @throws \Exception
     * 
     * @return array
     */
    public function sendQueue ();

    /**
     * Check if a retry could be done for the error exception
     * 
     * @param Exception exception to be analysed
     * 
     * @return bool true when could retry sending
     */
    public function couldRetryOnError ($exception);
}