<?php

/**
  *  Resizes an image and returns the resized URL. Uses native WordPress functionality.
  *
  *  The first function (3.5+) supports GD Library and ImageMagick. WordPress will pick whichever is most appropriate.
  *  The second function (3.4.x and lower) only supports the GD Library. If none of the supported libraries are available, 
  *  the function will return the original image.
  *
  *  Images are saved to the WordPress uploads directory, just like images uploaded through the Media Library.
  * 
  *  Based on resize.php by Matthew Ruddy (GPLv2 Licensed, Copyright (c) 2012, 2013)
  *  https://github.com/MatthewRuddy/Wordpress-Timthumb-alternative
  * 
  *  License: GPLv2
  *  http://www.gnu.org/licenses/gpl-2.0.html
  *
  *  @author Ernesto Méndez (http://der-design.com)
  *  @author Matthew Ruddy (http://rivaslider.com)
  */

if ( isset( $wp_version ) && version_compare( $wp_version, '3.5' ) >= 0 ) {

  ////////////////////////// WP 3.5 and above

  function mr_image_resize( $url, $width = null, $height = null, $crop = true, $align = false, $retina = false ) {

    global $wpdb;

    // Get common vars
    $args = func_get_args();
    $common = mr_common_info($args);
    
    // Unpack vars if got an array...
    if (is_array($common)) extract($common); 
    
    // ... Otherwise, return error, null or image
    else return $common;
    
    if ( !file_exists( $dest_file_name ) ) {

      // We only want to resize Media Library images, so we can be sure they get deleted correctly when appropriate.
      $query = $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE guid='%s'", $url );
      $get_attachment = $wpdb->get_results( $query );

      // Load WordPress Image Editor
      $editor = wp_get_image_editor( $file_path );
      if ( is_wp_error( $editor ) ) return $url;
      
      if ( $crop ) {
        
        $src_x = $src_y = 0;
        $src_w = $orig_width;
        $src_h = $orig_height;

        $cmp_x = $orig_width / $dest_width;
        $cmp_y = $orig_height / $dest_height;

        // Calculate x or y coordinate and width or height of source
        if ($cmp_x > $cmp_y) {

          $src_w = round ($orig_width / $cmp_x * $cmp_y);
          $src_x = round (($orig_width - ($orig_width / $cmp_x * $cmp_y)) / 2);

        } else if ($cmp_y > $cmp_x) {

          $src_h = round ($orig_height / $cmp_y * $cmp_x);
          $src_y = round (($orig_height - ($orig_height / $cmp_y * $cmp_x)) / 2);

        }

        // Positional cropping. Uses code from timthumb.php under the GPL
        if ( $align ) {
          if (strpos ($align, 't') !== false) {
            $src_y = 0;
          }
          if (strpos ($align, 'b') !== false) {
            $src_y = $orig_height - $src_h;
          }
          if (strpos ($align, 'l') !== false) {
            $src_x = 0;
          }
          if (strpos ($align, 'r') !== false) {
            $src_x = $orig_width - $src_w;
          }
        }

      }

      // Crop image
      $editor->crop( $src_x, $src_y, $src_w, $src_h, $dest_width, $dest_height );

      // Save image
      $saved = $editor->save( $dest_file_name );

      // Add the resized dimensions and alignment to original image metadata, so the images
      // can be deleted when the original image is delete from the Media Library.
      if ( $get_attachment ) {
        $metadata = wp_get_attachment_metadata( $get_attachment[0]->ID );
        if ( isset( $metadata['image_meta'] ) ) {
          $md = $saved['width'] .'x'. $saved['height']; if ($align) $md .= "_$align";
          $metadata['image_meta']['resized_images'][] = $md;
          wp_update_attachment_metadata( $get_attachment[0]->ID, $metadata );
        }
      }

      // Resized image url
      $resized_url = str_replace( basename( $url ), basename( $saved['path'] ), $url );

    } else {

      // Resized image url
      $resized_url = str_replace( basename( $url ), basename( $dest_file_name ), $url );

    }
    
    // Return resized url
    return $resized_url;

  }

} else {
  
  ////////////////////////// WP 3.4 and below

  function mr_image_resize( $url, $width = null, $height = null, $crop = true, $align = false, $retina = false ) {

    global $wpdb;

    // Return url if GD library not loaded
    if ( !extension_loaded('gd') || !function_exists('gd_info') ) return $url;

    // Get common vars
    $args = func_get_args();
    $common = mr_common_info($args);

    // Unpack vars if got an array...
    if (is_array($common)) extract($common); 
    
    // ... Otherwise, return error, null or image
    else return $common; 
    
    // No need to resize & create a new image if it already exists!
    if ( !file_exists( $dest_file_name ) ) {

      // Load image from path
      $image = wp_load_image( $file_path );

      // If unable to read image, return error or null
      if ( !is_resource( $image ) ) {
        return is_user_logged_in() ? "image_load_failure" : null;
      }

      // We only want to resize Media Library images, so we can be sure they get deleted correctly when appropriate.
      $query = $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE guid='%s'", $url );
      $get_attachment = $wpdb->get_results( $query );

      // Create new image
      $new_image = wp_imagecreatetruecolor( $dest_width, $dest_height );

      if ( $crop ) {

        $src_x = $src_y = 0;
        $src_w = $orig_width;
        $src_h = $orig_height;

        $cmp_x = $orig_width / $dest_width;
        $cmp_y = $orig_height / $dest_height;

        // Calculate x or y coordinate and width or height of source
        if ($cmp_x > $cmp_y) {

          $src_w = round ($orig_width / $cmp_x * $cmp_y);
          $src_x = round (($orig_width - ($orig_width / $cmp_x * $cmp_y)) / 2);

        } else if ($cmp_y > $cmp_x) {

          $src_h = round ($orig_height / $cmp_y * $cmp_x);
          $src_y = round (($orig_height - ($orig_height / $cmp_y * $cmp_x)) / 2);

        }

        // Positional cropping. Uses code from timthumb.php under the GPL
        if ( $align ) {
          if (strpos ($align, 't') !== false) {
            $src_y = 0;
          }
          if (strpos ($align, 'b') !== false) {
            $src_y = $orig_height - $src_h;
          }
          if (strpos ($align, 'l') !== false) {
            $src_x = 0;
          }
          if (strpos ($align, 'r') !== false) {
            $src_x = $orig_width - $src_w;
          }
        }

        imagecopyresampled( $new_image, $image, 0, 0, $src_x, $src_y, $dest_width, $dest_height, $src_w, $src_h );

      } else {

        imagecopyresampled( $new_image, $image, 0, 0, 0, 0, $dest_width, $dest_height, $orig_width, $orig_height );

      }

      // Convert from full colors to index colors, like original PNG.
      if ( IMAGETYPE_PNG == $orig_type && function_exists('imageistruecolor') && !imageistruecolor( $image ) ) {
        imagetruecolortopalette( $new_image, false, imagecolorstotal( $image ) );
      }

      // Flush original image
      imagedestroy( $image );

      switch ($orig_type) {

        case IMAGETYPE_GIF:
          // Return if unable to write GIF
          if ( !imagegif( $new_image, $dest_file_name ) ) {
            return is_user_logged_in() ? "create_gif_failure" : null;
          }
          break;

        case IMAGETYPE_PNG:
          // Return if unable to write PNG
          if ( !imagepng( $new_image, $dest_file_name ) ) {
            return is_user_logged_in() ? "create_png_failure" : null;
          }
          break;

        default:
          // All other formats are converted to jpg
          if ( $ext != 'jpg' && $ext != 'jpeg' ) {
            $dest_file_name = "{$dir}/{$name}-{$suffix}.jpg";
          }

          // Return if unable to write JPG
          if ( !imagejpeg( $new_image, $dest_file_name, apply_filters( 'resize_jpeg_quality', 90 ) ) ) {
            return is_user_logged_in() ? "create_jpg_failure" : null;
          }
          break;

      }

      // Flush newly created image
      imagedestroy( $new_image );

      // Set file permissions
      $stat = stat( dirname( $dest_file_name ));
      $perms = $stat['mode'] & 0000666;
      @chmod( $dest_file_name, $perms );

      // Get some information about the resized image
      $new_size = @getimagesize( $dest_file_name );

      // Return if unable to get size
      if ( !$new_size ) {
        return is_user_logged_in() ? "getimagesize_error_create" : null;
      }

      // Get new size info
      list( $resized_width, $resized_height ) = $new_size;

      // Add the resized dimensions and alignment to original image metadata, so the images
      // can be deleted when the original image is delete from the Media Library.
      $metadata = wp_get_attachment_metadata( $get_attachment[0]->ID );
      if ( isset( $metadata['image_meta'] ) ) {
        $md = $resized_width .'x'. $resized_height; if ($align) $md .= "_$align";
        $metadata['image_meta']['resized_images'][] = $md;
        wp_update_attachment_metadata( $get_attachment[0]->ID, $metadata );
      }

    }

    // Get the new image URL
    $resized_url = str_replace( basename( $url ), basename( $dest_file_name ), $url );

    // Return url
    return $resized_url;

  }

}

// Returns common information shared by processing functions
function mr_common_info($args) { global $der;
  
  // Unpack arguments
  list($url, $width, $height, $crop, $align, $retina) = $args;
  
  // Return null if url empty
  if ( empty( $url ) ) {
    return is_user_logged_in() ? "image_not_specified" : null;
  }
  
  // Return if nocrop is set on query string
  if (preg_match('/(\?|&)nocrop/', $url)) {
    return $url;
  }

  // Get the image file path
  $pathinfo = parse_url( $url );
  $file_path = ABSPATH . str_replace(dirname($_SERVER['SCRIPT_NAME']) . '/', '', $pathinfo['path']);

  if ( is_multisite() ) {
    if (preg_match('/\/blogs.dir\//', $file_path)) {
      // Adjust real path on multisite
      $file_path = ABSPATH . strstr($file_path, 'wp-content');
    } else {
      // Adjust normal path on multisite
      global $blog_id;
      $blog = get_blog_details( $blog_id );
      $file_path = ABSPATH . strstr(str_replace($blog->path . 'files/', "wp-content/blogs.dir/${blog_id}/files/", $file_path ), 'wp-content');
    }
  }
  
  // Remove any double slashes
  $file_path = preg_replace('/\/+/', '/', $file_path);
  
  // Don't process a file that doesn't exist
  if ( !file_exists($file_path) ) {
    return null; // Degrade gracefully
  }
  
  // Get original image size
  $size = @getimagesize($file_path);
  
  // If no size data obtained, return error or null
  if (!$size) {
    return is_user_logged_in() ? "getimagesize_error_common" : null;
  }
  
  // Set original width and height
  list($orig_width, $orig_height, $orig_type) = $size;

  // Generate width or height if not provided
	if ($width && !$height) {
		$height = floor ($orig_height * ($width / $orig_width));
	} else if ($height && !$width) {
		$width = floor ($orig_width * ($height / $orig_height));
	} else if (!$width && !$height) {
	  return $url; // Return original url if no width/height provided
	}
	
  // Allow for different retina sizes
  $retina = $retina ? ( $retina === true ? 2 : $retina ) : 1;

  // Destination width and height variables
  $dest_width = $width * $retina;
  $dest_height = $height * $retina;

  // File name suffix (appended to original file name)
  $suffix = "{$dest_width}x{$dest_height}";

  // Some additional info about the image
  $info = pathinfo( $file_path );
  $dir = $info['dirname'];
  $ext = $info['extension'];
  $name = wp_basename( $file_path, ".$ext" );

  // Suffix applied to filename
  $suffix = "{$dest_width}x{$dest_height}";
  
  // Set align info on file
  if ($align) $suffix .= "_$align";

  // Get the destination file name
  $dest_file_name = "{$dir}/{$name}-{$suffix}.{$ext}";
  
  // Return info
  return array(
    'dir' => $dir,
    'name' => $name,
    'ext' => $ext,
    'suffix' => $suffix,
    'orig_width' => $orig_width,
    'orig_height' => $orig_height,
    'orig_type' => $orig_type,
    'dest_width' => $dest_width,
    'dest_height' => $dest_height,
    'file_path' => $file_path,
    'dest_file_name' => $dest_file_name,
  );

}

// Deletes the resized images when the original image is deleted from the WordPress Media Library.
add_action( 'delete_attachment', 'mr_delete_resized_images' );
function mr_delete_resized_images( $post_id ) {

  // Get attachment image metadata
  $metadata = wp_get_attachment_metadata( $post_id );
  if ( !$metadata ) return;
    
  // Do some bailing if we cannot continue
  if ( !isset( $metadata['file'] ) || !isset( $metadata['image_meta']['resized_images'] ) )  return;
  $pathinfo = pathinfo( $metadata['file'] );
  $resized_images = $metadata['image_meta']['resized_images'];

  // Get WordPress uploads directory (and bail if it doesn't exist)
  $wp_upload_dir = wp_upload_dir();
  $upload_dir = $wp_upload_dir['basedir'];
  if ( !is_dir( $upload_dir ) ) return;

  // Delete the resized images
  foreach ( $resized_images as $dims ) {

    // Get the resized images filename
    $file = $upload_dir .'/'. $pathinfo['dirname'] .'/'. $pathinfo['filename'] .'-'. $dims .'.'. $pathinfo['extension'];

    // Delete the resized image
    @unlink( $file );

  }

}
