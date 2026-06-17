<?php
include "app/Http/Controllers/DashboardController.php";

use App\Http\Controllers\DrasthaXlsxWriter;

$headers = ["Column 1", "Column 2"];
$data = [["Data 1", "Data 2"], ["Data 3", "Data 4"]];

$path = "storage/app/public/qa_test_template.xlsx";

echo "Attempting to generate file at: $path\n";

try {
    if (DrasthaXlsxWriter::generate($path, $headers, $data)) {
        echo "Success! File created.\n";
        echo "File size: " . filesize($path) . " bytes\n";
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $path);
        echo "MIME type: " . $mime . "\n";
        finfo_close($finfo);
        
        if ($mime === 'application/zip' || $mime === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
            echo "VERIFICATION PASSED: File is a valid archive/xlsx.\n";
        } else {
            echo "VERIFICATION FAILED: Incorrect MIME type.\n";
        }
        
        // Clean up
        unlink($path);
    } else {
        echo "Failed to generate file.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
