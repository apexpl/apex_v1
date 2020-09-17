<?php
declare(strict_types = 1);

namespace apex\app\sys;

use apex\app;
use apex\libc\db;
use apex\libc\debug;
use apex\libc\auth;
use apex\app\exceptions\ApexException;
use apex\app\exceptions\EncryptException;


/**
 * Encryption Library
 *
 * Service: apex\libc\encrypt
 *
 * Handles all encryption within Apex including user based two-way AES256 
 * encryption to multiple recipients, basic insecure encryption, RSA key-pair 
 * generation, PGP encryption and key management, etc. 
 * 
 * This class is available within the services container, meaning its methods can be accessed statically via the 
 * service singleton as shown below.
 *
 * PHP Example
 * --------------------------------------------------
 * 
 * <?php
 *
 * namespace apex;
 *
 * use apex\app;
 * use apex\libc\encrypt;
 *
 * // Basic encrypt
 * $encrypted = encrypt::encrypt_basic('some plain text', 'mypassword');
 *
 * // Basic decrypt
 * $text = encrypt::decrypt_basic($encrypted, 'mypassword');
 *
 */
class encrypt
{


/**
 * Generate new RSA key-pair 
 *
 * @param int $userid The ID# of the user / admin to create a keypair for.
 * @param string $type The type of user, defaults to 'user'
 * @param string $password The encryption password for the private key.  Generally should be the user's login password.
 *
 * @return int The ID# of the encryption key
 */
public function generate_rsa_keypair(int $userid, string $type = 'user', string $password = ''):int
{ 

    // Debug
    debug::add(2, tr("Start generating RSA key-pair for user ID# {1}, user type: {2}", $userid, $type), 'info');

    // Set config args
    $config = array(
        "digest_alg" => "sha512",
        "private_key_bits" => 4096,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    );

    // Generate private key
    $res = openssl_pkey_new($config);

    // Export private key
        openssl_pkey_export($res, $privkey);

    // Export public key
    $pubkey = openssl_pkey_get_details($res);

    // Encrypt private key
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $privkey = openssl_encrypt($privkey, app::_config('core:encrypt_cipher'), md5($password), 0, $iv);

    // Add to database
    db::insert('encrypt_keys', array(
        'type' => $type,
        'userid' => $userid,
        'iv' => base64_encode($iv),
        'public_key' => $pubkey['key'],
        'private_key' => $privkey)
    );
    $key_id = db::insert_id();

    // Debug
    debug::add(1, tr("Generated RSA key-pair for user ID# {1}, user type: {2}", $userid, $type), 'info');

    // Return
    return (int) $key_id;
}

/**
 * Change user RSA password 
 *
 * Change user's RSA password.  This will decrypt the user's current RSA 
 * private key, and encrypt it again with the new password. 
 *
 * @param int $userid The ID# of the user / admin
 * @param string $type The type of user, either 'user' or 'admin'
 * @param string $old_password The old / current password
 * @param string $password The new password
 */
public function change_rsa_password(int $userid, string $type, string $old_password, string $password)
{ 

    // Get key
    if (!list($key_id, $privkey) = $this->get_key($userid, $type, 'private', $old_password)) { 
        throw new EncryptException('no_private_key', $userid, $type);
    }

    // Encrypt private key
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $privkey = openssl_encrypt($privkey, app::_config('core:encrypt_cipher'), md5($password), 0, $iv);

    // Update database
    db::update('encrypt_keys', array(
        'iv' => base64_encode($iv),
        'private_key' => $privkey),
    "id = %i", $key_id);

    // Debug
    debug::add(1, tr("Updated password on RSA key-pair for user ID# {1}, user type: {2}", $userid, $type), 'info');

    // Return
    return true;

}

/**
 * Get public / private key from the database 
 *
 * @param int $userid The ID# of the user / admin
 * @param string $type The type of user, either 'user' or ad'min, defults to 'user'
 * @param $key_type The type of key to retrieve, either 'public' or 'private', defaults to 'public'
 * @param string $password Only required if $key_type is 'private', and is the password the key is encrypted with
 */
public function get_key(int $userid, string $type = 'user', string $key_type = 'public', string $password = '')
{ 

    // Get row
    if (!$row = db::get_row("SELECT * FROM encrypt_keys WHERE type = %s AND userid = %i", $type, $userid)) { 
        throw new EncryptException('no_private_key', $userid, $type);
    }

    // Get key
    $key = $row[$key_type . '_key'];
    if ($key_type == 'private') { 
        if (!$key = openssl_decrypt($key, app::_config('core:encrypt_cipher'), $password, 0, base64_decode($row['iv']))) { 
            throw new EncryptException('no_private_key', $userid, $type);
        }
    }

    // Debug
    debug::add(5, tr("Retrieved RSA encryption key, userid: {1}, user type: {2}, key type: {3}", $userid, $type, $key_type));

    // Return
    return array($row['id'], $key);

}

/**
 * Hash a password.
 * 
 * Simply uses the password_hash() function, and only reason for this is to 
 * hardcode the cost integer into only one spot throughout the code.
 *
 * @param string $password The Password to hash.
 *
 * @return string The resulting hashed password.
 */
public function hash_string(string $password):string
{
    return base64_encode(password_hash($password, PASSWORD_BCRYPT, array('COST' => 11)));
}

/**
 * Encrypt text to one or more users 
 *
 * @param string $data The data to encrypt
 * @param array $recipients An array of recipients to encrypt the data to (eg. admin:1, user:46, etc.)
 *
 * @return int The ID# of the encrypted data
 */
public function encrypt_user(string $data, array $recipients):int
{ 

    // Generate key / IV
    $data_key = openssl_random_pseudo_bytes(32);
    $data_iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

    // Encrypt the data
    $encrypted = openssl_encrypt($data, app::_config('core:encrypt_cipher'), $data_key, 0, $data_iv);

    // Add to database
    db::insert('encrypt_data', array(
        'data' => $encrypted)
    );
    $data_id = db::insert_id();

    // Add administrators to recipients
    $admin_ids = db::get_column("SELECT id FROM admin");
    foreach ($admin_ids as $admin_id) { 
        $var = 'admin:' . $admin_id;
        if (in_array($var, $recipients)) { continue; }
        $recipients[] = $var;
    }

    // Go through recipients
    foreach ($recipients as $recipient) { 

        // Get key
        if (preg_match("/^(user|admin):(\d+)/", $recipient, $match)) { 
            list($key_id, $public_key) = $this->get_key((int) $match[2], $match[1]);
        } else { 
            continue;
        }

        $pubkey = openssl_pkey_get_public($public_key);

        // Encrypt
        $keydata = base64_encode($data_key) . '::' . base64_encode($data_iv);
        if (!openssl_public_encrypt($keydata, $enc_key, $pubkey, OPENSSL_PKCS1_OAEP_PADDING)) { 
            throw new EncryptException('no_encrypt_user', 0, '', $recipient);
        }

        // Add to database
        db::insert('encrypt_data_keys', array(
            'data_id' => $data_id,
            'key_id' => $key_id,
            'keydata' => base64_encode($enc_key))
        );

        // Debug
        debug::add(5, tr("Encrypted data via AES256 to recipient: {1}", $recipient));

    }

    // Debug
    debug::add(5, "Finished encrypting AES256 data to all listed recipients");

    // Return
return $data_id;

}

/**
 * Decrypt data using ID# of data and key, plus encryption password 
 *
 * @param int $data_id The ID# of the data to decrypt
 * @param string $password The decryption password, generally the user's password.
 *
 * @return string The decrypted data, or false if not successful.
 */
public function decrypt_user(int $data_id, string $password = ''):string
{ 

    // Get password, if needed
    if ($password == '' && !$password = auth::get_encpass()) { 
        throw new EncryptException('no_session_password');
    }

    // Get key
    $user_type = app::get_area() == 'admin' ? 'admin' : 'user';
    if (!list($key_id, $privkey) = $this->get_key(app::get_userid(), $user_type, 'private', $password)) { 
        throw new EncryptException('no_private_key', app::get_userid(), $user_type);
    }

    // Get keydata
    if (!$keydata = db::get_field("SELECT keydata FROM encrypt_data_keys WHERE data_id = %i AND key_id = %i", $data_id, $key_id)) { 
        return false;
    }

    // Get data
    if (!$data = db::get_field("SELECT data FROM encrypt_data WHERE id = %s", $data_id)) { 
        throw new EncryptException('no_data', 0, '', '', $data_id);
    }

    // Decrypt keydata
    $privkey = openssl_pkey_get_private($privkey);
    openssl_private_decrypt(base64_decode($keydata), $decrypted, $privkey, OPENSSL_PKCS1_OAEP_PADDING);

    // Parse key data
    list($key, $iv) = explode("::", $decrypted, 2);
    $key = base64_decode($key);
    $iv = base64_decode($iv);

    // Decrypt data
    $text = openssl_decrypt($data, app::_config('core:encrypt_cipher'), $key, 0, $iv);

    // Return
    return $text;

}

/**
 * Very basic encryption. 
 *
 * NOTE:  Very insecure, and please treat virtually the same as plain text, 
 * albiet a tiny fraction better. 
 *
 * @param string $data The plain text data to encrypt.
 * @param string $password Optional encryption password to use.
 *
 * @return string The encrypted text
 */
public function encrypt_basic(string $data, string $password = ''):string
{ 

    // Debug
    debug::add(4, "Basic encrypt of data");

    if ($password == '') { $password = app::_config('core:encrypt_password'); }
    $encrypted = openssl_encrypt($data, app::_config('core:encrypt_cipher'), md5($password), 0, app::_config('core:encrypt_iv'));
    return $encrypted;
}

/**
 * Decrypts data that was encrypted via the encrypt_basic() function 
 *
 * @param string $data The encrypted data to decrypt
 * @param string $password Optional decrypption password that was used to encrypt the data.
 */
public function decrypt_basic(string $data, string $password = '')
{ 

    // Debug
    debug::add(4, "Basic decrypt of data");

    // Decrypt
    if ($password == '') { $password = app::_config('core:encrypt_password'); }
    $text = openssl_decrypt($data, app::_config('core:encrypt_cipher'), md5($password), 0, app::_config('core:encrypt_iv'));
    return $text;
}

/**
 * Import public PGP key 
 *
 * @param string $type The user type (user / admin).
 * @param ing $userid The ID# of the user.
 * @param string $public_key The public PGP key
 * @param string $password Optional password of the PGP key, if a private key.
 *
 * @return mixed If unsuccessful, return false.  Otherwise, returns the fingerprint of the PGP key.
 */
public function import_pgp_key(string $type, int $userid, string $public_key, string $password = '')
{ 

    // Encrypt pass, if needed
    if ($password != '') { 
        $password = $this->encrypt_basic($password);
    }

    // Initialize
    if (!function_exists('gnupg_init')) { 
        throw new EncryptException('no_gnupg');
    }
    $pgp = gnupg_init();

    // Import key
    if (!$vars = gnupg_import($pgp, $public_key)) { 
        throw new EncryptException('invalid_pgp_key');
    }

    // Check for row
    if ($row = db::get_row("SELECT * FROM encrypt_pgp_keys WHERE type = %s AND userid = %i", $type, $userid)) { 

        // Update database, if user already exists
        db::update('encrypt_pgp_keys', array(
            'fingerprint' => $vars['fingerprint'],
            'password' => $password, 
        'pgp_key' => $public_key),
        "id = %i", $row['id']);

    /// Add key to database
    } else { 
        db::insert('encrypt_pgp_keys', array(
            'type' => $type,
            'userid' => $userid,
            'fingerprint' => $vars['fingerprint'],
            'password' => $password, 
            'pgp_key' => $public_key)
        );
        $key_id = db::insert_id();
    }

    // Return
    return $vars['fingerprint'];

}

/**
 * Encrypt a PGP message to one or more recipients 
 *
 * @param string $message The plain text message to encrypt.
 * @param array $recipients The recipients to engry message to, formatted in standard Apex format (eg. user:54, admin:1, etc.)
 *
 * @return string The encrypted PGP message
 */
public function encrypt_pgp(string $message, $recipients)
{ 

    // Initialize
    if (!function_exists('gnupg_init')) { 
        throw new EncryptException('no_gnupg');
    }
    $pgp = gnupg_init();
    $recipients = is_array($recipients) ? $recipients : array($recipients);

    // Go through recipients
    foreach ($recipients as $recipient) { 

        // Get key
        list($type, $userid) = explode(":", $recipient, 2);
        if (!$fingerprint = $this->get_pgp_key($type, (int) $userid)) { 
            continue;
        }

        // Add fingerprint
        gnupg_addencryptkey($pgp, $fingerprint);
    }

    // Encrypt message
    $encrypted = gnupg_encrypt($pgp, $message);

    // Return
    return $encrypted;

}

/**
 * Get a PGP key from the database 
 *
 * @param string $type The type of user (user / admin)
 * @param int $userid The ID# of the user
 * @param string $key_type The type of key to return (fingerprint or public_key)
 *
 * @return string The fingerprint or full PGP key of the user
 */
public function get_pgp_key(string $type, int $userid, string $key_type = 'fingerprint')
{ 

    // Get from database
    if (!$row = db::get_row("SELECT * FROM encrypt_pgp_keys WHERE type = %s AND userid = %i", $type, $userid)) { 
        return false;
    }
    $key = $key_type == 'fingerprint' ? $row['fingerprint'] : $row['pgp_key'];

    // Return
    return $key;

}

/**
 * Reimport all PGP keys 
 *
 * Reimport all PGP keys from the database into gnupg on the server.  This is 
 * used when transferring the system to a new server, as all PGP keys in the 
 * database must be imported into gnupg to encrypt messages to them. 
 */
public function reimport_all_pgp_keys()
{ 

    // Go through keys
    $rows = db::query("SELECT * FROM encrypt_pgp_keys ORDER BY id");
    foreach ($rows as $row) { 
        $this->import_pgp_key($row['type'], (int) $row['userid'], $row['pgp_key']);
    }

    // Return
    return true;

}


}

