<?php
// memulai session & cek login
session_start();
if (!isset($_SESSION['login'])) {
    header('Location: login.php');
    exit;
}

include('koneksi.php');
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// judul laporan (baris 1)
$sheet->setCellValue('A1', 'LAPORAN BUKU TAMU');
$sheet->mergeCells('A1:G1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// baris 2 dikosongkan, header tabel dimulai di baris 3
$sheet->setCellValue('A3', 'No');
$sheet->setCellValue('B3', 'TANGGAL');
$sheet->setCellValue('C3', 'NAMA TAMU');
$sheet->setCellValue('D3', 'ALAMAT');
$sheet->setCellValue('E3', 'NO TELEPON/HP');
$sheet->setCellValue('F3', 'BERTEMU DENGAN');
$sheet->setCellValue('G3', 'KEPENTINGAN');

// cek apakah export berdasarkan periode atau semua data
if (isset($_GET['cari'])) {
    $p_awal  = mysqli_real_escape_string($koneksi, $_GET['p_awal']);
    $p_akhir = mysqli_real_escape_string($koneksi, $_GET['p_akhir']);
    $data = mysqli_query($koneksi, "SELECT * FROM buku_tamu WHERE tanggal BETWEEN '$p_awal' AND '$p_akhir'");
} else {
    $data = mysqli_query($koneksi, "SELECT * FROM buku_tamu");
}

// data dimulai dari baris 4
$i  = 4;
$no = 1;
while ($d = mysqli_fetch_array($data)) {
    $sheet->setCellValue('A' . $i, $no++);
    $sheet->setCellValue('B' . $i, $d['tanggal']);
    $sheet->setCellValue('C' . $i, $d['nama_tamu']);
    $sheet->setCellValue('D' . $i, $d['alamat']);
    // nomor telepon disimpan sebagai teks agar angka 0 di depan tidak hilang
    $sheet->setCellValueExplicit('E' . $i, $d['no_hp'], DataType::TYPE_STRING);
    $sheet->setCellValue('F' . $i, $d['bertemu']);
    $sheet->setCellValue('G' . $i, $d['kepentingan']);
    $i++;
}

// baris terakhir yang berisi data (minimal baris header)
$barisAkhir = max($i - 1, 3);

// border untuk seluruh tabel (header + data)
$sheet->getStyle('A3:G' . $barisAkhir)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// kolom nomor rata tengah
$sheet->getStyle('A3:A' . $barisAkhir)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// lebar kolom otomatis
foreach (range('A', 'G') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$writer = new Xlsx($spreadsheet);
$writer->save('Laporan Buku Tamu.xlsx');

// unduh file hasil export
echo "<script>window.location = 'Laporan Buku Tamu.xlsx'</script>";