<?php
declare(strict_types = 1);

namespace apex\app\interfaces;

/** 
 *Authentication Interface
 *
 * Handles authenticating sessions, checking whether or not a 
 * user is authenticated, invalid sessions, 2FA, and more.
 */
interface AuthInterface {

/**
 * Check if the current session is authenticated.
 *
 * @param bool $require_login Whether or not an authenticated session is required for this request.
 */
public function check_login(bool $require_login = false);

/**
 * Login a user.  Uses the POSTed variables for username / password.
 */
public function login();

/**
 * Auto-login a user.
 *
 * @param int $userid The ID# of the user to login.
 */
public function auto_login(int $userid);

/**
 * Logout, and close authenticated session.
 */
public function logout();

/**
 * Check username / password only.
 *
 * @param string $username The username.
 * @param string $password The password.
 */
public function check_password(string$username, string$password);

/**
 * Conduct 2FA via e-mail.
 *
 * @param int $is_login A (1/0) whether or not this is due to the user logging in.
 */
public function authenticate_2fa_email(int $is_login = 0);

/**
 * Conduct 2FA via SMS.
 */
public function authenticate_2fa_sms();

/**
 * Get the encryption password from auth session.
 */
public function get_encpass();

/**
 * Check reCaptcha answer and ensure it's a human.
 */
public function recaptcha();

}

