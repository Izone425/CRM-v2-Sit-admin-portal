<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TimetecProformaInvoiceDownloadController extends Controller
{
    public function __invoke(Request $request, string $invoiceNo)
    {
        $license = DB::connection('frontenddb')
            ->table('crm_invoice_details')
            ->where('f_invoice_no', $invoiceNo)
            ->first(['f_id']);

        if (!$license || !$license->f_id) {
            abort(404, 'Invoice not found');
        }

        $aesKey = 'Epicamera@99';
        $encrypted = openssl_encrypt($license->f_id, "AES-128-ECB", $aesKey);
        $encryptedBase64 = base64_encode($encrypted);
        $url = 'https://www.timeteccloud.com/paypal_reseller_invoice?iIn=' . $encryptedBase64;

        try {
            $context = stream_context_create([
                'http' => [
                    'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n",
                    'timeout' => 30,
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $htmlContent = file_get_contents($url, false, $context);

            if ($htmlContent === false) {
                abort(502, 'Failed to fetch invoice from TimeTec');
            }

            // Inline ALL <link> CSS tags (including ones inside <body>) - use same context for User-Agent
            preg_match_all('/<link[^>]+href=["\']([^"\']+\.css[^"\']*)["\'][^>]*>/i', $htmlContent, $cssMatches, PREG_SET_ORDER);

            foreach ($cssMatches as $match) {
                $cssUrl = $match[1];
                try {
                    $cssContent = @file_get_contents($cssUrl, false, $context);
                    if ($cssContent !== false) {
                        $htmlContent = str_replace($match[0], '<style>' . $cssContent . '</style>', $htmlContent);
                    } else {
                        $htmlContent = str_replace($match[0], '', $htmlContent);
                    }
                } catch (\Exception $e) {
                    $htmlContent = str_replace($match[0], '', $htmlContent);
                }
            }

            // Convert all <img> src to base64 inline so DomPDF renders them
            preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $htmlContent, $imgMatches, PREG_SET_ORDER);
            foreach ($imgMatches as $imgMatch) {
                $imgUrl = $imgMatch[1];
                // Skip already base64 images
                if (strpos($imgUrl, 'data:') === 0) {
                    continue;
                }
                // Make relative URLs absolute
                if (strpos($imgUrl, '//') === false) {
                    $imgUrl = 'https://www.timeteccloud.com/' . ltrim($imgUrl, '/');
                } elseif (strpos($imgUrl, '//') === 0) {
                    $imgUrl = 'https:' . $imgUrl;
                }
                try {
                    $imgData = @file_get_contents($imgUrl, false, $context);
                    if ($imgData !== false) {
                        $ext = pathinfo(parse_url($imgUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'png';
                        $mimeTypes = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'svg' => 'image/svg+xml'];
                        $mime = $mimeTypes[$ext] ?? 'image/png';
                        $base64 = 'data:' . $mime . ';base64,' . base64_encode($imgData);
                        $newTag = str_replace($imgMatch[1], $base64, $imgMatch[0]);
                        $htmlContent = str_replace($imgMatch[0], $newTag, $htmlContent);
                    }
                } catch (\Exception $e) {
                    // Skip failed images
                }
            }

            // Strip scripts and recaptcha
            $htmlContent = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $htmlContent);

            // Remove PayPal/Razer buttons and everything after the invoice container
            $htmlContent = preg_replace('/<table[^>]*width\s*=\s*["\']20%["\'][^>]*>.*?<\/table>/is', '', $htmlContent);
            $htmlContent = preg_replace('/<input[^>]*(paypal|razer)[^>]*>/i', '', $htmlContent);
            $htmlContent = preg_replace('/<form[^>]*(paypal|razer)[^>]*>.*?<\/form>/is', '', $htmlContent);
            $htmlContent = preg_replace('/<a[^>]*(admin-button)[^>]*>.*?<\/a>/is', '', $htmlContent);

            // Remove box-shadow (not supported by DomPDF) and fix width for PDF
            $htmlContent = str_replace('width: 729px', 'width: 100%', $htmlContent);
            $htmlContent = str_replace('max-width:729px', 'max-width:100%', $htmlContent);
            $htmlContent = preg_replace('/box-shadow:[^;]+;/', '', $htmlContent);
            $htmlContent = preg_replace('/text-shadow:[^;]+;/', '', $htmlContent);

            // Add minimal PDF overrides
            $pdfCss = '<style>
                @page { margin: 20px 20px 30px 20px; }
                body { font-family: Helvetica, Arial, sans-serif; margin: 0; padding: 0; }
                #site-container-invoice { margin: 0 !important; padding: 0 !important; width: 100% !important; }
                .box { border: none !important; padding: 5px !important; }
            </style>';
            $htmlContent = preg_replace('/<\/head>/i', $pdfCss . '</head>', $htmlContent);

            $filename = 'PI_' . $invoiceNo . '.pdf';

            $pdf = Pdf::setOptions(['isRemoteEnabled' => true, 'isPhpEnabled' => true])
                ->loadHTML($htmlContent)
                ->setPaper('a4', 'portrait');

            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Failed to download PI from TimeTec: ' . $e->getMessage());
            abort(502, 'Failed to fetch invoice');
        }
    }
}
