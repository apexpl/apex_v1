<?php
declare(strict_types = 1);

namespace apex\core\test;

use apex\app;
use apex\svc\db;
use apex\svc\encrypt;
use apex\svc\io;
use apex\app\tests\test;


/**
 * Add any necessary phpUnit test methods into this class.  You may execute 
 * all tests by running:  php apex.php test core 
 */
class test_encrypt extends test
{



/**
 * setUp 
 */
public function setUp():void
{

    // Initialize app
    if (!$app = app::get_instance()) { 
        $app = new app('test');
    }
    app::set_area('public');
    $this->temp_password = 'kd*angk3AigU';

    $this->userid = db::get_field("SELECT id FROM users WHERE username = %s", $_SERVER['apex_test_username']);
    app::set_userid((int) $this->userid);

    $this->data = array();
}

/**
 * Test RSA 
 */
public function test_rsa()
{ 

// Get ID# of demo user
    if (!$userid = db::get_field("SELECT id FROM users WHERE username = %s", $_SERVER['apex_test_username'])) { 
        trigger_error("The username specified in setUp, $_SERVER[apex_test_username] does not exist in the database, hence can not complete encryption unit tests", E_USER_ERROR);
    }
    $userid = (int) $userid;

    // Delete existing key
    db::query("DELETE FROM encrypt_keys WHERE userid = %i AND type = 'user'", $userid);

    // Generate new key-pair
    $key_id = encrypt::generate_rsa_keypair($userid, 'user', $_SERVER['apex_test_password']);
    $this->assertnotfalse($key_id, "Unable to generate RSA key-pair for user ID# $userid");

    // Get public key
    if (!list($chk_id, $public_key) = encrypt::get_key($userid, 'user')) { 
        $this->assertnotfalse(true, "Unable to retrieve public RSA key for user ID# $userid");
    }
    $this->assertequals($chk_id, $key_id, "The RSA public key retrieved does not match the ID# of the key generated");

    // Check for valid key-pair
    if (!preg_match("/-----BEGIN PUBLIC KEY-----\n(.*?)\n-----END PUBLIC KEY-----/si", $public_key, $match)) { 
        $this->asserttrue(false, "The newly generated TSA public key is invalid format");
    }
    $this->assertequals(747, strlen($match[1]), "Newly generated RSA public key is not of the correct length");

    // Get private key
    if (!list($chk_id, $private_key) = encrypt::get_key($userid, 'user', 'private', md5($_SERVER['apex_test_password']))) { 
        $this->assertfalse(true, "Unable to retrieve newly generated RSA private key");
    } else { $this->asserttrue(true); }
    $this->assertequals($chk_id, $key_id, "The ID# of the RSA private key does not match the ID# of the newly generated key");

    // Check for valid key
    if (!preg_match("/-----BEGIN PRIVATE KEY-----\n(.*?)\n-----END PRIVATE KEY-----/si", $private_key, $match)) { 
        $this->asserttrue(false, "Newly generated RSA private key is not of valid format");
    } else { $this->asserttrue(true); }

    // Check langth of private key
    $x = strlen($match[1]);
    if ($x != 3213 && $x != 3217 && $x != 3221) { 
        $this->asserttrue(false, "The newly generated RSA private key is of the wrong length, $x");
    } else { $this->asserttrue(true); }

}

/**
 * Change RSA password 
 */
public function test_change_rsa_password()
{ 

    // Encrypt
    $text = io::generate_random_string(rand(8, 128));
    $data_id = encrypt::encrypt_user($text, array('user:' . $this->userid));

    // Change password
    encrypt::change_rsa_password((int) $this->userid, 'user', md5($_SERVER['apex_test_password']), $this->temp_password);

    // Get private key
    if (!list($chk_id, $private_key) = encrypt::get_key((int) $this->userid, 'user', 'private', md5($this->temp_password))) { 
        $this->assertfalse(true, "Unable to retrive RSA private key after the password was changed");
    } else { $this->asserttrue(true); }

    // Check for valid key
    if (!preg_match("/-----BEGIN PRIVATE KEY-----\n(.*?)\n-----END PRIVATE KEY-----/si", $private_key, $match)) { 
        $this->asserttrue(false, "The RSA private key is not in valid format after changing the password");
    } else { $this->asserttrue(true); }

    // Check langth of private key
    $x = strlen($match[1]);
    if ($x != 3213 && $x != 3217 && $x != 3221) { 
        $this->asserttrue(false, "The RSA private key after changing the password is of invalid length");
    } else { $this->asserttrue(true); }

    // Decrypt
    $chk_text = encrypt::decrypt_user($data_id, md5($this->temp_password));
    if ($chk_text == $text) { 
        $this->asserttrue(true);
    } else { 
        $this->asserttrue(false, "Unable to decrypt data after initially changing the password");
    }
    db::query("DELETE FROM encrypt_data_keys WHERE id = $data_id");

}

/**
 * First pass of two-way encryption and decryption 
 * 
 *    @dataProvider provider_twoway_encrypt 
 */
public function test_twoway_encrypt_firstpass($text)
{ 

    // Encrypt
    $data_id = encrypt::encrypt_user($text, array('user:' . $this->userid));

    // Decrypt
    $chk_text = encrypt::decrypt_user($data_id, md5($this->temp_password));

    // Check decryption
    if ($text == $chk_text) { 
        $this->asserttrue(true);
    } else { 
        $this->asserttrue(false, "Unable to ecnrypt / decrypt user data: $text");
    }

    // Delete data
    db::query("DELETE FROM encrypt_data_keys WHERE data_id = %i", $data_id);

}

/**
 * Provider for two-way encryption 
 */
public function provider_twoway_encrypt()
{ 

    // Get app
    if (!$app = app::get_instance()) { 
        $app = new app('test');
    }

    // Single words
    $vars = array();
    for ($x=1; $x <= 25; $x++) { 
        $vars[] = array(io::generate_random_string(rand(4, 128)));
    }

    // Sentences
    for ($x=0; $x <= 25; $x++) { 

        $z = rand(4, 12); $words = array();
        for ($y = 1; $y <= $z; $y++) { 
            $words[] = io::generate_random_string(rand(6, 64));
        }
        $phrase = implode(" ", $words);
        $vars[] = array($phrase);
    }

    // Return
    return $vars;

}

/**
 * Change the password back to original password 
 */
public function test_revert_rsa_password()
{ 

// Encrypt
    $text = io::generate_random_string(rand(8, 128));
    $data_id = encrypt::encrypt_user($text, array('user:' . $this->userid));

    // Change password
    encrypt::change_rsa_password((int) $this->userid, 'user', md5($this->temp_password), $_SERVER['apex_test_password']);

    // Get private key
    if (!list($chk_id, $private_key) = encrypt::get_key((int) $this->userid, 'user', 'private', md5($_SERVER['apex_test_password']))) { 
        $this->assertfalse(true, "Unable to retrive RSA private key after the password was reverted back to original");
    } else { $this->asserttrue(true); }

    // Check for valid key
    if (!preg_match("/-----BEGIN PRIVATE KEY-----\n(.*?)\n-----END PRIVATE KEY-----/si", $private_key, $match)) { 
        $this->asserttrue(false, "The RSA private key is not in valid format after reverting the password back to original");
    } else { $this->asserttrue(true); }

    // Check langth of private key
    $x = strlen($match[1]);
    if ($x != 3213 && $x != 3217 && $x != 3221) { 
        $this->asserttrue(false, "The RSA private key after reverting the password to original is of invalid length");
    } else { $this->asserttrue(true); }

    // Decrypt
    $chk_text = encrypt::decrypt_user($data_id, md5($_SERVER['apex_test_password']));
    if ($chk_text == $text) { 
        $this->asserttrue(true);
    } else { 
        $this->asserttrue(false, "Unable to decrypt data after reverting back to old password");
    }
    db::query("DELETE FROM encrypt_data_keys WHERE id = $data_id");

}

/**
 * second pass of two-way encryption and decryption 
 *
 *   @dataProvider provider_twoway_encrypt 
 */
public function test_twoway_encrypt_secondpass($text)
{ 

    // Encrypt
    $data_id = encrypt::encrypt_user($text, array('user:' . $this->userid));

    // Decrypt
    $chk_text = encrypt::decrypt_user($data_id, md5($_SERVER['apex_test_password']));

    // Check decryption
    if ($text == $chk_text) { 
        $this->asserttrue(true);
    } else { 
        $this->asserttrue(false, "Unable to ecnrypt / decrypt user data: $text");
    }

    // Delete data
    db::query("DELETE FROM encrypt_data_keys WHERE data_id = %i", $data_id);

}

/**
 * Basic encryption / decryption functions 
 * 
 * @dataProvider provider_twoway_encrypt 
 */
public function test_basic_encrypt($text)
{ 

    // ENcrypt
    $enc_text = encrypt::encrypt_basic($text);

    // Decrypt
    $chk_text = encrypt::decrypt_basic($enc_text);

    // Check
    if ($text == $chk_text) { 
        $this->asserttrue(true);
    } else { 
        $this->asserttrue(false, "Unable to complete basic encrypt / decrypt");
    }

}


}

