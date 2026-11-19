<?php
require_once __DIR__ . '/config/database.php';

$conn = getConnection();

$result = $conn->query("
    SELECT r.*, u.name 
    FROM reports r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.activity_date DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cetak Laporan</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }

        .header p {
            margin: 5px 0;
            font-size: 14px;
        }

        hr {
            border: 1px solid black;
            margin: 15px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #f0f0f0;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .footer {
            margin-top: 40px;
            width: 100%;
            display: flex;
            justify-content: flex-end;
        }

        .ttd {
            text-align: center;
            width: 250px;
        }

        @media print {
            body {
                margin: 20px;
            }
        }
    </style>
</head>

<body>

<!-- ================= HEADER ================= -->
<div class="header">
    <h2>LAPORAN KEGIATAN GURU DAN PEGAWAI</h2>
    <p>SMK N 3 BANGLI</p>
    <p>Tahun <?= date('Y'); ?></p>
</div>

<hr>

<!-- ================= TABEL ================= -->
<table>
    <tr>
        <th width="5%">No</th>
        <th width="15%">Tanggal</th>
        <th width="25%">Nama</th>
        <th>Judul Kegiatan</th>
    </tr>

    <?php $no = 1; while($r = $result->fetch_assoc()): ?>
    <tr>
        <td class="text-center"><?= $no++; ?></td>
        <td><?= date('d-m-Y', strtotime($r['activity_date'])) ?></td>
        <td><?= $r['name'] ?></td>
        <td><?= $r['title'] ?></td>
    </tr>
    <?php endwhile; ?>

</table>

<!-- ================= TANDA TANGAN ================= -->
<div class="footer">
    <div class="ttd">
        <p>Bangli, <?= date('d F Y'); ?></p>
        <p>Kepala Sekolah</p>

        <br><br><br>

        <p><b>Dr. Ahmad Sulaiman, M.Pd</b></p>
    </div>
</div>

<script>
window.print();
</script>

</body>
</html>