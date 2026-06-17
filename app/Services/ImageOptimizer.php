<?php

namespace App\Services;

use Imagick;

class ImageOptimizer
{
    /**
     * Optimize an image file (compress and optionally resize/downscale).
     * Handles JPEG, PNG, WEBP, and GIF (preserving animation if Imagick is available).
     *
     * @param string $filePath Absolute path to the image file
     * @param string $mimeType Mime type of the image
     * @param int $quality Compression quality (0-100)
     * @param int $maxWidth Maximum width to scale down to
     * @param int $maxHeight Maximum height to scale down to
     * @return bool True on success, false on failure
     */
    public static function optimize(string $filePath, string $mimeType, int $quality = 75, int $maxWidth = 1200, int $maxHeight = 1200): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        // Try Imagick first, as it handles animations (GIF) and has better quality/compression options
        if (class_exists('Imagick')) {
            try {
                return self::optimizeWithImagick($filePath, $mimeType, $quality, $maxWidth, $maxHeight);
            } catch (\Exception $e) {
                // Fall back to GD if Imagick fails
                logger()->error('Imagick optimization failed, falling back to GD: ' . $e->getMessage());
            }
        }

        // Fallback to GD
        if (extension_loaded('gd')) {
            try {
                return self::optimizeWithGD($filePath, $mimeType, $quality, $maxWidth, $maxHeight);
            } catch (\Exception $e) {
                logger()->error('GD optimization failed: ' . $e->getMessage());
            }
        }

        return false;
    }

    /**
     * Optimize image using Imagick extension.
     */
    protected static function optimizeWithImagick(string $filePath, string $mimeType, int $quality, int $maxWidth, int $maxHeight): bool
    {
        $imagick = new Imagick($filePath);

        // For GIFs (animated), we need to iterate through all frames
        if (str_contains($mimeType, 'gif')) {
            // Check if it is actually animated
            if ($imagick->getNumberImages() > 1) {
                $imagick = $imagick->coalesceImages();
                $changed = false;

                do {
                    $width = $imagick->getImageWidth();
                    $height = $imagick->getImageHeight();

                    if ($width > $maxWidth || $height > $maxHeight) {
                        // Calculate aspect ratio
                        $ratio = min($maxWidth / $width, $maxHeight / $height);
                        $newWidth = (int)($width * $ratio);
                        $newHeight = (int)($height * $ratio);

                        $imagick->resizeImage($newWidth, $newHeight, Imagick::FILTER_BOX, 1);
                        $changed = true;
                    }
                } while ($imagick->nextImage());

                if ($changed) {
                    $imagick = $imagick->deconstructImages();
                    $imagick->writeImages($filePath, true);
                }
                $imagick->clear();
                $imagick->destroy();
                return true;
            }
        }

        // For static images (JPEG, PNG, WEBP, static GIF)
        $width = $imagick->getImageWidth();
        $height = $imagick->getImageHeight();

        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);
            $imagick->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);
        }

        // Set compression/quality
        if (str_contains($mimeType, 'jpeg') || str_contains($mimeType, 'jpg')) {
            $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
            $imagick->setImageCompressionQuality($quality);
        } elseif (str_contains($mimeType, 'png')) {
            // PNG quality: 0-99
            $imagick->setImageCompression(Imagick::COMPRESSION_ZIP);
            $imagick->setImageCompressionQuality((int)($quality * 0.9));
        } elseif (str_contains($mimeType, 'webp')) {
            $imagick->setImageCompressionQuality($quality);
        }

        // Strip metadata/profiles for smaller size
        $imagick->stripImage();

        $imagick->writeImage($filePath);
        $imagick->clear();
        $imagick->destroy();

        return true;
    }

    /**
     * Optimize image using GD extension.
     */
    protected static function optimizeWithGD(string $filePath, string $mimeType, int $quality, int $maxWidth, int $maxHeight): bool
    {
        // For GIF, if we use GD, it will flatten the GIF (lose animation). 
        // So for GIF, we don't resize or compress if using GD to preserve animation, unless it's not animated.
        if (str_contains($mimeType, 'gif')) {
            if (self::isAnimatedGif($filePath)) {
                // Skip optimizing to preserve animation when Imagick is not available
                return false;
            }
        }

        // Create image source
        if (str_contains($mimeType, 'jpeg') || str_contains($mimeType, 'jpg')) {
            $image = @imagecreatefromjpeg($filePath);
        } elseif (str_contains($mimeType, 'png')) {
            $image = @imagecreatefrompng($filePath);
        } elseif (str_contains($mimeType, 'webp')) {
            $image = @imagecreatefromwebp($filePath);
        } elseif (str_contains($mimeType, 'gif')) {
            $image = @imagecreatefromgif($filePath);
        } else {
            return false;
        }

        if (!$image) {
            return false;
        }

        // Get original dimensions
        $width = imagesx($image);
        $height = imagesy($image);

        // Check if resize is needed
        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);

            $newImage = imagecreatetruecolor($newWidth, $newHeight);

            // Handle transparency for PNG/WEBP/GIF
            if (str_contains($mimeType, 'png') || str_contains($mimeType, 'gif') || str_contains($mimeType, 'webp')) {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
            }

            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $newImage;
        }

        // Save image with quality/compression settings
        $result = false;
        if (str_contains($mimeType, 'jpeg') || str_contains($mimeType, 'jpg')) {
            $result = imagejpeg($image, $filePath, $quality);
        } elseif (str_contains($mimeType, 'png')) {
            // PNG quality parameter in GD is 0 (no compression) to 9 (maximum compression)
            $pngQuality = (int)max(0, min(9, 9 - ($quality / 10)));
            $result = imagepng($image, $filePath, $pngQuality);
        } elseif (str_contains($mimeType, 'webp')) {
            $result = imagewebp($image, $filePath, $quality);
        } elseif (str_contains($mimeType, 'gif')) {
            $result = imagegif($image, $filePath);
        }

        imagedestroy($image);

        return $result;
    }

    /**
     * Check if a GIF image is animated.
     */
    protected static function isAnimatedGif(string $filename): bool
    {
        if (!($fh = @fopen($filename, 'rb'))) {
            return false;
        }
        $count = 0;
        // Chunk reading to find animated frames
        while (!feof($fh) && $count < 2) {
            $chunk = fread($fh, 1024 * 100); // Read 100KB chunks
            $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00\x2C#s', $chunk, $matches);
        }
        fclose($fh);
        return $count > 1;
    }
}
