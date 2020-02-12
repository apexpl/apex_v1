<?php
declare(strict_types = 1);

/**
 * Notifyable Interface
 *
 * When a class implements this interface, its objects can be passed to Apex's 
 * notification system and be sent e-mails, SMS messages, Skype messages, and other 
 * formats of communication.
 * 
 * For full information, please consult the developer documentation at:
 *     https://apex-platform.org/docs/notifications
 */
interface Notifyable 
{

/**
 * Get e-mail address
 *
 * @return string The e-mail address of the object.
 */
public function get_email():string;

/**
 * Get full name
 *
 * @return string The full name of the object.
 */
public function get_full_name():string;

/**
 * Get phone number
 *
 * @return string The phone number of the object.
 */
public function get_phone():string;

/**
 * Get a IM username.
 * 
 * @param string $im_service The IM service (eg. skype, slack, whatsapp, etc.).  Consult documentation for details.
 *
 * @return string The username of the IM app, or blank if not exists.
 */
public function get_im(string $im_service):string;

}





