<?php
declare(strict_types = 1);

namespace apex\app\interfaces\msg;


/**
 * E-Mail Message Interface
*/
interface EmailMessageInterface
{

    /**
     * Set to e-mail 
     *
     * @param string $email The recipient e-mail address to send to
     */
    public function to_email(string $email);


    /**
     * set from e-mail address 
     *
     * @param string $email The e-mail address the e-mail is from.
     */
    public function from_email(string $email);


    /**
     * Set recipient name 
     *
     * @param string $name The full name of the recipient
     */
    public function to_name(string $name);


    /**
     * Set the sender name 
     *
     * @param string $name The name of the e-mail sender
     */
    public function from_name(string $name);


    /**
     * Set the reply-to e-mail address 
     *
     * @param string $email The e-mail address of the reply to.s
     */
    public function reply_to(string $email);


    /**
     * Set the CC e-mail address
     *
     * @param string $email The e-mail address. 
     */
    public function cc(string $email);


    /**
     * Set the BCC e-mail address
     *
     * @param string $email The e-mail address. 
     */
    public function bcc(string $email);


    /**
     * Set the subject of the e-mail 
     *
     * @param string $subject The subject of the e-mail address
     */
    public function subject(string $subject);


    /**
     * Set the conents of the e-mail message 
     *
     * @param string $message The contents of the e-mail message.
     */
    public function message(string $message);


    /**
     * Set the content type of the -email message. 
     *
     * @param string $type The content type
    */
    public function content_type(string $type);


    /**
     * Add a file attachment to the e-mail message 
     *
     * @param string $filename The filename of the file attachment.
     * @param string $contents The contents of the file attachment.
     */
    public function add_attachment(string $filename, string $contents);


    /**
     * Get the sender e-mail address
     * 
     *     @return string The sender e-mail address
     */
    public function get_from_email();


    /**
     * Get the to e-mail address
     * 
     *     @return string The to / recipient e-mail address
     */
    public function get_to_email();

    /**
     * Get the sender line
     *
     *     @return string The recipient line (NAME <EMAIL> format)
     */
    public function get_sender_line();


    /**
     * Get the subject
     * 
     *     @return string The e-mail subject
     */
    public function get_subject();

    /**
     * Get the header of the e-mail message.
     * 
     *     @return string Headers of the email message
     */
    public function get_headers();

    /**
     * Get the contents of the e-mail message.
     *
     *     @param string The contents of the e-mail message
     */
    public function get_message();


    /**
     * Get the recipient line
     *
     *     @return string The recipient line (NAME <EMAIL> format)
     */
    public function get_recipient_line();


}


