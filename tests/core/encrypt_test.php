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

/**
 * Change RSA password - no private key
 */
public function test_change_rsa_password_no_private_key()
{
    $client = new encrypt();
    $this->waitException('Unable to decrypt private RSA key');
    $client->change_rsa_password(8324925, 'user', '', '');

}

/**
 * get_key - No private key
 */
public function test_get_key_no_private_key()
{

    // Get key
    $client = new encrypt();
    $this->waitException('Unable to decrypt private RSA key');
    $client->get_key(8293582, 'user');

}

/**
 * Get key - invalid password
 */
public function test_get_key_invalid_password()
{

    // Initialize
    $client = new encrypt();
    $admin_id = db::get_field("SELECT id FROM admin WHERE username = %s", $_SERVER['apex_admin_username']);

    // Get exception
    $this->waitException('Unable to decrypt private RSA key');
    $client->get_key((int) $admin_id, 'admin', 'private', 'sdgsa');

}

/**
 * Login form 
 */
public function test_login()
{ 

    // Ensure devkit package is installed
    if (!check_package('devkit')) { 
        echo "Unable to run unit tests, as they require the 'devkit' package to be installed.  You may install it by typing: php apex.php install devkit\n";
        exit;
    }

    // Logout
    auth::logout();

    // Login
    $vars = array(
        'username' => $_SERVER['apex_admin_username'], 
        'password' => $_SERVER['apex_admin_password'], 
        'submit' => 'login'
    );

    $html = $this->http_request('/admin/login', 'POST', $vars);
    $this->assertPageTitleContains("Welcome");
}



/**
 * Decrypt user data - no private key
 */
public function test_decrypt_user_no_private_key()
{

    // Encrypt
    $client = new encrypt();
    $data_id = $client->encrypt_user('testing');

    // Get exception
    $this->waitException('Unable to decrypt private RSA key');
    $client->decrypt_user(
    $text = $client->decrypt_user($data_id, 'sdgsdg');

}

/** 
 * Decrypt user - no data key id#
 */
public function test_decrypt_user_no_data_key()
{

    // Encrypt
    $client = new encrypt();
    $ok = $client->decrypt_user(2823827622, $_SERVER['apex_admin_password']);
    $this->assertFalse($ok);

    // Logout
    auth::logout();
    app::set_userid(0);
    app::clear_cookie();

    // Auto-login
    app::set_area('admin');
    $admin_id = db::get_field("SELECT id FROM admin WHERE username = %s", $_SERVER['apex_admin_username']);
    auth::auto_login((int) $admin_id);

    // Decrypt, no session password
    $this->waitException('Unable to determine session');
    $client->decrypt_userid(1);

}

/**
 * Import PGP key
 */
public function test_import_pgp_key()
{

    // Initialize
    $client = new encrypt();
    $admin_id = db::get_field("SELECT id FROM admin WHERE username = %s", $_SERVER['apex_admin_username']);

    // Import valid key
    $public_key = base64_decode('LS0tLS1CRUdJTiBQR1AgUFVCTElDIEtFWSBCTE9DSy0tLS0tCgptUUlOQkZkVzdWY0JFQUQwZTJsQUp3Ym1MS1pJM3QxdVprUUJNMEZiSmlRVlBmOThXOW1uSERFdU9mRk9tYnAwCjFSWW1pL2I3QmtIMTBVRkRTMXZjZXpiWEtCNVM2T2pjWklkL1g1bmN2OWRkRGR6ZUtsdVVFSTRmMmZMNnh2V3oKRnJEOVFqRUtnWWhwaDRoem81QmFEV2sxR3V1amhZZ3g1RUVsY01IbElQVDlZbmhhYUMrRWt4T1d4bmFUVkRnWApNZDhLb25OOWhTUzZHOFovNVpmdEpBTk9BbVFXUmhHUmhCc3pldFNhMEJhYkl4SzVBQWFKTUVsbUtieTFhdlozCm0yY2RYL0RrVjRYbUk3V0xFeHVhWGsvZ2VxNnUvL3Z6UDVGRFpjbkRzYWE0bWo3NHd1amtJNzhoTHJ1Y0IrbGQKSUFrSWU0bGtDclhoTzdNMC9JOUtQRWs2dDZlMkFraFYyWFdpd3pNdTVacE1NNEo0QkI4bzU0bkpQOVd6SkF2cApMdnpPSVpqcSs3aDdkUXVZeTlxdVZteTQybXhpeDNuUUd1QTdCQzFQWUNudEZtRzBlVHg4KytOWU5DK0l2NXBLClZqMDV1MVJ2YmpyRlpJQUVNN1hFbzFmUTVPYTd4VmFubUZtclBTY0xnSlF4MDVkNm80d3BMenlMbWpqWTk0SXcKTTRyOUVvQXVBUGpvM3drMFJIZEw5OE50S3BNTUlTbEMyZkdoYXhNSkk2ZVNlZjB0bUt2aU1pNlRSZndscU1NUQpsRjUveTEwMlVjb0RJM3dmdmlVR3Q4RTdtazZ1aVlKOTVKcm9JZUNnVzJITmZkWmFrc3hKUC9RRmY3a0JrWEhUCkQxU0xIYzl1VUsvN0hkNVJWZ09HTE9hbElib09xT25YalR1YXZsc3VRMWtWeEs5ci93VEZBWTVlcVFBUkFRQUIKdEM5TWFXNTFlQ0JOYVc1MElFbFRUeUJUYVdkdWFXNW5JRXRsZVNBOGNtOXZkRUJzYVc1MWVHMXBiblF1WTI5dApQb2hHQkJNUkNnQUdCUUpiN3Y2bEFBb0pFRDdtZnowUDlBV3lVNXdBbjNHQXViZEtLNkVpMnZKbFdVZCtSWktGCjVhc2VBS0NGYmdNSmtDWXUvRk1jK2lrMTNTd1lEdEpOQTRoZUJCQVJDQUFHQlFKWUtidk5BQW9KRUtBdkppeTMKWVh4cHR6TUJBTEd0YmRXTkh2KzdlTlZCb1ViUHlZN1YxTWpvYnYwcjN5UlJJZ3YzTW5ZU0FRQ2UzSEtsMzlTOQo2VzBrU3BsOENZYjZ6WnJQUG1DRFgrV1VqV0JZT2dKN1BJa0JIQVFRQVFJQUJnVUNWNVpEVEFBS0NSQkpqNXJaCkN1YVNUai94Qi85bGhxZUhOZmUxUHI0TmpuVGluMTEyeDNETm5LUFlSTURMZlRZSGR5SlZITEs3N1dReXNjZ2QKTm12bWE1NHB1SlNuSldHcGMyejFJL2ZzWldvT3NEYzdzUFJHREdaUVNjOG1RR09OUDVUODhGb3dZNXlMTEg1egp0a1RYbzYwYzV1UFVMSjN3MkdGUW9wd1Q1RHJIOGgrS3lTcHl2M3VPamRRaWtiS05NNUl3MkdIeUpKaEJxcWYrCjZIdGEyS2F6RFJtK0phU2NPNThoR3FLMjdVQ3ZPeVo0cytvZFh2WDd1b3AzR3ZhdzlCbmhjdy9naEFxZXdPNzUKcm0rOG4vdGMxREZBVnkyZ3M3Q2VhcSs3bHY2Q1BsVS9PamJ2ZmdkN2xsNWluQXEvZ2VzRGVtUkxIMGgyMld4TApUT2RlVFFDZjBvdnlBSk9NOEI4MXRmWStlVjAwTmNDeWlRRWNCQkFCQWdBR0JRSllTOTEvQUFvSkVQdHBkUGhDCnRjbjU3VzRIL2owbU04YkxNblNrSXhvZGo0WU5nekY3ZmQrU1lBV2c4UE9YYkJFaGIxUXdiZkxIcGxOV0xINmgKeG5vR1lldmQ5dXdIZ256Mmx1MDhVeVdZaDZmNkRoVDc3QUI5QW8rZFFQTUtmSXNFbzFyaUF6T25oWWFGQjJXbQpjNXFjRERISitJdGhEL2tmSS8wbEZlZG1NZXlxMHJJNlJSN1FpaVpLUUxWendTYU01YlVlckZHVS9qdmxGTDBFCnlRQXNQV2JNKzRVaWZhSVkrSXNydDhrWGVMYThsY3J1NTRuWUVIWnc5cmlWWmZFU2xsWlh4d3B5L0FNQURvN1QKL1RUMjRrNlNJTHdiMDgwTWJDaEhSdUIzRGFURDZBU1N0cC85bVhtOVNRTk9aa1UrZTVDZXc4aFVEWDJZalFHawppQnJJbXhwZTE1clRmRmxYb2pISU9vOHh4UzdIZVZXSkFSd0VFZ0VJQUFZRkFsZGpGbVlBQ2drUWxPa3QrU3FxClhEdFVnUWY3QkNxeDZoZ25YbWpWZFdkSHU2UEd4TnFUamZ6bmdtNStNOCtmSGZYQjl5bmlEQWNtT3FTOHcvbm0KVFdmbXUraEpNUUpDUXkyajNLajhhNHhydzRyQk5jTHg4SGJ4UGVMNm44RzJEalVGNUhOY2pCaWVjMmNOaTJQUwp0b0dVVFo3MEtkdUN0cDdoNjBURldLNGVkcVNCMnFZY1VZKzhJKzF4YVRaRTNRYWtmYnByb1hPUUxnS0hEbWJKCmpoTUN5RXdaek1UY1VDeE5KaFA3WTdzWVRnUnhJS05WY2ZqMTZlMC9tZUs1bG1oTlQ0bWhsUUcwZURiNnFWSjkKYWxmQmp4b085Zjd1Q1RzYUpJdmJKZndQOFVuc2lkRDVCaStLSFc2YUg3c29xZkpwSWlKbCtqaHFweElwckZTNwpnamxaRzc0K3lOK1ozM2xuZGlDNVFYZklBS3lqOW9rQk13UVFBUWdBSFJZaEJEbE10YklCd3BMUEJxZUc2K3FICmRhcHFwZUhLQlFKYldyemtBQW9KRU9xSGRhcHFwZUhLNDZBSUFJblg5TERhZ1hnSVZwQ1ZKSndWNlpoVFM1V1AKSC91N3dCV0x1M2NFekJLVXUrNjNEYWJpTitvLzI4NHl0T1V0WjErMTB4eUc2ZjhVSFN2Rkd4ck9LV3EzVlVnMgpFM0pRMHdJR0VoZWthSk5ZSWtIRkJZQ2JQejNkbkc3Qjh3eFlyVVliaG16VUZJenE0aUJtcDUrTEFaU1duZzM4ClFpTTR0aVdURFNyUWMvV0xWeWN3ZEJVOTZDQVliT2gvYTVyTVA3MFg1clhLMmpHWjVqdDFBaFZsTTJRSkVlM2cKRVg1dHRLblJTb1d6ZHpSMmlMMmh3SVdUZll6R3g5UEVING42N0MrZ24rK09ZLzZ2QVlCMHhaQ0pXT2RHbHBzRApDbUZyRjNHS2tnb3h2ZmJ2NVZ6dE1ZYmdBakFyMm5wbXU3eG8xSlRTc3lPQnJKckFLUnI1a0VieXEyMkpBVE1FCkVBRUlBQjBXSVFSQXM3TE9nVFkyS2xjb1dZRHBPS2lmWllYRzZRVUNXOHliUVFBS0NSRHBPS2lmWllYRzZTVloKQi85ZmY3b1pOOGRuZGhmS0ZoRW1PdUtXaCtWTG9VVTFqUGdwRmFXWEFldXBiM1lMUVFqenc3MEQ0U2x3Y3lQSwoxUEFoZWU0dFB0QkdNNmVDT3R5SDMyQi85Z0ZSbVg2RHdwcFYvWTAxMEFLK0wxcG1xdlJEYzc5NDNiYVNNWWZ5CitMRVhXcTFvUm9xckRIN3RUbHN3WnJ3dWxGaDYzQUY0YmtvT2svcHpLSVFjb2JhNVdhMFEwZUYyU1FWN2kzd3IKOUdlbisrejlvMnQzWVlwbmFNb21tN2I4UXJidlY0blFaMXNYdTk5eHBrR0IzaCtySUpHL2tyNkxETW5rWmRDQwp5QVR2TVdqQSt2azdqUlphTWQrbjNVLzU5K2IzQmczWUxtRFljMkczY1pXNWxCdHNjdVMrQjJtUWFLQmQzMkY3Ckl4MGZKL1FIMFM5Qlo2bHBENWltY0RtM2lRRXpCQkFCQ0FBZEZpRUVTVE5CMzJlTzZxT2lsaEZKMUE1QVltNnMKVnhzRkFsd0NmaGNBQ2drUTFBNUFZbTZzVnh2RnF3Z0EzeFJtUFBKY2RVUGxpbUhxZSs5UjRtb3FPR3JMcmI4UAp5ZktSTzFQWXIwa1RSMExPZGphQnpJY2wyMVZKMEhXUmlIK2FVUFNDQ3M1Qkw5VFpIdkRrKzYwUVorRTJrRDRECkpHYm9Tb0JwSW9sTTd4M1FKVGQwQWFXTzVpT1l1N2N2OFRuZmsvaytEdXplUG1JR2s1NGs2amZsTnFPMHlONW0KT2ZzYVlmWExrcG11RkV4b1B3dUF5UHFjSWtnNm9oMWNvd0JCM0dKU1FXb1I3djJwbnVINnRDbTFnSlhjRFR3dAo3S0hrNTJ6TnVYbHROQ0VpQnhnMk1mTlZYSmVWdnR5ajNXdUliN1VUZ3VsNldpTXU1aGtXMDVmZlkvYVNBYlRpCmxKeUFPRjlwRmxxRk9RZjZYK0FsMkJHQUVsaVJjVkwyVEd1WHJVdDY4QUZXU0ZZTUo4SHVFNGtCTXdRUUFRZ0EKSFJZaEJGSGtRRU1oTXlvbTJqVGdIU0w1MkY1MjczNURCUUpiNkk3akFBb0pFQ0w1MkY1MjczNUQ2cjRJQUpYNAprOHFsRFZwcFp4cEV4NTRQelBDTTNRMlkrTllZcWEyaGt2WXpJV3c2eDNpeTJtUFBTbjFOR2FkdTN1R2RqWGdTCk5uVGRydlNxRUdJRzBBQkVXOUxCdlNNS2hWdHNQeXRqdUNxZGNwQmJXWlRaZnBCV1hDSDM1QzRkeEtjWktmNXMKNFJodXhWeTNOS0MwcVNzNmQ3aU44TThlcklpZVBBNzZMSU5iT0c3SEIvaldpWE45UFk3dEFQRDJ1dDgxc0VWTgo0Y25oTE56WGVSY0p6RDZ0OU1NbXA1OWt0WG9Nd0ljVW4xVjRwNUhOZks5YnRCZzdVekZLaVBvczdQekVjellRCnhXaVVqZVhBSWF5NkttSjdMbXcrbGNtblNPWmZQemZMdldVWCtpOU9WUnFJTHdqTS9Cem1PNEFBeEpDVlZVYnYKb2dqaTVHQ3J1Z0JvWjNuRllSaUpBVE1FRUFFSUFCMFdJUVI1dCt3Q01KYTlUL2swaGFtRUlXZENJZjBBS1FVQwpYR1pkVWdBS0NSQ0VJV2RDSWYwQUthbFlCLzBaNWU2bk5kMHlHNzZydjJpYXdkVEJEQVhGdC9SSDdZK2JkTE8wCmE3TldmbWlqTW5RZ1ZjUWZVMzkxdTJIdEJLcXVYc0QwUDRpTUF4UmJkOVdsV0ZhQ3k3YURWVUd3UnJzV2ptUGMKRXZNNWNJUi9tdXRsbXpTMW1zZlJXeGpkK3hjM2h0WmRTTEdNSWRFNHZlOGlKanROTlZRR1pocjltTEJwQkoyVgpYaS9NOHQ0MkVvMjlUUENYZElDMjA1Rk0za2Q3UU5UdDdKRXdiNlVRNUtkNjdLM3hOazVia0M2UlQ1RlpvVEhjCk14RU0rOTZuSG40VGlZQzkzQy9IS1ZOcW9ESmcwSE1vMHhXTENHMTUzK0ZsN2c2K3pydW5YdTU4Z1VZTFU4N2wKUXM5RVJ4QWZ3cXEycjE3ZzhDdGdBVVhsbHU4UjhWcDhwcm5DZ2lJSk1WVDU0Tm9saVFFekJCQUJDQUFkRmlFRQpndVc1NytxQ1ZWV3VnWmNRQ2hmSVgyVVNiWklGQWxyOUEzMEFDZ2tRQ2hmSVgyVVNiWkltS2dmOUdDZkd3MmlqCm12UVlRcTNmam9NQ1BTM2FRdENRcUlmZlFyazFCUnlnU0xRdDN6bjZaU2h0Zk5oeWRZZERaQ0hJZFkwSzk1eHQKYUd0STdXVHRBOWNiZDBuczc1TlU5R2RxM0RTenpwYisyNE9qdFJhRFhCdDdiWGZzdWE4MmlKMEJXeTEvSlJnbwo3d09HWFA2cWlhaEQ3ZVRmVnMrU0E0UFJpZjd6MnlMTS9qVFpBUXdVOTVYU3IxdWdmeEg4enhLOVNkRUd5akQyCjhQSjl1WHlkbWI2eWhkUjM2bnBCeEFER1lpR2dmRW5UZXZPK3ZOMWNwWW5GTDBDNytFN3ZTckJDcGtWckQ1MHEKSjVHL2ZBb3hSaExST1RpaUYyenJyYkZzU0ZrZk92VSt6eExlbjAxYlIwajh0N3FsRG1aK0ViVGgwM2tpbG9MaQp5RVZvT25temhxVm9sSWtCTXdRUUFRZ0FIUlloQktlY21BZXk4OUhEQWtqRHo4ZkhZczM0ZnhEVEJRSmJCZE5JCkFBb0pFTWZIWXMzNGZ4RFQvSjhJQUpwS09FQ1lYRE5nNTVuNE5UNnB2aFdkaWRBeW1DQUNnNjZFNmw2TVQwTzQKSHJzTmNsWld6Lzl5L1dnaW52dDMxc1VSdnpTUjdIa1FtNGdoUFZXaloxbDMwM2JxNGFFRDV0b09pY1JPNWlTcwpZTWpmNmozY1RrdENQZ0Z2TkJRTW9vNlNCYlAxTFltZVBHVTZCTGpMQkVFdmN1TXFLNlBVK0NWcElucGt3eG9RCkxkTU85SCtERHZGc2hyNE5mWkQzMmNheVR1UGpJNlBQYVFmbHkyVXRoMEFqTlNxL1JDWk9veEgvMVVLV28zUHEKakZDcmUxMXc3b2I5aTdvTVlzTWpCSXdZNXU4Ykx4bXp2cGRkUXZ3Q0FEOE83Nm8yNk4zZmkzMWRzeVNHVUN5NwpjOWhmbE9Jd3NxWWVBNFBwU0hJcXVqaStER2d1TlF3Yll4YjlwbHk4TGFlSkFUTUVFQUVJQUIwV0lRUzUyQVJYCmQxQnU0b1VEcVVuSUhrMjlVME54eEFVQ1d4c2lyd0FLQ1JESUhrMjlVME54eFBncEIvNDJqbWxTQ0drSzROeVkKKzdvYkxVRlZncURKd1I5Smx4S2Vmc1AvZ2ZVU3FCdk1FNmZ0VC9BUzJidVBBOHFwV1hmUzJuSVpXYStRdE5Ncwo0SzM3aHl5dkZ6ZmtWS1E0U1ZLTjhna252cHFlWUlVN2Y0UkcySnMzTTdFUFlRUDFtU25rdElhUGRQTWJlQ2xtCjFjWXZRcU9pYm9mRFU0OXVmd2ZESkVCaHo1ellFcEVnaWc1VGkvQ0UyTlhTTjlvM0RUbllkdVZqVTRwUmRjSS8KaGg2aTlwT0JlN2l5RGliRU1qeWRJZS9kVmswYUlTV1J0L2tnWGw4b20wcFZDTkNiZ2ozZTd4Z3IvY1pUWmtWQQpQNXhKZFc2ZkFvSDVnaXQxNWlML3JlMzhTSy9aaUZvVXVYUmtxSHFURlVpbWUvc25pWFdsRkp1elNjNlBNRnJCCktsenRmMyswaVFFekJCQUJDQUFkRmlFRTVMbnFaNUN5bDVFWVhNSzYwQy9UOVNkUzUwOEZBbHZNbGhRQUNna1EKMEMvVDlTZFM1MDkydGdnQXIycGxORGxrRGs3UVlleE9GaVYrOEc1dHJCZWNFbHZVb0ZLdzYvQ0krbGVzdlV2Ywp5RnFhN2Y1bG9oRkVIQk5SNkVoT0kzTTFoRGlsZVZZR2NGbVEwcEE3RGtFNFZsTE9zMTR4UUxRYXRiTU5XMnBUCkFIWW1seVRjSEJKUU9EOFBZaW1ZSWRxNUJpN3VsZkIvbWhlSFRhNkJmRUlBOWRodVBKRHQvTkRIbmZhL21ZZ0kKYU5wR3B6UEl2TDdUa1dUaFR3c1Bwc281QjI5Mno5Mm42RWdaZzRuQ0hDaHNtSiszNmlPUXNoRmQrRk1oTHJRcAprSFRQREt2TkUwaXVST01vWWZCeFkyYVdVSWczbEZMZG01KzlzVWlMZ3dQM0hvcGJyOUQ4RXkzYUdIMVlMMEFICmMwM3BiaDgzTjV2OFA3akVKMGFJVnBQKzJlSFhTdlNkUnQrMFU0a0JNd1FRQVFnQUhSWWhCUFd0dVhqbFlrUEEKcjVRd0RUVUtaSnZOdWZ2S0JRSmJvWWQ2QUFvSkVEVUtaSnZOdWZ2S2FRSUlBSlFvak5MQUhndHVyb2FFSnM5RgpJTlIxdXlWOUROU3l3ci8vc2J6RE1XVzhSZ3NHaHVWTk9INGkrUGFhZ3UzQWZLd0EvWXhKcnl2ZkhzNmFUdFJ0ClhrMVJkMXAvWW9ZRDVUSit3M214WXFhR2duNUordTU1Q2VmcTh1Z3lhTXZCYWZWeFhNZnQvWFdncjh4V2QyMVQKU1RaUlZUZ1pPYnJKS04yZzBkaVU2MGtjV0duY29iTDVwVm91TzZkdEx1eVFmejlsYk1VOVlEVlRtdEdZeC9VMwp4SEovT25qWldVcDVCN3VmRitFR2J2OWw4Qm1nZ1FJclJub25ESk9sMW1ETHdIbExIYytFeEZyUTlFbVRlczJ1CkJ2aUhhc3g5dlArd2VtMTIvaWptak9zUGRIbnI3Zys2VExzOTEyWEFBalAybFk2aGF2T2JMVlozSHpWcTFzQ1MKYW1HSkFad0VFQUVLQUFZRkFsZU1JZndBQ2drUXNaN1JxSXBJRWRWRjJRditNWUlpUVRPVWk4bEVHSjNVVzlDUwpSVTlvK0NZTmlKQjdLaDU3UWwrcWVReTB3YTFCa3g0UENtc2pIaUtDb0g1cEUzY1VVTHgvWlZCNmQ2YjgxOXdICkFnMHZNUFduTkhPeHM0dC9iYUc3OU4wdGNKaFNXcUhCZ1NwcVVjSWtZbEk3U1pnZGtEUURPOGpST0NydFU5anAKTmVvNkRub1ZTaE9IWm5LczZKQ1NhbWxkZ2trQ2twMCtWYytLbkhyU0I5NWJia2c4MzJWMUdiS2paWnMxQnhERApGcVVuWUFxRkx4aTdyMkhpR0lTc210emdsRTFDaTU2dURsQWRiRmRScjdhbExrdWhFTTUxZFBpeXRUQndVclhICnh1Qk9HRXRqSGFEV1pXUU5nNS8zaUIvSytVczBuYVRzeGhwS2x4NDJjYkwyQWhYQ1V5ZTBKdHFnM3dmYlFGSkwKd2oxSXFodlF2d2Q5SVhtbVJxMVFjNFEvb2F1bVMrUWxYZ1RNWnZrb2VxV2RBU2c2QjNWdGJteWg2RmorbDRlQQphSk4wTVBwTjVWN0JlWitRM3cwanZBSHVmbnFwbzJmVEpvbERxRFhMczBtNHlCV2xGT1VUV0xTR2J2SmhyNmxmCnB1ckFocUF4dmxIYk9ZSCtqamZSUE9MZy9LUm9HRHBXNVg2MW54Zy9VaHNlaVFJY0JCQUJBZ0FHQlFKWGg3SVUKQUFvSkVMaEQ1djJOTi8zcEw3Y1AvaWVGUkxPMjNwSjFVYTM0enZoS2MvNWVETmsrTGhuYUZ4K01qdklNTVlHaAp1RWUwSWt0V2R0SFVPd3A1THM5aU5zRkFDTktIQUNJNS9lVlJvSDBHZnJPZGZWWW1aQlVhUXJkUXRXejB6bVFKCjZLQnNRUnFpcUYwYjlndEcvRllwVHpnMVN5VGtFSDZ0UWRkeS9qa2tpdENTT1VKMlRJbStIZlF1Y1ZaUFUrUnAKV3A2V1NMNVRtNFRyQkQzZk5xYmhTZGF2OS91ZzZpRmlXMmJYTWduT0Ntd2xFdnRvNE54d3lwcWIwUC9VdXlleQpMaE4xN0VOQThZaFBPRlpwdXo3V1pWTHB1cUVCZjlvNWU5cXhHWkJQMHBTUDhTdWtpM3dTdjN3UWNqRVNtSjdnCmRvbFc0MHpJMWtOZUNsMnhoMDVzVmRMNGtaRjlIeDJiMHo5MlBtQURLL3RkVVpTYnhZWU02ME95Y3EzM2F5NVAKUTBGNzg3QlVKVkdMNGdkSjBlbnN4bE82OXNOVGlOSzJvUmYxT281OXZZUXpwM0M1TFhEVkdHUVBiQnlXbG1IVQpIaWtHcnZ4UmZPcks1ZW9vME9BRDZHcTRWbUhkcDlnMXdYTXE0ZUhEcy9yS1pmdW5xN3BLL05TYlFKSzFXTjlyCllUYnBsL2pXY1hpNS9JT2RwRnVUcGQvTXp2bjkrcUFJWitza2FDRzMzM3g3dTczejUrTHY0OWFSbnBieUhrQ1YKazFRTnhEV3hUdUxGOHdpV0JXamsvNmRXTXZNdFlISzFSQ0hYUUJnSUU4NXg2dnV4ckt2WmlxRldCRDBZTHZFWQpQK0N5ZWdlSytRNFc4WG9wZ1V6OFptL2lxZHNDZXAwblBjRVNCK3VKSmltRGYyb01CbkxpdEVJRUh1MnhCZEVnCmlRSWNCQkFCQWdBR0JRSlgxQm8rQUFvSkVEZFluN3JrMml2RDBCc1AvanYzOCs0cHdNUi96ZUZ0UlU1cWNaTXAKSk5LZFpNWlB3YVlsY0xxYkxnRG5FMlBmRnVhYmJrQlRzZDFaWU1CR3VHQlY0aERlU2lJdGRYK3M0b09MYUlPTQpUNUIvSHIwcjNsM1RwSnNDUFFLTDJPblpTMFVwcU9aRjNtZitKUjZlU0UrVis5SG9xNGwyemZOKzZDaWhLM3RPCmZOb1h2TkRwVXFjbDNCa0VzaE5yeHhJclNlOEdUZ2NESlZCc1NhMVpwVmNTbTFCNlRGcW5EcEJGays0dEFvTEIKbStVTHFySGNkU1oyUm5OVU5hVlp2c3hGWk4vZWQ3ejBrRmpBZnUyVlZ0eHR2bERQeVJEbzQzUU5yTjRSZWdxNApzYzgxMGNoeXpOZ3JrRmVCSGk1TXpTS2U1Z1UzN0VOTllVQk4zQ3RJSlV6TU14QlhKUys1aWM3ZmJLMnl4ZmdPCkMzRkthUVoyZE9XZDUzYWNZRTVCTkYzeCtHcWNFZVl0UXBaTnpaNWtDWjVLcDVyQzhmdmxZR2I1N1VzRjNiWUcKUkNXQ0dJbzE2b29nOXFrYlE1bWJKRklhRCtyeVZNZ1hBeERPaFpXNjFzSzZaOEZuamNkaVFhdkhLMDNRM01HcwpFSzA5cWpid0h1R3dLVGZNNG5ibEQ5cE1ncTVzOThyY2dWN1pkWjN2a0RIMWdGb2UxTGl5SVlac3VDM3dGbWFNClE2NDNSekFvREgyMjdaTWx0cGE1MHM1QnFVdFJ2bWUvZ2hOTzNrSHRmdTNRNldCR2gyVGdHOHVyZER6ZWRiaGYKSDlKZlAvSVJ3ck8vc291SmFDYmNGdTVpbFVrVlRka2hHU2k4WHJIL1k2OFBYSEZYdk52elVnOS9tb3ZYOUI2dwpWc3ZSZ25hR3UxVHIwKyt5TzZzNmlRSWNCQkFCQWdBR0JRSlo0VEVzQUFvSkVNSUtHUkZCc1lhT1dDMFAvUnQyCnpzd1hrREVpV1V1VTZ4LzlBN292VFJGdStnQTBVNG4ra1hvZGpKQklDRUZma2hYVjg4Ymdpckh3UFlaY2QrU2gKQm0yNk94dnQ0Z3ZaSTlHMEVzcWptNUJaVmlhdjVXZnJaL1lkVGhwVzZEMWRqbjBtOVhCQjVGczZFZFR3QTdYeApNQldmeUVQaWpoOWxEb1ZFRWJFUFVCSkRXQUNmc2ovQlQ0Um9hZng0eXdVWXRaanpYV0FMSFIxRWJ2QzBGbFhPCnd6T1R3R0dCYVVUckFVUC95ZVhBU2FaT3JXa3Y5NWRXZi9ZdnhtUTFFV280YU1rbW1pL1hFTW5GMmQ2VjYyU2cKazc3cldvd1pxcUorNWJadWJNT2hWaTRlMjJEQ3UyZjNiSjZYZTgzeHFFV3dZcjJEcmRBdjNDQzBLK3dybFo0NApNM1h4aTZxZG5DYzBpaGVtS2hPMlNkaHg1bTdQdkNBQmY4ZmJCVVVZL0J3RlhJYUFJaWNqTzlOSzlCQnRmVmZOCkJpbi9JNk5MWlllMFEvU3VjdlNkeWYwREwxb05OSW1BV1JqT1dINFJ4Nk9LalhDZTY0N3hRS1hZcnArTzRoRkIKc3FvZ241Z0c1dUgxUExFa2dURlpZOXRZOUkzUk1KUjg4Z2hhaldSZzRUNkxqQWY1SnlGaDdCaTVtQjhTbEIyaQpaNHp5SnJEaHA0eENUY25WOWQ2Q29jVG55ZStDWFh6d3Z0QkZUQVcrQjg4L01QVWhlbU45aEpEbGVJUzBiZ1VnCjQ2TUdscmJUTytKTGFmYjJoTkxVTS9ldHNHMDJmMUJ6NWNnWTFGQlIvRFppVDhSWC9hZGJHd1l5SDNnUytxekgKbWEyamhLbDc4SVZZN2ZGbVlwVXVKVGFRT2R0alJvT25sTmxuWC9mamlRSWNCQkFCQ0FBR0JRSlhXMm05QUFvSgpFR01nL3E5YzFmMi83Z3dRQU5Lcm13V08yaWtOZDA5RENzWmNmK28vQWJUeTJ4WWR0VVo4MVpLYnRDSHdadDhzCkJNcVcwbGlvYmFvekgxY0hZWVpUNkJkYlRQd0c1NUg1WWFvQXZDZ0xjY1VTTWh0NStNOXVHLytCV2NJdm1BVm0KOEs5OXhHYnV6MGo3dFVWZ3hNVVpoYXF0N2lFcDdFTHZhNS9CbjhHVzFGaFdBbjJmZkl5eFVGNGRCbytEcEx3cAp0T2IxQm9Kbm1PdWttU0szSkErajdNWUVsU1E2U0w4YWo2RHRCYlVsUXdGcFJWRlRScENVMWNkaHd0bC90TFp1CjNFbHRHKzloWTdLTUNXcXUxTytiTzJtVHdFYzdST3llNHhHMElVMnJwSzU4L2tlSHBHVFpKSlNPRk5VcGRUaDUKaTY4T0lubitDYi81aTVUS3VqcUU4ejhvd2kxSjFHMVdUUGhqMzJJdWlOdDU0VFZ2VUk4dHppODhBQk1ITjFVcwptRThqbWhWYTM2Y29HQVVVbmpQQzhDUzk5N3ZNQWRrWjdpNFRzMkNLZ29zSmxZRU9Ra1BGYlVINjZRWUxuK0p2Cm1PMEhKRmhTKytXVitBOWM1MjlOSkdEZHJHeDNoTlRPTGhnYXJSZzBxVmd1b01zcmxsdGFpRkkvS3pRbWc1SWkKZTllMHZjaXNpeXp4Y1VQZHJwRlBIMlcxWUpMaVJVM25mS0hHbXBPU1NCRXNjdllLUFpHWi9Wb091bGRvdFNrbwovdlh6amYxaHA5S2FKR2N5dTJKTlRSYTlGK3NEUUNnUkFQSHpIRjBPVHlKUGgvclhCR0p1WDZhOEhWM0Y0K3UvCk5TYkYzNnpXZ0p2R1NwQ0NKblVMTlA4RUJJMDJZY3NpbU5NRUFtQTNnaVdZYlpZS3lzdTJna0J4M1Nyb2lRSWMKQkJBQkNBQUdCUUpYWGVxNUFBb0pFQThSc2VpTjFibkNLbFlQL1I1YlR4dTBzREdvVmh1OHRUdFJvMXBvTjEvMwpid2hmY0xoVU51cUZ4K1I0T0VhdjBOcVpQUk9KRi9EWUpvZ0hrd05ScWhzbmdaMEh3d2dMUndRemRuTzhWekloCmlTT21HZVYwU28yYnpDbDBuY25qQnRoSjBvMzZlckVVQ2ZUY0w5bVVZaTlKNXRBbHY2bXJ5dno4QUFNYXdNbloKMnlvc0JnNVcwZXUzWGFFUkhYNlhmMnFHVVd2eFVUMkduK2d0QVo0TXdpOWhlU0VMVDgwajdmSmtkYmdOMHlUcAp1anJnUzJZQlNHazh4V0pTRTR1OE5Wb25JS3BGT0xKMXROZ3VWeVpQbEYyZXllbHN5K0JxMjFrUFpPVzNhRkhMCmlYVEhVcTlVSk42VTZwajIvelhVNFBLZjYxc1p5dUpucXlNZzZiZEk0NUhSSXVCUW1Vb2o5TXNEdzdBeFlsaysKTll3alJXTVBxM0RxTG93R2xONGNpRG5SUHhLOTNoRGxpQnBRQS9SREpzUHhxN3R6ZlNPUVVGY2NVazlNeDdFMgo1V3FoYnZ2cFJWR2lrNmxGTkpJL2lXNzJyczh1UFZUalZTaUxKMURUMUhOZXVsOUlLQ3NNTUVvOWpGcFVTK3VNCkxON0QwQXNReGxsRE80QUxwS3NmUXdwTjZlckRWenhBM1kvbjkrczl0YW1Mb3hrTzBhVUdBM0NvanJsM0FwckMKUGV6SjhZZllVRFlkenFVR0pzQlg4MHVHMFV5Y0M4enBiTWdGQ1p3bDdGTXpjS2VVS2s4ZnJmejNqZGh4QnJ4WAo2RG9Gd3VQOHYrbDBad1UvbThuQ2RYSE5xTDBrQnFzcnBTenllZW9aM1hUTlFtZ2dLa1lrRFRjMTN1bUJqM2Q4CldxNzl4djIzckRRd3lQbWVpUUljQkJBQkNBQUdCUUpaWnBoaEFBb0pFQ3ZvbzYwT0lhMmRIbUlQL1I2WnY5cGEKUXU2SHl6UENNVURrcGNXY0taSnhQS09GTGdHZWkvQzNnd0tjUllOakdBTk51NkZOWHJEMHRGYUpSSmxvcWk3RgoyT3dsVU5tYm0rTUhIUlBZNlRMWGRyYklGRUlERTlSRXpsb1ZDUFpDTHlXWE9UeUVHZTJrcHhneUFmZXFscEcrCnlZNktQWjhzWnl4S0VkeGtscEluQUx6U3BpZFpTV0pTVitiVU1jV211NGd1ZHZocWVVUkpWcTk0NEl1a05NYi8KcUM1bTV3eE9NbXpKa3JEVlZCYkVEWWl3SnV3QW5TOFBDc0tsZUxNQ2FKakhBOVpJaVJBVDFZbkVTaFBBUHdXUwpxbjhzdGErUXZsejlNSjJzbzhQMXRlVDVJL285dFoyTHhBS2VIOVdDYVBZQXVWaENXRDZoaFZYaEVKbWloaW11CkRMRmVIQzlwUklZa2VwRGE2QlhCcDUveFV1eCtkTzBZVSt3R1dNSEl5TU5DaEJzaGNER2dLdno2elo4Vk05emEKUnByZlgyQlhiTjQ2elRwZHFsdi9rWEtGSDA3enNtWmI1T09GYXN2SnJ6Zkh4UjVTVHBYSW53d3cyQncreldrTwpMcjZPME1DN2pkSlVFNmJJTlZxMllHSTd4Z2JKV2RTeC9UcFdaZXc2cmVabGVpVS9iYnlnZk8vZ2VqTkIxWVZYCi9vNVR3WG9jMG54NElQUklCRTloMHUxTjF4blB0d1BrQmptd09iUENESHN1QWprWkkxV2QwdUtpV0ZZN3llOWwKU2VNNGR6UWVTbU5sTWZ4NXNieXM5Ymh2SXFmV09YSG1mbWUxRjJZVzQ1WEJYK2lGc0cxMWhXazdhbTEzQThKagpXTUFpa0wyQjQweHQvMHdncWN4YThnQU0xM0FKY05FWUNNZXppUUljQkJBQkNnQUdCUUpYV3NrT0FBb0pFSGg3ClE5MGhMVUd6bVY0UC9peThkK242V2FTRGFpcUw4YnQ5RmVaa3F6VUJnZUowR2JvcFh0Qm9SNG1KYXh4Z2gram4KQXNwMHhBSzVaS252ZGlCeGpCTldlcWVhZjBQb1dOMnBpcENpTWJBc281TUZPdnJodUl6TjFUZlpKODNKa1o3eApvKzlzSExmdkN3VDlDY3NXSFJmUXpocUR0bEdJVGxzSDE0cHlRa1Z5cGlMa09Mb1NrQlBjK1FOOHNQSnFSRkVFCmNobUFuYVc2TEhySGFtZGlPNHNIaDZSYy92RlZmZEMvUFhGTUxuQmkyKzJiaG1wTWNyczNoUTVQYXYycXh2MnIKOWR1aUFlSTVFUlJzZVd4V1d0RzQvZFhKVkQyZHh4YWEwTy9Vb1E0SDZTdTZzbUtXL3ZWa2IwcU5qai9OR2kzYwpERmcwQXNUajVPanZ0M3lpT1hITU1CRHMzM1I3SThvK3BJTElLbGtFSEdLWUtjMWMvVUFMYkdqSWRXRHhQRHVyCmhtNkYrQW54aEdYZ2dnVlI3d2hwR1E1Tk5xVVJ2N0FCRjZJQnNHcDgrblRYeWRJR2lTSUNvUkU3d0JVVy9tWkEKTk5mcHF5RTJ5NW40blhObFlsVDh2UzgySEtlZVcwS1dFZVpxZVJiU09acXlVTWtHcFpBVHBKdXUvM1FiTDNmUQpaay9zTUtMbTY2M3kzcVIvaUR1NWJKanlUREFFWXBxL2lnb1E3Z1lmdTVsRHlDSm5KSGdyam96VnNsYStJNzNTCmlrdlpzYWoyWVUrMUw3aUJpOW5ydkxhZmpZUlFCdkxUaFIrdjBTYWtaLzIweUlkR0xJRWxLVW85YU1yQzR3aVMKb2NyeVh1NGJlMUpaamwvNGRNMkVwUTRTZExqY2UwaE9wcjBBczBhNDI3OTVRS0xsdkZySFFEMlNpUUljQkJBQgpDZ0FHQlFKWGZkU2tBQW9KRU9nTGM2ZVZMSk5nTlBvUC9SREVuaE5Nd0lFL2xoRXFva1RjMDhGRmR6OTFOekJOCmhzdjhKQkQ4UE40MVdIOERCZ0Q5OUd2THAzSE90U3p2K3c0WSs5Ky8xdStHdjJ5MklKdlBVTVNBcTJ0MEhxQksKWEVsSHdwYjF5dGRmUXE3d3BWcVUrWVhHSjU2T01wQ0dLQWJycXEwbndKMVhMOWdaUzU2K08yVTR4bldIbE02eAo5ZWh1amJ2NS83bXFhU2xvTTNZTDY0SnBLYjZIYmJ1REdwVW1Hd2N4UGRmb1hnbkRybjFXem15NWFkeEZNdUZTCjhSYm1lbzdJTm9YQ1VvUk5qRWlhUzVWcmhyelFmVGZ5ZUx2WmpHdnl0ZTVVcTU1ZDY0V09xSU9TL09FUnJEdXkKS1MwVmRWWTRLTFNCY0cyMXhYZW5FT2tubHRLRTZVajlxYVR5VU1YZEdmUW9vVE0rZ0RtcDZBYzJoUUJoTlhhaQpxbUdIS1NpeWJ4NjlXdzBYdld2UjZlai9HTUE0dHZiTmdMRXphdEVZNnp2dkRKQmphcXppa1BSd3ZFSVYrUmFYCjVSMU1oUklQTnZZVUdqd2RTYzFYNWxTWEVsOFFlY29ZSVJyRjd5RUh6REVwaFJtN2kwVW5nQjJaMlBVODZLM1QKbW9UUy9yTUtqWFZ6Z29QTmFuR2FxZXVUUDdwdWtNQ2JvWVpqZFFRQmhtdVE0MWtnTlBRb1hYNDFlK2xNNXh1SgphVlkzWG02NnZmWlpDMUxzbXkyQjNqMlYxb3B0VXdvcVBsL3RYZDZrdk45OS8xek82QzdnVnBscTByTjdha2F6CnppbFJpTXhsZXpZUHNuUk5RZDgyNnlGaDI3dXFrMDZFUzBKNVkxMGhxb09zWk8rNldDRFh6aHpzMTZHSytmYkkKV0VEYmpmbW5HOWFuaVFJY0JCQUJDZ0FHQlFKWVF2T2RBQW9KRUV1RHZJOTN0cTVrVzE4UUFJWlpMc2hOUlB3NAo4QWlGdmlOemtGamdMdVd1cVptMVdXN1dGVXlOTzZSOEVMU0R5bXVrQ3EvTTFhSC9ybU1sbmxpK2diMSt3dk9ECnNhUVZTOFpWTlFDMWVYeUhNWnJDc3lOY283WlA1T2xKZzFIYU9jYWVVTDJCc2tOUDlnVEtpVDRkZTBRbTRVOEIKWnZTaWxDTC9ZcGxteU5RTmpmT0RGMCsyL1BvQ2l4dnB5ZkNmMFdwbkJ5WmNybldFNjNqSXl4c25KRGtjK2I2eAo5S0tYL2JUMkx4cEQwMHNkQ25jSzNXRzdZRnlmYTh5SmtkaEhCeVZwdGVuWGltd3F2NE8zaUNBanVHa0h6dzh1CjdLKzJJRWZTdjJhWUZ3YXZxNzRyank5cWNibHBEZ3dXWDZTSm9lbFpGSkI3M09Hb0RWZjAzN1BzNUZJcFQwdEYKMjRkYy94Tk5WUzlIbTUyM0ZIQnNkQ3BjdVpJRFFqVVg4d3QxWWlxYTZVNjNMU3ZuVVFudzJXU3AyR0h0ODRCdwpvQkNDZm12YndLOUlFQzZVZGxoQmc3K1JpM3BRRUtTcmU5cnJSNGU1UlFlb1JtNCtGM1dORC9qNzN5bnFLZ2xyCndqLzh4ZHJOME1rRzZ2QjY4SFpURUxuazJhRGt4cTZIbFY5dldjc0pOakxDZktGUXNpK3d4M0phTzZFenU1MDcKOGs5VnpGZXcwcStDNUVULysxM3RHK2lZZkFkbXE5MEl5d21QbHozOEFCYkNLU2NOMDFMUlJsK3BQaU56SXUyTApwNHJEV2VnZURBc2hxK1FvZlFESUZFOTBTcHNaVGxabWFwQUZ4QlA4Z0xLWjlzR2VBRk1uK0NHTjdHTXljSkUvCmYxWk10NU9mOUxqNHVHWFlNeEo5TGNnUjRZTnFNMXZiaVFJY0JCQUJDZ0FHQlFKWVNBaTdBQW9KRVBtTTdUdmEKN21mN1NEQVAvUmlmUzFIa0dVMURiWElGZW8vUkp3eWNXRVNqR1FwSUkzR0tqZkoycExBNis0c1BTZHQyeGI5Two0Uy9Nb3FRVUNFQnFnYkVsM2FWQUl5TDZ3OXRkTHRxZWZDUHoyOVpUamlmbFZNQzkxbjEySHpSYStvRXQzc0JtCll6RXRwVG9tTThIYVpBOXgxdXZCV29Yam1jY1dNQmFrVzlEME9YUmkzWmQwditiSHAxQkNnd2R1K3NjUk5TdEIKYWJOQzVUT0hMbVc0OTg3L1prOTZ0eEFvUGJvTDdjbjV0bU1yWEJDK291ZlI0S2F1YXBaQmJqaWNxdHJDMXlDWgpJYU5kQU9MVmpKRGM2MWxadVpGNEpJWndZZ2Z5cWprVzc5bVYzdXVzYU5VeHY2TmhKOS9za2ZZcmVqWSs4OFd5CmZOZFVQcVpCejJmdHVVZW9aU2oybXRaMkREdEozYklzVzZUaUJkdjdGc096L2dDUzA0czZ6dXRVTkk4L3k4bGEKaXB6S3FRcWZBUEFJcW1Jb2lWWVpIK0VkWGhsMFpUUUdtVmJuZHJXRWQ1OEd5blFtQzIvSllCd0xGdmw2UlJjTwpmYXF3L09lM21TYmYxcU1xWFIwL3NadnowWDF4VFZIbXpxbFNPb3RCN0VCczFqSzNMQnI1UGlNL1BqUCtDOXlECjZUNkl3WTk2V0lSalpRZ2dVb0djSWRka1dFeWxHTDZnUG5CclM3eVR0NmpKOFlZbmFvM3pYWXNGSldBZUZFN1cKcjdkcTNKTm9DRCs1Y3g4VzlEbVluOWoxWlY0WjdHVVVhUGRJVkdqMWtBN3BMOTdSZFd1OTk3TlFjY0l6SDVaWgo5K1d2Q1NuL1gxUUVKN0U5SWl1cTU5Z3FseUI3RktlQ3lvSzBFWWJvTDNkSGluU21iOHpVaVFJY0JCTUJDZ0FHCkJRSmI3djUvQUFvSkVLWmhZUWxGRzd2eVlPc1AvMHRXZzlHaGhaTU9MSjByd1E2TmJKZkRKblhmUFZHdUZjN2gKd2dLanc5bk8xUnJRVUUvOXM2eHo5U25VajZoYjdoTGRSb3piU2toOEhjeHN4Wm9ySWtnOFNUOEVJcjFVMWZURgo0WjI1VWNYSWZhTzc2R1E5L1BJbHNMSmNOYlRpNDBpVlVkQ2xEOWdGc2Q1UllyMkxDRmt3L2hFbWFubVVPd3F1ClBkZXRmVjVoN1FTRXUyaXFxNFNCOGNWaitzcEJ4OVVPK0MwS0ppbTIzY25YWmh5UWgveFhLV3dXUlF3OVJURmEKUTEyNnRsb0Z2NmZIZ3FXWU1yVUJQREYzSGJ1bXRuY3ZYeEllc1YrZlI3SGNWTXBuQ2JtWlVHV2h6MVRiM01jVAp6WHZLN1dVNC95OTFaaGVCMzltR3NBaWVodVo3bXNFUWU3MFdJNHNtOFF4M3FBc1BVNHYwUk0zNk1kYXJReEh1CkhxR0M1Q0NqTjBsOHhkL3Z6L3ZJV0J6VTFkOU5xbEtobldFblRFeFBEYWdNKzNKY1JzME1kdjdGNEVXbGJsUVkKMytQVXMvekxuVzY4TmtiZXA1SGNCblU1U1VreVJ2N1B2WXNodkdUb3BFVEZJQ09DSC9rRzluSGhJV094c3lwMwpnbS9VQllQYWVLTHgrVGFGaWVRbklmWEQ2WlMzNDZlT2p5TXZ6QzN2ZXNrWC9tY3E0Ry9Ma1JSeDVrcGVOcW82CnFxOW8rcFhwNnp5L294QXBxbGQ4MlV1anlpTlBldk1HbURGVmNKUDBUYm1GN2pOREZuRkNhR0JGbk1DS0srMkUKVHU4T3dlNjFUbVgyNVhuWVVHYWFCM3lEMFUvYTFBdGp3YVk4OXFNRkFHNEtSQ0pYbGp5LzFEclJDTVg3QWIrbgpkVHVQZyticWlRSXpCQkFCQ0FBZEZpRUVWM0UvU1d6Q0d2NFVaODFpcmJoenNiZlFPUDRGQWxyK3pzTUFDZ2tRCnJiaHpzYmZRT1A3ejd3Ly9TY3p2K3pvd2w5TmJoZTlXRHNiUTNZTk9zakJET0l4OUJGTXdNYXVzenNKeWREOTgKeGtQMHhNczlVQ3FYbnVPYzdxVUhMNyt5by9SZXYrL2RxR1RYZFo1MGdjc1Y2Z0pQdDNqT25TQVc5TEVaSElVQgoxdE9TS2pJSFJlYThuMWx3WC9BMCtpN3BPNHdxNlBDM29QR2xYMDFrVW1uUVczMldvK1BYVml1U0oxcHdpUEpZCmNUbnMvZzk4Mm5BbThvUXplaXlrODVaWTVsTkd0K1IrTGpPamhXTEhuY1JMOVh1QkhoaTFMVWY5V3VtRnA2YU0KbWgvZ0hnZkNNYkRYbUF1M2o4dUo1Y0dzc1NRcXNyMGlBTVpmakhQbEttMi9ZK2pVMEx6ZmxtbFQwSFdMWXJHagpuWDdoV3V4OXRoTjhmT1NVdE4yallOTFRlaGNrb3RyM2dyeUJJSDZiaDB1ZENsVWl5bGFSaDdmdk9jaTNpKzFkCnRHcUZwbDBKaWNpZnlFWXc3a3hJdGhVcnRzZ25wZkRNbEZPa1I1eGFFTFp6bjg1bmxSNXArVGFFSm5uVlRLb1gKS2ptSm52RVg5S1RUQS9Bb3hldHJ2bUFIMVByQmdHSTRCZjdRUm04RmtnUWhudmxxNyt0RXdMbDlETkgxZGNzVwpWQXpYSU5RQTI3RklrT2F0bHRhaEQ3dC96SngzMGhnY1BHRDhFcW5tazlUUFdPekF5ekN5SDJEcXVsYktEUFdaCmJVU0lRYWQydjVHSVhKcDdDWFRZME1USDBhQm51UGF0b25tSUV3ejhlUmVkc0k3MStKajRCWVIxTEtGT25qT1cKQ01iRGJic3prQ242U0xzSWJzcFNteVVVeFRGVXNuRGZDNGZhZGRPS0tGUTJHNUJ2OE9rc05ISlBic2VKQWpNRQpFQUVJQUIwV0lRUjNaTndiL1Bubmw2ajFUbFoyNEpuVWhZakpCd1VDWERpYzB3QUtDUkIyNEpuVWhZakpCOEpICkQvNHhHTENJdG9ZczJhV1k1cTUzcUxhV0NpT2wwU0VnazFJS09tS21ZR1pORUFtYS9ERG01alYramg2SE1VUDUKL0orbEo3dkRmOWRFRUM0R3o4WEIxWU1FbWpXMk1qaTBtcU0xZXlWeXI2a0U0WS9kQlhpaDkyU2dwMU9MRDh0QgpHa0xrNlppTXdnSWhaYXpuVEFsMUJMc3F3Y3FRNVMxWGxIaGU3TkM2ZUlSUnFhajdWNFVjNFd0RWdSM05EUTZ1ClpTMmtrdENNYXpGVGtUSDdnbXU1S0VRNWtGZW5LZFJTV3Nweitwd29qT01hbnIzaFNLNWhxOFcyc2tnN0d5NXYKOVIrMlh6NmN6c09pelM5b1VjY1N0enE3RlNDVDhoQWFpV1JoREZCNE55UnIxZnpjdlh4SEtFY0ZQMXBSNVlPdgpGcktlZ21MSlIrOHpzTFIxeVBNL3ZMMXZWVFJlQXd3M3RCcXFWdnBQNTJ4R0lzZVRvY2kzTmdaVHo2bWxSOUpyCmZ5SjY0RHB0WFFXN1lFUWEwWjBsWlA1UEM5bXdDd2YwSDR6ZEtmVUorclB2LzlDbFg2S2JoYk43WU1pMVFpbm4KTmpuMHk1cXJRblpSMzdLRkhWalpQZkdqLzhTSlFyOEUyb2JhNFVhM0ZCazczdHQyOTJNTnRqVnB4bGx4RGp6OQphSURBWHkzaVp2bmZTVWxpMHVSRisxUlZzS0Y3dmQxZFI1MUdIZmVBZWhPcHZDN01xakFXMzFPT053aVdabEpaCksvOGQwVGM2dHVwUkVRVE1OTTVQWmUxVEpjQWovMlE5c0xqUGluZ1cyT2c2NDFrNEorV0Z2TjNUWUNGRG5YckkKNU40TENaZ0o4Vnh0ZXN5ZjI1clRwVmZBWjZKM1UxNWt5S1dNWWo5U1BTMjJvb2tDTXdRUUFRZ0FIUlloQk9weApDNUJlZ0daTHhlRVkrVExwd0pPZXZ0U2hCUUpjbkZPckFBb0pFRExwd0pPZXZ0U2hoUjhRQUlUUEU0dEJBVW1nCmJSRkdwSjZISVhTdlVSQjJYSVQ1WFB5OHBCSW5aWXZxVW5HUFNIdlVPaWxJYXVOeVYxM01Pa3ZVdnRNWUZ0ZnQKalJtRW1ESkNmUGlMMnlmTHoyeFJzWEFVR2dyYlBBMWtzbHFQektOdVhzUVBWWm9kWW9CMlRwTnZudm9pZDBEeQpvVDdSSmk2aUZnN2NIbXlhWVI3R2c0cHJLTWxoNG1uRFA5Q2xWTFNobnhQUVBiODVZbVIwdGtRTmJ6bVdTOTBwCnVoVStwaGRZaXVhYUQyYi92SDlLSFdqeklDSVZySGZvZjZmR3kwNGE0YlJqZVFNbENJNXZQbFRjZzgwSjRlcDAKMDY5SVF2T1FjR0IwOW9lTFdqZ1pialpYYnhWQjM4dmxlanE3bElRYW1GQWZmSmZGUytBLzZhb1Y2UlFPRVZHRwpMdlRCRjNHYmJKZm9CU2V1UG5CbEhEamM5MnlKQ0NEWnBVZC9GeDU4T0d0S3pLd1NOODJlNnVFYkFyWUMwK3J2CjlGTHk2OUwwaXdZSjZDL0ZRMEZpNzZSN1ZKQWQ3OXZOSzNZQTJteWdJTFB4SlY1b2FFbWdWRnA0ZnZsMldibjkKNlJQZStWd1JYZUN5UWRNb1JQbERHYmpGcEtaeXlrWFhEaVVwcnJ6NTZBYzlrbUhPNXMxSng0TG5kZEJQTExhSQppS1FyS040SmZNQytCUExsbktXM0VLaE9scWpyQmN2bGVXSk9SKzBRYXB4d1pwQjZPOUJqZ0xQWER5RUpEeEJGCjJWdGM3cWZuZjBRdlRtOHJoT0pWSFhHLzNoQ3VKWUdqM1dQK0cvRjNaK2cyT3kvNVJiRlNxQ0ZWZUFGbFoyQWsKSmIyaDRFMGVDNUx0OEhVUWFSTFJkczZGOW1wUkpSZktpUUl6QkJBQkNBQWRGaUVFL3NUcFI1bmdpbkxxUUlOTApmWWVzQTgwdDUwUUZBbG9uU0MwQUNna1FmWWVzQTgwdDUwUVROZy8vV2dlbHQwWjhvTTRKcmJLMXZoUEgyUXNmCkZQa1hBVUs5azR4WnNRNG03SW9RU3pFd0J1Umpmc1lpZXdKbkJYOVZRVHpQNWVYY2NlNk5XYXFlYzM5cGVSbjkKcSt0a0hVdDB6Z0I1WHBwbzI0cXJpLzJlL3ZyOWJoL1dSQmV3ZThLQ1FsYW1seW9Gd1BpNEgzamQzUHptS3JQSgpLd1U5SU5HSE0yNFVJSlZYcVRMOE1zZXJweFRTNkZxRzlmZXlOd2hqRnQ3TUJxUVNzYlBqWHdLdzZuckkyNHFNCjhEd0RwYmtNYkVxaWpaMzBvR2tjZ2NNQm4vYytLOUtyME4rdXhJZk5NT01JWndlYVFRQTRiMUVsRDF2QlRJekMKSCtKV0tqOGZoL3BuR2E3NHJkUldVWUh0ZzdxU2lGWW0vbTNIYW9KRDdrcEN3VGE5Q1hCbGpuZERVWnRNaDd2NgpQOHhXTmRNOFI3Q2lWbEV3SUJiZmhqR0IwaHJ1Mis4ajllQ2NhckE3Y2R0TEZKT2lRcUppQU1nNThCRFRxdWpSClVyZmxWNS9UZGoyRko4blJyT0lpRXB4VUszbXQrTDcveXV6SzFjcms3ZFlxWmZ1QVRHYWRLMHljN3NEaUIwUkwKZ2tSV3dpMCtqa1VQSG43YjNPNitRU3IydXNLQTFWSENZZ1Npb2FtRUwvZ0l6ZXFIeVRScjRhNEFYTFJET0RLawpNUWpzaXVueHNVdU8vRCtjdHpJVzE1Z0V2S3NEN1l6OFo4Uk1ORnhEckg3Tk1pbkZ5bmdESGlxZnJXVzNYRHFrCmpmSllMdkcrSFF1T2NRcC9UaVU5K2VwUmcyclp1Z3Y5WTV4a3hObHRlSHV2NXJsd0l2a2dsWjZ6MGhHQU93ZWoKV0FNd283dXJVQTByWEZYSTIydUpBamNFRXdFS0FDRUZBbGRXN1ZjQ0d3TUZDd2tJQndNRkZRb0pDQXNGRmdJRApBUUFDSGdFQ0Y0QUFDZ2tRTUErRWE2SmJyZ25qcnhBQThaMmhralcxbDE3QXIvWEdsdTQ0cGRYd05BeWFhTXdMClN4NVdEOVppelRnMDVHaXowWnMweUlrNnBKTEF6WW82TVFGRFRIN3pKTkhMUzNSUzk5bWF1VjFVNTF1OWdYbDYKQUpXcVRCa1FhdEJOVnE3YTdZSld4MDIrbWJUM3hjS0FJMXhBSTVNZVJvQkdGRzB5VVJMbGtkR1dsMGN2bC9mOQpQMUNidWlFSTRLTVBSM1lSTGtJK3A1RGhMMUx5Mkp3NWt2akcwNkh6R2U2Z0lLbWpYcU0waTgrZWYzeXQxTm5HCkdFK3YxcnM5Snk3TS9rSUl3aWdmSm1SUHJoeit2K1FuSFQyNG5zOTRmWDJ0aDR4cnJ4OGR0WkY0Ui9tQWRMY3QKUWpSbE1yQWZhTDBZOWE4bGFwYXI5MWRLcnhoTmJDVGdrTE1TNWF4ZFBOVzFDbEhUSXRTaVpGUWNXMTRpNnNCdgpKV25LekpKcnZRUE4wWFU3WWcvRk1pN3V0OWNuY2RESDlscGtLWTRhV2Y3LytnVnBwL3lvVTJyOWtjczA3Q0lNCjgrUXliUW92NVhCYTIvVGJMcEJGeHhvbHcwZFIrbnBHWVlialE4ajNjNVJjYmxvYnhyVG8rZ0Fhc3krQ0VncG8KRGVsd1FWWWhmb0N2b2htQ242V0d0N3hvZThtMVJHL21iYzArdGVKM0VMYWRkTGFLSUJZVVBBNkh6Y2xBNlJ0SwpVUDZ4V2R3dFVIZnRhcFRDMWRCbFhIMG1CYVB0ZnFmRUh1ZGl5Nm9nK2laRlVIWXVlVFZUZmp1RitGQXhPM3BZCkFaRFBxcXBGa2pNYnNQaDlMOXBBZW9UVXFDVEcwb1lTRVJFYnhMYzhDTFhiY2RsczNhVDJIWElaOGd3cXpBdWgKV1B2ajExMEk5ZEtKQkJ3RUVBRUlBQVlGQWxnUzJ2Y0FDZ2tRbnNWOGVCT2JjYVhrb2gvN0JSK25qdWdTUXVncgoxd0orbGhUYUhSWmpScUNic1FZd2hUNkMrY011bHY4SDZhdHZKVzV5bEwvSUh3VmVSTEd0dHJLS3NpcmhiOFl5Cmw2cysyRUxGanNvZlh2a240MTQ0Q1RMTXFUbFBxSmFKeDVmdm85YkQ1Vk9jbjlpZFg2K3dMcnhVRklmK3U2MXkKY0ZxbFBvSmQ1UUw0MWUzNzNSR2p6LyttTkg1MzRHaDRxZU4xaU82cWY4VkhPOFhSTWJjd2FYelVhMFlKWC92bwpLb2poMU96K3FFblZEQjNnWng4MEwxT1hpVS9mV1BOR3E1T3pxa0JNNmtrS1Vza3pkcDZpL3NJL25DSTJmM0I3CjJuanBTcTFHQU9hK2EzQ0htbzNGUXJTWitudFJjaldCNDMxbEhRNkhJVis5RXBkQWZ2MkJZS1BEL3ZsSkRENGwKVFhoWkJUVFA1a3BiQmY5M0t0aHNIWGZpL2xGbmFpNGI0TXZ1S2Q4UGx4VllFdkVDSnVvTDVhemd2MlorbmlvdwphSmtUTk16blFxUkNoeHEvMHBNVlIzZ3p0WWRYZkRubDZGME1XVnh5YXNERlc4QzBaNVRRa1NjK0VkMVdlc09CClNaeWR2OHVmaFo5c29KakE2dG9hNXRMRUFEeldMdDIzSVlkSXdZWDFRS2VTUi9TK2FmM0NrSEprcU1qOW9OVVMKTzUyeWFnSm9PVUJONUt2YVpRazZhRGc2RWEvcGVUSTFMOVNHZC9oeDE4dld6bUl5QzhOMUlkTzZUazZTMEVDagpxY0pDNE1Cb0o1KzFncGdrM29sb2lmNk1EaUdCRnFjNHZoc29hTFI1Sm5ubjBOM1pHOFF0QTFPWTQ4L000VTNRCkJQVVNIamVyVWFZK2NTdHdaclYxN0ZMMnN1SnN2ZFMxQ213OFEvbHRURGlUL3gyVkV6TlFhMUZ5OTEzTjhmOWYKRUpwb29lQjM5NGdYNFJCMzlXaFBLTUswaTRhRlhPUG9nNVRydWtyaG5DWUNZZ0J2bHlqQlcraDNrQlFXYTNNQwpDWWRYcnNKeU5tTU9ZV3l1T3NGYVRVdWY3VHRydVVMUWhma0Jlam1nWGsrcGtEczcvTFh0eUREU0M1clR2eFA1Cit6NlBiaWtHejc4SjgyUXpHNFAyaitFWWpNTXF3SlRCVnREa2x6ODA5YjNmY3daRHprWnBSb0F2R0tEWllTQzYKUEgxcHY4SDJpK1VMelgyK0g0Mm0zTHNLaUc5KzdWelNObnZjMlVXaUlqL3p2c3Vad1VOWERETEwrd0RFLzlCMQpxUVVneWdKbkhFUjhTWSs2K0ZmMHRYejl6dktLWHowVmtlalVoTFZGYmVzRWdKazdXRXc0cVJLZzRjMm1KQWFqCm5zYW85eUZjOElJQkNiRjhJekFYR3BHWW5DcTJ3UTVWT2xmYVFwRXFiK3FPZ3ZSKytvVWNvMFZtSUpRTDd6KzEKRGlxM1pGUnBveHBacU5rWnFSUjRqeEV3YXcwSVl6SU5TQ3JvUHhOMmtQNXFIb2R5NGdSczMzaUJjTGxKVm9LTQpVaEdaUW9vSXRrZ0UwMVlsUVhiY3hUUHR4RHB2ZWlGb2tRRzZ1U2hyVGc0SndSUmsxUlNXZU5VS3VuTlovd2tFCmxqcW5QVUJFS1FpNDY0K3daL055OHNUQUhkZWFxdE9CT0cxSFpEOUk1SklCdVIyWkJZYmdXL0dLcHBlMHlUQjYKUUF4endTWndJRFByamd5cFM0YkdtVzBVTTR4dzJqajlCS0p5a0pSUndmMklJTHZBdjNGT3pzUmlPM0JXRjNQeQpMdS85Y1ZmNGg1a0JqUVJjNzJaUkFRd0F4NUQ1ZkJrSS92bEpmbGlVdVNpNHYxdExRMzMzSTJxSUZYanVUa0lNCmJDMDNNR1NYN204ZlZFZDh6VHZjVi9pNCttc0JRL3FSbnd4elNhS005bWhEQVFFY3NhUkFLRUo0V1lIV01ZMUoKVHBhU1ZIQWJqN21NaG5MT3NQMHJGU1NVc2pRTXBURU5qVHEraUJvRFNrbTI0L2llU3hIRG9PTEpMZkJYYkhEdwpBMUYwKzd0dnQwRXhUZ3ZCNkZVTXY0YTVRQ09BSFFhK3hkVnIrYURhR0RLamUrU0xQTnZrUzRCRlBWck92aG1OCmRtaUh1Z2VhZEEwVk1OTGtKUFRzUzRqYmdZbWVpalJhQjZrUGpZYUxSa0k5S2ptWlc3emQweXl3eTc0dmVVbVAKWkhVSUoyV2hudXkzTWtjbXhsaklmVlZqTDRQbCs1blZTdGlQMENteDNnUTIvWlNqbjZnc0JUenh2cFlod0hZYwo0ZEt0YVUxYy9rUXdwZ2dwSEtCUlJ3NnRDUjZtcDI1N2phNmZ2UlZnSnA0Q1BxWWpnYkNwajJzaEkrVWgzQ1IxCkt1S2g2cy93dVBvOWxFUG5KUUJ1RmJzeEdjQTZOQlR4dlVBR2FXSjdnQXlGTCtFUi9zY2gvMkoxbFJpUGFJb2oKRm1uVzlyVEIxd3JIVzFaTjYydXVyWkM1QUJFQkFBRzBJVVZ1ZG5KcGJpQkhjbTkxY0NBOGMzVndjRzl5ZEVCbApiblp5YVc0dVkyOXRQb2tCMUFRVEFRb0FQaFloQkdYQUNyYXlya0ZPa01EczYreHJwckRPeUxFM0JRSmM3MlpSCkFoc0RCUWtEd21jQUJRc0pDQWNDQmhVS0NRZ0xBZ1FXQWdNQkFoNEJBaGVBQUFvSkVPeHJwckRPeUxFM1VxWU0KQUlLblFYVy8zQ2pRbHlQQTNZak9VUDlQTDczWGQ5Q2hGdGtXbkFVYURZQnRnc2l5SFJBWkc3dHQrT1g2eng3NQpneElhc3UzNEhBdXdvQ21LR0xXS25WRnNiSGFCSG9DVkpYcldKMlpBNUhaRnZNbzE5VTJSVEcyUlUvTzQ5anRHCjRDZm9nWlhidVZSN2wxeUdiZHhjWEIwbnp3YmRrNHhhcjFvMVU1SEVNcXhZbUpuVVRZL0I3OXdPQmkrTGl0M2EKZ3drOUQ0WkFUa1ZWV29odVN4TmFzL3hER2hTRXFtbnRhbmFZMGJ6K21GcWE0SU8wSStzRVdPSDJ5WmFud0hTbwpPdXRCRGN5OVoyLzhkRjRBRXA0c1ROSGQ1MWF3OXNHTHUwaHlUNEVYODBQVjdQMmRYV0doMlkvSjdkSmRIWmFpCjNmV2g1K1UxRkZCWWxNVEdZMVBGQitpNjNDTWRxdUJNeitRR1QvL09kZ0t0UnZkRFNqMUpJaVdEUzdabTJNQzkKVk5YSVQyOVhSei9BUVYyRFFvUC9rOFI5b0JuMm1UMERQeHBlL3FhK3IrQW1YL3NDNGdqWm5QamZQUGdjSTZNYgpBczBTOHBLWXY5d2NQN3JPYkg3RER5YVpkNGNLL1ZhdXVGZm1DOTNhaVE5Y0FaVDNIeHVON21yOXJwejFIWVhMCjdMa0JqUVJjNzJaUkFRd0F6U3BvamNXQzY2b24vUVVlRmVnNW5ROUpsY1FyYVBWdHFKWHVSYWpkckVVVFNtTTcKdjZFc3hSMCtna0dDZHFZUWdLWmpudnpSR21DYjRndmdDQXhLUnl3dFdWR2J5SFNUaWRUYmhDZ3ppbXNHL2ZFYQpxRWpEZnVVUnFQQ3BtRHlnUFhydGZZSVRNM0MxbklYakpTY3ZEblBRQ0RqWXF0SVByT1R6Y29TVHpESkZjUjZ3Cmd3ZkNCQlFOQzArN3FnU0VHWWpKbGIvNnFDMGUzSkxmaDE0MTBoMGRkWDJGRHNzaFU1M1hLM3B0NHhkUUluWDQKZnBhS3M2UWJZL09JRnJDQjQ5eXRqbys1VGUxb2RiUWp4b0dsTXFZMVBSKzNwZ2lWWUJMMWlXbjRFS1VvcGtEZQpyUUdiUFlZUEFENDZEdU5JdmljenBGd1RwWHlhWkRqaDQ4TDY2Q1VQeUg2QnBKUVdXREw3cFNwU2NPaFViSlRqCjgvNU1tclJBVkRpTDNsVVZwalRGLzd1em55bTJQU012L0ErWkJxRjgvUC82MFBJUDRnUUJDTC9nc25DdXJWcmEKNnpOOG5lWG1VY2FKaGg0dUt1ejhWaXVXQVIxOFBuNVZITHgxcWhVa2N0R0R1YWxERi9sZ3d1N01HeUgwMk94TApJNk5zYjEvRlpJQ1BnelAxQUJFQkFBR0pBYndFR0FFS0FDWVdJUVJsd0FxMnNxNUJUcERBN092c2E2YXd6c2l4Ck53VUNYTzltVVFJYkRBVUpBOEpuQUFBS0NSRHNhNmF3enNpeE43TWJDLzl3OTRmYVhzRTlTcW05Y3ZKQy9rRXcKTTI2WERGVTd0WCtNcXYvTHl0MkZrOUd1TnJpU0ZRVEJIbDVLdi9aRWVZNllXRk5ZMmxqVlBWanBsbzliQ2M3UgpBeDVTTHZKc05hdUhHUm5CVXZZd21jZ2piVEhGQXp3TkNCOEQxVkE4TVFIRkljenladG5FcmpTU0R0N2pjQXB2CldsUVptTVpWRHlWS2gyZFFMVEhFelFOMk44V1NFdHJ6NHVJSTl6Uld0YjRleVBYMEVNQUlYSEo5cVdLV2pmS3QKN3g2NXNyanN6cE9XWjdvcmZocHJWdFkwQzZLTVpQNUFBdnBFTzF2ZG9PUDBtVUh5eGJFZjRHUDIxdnFqQUJOawovSTEzb3YrT1Q3V0VwekNsK0M4U0I2enEzaXk2d0dEcER4THFyd3JobHRuZmR2S3BDYTZOOG1wdm9jUUIrRXJICm56b1NKM3dIYmpXVGVuUE5QVThqcGtXUFJjejJzclE1ek1ZVC93TzEwQW92Nk0xMDhFaXRORmlrSUxEWEVzUnIKMERiRHlkNlZXR1B1amxBMTVvNmttL1lDd21HYXpPYmE3SXlpNWh3YllyNi91MHE4V0NCTjl5UnJyeVBSWTdFVwpLZEFHbUc3dE9ta3JQQVRlcU44TUtSRHdLc3VQSGY0NXpxd2RjcDVRT0ZtWkFZMEVYYmZTZVFFTUFLbEVhV3NlCnBTcG5VcVlRNjNqTmh0QndEbkI5U2F1NnZ4YVBTd3ZyT1JRTVB1STdnWGlXQ3lCd0RSSllpQ0pZMjRPSGJQV04Kd1lpS2VDUWt2R3NmZUdRZVkyTllhQWp5bUloR3d4RmRIWFhMeTJvSk9JblFVeXRYTndzS0tJU1VjMXIxRmU0bwp1YVpWS0ZmR0psNERDWTZZRmxMWDhrdUpaQk1kdjNUSmZxSkJBdXpaeWZUNzNncnlZWmRwYm8ybU1GL1llNEMxClIrL210Qml2L0hSUC90SFhUdHZhWjRxSkhWUjl6aTcydHhVaUVYbHc2bGp1MVVwK1lmb29xSGpQUHB0Y0orWjgKL0lESE5NODhvNlRlUWxRVGUrSGNFS0hwbExtTWxmeFM4aGFsRnpEM0t1VWlrcUpJNFc4Nkp3R2Q3N3pTZFMvUgppN0E2Tm9DRGFEaSt6Y0JLdU80UkVXemhaTlZMaThRdEJZZmhMdjgrL3lTa0lQcEUvNHRYNDBVNzA1VjdScm9rCnlJN0tHU3R0bGl0QkZmN3IwTFdvVHdKR01QdXdvSVYzMG1KNVlGNkhiakgyOWdiYU5XNVFtblREb0daOXlLNlMKVkxRdGJGMXdnZkJCQW5xeENvL2RIWUc3VWRMRDk4bGwvemQ4RC9RbThuOHRPMTF1ejNqNWRVNENBUUFSQVFBQgp0QzlCY0dWNElGTnZablIzWVhKbElGQnNZWFJtYjNKdElEeHBibVp2UUdGd1pYZ3RjR3hoZEdadmNtMHViM0puClBva0IxQVFUQVFvQVBoWWhCT1dMRFJVazR0N1YwU2pDUXBOenV2YmdJTmhoQlFKZHQ5SjVBaHNEQlFrRHdtY0EKQlFzSkNBY0NCaFVLQ1FnTEFnUVdBZ01CQWg0QkFoZUFBQW9KRUpOenV2YmdJTmhoYkJFTC8yTWJZZC9tck1mVAo5eElvSzRvS2RJMWVpaXprL3JjSlB1czRmUGFtKyt4RVNpWG1mSTZ2SGdXV25pclhKQkFSc3huRGNCYW04RjNqCnJHZk9ISWtmeklRZ093NnRNM3A2ZXA3ZjVhaWN2UDhWc2FsSzE2QUp6MHFxOStQankzOTVaRmh0SUxubzYwbncKdVRhZHQ2Vjc3VVg3TzJGSDNiZzFGTzFHa3BtdW92K2Y1ek5JT2MzWHRJSU9wYXhqZkNWTU1xMjMrWlhZQkU4dwpHeWNSY2UzYW1DdWYyenVWcUlxRG5BRmxneElPNjBhWklLSkF0UUtjWHVyaGxtSXZqNFg4cFhKYXNTNDQ4Wlg0Cmxwb1I2emtCMWdDdDJLeWZOMWJPcWRDaXIrSEkvWjZGa3FndEpXbTVYNkFxYnRvdThOclZmR3VMOFVOemhEK3EKd1JVbVA5OUs5citBcWR4QjRPRlBwU3krOGJjdUpEZ1VIVHZucS8rNXdXN3VUelhyQ3JSY2hoVENxalkxNE5iZQpYamhXdk0xeDNpYkpYemNZTi9xRHlLQnVacVd1aVhhRzhyRHBxNE9nWnVNQVh4ZThJU0lrNDRZMWdWenNBNGZXCjI5OW81bjVJcWdRdTYzVXFIR2h6TXR1dlczdTlWdGVNbXFnNjBNVE80NjRjaThrTDJLazFkcmtCalFSZHQ5SjUKQVF3QWtiYUUyemNXK2VWQTZpcmNTVy9lbnhoZmh3ODlpUTNXbGY1a3hnaVZDcENWdkFaa3J2RldaOWRlNkRBTQo4WmtRaFAweG4yVTNXYlVQUDc3aWREMFI2NjVBS0k5dEp1dGZyTFAwZzdTSTRhSlRvMk03Nm9EMWJIYlU3UVQ2CnNEZ2hYTHQ0c2JFU0ovdS9iWUpUeGp1VlFYMVBGcGljRXZJT0JhWkYvQWlocWVJUkV6QzdMdlVQRU54ZGx1YnYKVXByT2ZpS2xxU01LcTcvMUlBK3o3NmFEN0poS3NtZXltWnl0YlorNDRyVnB4b1cxdTBCYmJHSTRZemZ1NW5RdQprSkYrRlVkOGdsb1NKKys5WUsxb3g3ejhRbTFObVNHUlhyVGtPemg2NVZudkNQc3JCWGM1ZGJrZ1p0d0VXemJzClRyejlyRU5lbmFYTVpPWXBXWmVTZmRzL1IyRXMxM3dHbCs3ekpqMm1rdUx3Ri9JWEg4T1lJdU8wbGRFRHFVdnAKQUNJQnE1QzVlekM4WW03Vkd1MFp6RlBZU2NDWlEvMlA4ZlpTVTVzSGVzekYxZmxVQldTbGF2NGNGWHN4QlI4egpTZEwxczEzQ3RaSlh2dFVvNTQ0Nzc5NFY2TTFvcW4yYktxaGhYV0Nka0NScXBnTy9VQlVORGVoYzBhLzAvblVKCnhmQkhBQkVCQUFHSkFid0VHQUVLQUNZV0lRVGxpdzBWSk9MZTFkRW93a0tUYzdyMjRDRFlZUVVDWGJmU2VRSWIKREFVSkE4Sm5BQUFLQ1JDVGM3cjI0Q0RZWWZLMUMvMFRhUVJFcTk1U3gyK1RsUlZidTArbmg3TWQrWFZqUEM1cApLRGUxc2x4N1YzemQ2OVVOUU1rS01ZTVFEVWdwOEpIdm5tYW5DK3BaMTRYRjkzUmtlaDdxWmJiZEI4eUZtOXhGCmliOTREUS82b1RRTmdTRGxxWkZnQVlKQ1hMWFg1STFqdjQ3b2tSK2dVRmFSdU5VRDg3RHc1MDh1SVQrdzZaN2kKSUFzUzA0UlhoczJmYXlES2VSbmFIczZKM3FmbVJGSER2aXZzRTh1UEs4MVVGVzJEbjROWERMSThXQnk2ZVpVNgovWXl4aDNKa2dsM29jY0dvbiszc1VMRUFaRXQzeEpmaitmd251Mm9DR3lsazNEUzk4ZHlaanVveXVoQmtLWDNFClludXZtaGFzOHdybzhDQzVvVEVNVzk3aVhHVXFNajZxejAwMVpHL3IrM3RBOHY1WGdUaEc4R3lVcFpaOURSOFgKQnNwN3Y4dXVyOWUzbHdzTVR4dzZZK0tXdm9DekFQaUdMd25TczFSZzVhNHZDQ3hxVEpWbkRRcENzazJEVDQxcwo5bldXV2Evb0lBNjZVQmxCTHdjVkl1Vnd2WFViNm5OZk5qeW1ZdzRSalVUdGxqWm5OQzdBM3FFVzBRUlcwSGhSCmVwSXNWUjRPRXJvNXlaQmxjQUVFWVc4bTdhVjU4aytaQVkwRVhiZlVKQUVNQU4zU3BLWExnK2F3V1pmVVArWWMKMVh0YXhhdXI5eTJOeVN4a0xrU1dmT2x5OTlmbFRkTTNjenpCQiswM2pwYjN2elYwbVU5UDNwSEducW82QzVtNwpxU0tQaDR2azNWR3JPNWR0TUNsenRuMCtDcXJ0cCtxVEttUS9IVEdVSXRxT01pSW9FZ0Z5emVLc3E3N3pWdmJHCmZIbkFwVFVTaXVtT3J1K2tFTVRwa0RlOVBtUkVmWldpOElxU2xCd1F1T1craFg0SFJhU1p0ejlsYmFjaElCU04KYjBEL2VqWVhKUGR4WFR0Snk4NXhrYUNDQmFUOGZVVEJVSWpGZHNrdU9WUmZQVnlLQVdpUDhZUjl4ZkpzSE5uZgpjcE85b1RlQWtENHRvVm5EcE9wK3B3dmQ2cU1jZ3BiZFFkMGN1V0oyZXRkK1JDUVQ4NkZqYVVnUjZFS21ERDdzCjFtT1pxUDU1eEI2MGs4VHZPcGQ0Y1JoQWtMV1lMdEhQTmg0RXREYkNVTWtzeWVHVnRlMnJOeERwdnpFUGY4ejMKV3QzWEEvNjFsc3VVejdHdS81Yy9XWnVqdnhSTnB2cXpzV056UVZhQkJwMVBmdGRVd3pVcFFIbFYwcmNNT3FBWApPMFhIODFYRWphWTdycXFoQUhDTDgwRithU0Q3ZldaaEoxYUwvUzBCV1g2UHB3QVJBUUFCdEM5QmNHVjRJRk52ClpuUjNZWEpsSUZCc1lYUm1iM0p0SUR4cGJtWnZRR0Z3WlhndGNHeGhkR1p2Y20wdWIzSm5Qb2tCMUFRVEFRb0EKUGhZaEJJU1dHK3dGWGJnMUgvOW5hdnJSTTRPaDJkUjNCUUpkdDlRa0Foc0RCUWtEd21jQUJRc0pDQWNDQmhVSwpDUWdMQWdRV0FnTUJBaDRCQWhlQUFBb0pFUHJSTTRPaDJkUjM5WElMLzFXRzFmMk1nNXg0alZtTlFHWEtyMk5WCkFudFg5TDZ4S0tUczhjenh0WkV2eHZ5RHYyWUZJYUZaSkhFYm5JbVZZVHB3ME0xbkpSckxNUW4xNnl0VVR2VmsKSGx1cUx5T3ZuS0JYNmUxcWRMTlFlWUxMZmlRbGlYSStqVWpZazllYk16ZXRsaEIzWmwwZzNEVlArYXJkNDAyZgo1NktPcHdUQTgyalVmamkxanVSK20wejczRjRvN3FweUZYdWFaYU03Ym9zZ3M4ZmVjWStlcUZqTEV6Y1B0Wlc1ClVHUm9iNmVaSk9jNG95dHVqakFUWlpuU2N1c2QzLzVYa2xNdHFReDNOeXNpWFZTUFB2ZDlTSXFhanBqZTh1S0YKemxDYzFSU0ZxcmphU2g4ZDFqc1JnOXdOL3lCL3NZTnJ3RUtsTGNnWHdVWENlNEVzWlNCa3JQUWpVMDZHMFFxQwpGUjdkMHg1a1RxUG05MXBobVNOc2Vhc2s1K1NpcjNVNys3Y2ZObi9kZkRHeU5hTW5FZkZ2RjNpN0NxTndQOEZYCkwxTlJleUQ1NlQ0aC9tSXluV2R2b0pOZ1UwSGpuYUdqeVBJRGtoL2orWDRFYTdVS2tnYUlMZitFMEY1Ym8wVTgKT0pYMGdpaG9VK05FdzVXZWFVcDJkaXJzMTVYUjBiZjZnSlZBZ2Jsakpia0JqUVJkdDlRa0FRd0F6eUd0M3RidAp0MnM0MmNCSXJuM1FSbnEyNzBUc3p4S3VaNEVmMlRQOW5uaEsvck1IY3RKVHBWNTRTRnVnRlNmRDBUT0RLOEh6CklnalZvUW5TZTJrNXFpMVFydEkyK0dHWGlMUzJ3Yi9MaXkvSDhDVHZhVFJwS1JBbFBZQ2ZHaThxU0ZjYWt1NHQKanpkUVRxRVM4U2NlVm8rdkZvWitTVHR4VnA2NHhESzBGR2lETGplWjVLOGhkcThaZk1WNW1wNnRXa2pFb0pYOApRZkdaQ1I2ZnYzUnVEQVJENVNyUUNwUG02c3NubDZzc1FwZnF1MGFwZVg2akhKUTE5Z3hicmdDZG9kanJRa0pICitlMGtSMFFlVXIrQ0tTTDR4MEI4bnBMV2ZnOFdPdzM1cDN6Rm1Hejh6c2puS2wrZkV6ODdhemJVcEJUTThkUXQKZlAybkpIVW01TW9JSmdOYlhXREhrNlRBTmh5bjlwUElmVGkyNEZ4d3o1K29oQkZKR0pORWlVclJwbWZjUjlCcgowMzc1OCtmYUd2MEFLaUVSZFJIQ0FiUkJkYW9wM2p6RUltMGRZV0NyRTlFblRmZjNGSWQyUW9hMnFDSGY5RDlYCkhleHRjWlpPQzJiZ2lLNSsvVWl5cjlPcEY1dmpJUnJOYVNZbGVlZ1czV29ZNTNBWlA0L1BTVnZUQUJFQkFBR0oKQWJ3RUdBRUtBQ1lXSVFTRWxodnNCVjI0TlIvL1oycjYwVE9Eb2RuVWR3VUNYYmZVSkFJYkRBVUpBOEpuQUFBSwpDUkQ2MFRPRG9kblVkenpWREFEYWlEVlpjbDR3UnBPbjNxd0Jjb3BqdDMzKzhIcmx2TFJnRXl6S1NZdWs2WHd4Cnp4ZmpDeTlYNzE2WDhxcCtXNUY0SFB5Q0lHK1RxdGk2MnMrQW5remFmc3hzbDRWMFF2Y0h6Vk1PNm02cWZIR3QKdnhDcFlneGpUYXdrY3BtWFl0UDMrRE8zbGFlZjBBQTBTVjVHYWxUKzVMNm9zNk12d1A3ZERWOHNOS1dyakNJRAp1SzVDUGczRUxjNjQrM1VFRDN0UW9Xb042VS9JZUZybmh1Wk50TkR0U2lyTUVRS2ZFQjhVazJ2N1BuSGpYWmVHCjM3N05LQzRqZmkzTFo3WmhrS3FVQXd1L3cwaTIwSkl0ejhwRi9KWjRIZWFwSHVsRDA4cHBObjBJdG5PSURWM1gKTWNxZ1hBZkcwSGp5ekdpVnhtZ3R3NlJyL2dhNml1c2x2R1ZZbkNxTlZmNUowcFNJYmRxR1IxV2g4dUdFVWw5VAp4OEp0V3djNnZETlZrWER0WTc4S2JCcmQzMXdEcVdhWkt3YmljbkwyQUNCUG5LdmlSY3VMbFlsOHp0RG12Y3BtCjhEcVdtRUFhMFlZNkhaejdTaHNTOGR3Q090Ylo3UHZVWXZnQWYrZk45cG9aazM5ZnFQaGRWRytXclZmMmdkNVAKMHViWHpaaUhNVFg4c1g5RStVV1pBWTBFWGJmVWt3RU1BTWxEOXVYT0krU1RDVjZPcitqZE1McFk2b1BxQVFaNApWeWhvUDZVTjZwc2N2NHFQZ2lFSGNlNjFaZjZ5azZPaHpTVUdoZUZISnJ6RHJQTnIzeFp4UDNTVFlZOTJSUzVkCnlrS1RwRkV1UWhHMnlBT3pxTFdHWVpxZXVGQnVlZXFPV1h4V1FUWnUvSytmUlNIVnZYbDlPR0RXeG9Fd3pNSXcKcFhPdHF0dEo5djJrczZjSXEzZnJQUXlrNWErN0FWeWNZMUE0QUg5a0R3eEtPaUtVUWNQbDRmKyt0MzMvSHYyWApGaWVDM1FVTm0wQXhiVW85eHBlYUtnaXY2c0laSGlsVE1YWXNHZmx1OUpHWnpnZXRBT2xyZUFteUFpWGNIMTI4CnhKbko1NjVaeHlvd3dFOWVZRUFrYnFPOWR4Q0xTbmV0SVgwbVFWOWtYV0s0Vytia1lhK3FWYVFPWHRlT3NyZi8KM1lwYmF5bzE0TGZ6aTZNVTZnVUV3L1JiK2t5VGpXdXFFY2NjQ1pGekF0S2Q5YnlkV0I2NUpKdFk4T2FhMk1mNApCd1ByVSszZkNTYVJRRjB0WkVRY25VcnJoWTQwYVo0dDNSTGQ2MGhIbGdhSVFFTDltNDQ3QURyaDVJYUM3YlVjClRlRlN6ZGMxZEVnY0hoUDVqQXo0OURwQTBSSXFCenFxcFFBUkFRQUJ0QzlCY0dWNElGTnZablIzWVhKbElGQnMKWVhSbWIzSnRJRHhwYm1adlFHRndaWGd0Y0d4aGRHWnZjbTB1YjNKblBva0IxQVFUQVFvQVBoWWhCSDlqQXhPZgpySGgrK3hNeUVvWUJ3c1VMTE1XakJRSmR0OVNUQWhzREJRa0R3bWNBQlFzSkNBY0NCaFVLQ1FnTEFnUVdBZ01CCkFoNEJBaGVBQUFvSkVJWUJ3c1VMTE1XakJpa0wvaU1Vc3NxNkE2djJZdEQ5N0tRN0FsSHlhaWxETFVseFA0b2EKWXlkb0ptQjh0cFh6WTJJUzZnSHZ1MTQzSzBWODFLTlZUM1BHa3cvd21QVzJ4VWpJV05YeWp3cGU5WUdkaXlrNwp2cUtBSDBSVktJaUNCYUZrWHJjOGpKQlZDSFZyUkZSTnhTTU9JUE84bDdxSlEvell3MEhCNTN3VEorRWlOV2lnCmozdld3TE5wOVhBTWhyS1cyZ2RaK0JhY1prb1hGRWVwaEtTdm9kRmZTNUhlaUxrZnFVLzJ4N0JjbzV4b0dvMzQKZVhmcU02M2V4akhyOVVkQ01HVktLUDJ0ek1BSW5DZ29EVVVjRmhLRFRheU0zQitWaWZLRFhpclA2Q0lZN3ZCSAp2dFRhM0wwamFtYTZYYmVjTC8yRllZdlpUcHFrQktkZjNGWEZ6TFRsQTd6cnJ6Zmo4VG1lcHJOa1FwZ3RsWHJYCjFTcFdGMjFqZlpDeHZKN2o5bTF4NzdKWmptTzJXNFdZYVVHRTFaRjlWQldPQVVNVlZ6L3NhanFVMk9vSTF3V0YKcnlEQ0dCWDVzNk5mb1A2ZTM3MjdYZHZKNE96dTVNWTRFcW9JTmhuM0l3cDhwRllKdjR0enRjTXZFUXh2NjlUWQpLd1kyaHA1RU16M2FVYnhOZHIvcTM5YXRUeEl4OWJrQmpRUmR0OVNUQVF3QWxWNmtZVTNvQ09PUEsrQStyRDUzClAwMGNBeWEvM3dSdFRSck1Dc2haSnFmbytuanlFbnh1aFo5Z0Q5cVJhOEhXUXB5VWxHcDFjeXRPUmZ2ejRIUTcKOUVoY2Z2ZlVNTlZvOFE1anVjQU94TGZyYUhZTk5vRzlCZS9LcTA3dHFXYmUxaTNXdXd5MWhHMTNFS3NPK3B5Zgp4N0NTVWhKUlZ6YWRTS0R6eEt3K2xHNDNhN0s5ZkJxSzBxTTRNOWR3Z1VIUmZBQVdrSWM1bWJTQ25Zdkt5LzVrCld5UUVtanNFMDdUTDZ4Nm5WTTNxWXlPVWEzb3cxZCtPenBoRmljWEdNSUh6TTJMci9OVGRTTi9ZWTBLeTY5NzQKU1BMZ3JVOUNTQk9FQ0lCZW9jTkNiNWJCQTJSME5aaUx5SmJDWnBvalpjSDlKWkJMRjdMTjlNZDZBU0lGSTR0TApJSmFvaDhPdUYwcVdNNDhseTFKdzFQVzBhb3VKZmFIZTF3QXIwbktaa081cnhBT3o3ait2NE1ZOUVkUFVsQnFZClM3ZjdHdlJ1TXRlM282Y3NxSGc0NXU5UFVLN3Z4WXM4UENaMUJ6UzNJVkxNVnRzRTBhZ2RScTJEelJLMFJjSzcKcUlzSGlicE9rZlp4aDVJZGZiMTE1VUUrbjFjTVA4RlVFS1RyOURIQnNnT25BQkVCQUFHSkFid0VHQUVLQUNZVwpJUVIvWXdNVG42eDRmdnNUTWhLR0FjTEZDeXpGb3dVQ1hiZlVrd0liREFVSkE4Sm5BQUFLQ1JDR0FjTEZDeXpGCm84MUlEQUNONjh0cE9mWm9BS2pLeFkzTUc4ZjNRK2hCdjVWOEFGS3RIaEdWQ09zdWpKZjVYM0Erb2FVUVAyK3MKS1d3SlRyTjl5elJiOStLdEZ1Zi9FYk5ZOGNjeDZ4YnMycTRFVTBZSWYwTHlHMEZ6TE9kVnl6ZEdseE1sdWRqeQpxV0laRjVuZHQvVzBlS1I3ZHplOURqNEdGalQ2ei81N1VlQ0tiWjYrSjhsV0t4T2VQZDRNN1h5MHJJOGZHeE5zCnUyQ0RDOWQ0b0lLSHI5TEFxcWQ4Q1IrRnlXY0tnRmNtWUQ5TXF3WmdyTWtzd2p3SnhVY3NXZ2ZyNDNXL0hVZEQKTXQ0aDJOWnNMNnZzT3pmTUdNanBLNDg1d2UwVklYMVkrYWdoTHNOYWZaYUsxYUhwZjJDQUtGTmo1eXB0dmRkcgp2dVBxZXJqT1piUDhWTlZpQXJPZTJiT3BLd0RSOXlObUk2YTB3c1FONEUyVnhxUXpvOSszQTBYN0FtcWxzSmhhCm9Ub2tvTzJSZnI4Z2xuQ0M5YWZ4aWxkZFBRK2tGVlc0VFpyUDJyQVphd2dZUm9XTWdZV0UwUVNjWVJrTjZSSUEKUS9ieEdhcWhybUMzU0tNZXJGbmNEVzNMQXp0VSs2TmJhR29jbTNMaEdUT2JzeDZwVlhPOGY2MzhsZ2phOWhYQwoyWmhseTBpWkFZMEVYYmZaZEFFTUFPTzJTanN4U3NTbElNSVdDb1VaOTAvTFFVWFErUDgxOFZ5WHNVNklXMTlFCjJOdjNYRVhyMjBxSFlUeFdTYmJSQVZOUVFLbVEzMlJlRVk4US9XK3BSRnFoTUtuSHduL010dFd1YnVmS09WRmkKVE9NZjZTZUpJM2FXL2pwdk5OY2dqWVlxd3dEWElXd2JhNC83VENyT0dhbkZyTk5tZWZ6YVNja0oyU0tzZStFZAphblBERWUyWUY0b29OR2NBbTN5c0hSa0lLa29POG9RUjRFbWJETGJjSE82M0FtN1hObW5nSGhQdStJOHpTdURYCktoWjdGZmg0MFVwOWovVnV0dlQrcHA5dnZZdVJrOWM3eVo4cjc3ajV3WFhUdlJ6TkpjcXBZczl4VlBCamZXd0gKMHVRQThOcTlUVHJtVFhkQUx1ZXJhZ1ErSzhDcDU5VGxqOTFqQlRBVTZsWVd4SStJTHNXNlJJaWpyUVVjbGdkOQp2Nmp0L3hSLzNFQ1BydjRCZHBPYWdLcmNYV0xIWEcwQ1NrNGthVkQyeGxDTDdPaDRZckpYb1dTT3NJendkN0Y1ClVqYW0rc3c5QTc3K2RiMG1TUFczWTJtL29PbEFKb05ZaTdOWGZuakkxbC9TdFFvak1hSDZJNU1XZUd4R3Y3VVkKbUIwQzJMU2JuTnErNGJ5b2VvZFUxUUFSQVFBQnRDOUJjR1Y0SUZOdlpuUjNZWEpsSUZCc1lYUm1iM0p0SUR4cApibVp2UUdGd1pYZ3RjR3hoZEdadmNtMHViM0puUG9rQjFBUVRBUW9BUGhZaEJPN2pmTytvbEVlSDZrcU5Lek1QClFQQXg5b2R4QlFKZHQ5bDBBaHNEQlFrRHdtY0FCUXNKQ0FjQ0JoVUtDUWdMQWdRV0FnTUJBaDRCQWhlQUFBb0oKRURNUFFQQXg5b2R4ZUdjTUFJaWltdUNxcDZJaXBhaTFtNGpzenV6eTdrckpDK1ZDZUhOM2RTdXl5aXZ2eU1DaAoyN2h4SVZYUWtFTnREbmE1cXd5WFBPUUhuS2U4VkZ1K0x6WmlPQXlzSEo2T0J6MndWQjNPTFlSTGF4cGdhMlNsCnByam5nUUp3ZW9FaXhDcEQ4dm5JcmlrODY3S3BSYktSL25GNkNySldVUnh2eGJScEtzMzExakZzM3A4c1pKVEwKalpTQmxwZXZGT2tIVWFJaTZiMTl5Y1dWTHZ4N1h6TG9zbE96ZlU3QittUGdLeko4eWhubjBFekNLTC9LbVZGRQp6ZzloVXRzMnJwMGMvczhBN3RsMVFUbWU1Rm54NjBVMzFOYzN0ekJuOVhwVmFUNzBYTHNKRjNQbnF4YXBRekZoClY3SEdTbGJaTlhNMEFmQW9SVEVISEpERU0reS9HZmlFVWZyZWpXSmx5d2VuUy9uVXpka041SGZ4bStNUk4xWTMKejNqNjRwRzNZRTdmcm1LVkFVR3gvZ3lQOWJaSGJFQVdzclNxYWtFMHhydFdDWEl5b2lpaExUbHNLSEtPS2h4QQpmVTJ4OUI1UHRlRkptMnZsSHdTN2VhdmxjNGo5ZGphYVd0N2U1SVVyaU9HNU5xY0VpUE9SQ0FZUEsrYTFkd3NPCjhoMWN1SlhFOVQzREFOWlBRN2tCalFSZHQ5bDBBUXdBd3VMYVdVOXhzUzNBdVhVaUJaZnppUnM1MkM3L29YYk0KN0dWZ2NtSmhlQ3UrcVc2Qm1MTlBXTHJTNXRiVS9Ma1RBeFNGSDhxNE96ZXAxTVJDdExpaEswb1VlNHZUSXVmVgpQSnZjZm9lMGl0UDc0dDFxZEhGS29QMFNJR3pVZm9MaFZ2andCb1UyTTMzTUJ4SWhlVjNPOUNuWE83Y1Q2WXQ3CmgwNmJGdGRyZWczMnlqWXkyZHRTVjRHQnowd09DdmFSSktLdXpCeWZqOHhyWDYzVDE2V3JXZTN3aFVYT3NOMUQKR2Z0cXZWSFdwb0Qwak9OaUNzdThiWnBLRDNZaVlEZkxqZnhPOWVMZjVQNE81YUlqRFlPMXRjL2d6VHNhODUvVQpCM2E3eDVjbVBjREFuYlU4Wkk0bXRMWDc1eHJhcjNnMjQwZHVGRGNrWjExbnhCS2NLZUxTZ0UxUGpFT3NhRWlqCm5TdWhSeE1kNDdCUjRrdkhqVllsYnk3bmtoNitoN3lUK0hXZnhoalhmemMxOXlFMm8wT2Z5am9wMm5ZVzM3N0gKaVpzOTd3cHhZUnVSajZ3TlFTWE9LUTZYVW82aFd3a25vUUdHU3crQ2RPZ0MzNVYzWFEzU3N2S3ZVbFltbkUxVgpJSzRhWVVJbzRqVXdDam1GMFE2a0ZqVUs4N3NkQktGcEFCRUJBQUdKQWJ3RUdBRUtBQ1lXSVFUdTQzenZxSlJICmgrcEtqU3N6RDBEd01mYUhjUVVDWGJmWmRBSWJEQVVKQThKbkFBQUtDUkF6RDBEd01mYUhjYUN3REFDR1E1cWUKWGd3YzZRczVNNkFPL2tpdVVuK0ZwVlFFQ2xjZVBMNE5yZkFHMVREZUxJaml5MkJaT1NwKzJaQ2NoS1k5WXp5WgpLYzVGWXB0YWR5elB3emFNeUU0WXBVOTdGUXRRcmJ1Y3dheUh4aTV3UG9sK1Z4YWtBU25kVWtuVWhBYkRnSHZHCjg3ZHJJUEcvZnJ4QVQ4ZmtmVEZiQk9uZlNlR1Z1OGlnMUJzd1JDaEpvR1ZXY3g5TU9IM3JPSDBmWDBmaVcvR0cKVE9yN0k5cjVJaUQ5cGNrWTR2MENqV1VXZCs4aGp2bDhENlcrWWxqUzg1cGFSNXQ5ZENhZDBmVXB4aDQ1aFRoKwpqd0grKy9wNFNCSDlBRkpuRWdUTzZta0pVTGlZVElxVVNGRi9uMnZaUXljdVQyTHVycnVZc3lpSjNsMDlvSEhOCkJuQnp6UXZJZFhLWFdSeVZSNkVnTnk0YS84Ly9HMnVkWWs0VTBSUTdrcDhYK0tSQ3AzMWpaR0pxOEE3eC9CWHUKakRsWXkxd3R0REFoTk1BbmVVUGFLbVlScFIrSEw4ZmNwMFBtV1dXVVZlVGpjMzlMMW9uNXUwTVNyNWxTMGJZbAp3dGhheEZJS2N4R2NMcmttenNmOXZtZHdkZHdhY3dMNUF5ZFUxSU9vdSthS2hoMmZOeloyNm9yRWlIZz0KPWNuTHYKLS0tLS1FTkQgUEdQIFBVQkxJQyBLRVkgQkxPQ0stLS0tLQo=');
    $fingerprint = $client->import_pgp_key('admin', (int) $admin_id, $public_key);
    $this->assertNotFalse($fingerprint);

    // Update PGP key
    $fingerprint = $client->import_pgp_key('admin', (int) $admin_id, $public_key);
    $this->assertNotFalse($fingerprint);

    / Throw exception
    $this->waitException('Unable to add PGP key');
    $client->import_pgp_key('admin', (int) $admin_id, 'tester');

}

/** 
public function test_encrypt_pgp()
{

    // Initialize
    $client = new encrypt();
    $admin_id = db::get_field("SELECT id FROM admin WHERE username = %s", $_SERVER['apex_admin_username']);
    $message = 'unit test pgp message';
    $recipients = array('admin:' . $admin_id, 'admin:8235235');

    // Encrypt
    $enc_message = $client->encrypt_pgp($message, $recipients);
    $this->assertNotEmpty($enc_message);

}

/**
 * Re-import PGP keys
 */
public function test_reimport_all_pgp_keys()
{

    $client = new encrypt();
    $ok = $client->reimport_all_pgp_keys();
    $this->assertTrue($ok);

}

}


