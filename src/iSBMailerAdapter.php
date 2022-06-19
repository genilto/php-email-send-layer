<?php
interface iSBMailerAdapter
{
    /**
     * Sets the from field of the email
     *
     * @param string $address
     * @param string $name
     */
    public function setFrom($address, $name = '');

    /**
     * Sets the Reply to field of the email
     *
     * @param string $address
     * @param string $name
     */
    public function addReplyTo($address, $name = '');

    /**
     * Add recipient to the TO field of the email
     *
     * @param string $address
     * @param string $name
     */
    public function addAddress ($address, $name = '');

    /**
     * Add recipient to the CC field of the email
     *
     * @param string $address
     * @param string $name
     */
    public function addCC($address, $name = '');

    /**
     * Add recipient to the BCC field of the email
     *
     * @param string $address
     * @param string $name
     */
    public function addBcc($address, $name = '');

    /**
     * Add an attachment to the email
     *
     * @param string $path Path of the file in server filesystem
     * @param string $name Name to display the attachment in email
     */
    public function addAttachment($path, $name = '');

    /**
     * Sets the email subject
     *
     * @param string $subject
     */
    public function setSubject($subject);

    /**
     * Sets message type to HTML or plain.
     *
     * @param bool $isHtml True for HTML mode
     */
    public function isHTML($isHtml = true);

    /**
     * Sets the email body
     *
     * @param string $body of the email
     */
    public function setBody($body);

    /**
     * Sets the email alternative body
     * Displayed when email reader doenst support HTML
     *
     * @param string $altBody Text body
     */
    public function setAltBody($altBody);

    /**
     * Sends the email
     * 
     * @throws \Exception
     */
    public function send ();
}