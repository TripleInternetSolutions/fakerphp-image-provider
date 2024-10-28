<?php

namespace TripleInternetSolutions\FakerPhpImageProvider;

use Faker\Provider\Base;
use Faker\Provider\Color;
use Faker\Provider\Lorem;

class Image extends Base
{
    /**
     * @var string
     */
    public const BASE_URL = 'https://placehold.co';

    public const FORMAT_JPG = 'jpg';
    public const FORMAT_JPEG = 'jpeg';
    public const FORMAT_PNG = 'png';
    public const FORMAT_WEBP = 'webp';
    public const FORMAT_GIF = 'gif';


    /**
     * Generate the URL that will return a random image
     *
     * Set randomize to false to remove the random GET parameter at the end of the url.
     *
     * @param int         $width
     * @param int         $height
     * @param bool        $randomize
     * @param string|null $word
     * @param bool        $gray
     * @param string      $format
     *
     * @return string
     * @example 'http://https://placehold.co/640x480/png/CCCCCC?text=well+hi+there'
     *
     */
    public static function imageUrl(
        int    $width = 640,
        int    $height = 480,
        string $category = null,
        bool   $randomize = true,
        string $word = null,
        bool   $gray = false,
        string $format = 'jpg'
    ) {
        // Validate image format
        $imageFormats = static::getFormats();

        if (!in_array(strtolower($format), $imageFormats, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid image format "%s". Allowable formats are: %s',
                $format,
                implode(', ', $imageFormats),
            ));
        }

        $bg = $gray === true ? 'CCCCCC' : str_replace('#', '', Color::safeHexColor());

        // Calculate straight from rbg
        $r = hexdec($bg[0] . $bg[1]);
        $g = hexdec($bg[2] . $bg[3]);
        $b = hexdec($bg[4] . $bg[5]);

        $light = (($r * 299 + $g * 587 + $b * 114) / 1000 > 130);

        $fg = $light ? '000000' : 'FFFFFF';

        // TODO fg

        $params = [];

        if (!$word && $randomize) $word = Lorem::word();

        if ($word) $params['text'] = $word;

        // TODO font

        return sprintf(
            '%s/%dx%d/%s/%s/%s?%s',
            self::BASE_URL,
            $width, $height,
            $bg,$fg,
            $format,
            http_build_query($params),
        );
    }

    /**
     * Download a remote random image to disk and return its location
     *
     * Requires curl, or allow_url_fopen to be on in php.ini.
     *
     * @example '/path/to/dir/13b73edae8443990be1aa8f1a483bc27.png'
     *
     */
    public static function image(
        string $dir = null,
        int    $width = 640,
        int    $height = 480,
        string $category = null,
        bool   $fullPath = true,
        bool   $randomize = true,
        string $word = null,
        bool   $gray = false,
        string $format = 'jpg'
    ): string|bool {

        $dir = null === $dir ? sys_get_temp_dir() : $dir; // GNU/Linux / OS X / Windows compatible

        // Validate directory path
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new \InvalidArgumentException(sprintf('Cannot write to directory "%s"', $dir));
        }

        // Generate a random filename. Use the server address so that a file
        // generated at the same time on a different server won't have a collision.
        $name = md5(uniqid(empty($_SERVER['SERVER_ADDR']) ? '' : $_SERVER['SERVER_ADDR'], true));
        $filename = sprintf('%s.%s', $name, $format);
        $filepath = $dir . DIRECTORY_SEPARATOR . $filename;

        $url = static::imageUrl($width, $height, $randomize, $word, $gray, $format);

        // save file
        if (function_exists('curl_exec')) {
            // use cURL
            $fp = fopen($filepath, 'w');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            $success = curl_exec($ch) && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
            fclose($fp);
            curl_close($ch);

            if (!$success) {
                unlink($filepath);

                // could not contact the distant URL or HTTP error - fail silently.
                return false;
            }
        } else if (ini_get('allow_url_fopen')) {
            // use remote fopen() via copy()
            $success = copy($url, $filepath);

            if (!$success) {
                // could not contact the distant URL or HTTP error - fail silently.
                return false;
            }
        } else {
            return new \RuntimeException('The image formatter downloads an image from a remote HTTP server. Therefore, it requires that PHP can request remote hosts, either via cURL or fopen()');
        }

        return $fullPath ? $filepath : $filename;
    }

    public static function getFormats(): array {
        return array_keys(static::getFormatConstants());
    }

    public static function getFormatConstants(): array {
        return [
            static::FORMAT_JPG  => constant('IMAGETYPE_JPEG'),
            static::FORMAT_JPEG => constant('IMAGETYPE_JPEG'),
            static::FORMAT_PNG  => constant('IMAGETYPE_PNG'),
            static::FORMAT_WEBP => constant('IMAGETYPE_WEBP'),
            static::FORMAT_GIF  => constant('IMAGETYPE_GIF'),
        ];
    }

}
