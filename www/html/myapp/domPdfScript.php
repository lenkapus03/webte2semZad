<?php


require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('defaultFont', 'DejaVu Sans'); // podpora pre UTF-8
$dompdf = new Dompdf($options);

$isPdf = true; // aktivuje podmienku
// získať HTML obsahu z návodu
ob_start();
include 'manual.html';  // rovnaký obsah ako používateľská stránka
$html = ob_get_clean();

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("navod.pdf");

