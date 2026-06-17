<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\ImageOptimizer;

echo "==================================================\n";
echo "IMAGE OPTIMIZER VERIFICATION TEST\n";
echo "==================================================\n";

// 1. Check extension support
echo "Checking PHP Extensions:\n";
echo "- Imagick: " . (class_exists('Imagick') ? "Available" : "NOT Available") . "\n";
echo "- GD:      " . (extension_loaded('gd') ? "Available" : "NOT Available") . "\n\n";

// 2. Generate a large dummy image (2000x2000px red square)
$width = 2000;
$height = 2000;
$image = imagecreatetruecolor($width, $height);
$red = imagecolorallocate($image, 255, 0, 0);
imagefill($image, 0, 0, $red);

$testJpg = __DIR__ . '/temp_test_large.jpg';
imagejpeg($image, $testJpg, 100); // Save as uncompressed JPEG (100% quality)
imagedestroy($image);

$origSize = filesize($testJpg);
echo "Generated large test image (2000x2000px JPG):\n";
echo "Path: $testJpg\n";
echo "Original size: " . round($origSize / 1024, 2) . " KB\n\n";

// 3. Optimize the image
echo "Running ImageOptimizer::optimize...\n";
$start = microtime(true);
$result = ImageOptimizer::optimize($testJpg, 'image/jpeg', 75, 1200, 1200);
$elapsed = round(microtime(true) - $start, 4);

if ($result) {
    $newSize = filesize($testJpg);
    echo "Optimization Success! (Took {$elapsed}s)\n";
    echo "Optimized size: " . round($newSize / 1024, 2) . " KB\n";
    echo "Size reduction: " . round((1 - ($newSize / $origSize)) * 100, 2) . "%\n";
    
    // Check new dimensions
    list($newWidth, $newHeight) = getimagesize($testJpg);
    echo "New dimensions: {$newWidth}x{$newHeight} (Expected <= 1200x1200)\n";
    
    if ($newWidth <= 1200 && $newHeight <= 1200 && $newSize < $origSize) {
        echo "\nVERIFICATION PASSED!\n";
    } else {
        echo "\nVERIFICATION FAILED: Dimensions or size didn't meet expected values.\n";
    }
} else {
    echo "Optimization FAILED.\n";
}

// Clean up
if (file_exists($testJpg)) {
    unlink($testJpg);
}
echo "==================================================\n";
