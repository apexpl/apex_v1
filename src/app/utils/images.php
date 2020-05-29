<?php
declare(strict_types = 1);

namespace apex\app\utils;

use apex\app;
use apex\libc\{db, debug, forms, io, cache, storage}; 


/**
 * Image Handling Library
 *
 * Service: apex\libc\images
 *
 * Class to handle image storage and manipulation, including 
 * uploading / adding new images, generating thumbnails, 
 *retrieving / storing images, and more.
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
 * use apex\libc\images;
 *
 * // Upload image
 * $product_id = 432;
 * images::upload('product_image', 'product', $product_id);
 *
 */
class images
{




/**
 * Add a new image to the database 
 *
 * @param string $filename The filename of the image
 * @param string $contents The contents of the image
 * @param string $type The type of image (eg. user, product, etc.)
 * @param mixed $record_id Optional record ID to retrieve the image later
 * @param string $size The size of the image, defaults to 'full'.
 * @param int $is_default int (1/0) that defines whether or not the image is default for this type (eg. default user avatar)
 *
 * @return int The ID# of the new image
 */
public function add(string $filename, string $contents, string $type, $record_id = '', string $size = 'full', int $is_default = 0):int
{ 

    // Debug
    debug::add(4, tr("Starting to add image with type: {1}, record_id: {2}, size: {3}", $type, $record_id, $size));

    // Save image to filesystem
    $tmpfile = tempnam(sys_get_temp_dir(), 'apex');
    if (file_exists($tmpfile)) { @unlink($tmpfile); }
    file_put_contents($tmpfile, $contents);

    // Get image dimensions
    if (!@list($width, $height, $mime_type, $attr) = getimagesize($tmpfile)) { 
        return false;
    }

    // Get filename
    if ($size != 'full') { 

        // Get image extension
        if (preg_match("/^(.+)\.(\w+)$/", $filename, $match)) { 
            $extension = $match[2];
        } else { $extension = 'jpg'; }
        $filename = $match[1] . '_' . $size . '.' . $extension;
    }

    // Delete existing image, if exists
    db::query("DELETE FROM images WHERE type = %s AND record_id = %s AND size = %s", $type, $record_id, $size);

    // Add to DB
    db::insert('images', array(
        'type' => $type,
        'record_id' => $record_id,
        'is_default' => $is_default,
        'size' => $size,
        'width' => $width,
        'height' => $height,
        'mime_type' => $mime_type,
        'filename' => $filename)
    );
    $image_id = db::insert_id();

    // Save file to server
    if (app::_config('core:image_storage_type') == 'filesystem') { 
        $prefix = substr($filename, 0, 2);
        $filepath = 'images/' . $prefix . '/' . $filename;
        storage::add_contents($filepath, $contents);

    // Add to database
    } else { 

        db::insert('images_contents', array(
            'id' => $image_id,
            'contents' => $contents)
        );
    }

    // Debug
    debug::add(3, tr("Added new image to database, type: {1}, record_id: {2}", $type, $record_id));

    // Return
    return $image_id;

}

/**
 * Upload a new image 
 *
 * @param string $form_field The name of the form field of the uploaded image.
 * @param string $type The type of image (eg. user, product, etc.)
 * @param string $record_id Optional record ID# of the image to retrieve it later.
 * @param int $is_default Whether or not this is the default image for this type.
 *
 * @return int The ID# of the image
 */
public function upload(string $form_field, string $type, $record_id = '', int $is_default = 0)
{ 

    // Debug
    debug::add(4, tr("Starting to upload / add new image of type: {1}, record_id: {2},from form field: {3}", $type, $record_id, $form_field));

    // Get the uploaded file
    if (!$vars = app::_files($form_field)) { 
        return false;
    }

    // Add the file
    $image_id = $this->add($vars['name'], $vars['contents'], $type, $record_id, 'full', $is_default);

    // Return
    return $image_id;

}

/**
 * Retrive image from the database 
 *
 * @param string $type The type of image (eg. user, product, etc.)
 * @param string $record_id The record ID# of the image.
 * @param string $size The size of the image
 * @param bool $allow_default If yes and image does not exist, will check for default image
 */
public function get(string $type, $record_id = '', string $size = 'full', bool $allow_default = false)
{ 

    // Check cache, if appropriate
    $cache_item = implode(':', array('image', $type, $record_id, $size));
    if (app::_config('core:cache') == 1 && $vars = cache::get($cache_item)) { 
        return array($vars['filename'], $vars['mime_type'], $vars['width'], $vars['height'], $vars['contents']);
    }

    // Check database
    if (!$row = db::get_row("SELECT * FROM images WHERE type = %s AND record_id = %s AND size = %s", $type, $record_id, $size)) { 

        // Check for default
        if ($allow_default === true && !$row = db::get_row("SELECT * FROM images WHERE type = %s AND size = %s AND is_default = 1", $type, $size)) { 
            return false;
        }
    }
    if (!$row) { return false; }

    // Get contents
    if (app::_config('core:image_storage_type') == 'filesystem') { 
        $prefix = substr($row['filename'], 0, 2);
        $contents = storage::get('images/' . $prefix . '/' . $row['filename']);
    } else {
        $contents = db::get_field("SELECT contents FROM images_contents WHERE id = %i", $row['id']);
    }

    // Set vars
    $vars = array(
        'filename' => $row['filename'], 
        'mime_type' => $row['mime_type'], 
        'width' => $row['width'], 
        'height' => $row['height'], 
        'contents' => $contents
    );

    // Unescape, if PostgreSql
    if (app::_config('core:db_driver') == 'postgresql') { $vars['contents'] = pg_unescape_bytea($vars['contents']); }

    // Add to cache, if needed
    if (app::_config('core:cache') == 1) { 
        cache::set($cache_item, $vars);
    }


    // Return
    return array($vars['filename'], $vars['mime_type'], $vars['width'], $vars['height'], $vars['contents']);

}

/**
 * Add a thumbnail 
 *
 * @param string $image_type The type of image (product, profile, etc.)
 * @param mixed $record_id The ID# of the image, unique to the image type.
 * @param string $size The new size of the thumbnail (eg. thumb, small, tiny)
 * @param int $thumb_width The width of the thumbnail to generate.
 * @param int $thumb_height The height of the thumbnail to generate.
 * @param int $is_default A (1/0) defining whether this is the default image for the image type.
 */
public function add_thumbnail(string $image_type, $record_id, string $size, int $thumb_width, int $thumb_height, $is_default = 0)
{ 

    // Get contents of existing image
    if (!list($filename, $type, $width, $height, $contents) = $this->get($image_type, $record_id, 'full')) { 
        return false;
    }

    // Save tmp file
    $tmpfile = tempnam(sys_get_temp_dir(), 'apex');
    if (file_exists($tmpfile)) { @unlink($tmpfile); }
    file_put_contents($tmpfile, $contents);

    // Initialize image
    if ($type == IMAGETYPE_GIF) { 
        @$source = imagecreatefromgif($tmpfile);
        $ext = 'gif';
    } elseif ($type == IMAGETYPE_JPEG) { 
        @$source = imagecreatefromjpeg($tmpfile);
        $ext = 'jpg';
    } elseif ($type == IMAGETYPE_PNG) { 
        @$source = imagecreatefrompng($tmpfile);
        $ext = 'png';
    } else { return false; }

    // Get ratios
    $ratio_x = sprintf("%.2f", ($width / $thumb_width));
    $ratio_y = sprintf("%.2f", ($height / $thumb_height));

    // Resize image, if needed
    if ($ratio_x != $ratio_y) { 
        if ($ratio_x > $ratio_y) { 
            $new_width = $width;
            $new_height = ($height - sprintf("%.2f", ($height * ($ratio_x - $ratio_y)) / 100));
        } elseif ($ratio_y > $ratio_x) { 
            $new_height = $height;
            $new_width = ($width - sprintf("%.2f", ($width * ($ratio_y - $ratio_x)) / 100));
        }
        $new_width = (int) $new_width;
        $new_height = (int) $new_height;

        // Resize
        imagecopy($source, $source, 0, 0, 0, 0, (int) $new_width, (int) $new_height);
        $width = $new_width;
        $height = $new_height;
    }

    // Create thumbnail
    $thumb_source = imagecreatetruecolor($thumb_width, $thumb_height);
    imagecopyresized($thumb_source, $source, 0, 0, 0, 0, (int) $thumb_width, (int) $thumb_height, (int) $width, (int) $height);

    // Get thumb filename
    $thumb_filename = tempnam(sys_get_temp_dir(), 'apex');

    // Save thumbnail
    if ($type == IMAGETYPE_GIF) { 
        imagegif($thumb_source, $thumb_filename);
    } elseif ($type == IMAGETYPE_JPEG) { 
        imagejpeg($thumb_source, $thumb_filename);
    } elseif ($type == IMAGETYPE_PNG) { 
        imagepng($thumb_source, $thumb_filename);
    } else { return false; }

    // Return file
    $thumb_contents = file_get_contents($thumb_filename);
    @unlink($thumb_filename);
    @unlink($tmpfile);

    // Free memory
    imagedestroy($source);
    imagedestroy($thumb_source);

    // Insert thumbnail to db
    $thumb_id = $this->add($filename, $thumb_contents, $image_type, $record_id, $size, $is_default);

    // Debug
    debug::add(4, tr("Created thumbnail for image of type: {1}, record_id: {2} of size: {3}", $type, $record_id, $size));

    // Return
    return $thumb_id;

}

/**
 * Display image 
 * 
 * @param string $type The type of image
 * @param mixed $record_id The ID# of the record, unique to the image type.
 * @param string $size The size of the image to display.
 */
public function display(string $type, $record_id = '', string $size = 'full')
{ 

    // Get image
    if (!list($filename, $mime_type, $width, $height, $contents) = $this->get($type, $record_id, $size, true)) { 
        app::set_res_content_type('text/plain');
        app::set_res_body('No image exists here');
        return;
    }

    // Set response variables
    app::set_res_content_type($mime_type);
    app::set_res_body($contents);

}


}

