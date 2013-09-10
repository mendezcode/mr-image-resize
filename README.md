
# mr-image-resize

Resizes an image and returns the resized URL. Uses native WordPress functionality.

The function supports GD Library and ImageMagick. WordPress will pick whichever is most appropriate.
If none of the supported libraries are available, the function will return the original image url.

Images are saved to the WordPress uploads directory, just like images uploaded through the Media Library.

Supports WordPress 3.5 and above.

Positional Cropping is supported using timthumb-compatible [parameters](http://www.binarymoon.co.uk/2010/08/timthumb-part-4-moving-crop-location/), which
allow you to control how the image is cropped.

Based on [resize.php](https://github.com/MatthewRuddy/Wordpress-Timthumb-alternative) by Matthew Ruddy.


## Parameters

The function accepts the following parameters:

- `$url` _image URL to process_
- `$width` _output width_
- `$height` _output height_
- `$crop`  _enables cropping (true by default)_
- `$align` _positional cropping parameter (defaults to center)_
- `$retina` _use double pixel ratio (true by default)_

If either **$width** or **$height** is not specified, its value will be calculated proportionally.


## Example Usage

```php
// Put this in your functions.php
function theme_thumb($url, $width, $height=0, $align='') {
  return mr_image_resize($url, $width, $height, true, $align, false);
}

$thumb = theme_thumb($image_url, 800, 600, 'br'); // Crops from bottom right

echo $thumb;
```


## Positional Cropping

The **$align** parameter accepts the following arguments:

- `c` _position in the center (default)_
- `t` _align top_
- `tr` _align top right_
- `tl` _align top left_
- `b` _align bottom_
- `br` _align bottom right_
- `bl` _align bottom left_
- `l` _align left_
- `r` _align right_


## Skip Processing

If an image has the `nocrop` query string parameter, processing will be ignored, returning the original URL.


## License

GPLv2, See [LICENSE](https://github.com/derdesign/mr-image-resize/blob/master/LICENSE) for details.