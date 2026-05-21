<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>TIMETEC HR - SYSTEM GO LIVE</title>
    <style>
        @page {
            margin: 2cm;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            margin-bottom: 20px;
        }
        .title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        /* Client Information Table */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-table th {
            background-color: #d9d9d9;
            font-weight: bold;
            text-align: left;
            padding: 6px 10px;
            border: 1px solid #000;
            font-size: 11px;
            colspan: 2;
        }
        .info-table td {
            padding: 6px 10px;
            border: 1px solid #000;
            font-size: 11px;
            vertical-align: middle;
        }
        .info-table .label {
            font-weight: bold;
            width: 40%;
            background-color: #f5f5f5;
        }
        .info-table .value {
            width: 60%;
        }

        /* Acknowledgment Section */
        .acknowledgment {
            margin: 20px 0;
            font-size: 11px;
            line-height: 1.6;
        }
        .acknowledgment p {
            margin-bottom: 12px;
        }

        /* Signature Table */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .signature-table th,
        .signature-table td {
            padding: 8px 10px;
            border: 1px solid #000;
            font-size: 11px;
            text-align: left;
            vertical-align: middle;
        }
        .signature-table th {
            background-color: #d9d9d9;
            font-weight: bold;
        }
        .signature-table .pic-col {
            width: 18%;
            font-weight: bold;
        }
        .signature-table .name-col {
            width: 22%;
        }
        .signature-table .signature-col {
            width: 22%;
        }
        .signature-table .date-col {
            width: 18%;
        }
        .signature-table .stamp-col {
            width: 20%;
        }
        .signature-row td {
            height: 40px;
        }
        .signature-font {
            font-family: 'Lucida Handwriting', cursive;
            font-style: italic;
            font-size: 14px;
        }

        /* Support Section */
        .support-section {
            margin-top: 25px;
            font-size: 11px;
            line-height: 1.6;
        }
        .support-section p {
            margin-bottom: 8px;
        }
        .support-section a {
            color: #0066cc;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="header" style="position: relative; min-height: 60px; margin-bottom: 20px;">
        <img src="{{ $path_img }}" alt="timeTec" style="position: absolute; top: 0; right: 0; max-width: 140px;">
        <div class="title" style="padding-top: 45px;">TIMETEC HR - SYSTEM GO LIVE</div>
    </div>

    <!-- Client Information -->
    <table class="info-table">
        <tr>
            <th colspan="2">CLIENT INFORMATION</th>
        </tr>
        <tr>
            <td class="label">COMPANY NAME</td>
            <td class="value">{{ $companyName }}</td>
        </tr>
        <tr>
            <td class="label">CONTACT PERSON</td>
            <td class="value">{{ implode(', ', array_column($contactPersons, 'name')) }}</td>
        </tr>
        <tr>
            <td class="label">POSITION</td>
            <td class="value">{{ implode(', ', array_column($contactPersons, 'position')) }}</td>
        </tr>
        <tr>
            <td class="label">EMAIL</td>
            <td class="value">{{ implode(', ', array_column($contactPersons, 'email')) }}</td>
        </tr>
        <tr>
            <td class="label">PHONE NUMBER</td>
            <td class="value">{{ implode(', ', array_column($contactPersons, 'phone')) }}</td>
        </tr>
        <tr>
            <td class="label">MODULES</td>
            <td class="value">{{ $modules }}</td>
        </tr>
        <tr>
            <td class="label">IMPLEMENTATION START DATE</td>
            <td class="value">{{ $implementationStartDate }}</td>
        </tr>
        <tr>
            <td class="label">IMPLEMENTATION COMPLETION DATE</td>
            <td class="value">{{ $implementationCompletionDate }}</td>
        </tr>
    </table>

    <!-- Acknowledgment -->
    <div class="acknowledgment">
        <p>I/We hereby acknowledge that the TimeTec HR software ({{ $modules }} modules) has been successfully implemented, and all necessary training and documentation have been provided.</p>

        <p>I understand that any further inquiries or support requests will be directed to the customer support team.</p>
    </div>

    <!-- Signature Table -->
    <table class="signature-table">
        <thead>
            <tr>
                <th class="pic-col">PIC</th>
                <th class="name-col">NAME</th>
                <th class="signature-col">SIGNATURE</th>
                <th class="date-col">DATE</th>
                <th class="stamp-col">COMPANY STAMP</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($contactPersons as $person)
            <tr class="signature-row">
                <td class="pic-col" style="font-weight: bold;">CLIENT</td>
                <td>{{ $person['name'] }}</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            @endforeach
            <tr class="signature-row">
                <td class="pic-col" style="font-weight: bold;">IMPLEMENTER</td>
                <td>{{ $implementerName }}</td>
                <td><span class="signature-font">{{ $implementerName }}</span></td>
                <td>{{ $implementationCompletionDate }}</td>
                <td rowspan="2" style="text-align: center; vertical-align: middle;">
                    <img src="{{ $stampImg }}" alt="Company Stamp" style="max-width: 100px; max-height: 80px;">
                </td>
            </tr>
            <tr class="signature-row">
                <td class="pic-col" style="font-weight: bold;">TEAM LEAD IMPLEMENTER</td>
                <td>{{ $teamLeadName }}</td>
                <td><span class="signature-font">{{ $teamLeadName }}</span></td>
                <td>{{ $implementationCompletionDate }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Support Section -->
    <div class="support-section">
        <p>For any further inquiries or support related to the TimeTec HR software, please contact our customer support team:</p>

        <p>
            Email: <a href="mailto:support@timeteccloud.com">support@timeteccloud.com</a><br>
            Phone: 03-80709933
        </p>

        <p>Our support working hours are Monday to Friday, 9am to 6pm (Malaysia Time, GMT+8) excluding Public Holidays.</p>
    </div>
</body>
</html>
