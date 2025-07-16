<?php
require 'config.php';
if ($_SESSION['role'] != 'doctor') { die('Access denied'); }
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';
$res = $conn->query("SELECT notes.id, patients.name as patient_name, notes.note, notes.created_at, users.username as doctor FROM notes JOIN patients ON notes.patient_id = patients.id JOIN users ON notes.doctor_id = users.id");
if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="notes.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Note ID', 'Patient', 'Doctor', 'Note', 'Date']);
    while ($row = $res->fetch_assoc()) {
        fputcsv($out, [$row['id'], $row['patient_name'], $row['doctor'], $row['note'], $row['created_at']]);
    }
    fclose($out);
    exit;
} elseif ($format === 'pdf') {
    if (!class_exists('FPDF')) {
        require_once('fpdf.php');
    }
    if (!class_exists('FPDF')) {
        die('FPDF library not found. Please add fpdf.php.');
    }
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10,'Notes List',0,1,'C');
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(15,8,'ID',1);
    $pdf->Cell(40,8,'Patient',1);
    $pdf->Cell(40,8,'Doctor',1);
    $pdf->Cell(60,8,'Note',1);
    $pdf->Cell(30,8,'Date',1);
    $pdf->Ln();
    $pdf->SetFont('Arial','',9);
    foreach ($res as $row) {
        $pdf->Cell(15,8,$row['id'],1);
        $pdf->Cell(40,8,$row['patient_name'],1);
        $pdf->Cell(40,8,$row['doctor'],1);
        $pdf->Cell(60,8,substr($row['note'],0,40).(strlen($row['note'])>40?'...':''),1);
        $pdf->Cell(30,8,$row['created_at'],1);
        $pdf->Ln();
    }
    $pdf->Output('D', 'notes.pdf');
    exit;
} else {
    die('Invalid format.');
} 