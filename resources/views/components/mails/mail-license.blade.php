<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Reminder AMS</title>
    {{-- Styling dasar untuk email. Lebih baik menggunakan inline CSS atau framework seperti Tailwind Headless UI --}}
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background-color: #007bff;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }

        .content {
            padding: 30px;
            line-height: 1.6;
            color: #333333;
        }

        .details {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            border-left: 5px solid #ffc107;
        }

        .details p {
            margin: 5px 0;
        }

        .button-container {
            text-align: center;
            margin: 30px 0;
        }

        .button {
            background-color: #28a745;
            color: #ffffff !important;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #777777;
            border-top: 1px solid #eeeeee;
        }
    </style>
</head>

<body>
    <div class="container">
        {{-- Header Email --}}
        <div class="header">
            <h2>🔔 Reminder Pengajuan AMS ( {{ $data['draft']['request_no'] }})</h2>
        </div>

        {{-- Isi Email --}}
        <div class="content">
            @if ($data['type'] == "SUBMIT")
                <p>Halo **{{ $data['author'] }}**, </p>

                <p>Dokumen sudah diterima oleh sistem.</p>

                <div class="details">
                    <p><strong>Nomor Dokumen:</strong> {{ $data['draft']['request_no'] }}</p>
                    <p><strong>Tujuan Pengajuan:</strong> {{ $data['draft']['purpose'] }}</p>
                </div>
            @elseif ($data['type'] == "OUTSTANDING_APPROVER")

                <p>Halo **{{ $data['approver'] }}**, </p>

                <p>Kami ingin mengingatkan bahwa ada dokumen yang memerlukan **persetujuan** Anda dalam sistem.</p>

                <div class="details">
                    <p><strong>Nomor Dokumen:</strong> {{ $data['draft']['request']['request_no'] }}</p>
                    <p><strong>Jenis License:</strong> {{ $data['draft']['softwareCategory']['name']}}</p>
                    <p><strong>Tujuan Pengajuan:</strong> {{ $data['draft']['request']['purpose'] }}</p>
                    <p><strong>Status Pengajuan</strong> {{ $data['draft']['status'] }}</p>
                </div>

                <p>Mohon segera tindak lanjuti pengajuan ini untuk menjaga kelancaran proses. Klik tombol di bawah ini
                    untuk melihat detail dan melakukan persetujuan:</p>

                <div class="button-container">
                    <a href="{{ route("requests.license.main") }}" class="button" target="_blank">
                        Tinjau & Proses Pengajuan Lisensi
                    </a>
                </div>
            @elseif ($data['type'] == "APPROVE")
                <p>Halo **{{ $data['author'] }}**, </p>

                <p>Ada update dari sistem terkait proses pengajuan lisensi anda. Silahkan cek</p>

                <div class="details">
                    <p><strong>Nomor Dokumen:</strong> {{ $data['draft']['request_no'] }}</p>
                    <p><strong>Tujuan Pengajuan:</strong> {{ $data['draft']['purpose'] }}</p>
                    <p><strong>Diajukan Oleh:</strong> {{ $data['author'] }}</p>
                    <p><strong>Diproses Oleh:</strong> {{ $data['approver']['name'] }}</p>
                    <p><strong>Status:</strong> APPROVE</p>
                    <p><strong>Note:</strong> {{ $data['remarks'] }}</p>
                </div>

                <div class="button-container">
                    <a href="{{ route("requests.license.main") }}" class="button" target="_blank">
                        Cek Pengajuan
                    </a>
                </div>
            @elseif ($data['type'] == "REJECt")
                <p>Halo **{{ $data['author'] }}**, </p>

                <p>Ada update dari sistem terkait proses pengajuan lisensi anda. Silahkan cek</p>

                <div class="details">
                   
                    <p><strong>Nomor Dokumen:</strong> {{ $data['draft']['request_no'] }}</p>
                    <p><strong>Tujuan Pengajuan:</strong> {{ $data['draft']['purpose'] }}</p>
                    <p><strong>Diajukan Oleh:</strong> {{ $data['author'] }}</p>
                    <p><strong>Diproses Oleh:</strong> {{ $data['approver']['name'] }}</p>
                    <p><strong>Status:</strong> REJECT</p>
                    <p><strong>Note:</strong> {{ $data['remarks'] }}</p>
                </div>

                <div class="button-container">
                    <a href="{{ route("requests.license.main") }}" class="button" target="_blank">
                        Cek Pengajuan
                    </a>
                </div>
            @endif

            <p>Jika Anda memiliki pertanyaan, silakan hubungi tim Support.</p>
            <p>Terima kasih atas perhatian dan tindakan cepat Anda.</p>
        </div>

        {{-- Footer Email --}}
        <div class="footer">
            <p>Sistem Pemberitahuan Otomatis (Jangan balas email ini)</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}</p>
        </div>
    </div>
</body>

</html>