<?php
// require_once __DIR__ . '/../core/fpdf.php';

// $pdf = new FPDF('P', 'mm', array(80, 150));
// $pdf->AddPage();
// $pdf->SetMargins(10, 10, 10);

// $pdf->SetFont('Courier', 'B', 12);
// $pdf->Cell(0, 8, mb_convert_encoding('Título con acento', 'windows-1252', 'UTF-8'), 0, 1, 'C');

// $pdf->SetFont('Courier', '', 9);
// $pdf->Cell(0, 6, mb_convert_encoding('Garantía: 36 meses', 'windows-1252', 'UTF-8'), 0, 1);
// $pdf->Cell(0, 6, mb_convert_encoding('Descripción del artículo', 'windows-1252', 'UTF-8'), 0, 1);
// $pdf->Cell(0, 6, 'Precio: 10,00 ' . chr(128), 0, 1);

// header('Content-Type: application/pdf');
// header('Content-Disposition: inline; filename="test.pdf"');
// // echo $pdf->Output('S');


// require_once __DIR__ . '/../core/fpdf.php';

// $pdf = new FPDF('P', 'mm', array(80, 100));
// $pdf->AddPage();
// $pdf->SetFont('Courier', '', 10);

// // Test 1: bytes directos sin conversión
// $pdf->Cell(0, 6, "Garant\xeda: 36 meses", 0, 1);

// // Test 2: con mb_convert_encoding
// $pdf->Cell(0, 6, mb_convert_encoding("Garantía: 36 meses", 'windows-1252', 'UTF-8'), 0, 1);

// // Test 3: con iconv
// $pdf->Cell(0, 6, iconv('UTF-8', 'CP1252//IGNORE', "Garantía: 36 meses"), 0, 1);

// // Test 4: sustitución manual
// $pdf->Cell(0, 6, str_replace('í', "\xed", "Garantía: 36 meses"), 0, 1);

// header('Content-Type: application/pdf');
// header('Content-Disposition: inline');
// echo $pdf->Output('S');


require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/../model/DBPDO.php';
require_once __DIR__ . '/../model/ConfiguracionPDO.php';

$appConfig = ConfiguracionPDO::obtenerConfiguracion();

foreach ($appConfig as $key => $val) {
    if (!is_string($val)) continue;
    $enc = mb_detect_encoding($val, ['UTF-8', 'windows-1252', 'ISO-8859-1'], true);
    $hex = bin2hex($val);
    echo "<b>$key</b>: encoding=$enc | hex=$hex | valor=$val<br>";
}