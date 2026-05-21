<!DOCTYPE html>
<html>
<head>
    <title>Masih Buntu Cara Memudahkan Proses HR Anda?</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Salam Sejahtera <strong>{{ $lead['lastName'] }}</strong>,</p>

    <p>Saya harap anda berada dalam keadaan baik. Saya <strong>{{ $leadOwnerName }}</strong> dari TimeTec. Anda sebelum ini telah memuat turun checklist HR kami.</p>

    <p>Kami ingin membuat susulan sekiranya anda masih mencari cara untuk memudahkan proses HR syarikat anda. TimeTec menyediakan Penyelesaian HR Berasaskan Awan yang membantu syarikat mengurus <strong>kehadiran, penggajian, cuti,</strong> dan <strong>tuntutan</strong> dengan lebih mudah serta mengurangkan kerja manual.</p>

    <p>Kami juga sedang menjalankan promosi, di mana anda berpeluang menerima <strong>Peranti Biometrik PERCUMA</strong> apabila melanggan modul TimeTec Kehadiran, tertakluk kepada terma dan syarat.</p>

    <p>Sekiranya anda berminat untuk mengetahui dengan lebih lanjut, kami berbesar hati untuk mengaturkan sesi demo ringkas bersama anda.</p>

    <p>Anda boleh merujuk risalah kami
        <a href="https://www.timeteccloud.com/download/brochure/TimeTecHR-E.pdf" target="_blank">di sini</a>.
    </p>

    <p>Yang Benar,</p>
    <p>{{ $leadOwnerName }}<br>
        {{ $lead['position'] ?: 'Jabatan Tidak Dinyatakan' }}<br>
        TimeTec Cloud Sdn Bhd<br>
        Pejabat: +603-8070 9933<br>
        WhatsApp: {{ $lead['leadOwnerMobileNumber'] ?? 'N/A' }}
    </p>
</body>
</html>
