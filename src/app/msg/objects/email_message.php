<?php
declare(strict_types = 1);

namespace apex\app\msg\objects;

use apex\app;
use apex\app\interfaces\msg\EmailMessageInterface;


/**
 * Allows e-mail messages to be properly defined with all necessary variables 
 * and information. 
 */
class email_message implements EmailMessageInterface
{


    // Properties
    private $to_email;
    private $to_name;
    private $from_email;
    private $from_name;
    private $reply_to;
    private $cc;
    private $bcc;
    private $subject;
    private $headers;
    private $message;
    private $content_type = 'text/plain';
    private $attachments = [];


/**
 * Set to e-mail 
 *
 * @param string $email The recipient e-mail address to send to
 */
public function to_email(string $email)
{ 

    // Validate
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { 
        throw new CommException('invalid_email', $email);
    }

    // Set e-amail
    $this->to_email = $email;

}

/**
 * set from e-mail address 
 *
 * @param string $email The e-mail address the e-mail is from.
 */
public function from_email(string $email)
{ 

    // Validate
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { 
        throw new CommException('invalid_email', $email);
    }

    // Set e-amail
    $this->from_email = $email;

}

/**
 * Set recipient name 
 *
 * @param string $name The full name of the recipient
 */
public function to_name(string $name)
{ 
    $this->to_name = filter_var($name, FILTER_SANITIZE_STRING);
}

/**
 * Set the sender name 
 *
 * @param string $name The name of the e-mail sender
 */
public function from_name(string $name)
{ 
    $this->from_name = filter_var($name, FILTER_SANITIZE_STRING);
}

/**
 * Set the reply-to e-mail address 
 * 
 * @param string $email The e-mail address.
 */
public function reply_to(string $email)
{ 

    // Validate
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { 
        throw new CommException('invalid_email', $email);
    }
    $this->reply_to = $email;
}

/**
 * Set the CC e-mail address
 *
 * @param string $email The e-mail address. 
 */
public function cc(string $email)
{ 

    // Validate
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { 
        throw new CommException('invalid_email', $email);
    }
    $this->cc = $email;
}

/**
 * Set the BCC e-mail address 
 *
 * @param string $email The e-mail address.
 */
public function bcc(string $email)
{ 

    // Validate
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { 
        throw new CommException('invalid_email', $email);
    }
    $this->bcc = $email;
}

/**
 * Set the subject of the e-mail 
 *
 * @param string $subject The subject of the e-mail address
 */
public function subject(string $subject)
{ 
    $this->subject = filter_var($subject, FILTER_SANITIZE_STRING);
}

/**
 * Set the conents of the e-mail message 
 *
 * @param string $message The contents of the e-mail message.
 */
public function message(string $message)
{ 
    $this->message = $message;
}

/**
 * Set the content type of the -email message. 
 *
 * @param string $type The content type
 */
public function content_type(string $type)
{ 
    if ($type != 'text/plain' && $type != 'text/html') { 
        throw new CommException('invalid_content_type', $type);
    }
    $this->content_type = $type;
}

/**
 * Add a file attachment to the e-mail message 
 *
 * @param string $filename The filename of the file attachment.
 * @param string $contents The contents of the file attachment.
 */
public function add_attachment(string $filename, string $contents)
{ 
    $this->attachments[$filename] = $contents;
}

/**
 * Get the sender e-mail address 
 *
 * @return string The sender e-mail address
 */
public function get_from_email() { return $this->from_email; }

/**
 * Get the to e-mail address 
 *
 * @return string The to / recipient e-mail address
 */
public function get_to_email() { return $this->to_email; }

/**
 * Get the sender line 
 *
 * @return string The sender line (NAME <EMAIL> format)
 */
public function get_sender_line()
{ 

    // Get sender line
    $sender = '<' . $this->from_email . '>';
    if ($this->from_name != '') { $sender = $this->from_name . ' ' . $sender; }

    // Return
    return $sender;

}

/**
 * Get the recipient line 
 *
 * @return string The recipient line (NAME <EMAIL> format)
 */
public function get_recipient_line()
{ 

    // Get sender line
    $recipient = '<' . $this->to_email . '>';
    if ($this->to_name != '') { $recipient = $this->to_name . ' ' . $recipient; }

    // Return
    return $recipient;

}

/**
 * Get the subject 
 *
 * @return string The e-mail subject
 */
public function get_subject() { return $this->subject; }

/**
 * Get the header of the e-mail message. 
 *
 * @return string Headers of the email message
 */
public function get_headers() { return $this->headers; }

/**
 * Get the contents of the e-mail message. 
 *
 * @param string The contents of the e-mail message
 */
public function get_message() { return $this->message;}

/**
 * Format the e-mail message as necessary 
 */
public function format_message()
{ 

    // Replace domain_name config variable
    $this->subject = str_replace("~domain_name~", app::_config('core:domain_name'), $this->subject);
    $this->message = str_replace("~domain_name~", app::_config('core:domain_name'), $this->message);

    // Start header
    $this->headers = "From: " . $this->get_sender_line() . "\r\n";
    if ($this->reply_to != '') { $this->headers .= "Reply-to: $this->reply_to\r\n"; }
    if ($this->cc != '') { $this->headers .= "Cc: $this->cc\r\n"; }
    if ($this->bcc != '') { $this->headers .= "Bcc: $this->bcc\r\n"; }

    // Add attachments, if needed
    if (count($this->attachments) > 0) { 

        // Get boundary
        $boundary = "_----------=" . time() . "100";

        // Finish headers
        $this->headers .= "MIME-Version: 1.0\r\n";
        $this->headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"";

        // Start message
        $contents = "This is a multi-part message in MIME format.\r\n";
        $contents .= '--' . $boundary . "\r\n";

        // Add message contents
        $contents .= "Content-type: $this->content_type\r\n";
        $contents .= "Content-transfer-encoding: 7bit\r\n\r\n";
        $contents .= $this->message . "\r\n";
        $contents .= '--' . $boundary;

        // Add attachments
        foreach ($this->attachments as $filename => $file_contents) { 
            $contents .= "\r\n";
            $contents .= "Content-Disposition: attachment; filename=\"$filename\"\r\n";
            $contents .= "Content-Transfer-Encoding: base64\r\n";
            $contents .= "Content-Type: application/octet-stream; name=\"$filename\"\r\n\r\n";
            $contents .= base64_encode($file_contents) . "\r\n\r\n";
            $contents .= '--' . $boundary;
        }

        // Finish message
        $contents .= "--\r\n\r\n";
        $this->message = $contents;

    // Define content-type for no attachments message
    } else { 
        $this->headers .= "Content-type: $this->content_type\r\n";
    }

}


}

