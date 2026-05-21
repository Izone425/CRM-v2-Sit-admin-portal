<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment {{ ucfirst($status) }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
</head>
<body style="margin:0; padding:40px 0; background:#f3f4f6; font-family:Inter, Arial, sans-serif;">
    <div style="max-width:500px; margin:0 auto; text-align:center; background:#fff; border-radius:12px; padding:48px 32px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
        @if($status === 'success')
            <div style="width:64px; height:64px; margin:0 auto 16px; background:#dcfce7; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                <svg style="width:32px; height:32px; color:#16a34a;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 style="font-size:1.5rem; font-weight:700; color:#16a34a; margin:0 0 8px;">Payment Successful</h1>
        @else
            <div style="width:64px; height:64px; margin:0 auto 16px; background:#fef2f2; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                <svg style="width:32px; height:32px; color:#dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
            <h1 style="font-size:1.5rem; font-weight:700; color:#dc2626; margin:0 0 8px;">Payment Failed</h1>
        @endif
        <p style="font-size:0.95rem; color:#6b7280; margin:0 0 8px;">{{ $message }}</p>
        @if(!empty($invoiceNo))
            <p style="font-size:0.85rem; color:#9ca3af;">Invoice: {{ $invoiceNo }}</p>
        @endif
    </div>
</body>
</html>
