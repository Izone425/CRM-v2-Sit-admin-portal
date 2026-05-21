<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class S3FileProxyController extends Controller
{
    public function serve(Request $request)
    {
        $path = $request->query('path');

        if (!$path) {
            abort(400, 'File path is required');
        }

        // Check if file exists in S3
        if (!Storage::disk('s3-ticketing')->exists($path)) {
            abort(404, 'File not found');
        }

        // Get file contents from S3
        $contents = Storage::disk('s3-ticketing')->get($path);
        $mimeType = Storage::disk('s3-ticketing')->mimeType($path);

        // Return file with proper headers (cached for 1 year - effectively no expiration)
        return response($contents)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'public, max-age=31536000, immutable');
    }
}
