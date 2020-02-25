<?php
/*
Plugin Name: wp-resize
Plugin URI: https://github.com/reevoke/wp-resize
Description: Resize images and create WebP versions of your images. Webp images are only served on Chrome.
Version: 1.0.0
Author: Alexander de Jong
Author URI: https://www.alexanderdejong.com
License: GPL-2.0+
Text Domain: wp-resize
*/


/**
 * Begins execution of the plugin.
 * @since    1.0.0
 */
Class resize
{
    // *** Class variables
    public $image;
    private $width;
    private $height;
    private $imageResized;

    function __construct($fileName)
    {

        // *** Open up the file
        $this->image = $this->openImage($fileName);

        if ($this->image) {
            // *** Get width and height
            $this->width = imagesx($this->image);
            $this->height = imagesy($this->image);
        } else {
            return false;
        }
    }

    /**
     * Begins execution of the plugin.
     * @since    1.0.0
     */

    private function openImage($file)
    {
        // *** Get extension
        $extension = strtolower(strrchr($file, '.'));
        $img = false;
        switch ($extension) {
            case '.jpg':
                $img = @imagecreatefromjpeg($file);
                break;
            case '.jpeg':
                $img = @imagecreatefromjpeg($file);
                break;
            case '.gif':
                $img = @imagecreatefromgif($file);
                break;
            case '.png':
                $img = @imagecreatefrompng($file);
                break;
            default:
                $img = false;
                break;
        }
        return $img;
    }

    /**
     * Begins execution of the plugin.
     * @since    1.0.0
     */

    public function resizeImage($newWidth, $newHeight, $option = "auto")
    {
        // *** Get optimal width and height - based on $option
        $optionArray = $this->getDimensions($newWidth, $newHeight, $option);

        $optimalWidth = $optionArray['optimalWidth'];
        $optimalHeight = $optionArray['optimalHeight'];


        // *** Resample - create image canvas of x, y size
        $this->imageResized = imagecreatetruecolor($optimalWidth, $optimalHeight);
        imagealphablending($this->imageResized, false);
        imagesavealpha($this->imageResized, true);
        imagecopyresampled($this->imageResized, $this->image, 0, 0, 0, 0, $optimalWidth, $optimalHeight, $this->width, $this->height);


        // *** if option is 'crop', then crop too
        if ($option == 'crop') {
            $this->crop($optimalWidth, $optimalHeight, $newWidth, $newHeight);
        }
    }

    private function getDimensions($newWidth, $newHeight, $option)
    {

        switch ($option) {
            case 'exact':
                $optimalWidth = $newWidth;
                $optimalHeight = $newHeight;
                break;
            case 'portrait':
                $optimalWidth = $this->getSizeByFixedHeight($newHeight);
                $optimalHeight = $newHeight;
                break;
            case 'landscape':
                $optimalWidth = $newWidth;
                $optimalHeight = $this->getSizeByFixedWidth($newWidth);
                break;
            case 'auto':
                $optionArray = $this->getSizeByAuto($newWidth, $newHeight);
                $optimalWidth = $optionArray['optimalWidth'];
                $optimalHeight = $optionArray['optimalHeight'];
                break;
            case 'crop':
                $optionArray = $this->getOptimalCrop($newWidth, $newHeight);
                $optimalWidth = $optionArray['optimalWidth'];
                $optimalHeight = $optionArray['optimalHeight'];
                break;
        }
        return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
    }

    /**
     * Begins execution of the plugin.
     * @since    1.0.0
     */

    private function getSizeByFixedHeight($newHeight)
    {
        $ratio = $this->width / $this->height;
        $newWidth = $newHeight * $ratio;
        return $newWidth;
    }

    /**
     * Begins execution of the plugin.
     * @since    1.0.0
     */

    private function getSizeByFixedWidth($newWidth)
    {
        $ratio = $this->height / $this->width;
        $newHeight = $newWidth * $ratio;
        return $newHeight;
    }

    /**
     * Begins execution of the plugin.
     * @since    1.0.0
     */

    private function getSizeByAuto($newWidth, $newHeight)
    {
        if ($this->height < $this->width) // *** Image to be resized is wider (landscape)
        {
            $optimalWidth = $newWidth;
            $optimalHeight = $this->getSizeByFixedWidth($newWidth);
        } elseif ($this->height > $this->width) // *** Image to be resized is taller (portrait)
        {
            $optimalWidth = $this->getSizeByFixedHeight($newHeight);
            $optimalHeight = $newHeight;
        } else // *** Image to be resizerd is a square
        {
            if ($newHeight < $newWidth) {
                $optimalWidth = $newWidth;
                $optimalHeight = $this->getSizeByFixedWidth($newWidth);
            } else if ($newHeight > $newWidth) {
                $optimalWidth = $this->getSizeByFixedHeight($newHeight);
                $optimalHeight = $newHeight;
            } else {
                // *** Sqaure being resized to a square
                $optimalWidth = $newWidth;
                $optimalHeight = $newHeight;
            }
        }

        return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
    }

    /**
     * Begins execution of the plugin.
     * @since    1.0.0
     */

    private function getOptimalCrop($newWidth, $newHeight)
    {

        $heightRatio = $this->height / $newHeight;
        $widthRatio = $this->width / $newWidth;

        if ($heightRatio < $widthRatio) {
            $optimalRatio = $heightRatio;
        } else {
            $optimalRatio = $widthRatio;
        }

        $optimalHeight = $this->height / $optimalRatio;
        $optimalWidth = $this->width / $optimalRatio;

        return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
    }

    /**
     * Begins execution of the plugin.
     * @since    1.0.0
     */

    private function crop($optimalWidth, $optimalHeight, $newWidth, $newHeight)
    {
        // *** Find center - this will be used for the crop
        $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
        $cropStartY = ($optimalHeight / 2) - ($newHeight / 2);

        $crop = $this->imageResized;
        //imagedestroy($this->imageResized);

        // *** Now crop from center to exact requested size
        $this->imageResized = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($this->imageResized, $crop, 0, 0, $cropStartX, $cropStartY, $newWidth, $newHeight, $newWidth, $newHeight);
    }

    public function saveImage($savePath, $destination, $imageQuality = "100")
    {
        if ( !is_admin() ) {
            // *** Get extension
            $extension = strrchr($savePath, '.');
            $extension = strtolower($extension);

            switch ($extension) {
                case '.jpg':
                case '.jpeg':
                    if (imagetypes() & IMG_JPG) {
                        imagejpeg($this->imageResized, $savePath, $imageQuality);
                        imagewebp($this->imageResized, $destination);
                    }
                    break;

                case '.gif':
                    if (imagetypes() & IMG_GIF) {
                        imagegif($this->imageResized, $savePath);
                    }
                    break;

                case '.png':
                    imageCreateFromPng($this->imageResized, $savePath);
                    imagewebp($this->imageResized, $destination);

                    /*$this->saveWebm($savePath);

                    $scaleQuality = round(($imageQuality / 100) * 9);

                    $invertScaleQuality = 4;
                    imagealphablending( $this->imageResized, false );
                    imagesavealpha( $this->imageResized, true );

                    if (imagetypes() & IMG_PNG) {
                        imagepng($this->imageResized, $savePath, $invertScaleQuality, PNG_ALL_FILTERS);
                        $this->saveWebm($savePath);
                    }*/
                    break;


                default:
                    // *** No extension - No save.
                    break;
            }
            imagedestroy($this->imageResized);
        }
    }

}

/**
 * Begins execution of the plugin.
 * @since    1.0.0
 */

function resizeimg($url = array(), $width = 100, $height = 100, $type = 'auto', $quality = 80)
{
    if (!empty($url)) {

        /**
         * We need to get the User Agent so that we can check for Chrome / Safari later.
         * @since    1.0.0
         * @var $user_agent
         */
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        if ( is_multisite() ) {
            $network = get_blog_details(get_network()->site_id);
            $network_site = $network->domain;
        } else {
            $network_site = get_site_url(null);
        }

        $url = str_replace('https://' . $network_site, '', $url);
        $original_url = $url;
        $url = 'https://' . $network_site . $url;
        $current_blog_id = get_current_blog_id();
        $upload_dir = wp_get_upload_dir();

        /**
         * Get the filename and extension
         * @var $original_filename
         * @var $extension
         */
        $original_filename = basename($url);
        $filename = hash('md5', basename($url) . $width . $height . $type . $quality);
        $extension = strtolower(strrchr($url, '.'));

        /**
         * Location of index.php/htdocs/www
         * @var $www_path
         */
        $main_dir = substr(WP_CONTENT_DIR, 0, strrpos(WP_CONTENT_DIR, '/'));

        /**
         * Original and cache image path
         * @var $original_dir
         * @var $cache_url
         * @var $cache_dir
         */
        $original_dir = str_replace(basename($url), '', $original_url);
        $cache_url = $original_dir . 'cache/';
        $cache_dir = $main_dir . $cache_url;

        if (!file_exists($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }

        $source = trim($main_dir . $original_dir . $original_filename);
        $destination_url = trim($cache_url . $filename . $extension);
        $compressed_url = trim($cache_url . $filename . $extension . '.webp');

        $destination_file = trim($main_dir . $destination_url);
        $compressed_file = trim($main_dir . $compressed_url);

        if (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false && file_exists($compressed_file) && stripos($user_agent, 'Chrome') !== false) {

            return $compressed_url;
        }

        if (file_exists($destination_file)) {

            return $destination_url;

        } else if (!file_exists($destination_file)) {

            $resizeObj = new resize(trim($source));

            if (isset($resizeObj->image)) {

                $resizeObj->resizeImage($width, $height, $type);
                $resizeObj->saveImage($destination_file, $compressed_file, $quality);

            }

            return $original_url;

        } else {

            return $original_url;

        }
    }
}


/**
 * Filters the content/excerpt for images and then resizes and converts to Webp
 * @return $content
 */

function irs_replace_images_wp_content($content)
{

    $dom = new DOMDocument;
    $dom->loadHTML($content);
    $x = new DOMXPath($dom);

    foreach ($x->query("//img") as $node) {
        $content = str_replace($node->getAttribute("src"), resizeimg($node->getAttribute("src"), $node->getAttribute("width")), $content);
    }

    return $content;

}

add_filter('the_content', 'irs_replace_images_wp_content');
add_filter('the_excerpt', 'irs_replace_images_wp_content');


/**
 * Filters the srcset of images and creages new Webp Images
 * @return $sources
 */

add_filter('wp_calculate_image_srcset', function ($sources) {
    foreach ($sources as &$source) {
        if (isset($source['url']))
            $source['url'] = resizeimg($source['url'], $source['value']);
    }
    return $sources;

}, PHP_INT_MAX);
