<?php

namespace App\Http\Controllers;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use ZipArchive;

class QrController extends Controller
{
    public function generateQRCodeFromUploadedFile(Request $request)
{
    // Validate the uploaded file
    $request->validate([
        'file' => 'required|mimes:xlsx,xls',
    ]);

    // Get the uploaded file
    $file = $request->file('file');

    // Load the Excel file
    $spreadsheet = IOFactory::load($file->getPathname());

    // Get the active sheet
    $sheet = $spreadsheet->getActiveSheet();

    // Get the highest row number
    $highestRow = $sheet->getHighestRow();

    // Create a temporary zip file
    $zipFilename = 'qr_codes.zip';
    $zip = new ZipArchive();
    $zip->open($zipFilename, ZipArchive::CREATE);

    // Loop through each row and add QR code to the zip file
    for ($row = 1; $row <= $highestRow; $row++) {
        // Get the URL from column A
        $url = $sheet->getCell('B' . $row)->getValue();

        // Get the name of the QR code from column B
        $qrName = $sheet->getCell('A' . $row)->getValue();

        // Generate QR code for the URL
        $qr = QrCode::size(300)->generate(url($url));

        // Add the QR code to the zip file with the specified name
        $filename = $qrName . '.svg';
        $zip->addFromString($filename, $qr);
    }

    // Close the zip file
    $zip->close();

    // Set the response headers for downloading the zip file
    $headers = [
        'Content-Type' => 'application/zip',
        'Content-Disposition' => 'attachment; filename="' . $zipFilename . '"',
    ];

    // Return the zip file as the response
    return response()->download($zipFilename, null, $headers)->deleteFileAfterSend(true);
}

}
