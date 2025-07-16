<?php
require 'config.php';
if ($_SESSION['role'] != 'doctor') { die('Access denied'); }
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';
$res = $conn->query("SELECT patients.id, patients.name, patients.age, patients.gender, users.username FROM patients JOIN users ON patients.user_id = users.id");
if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="patients.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Patient ID', 'Name', 'Age', 'Gender', 'Username']);
    while ($row = $res->fetch_assoc()) {
        fputcsv($out, [$row['id'], $row['name'], $row['age'], $row['gender'], $row['username']]);
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
    $pdf->Cell(0,10,'Patients List',0,1,'C');
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(20,8,'ID',1);
    $pdf->Cell(50,8,'Name',1);
    $pdf->Cell(20,8,'Age',1);
    $pdf->Cell(25,8,'Gender',1);
    $pdf->Cell(50,8,'Username',1);
    $pdf->Ln();
    $pdf->SetFont('Arial','',10);
    foreach ($res as $row) {
        $pdf->Cell(20,8,$row['id'],1);
        $pdf->Cell(50,8,$row['name'],1);
        $pdf->Cell(20,8,$row['age'],1);
        $pdf->Cell(25,8,$row['gender'],1);
        $pdf->Cell(50,8,$row['username'],1);
        $pdf->Ln();
    }
    $pdf->Output('D', 'patients.pdf');
    exit;
} else {
    die('Invalid format.');
} 