<?php

include "libs/autoload.php";

$config['max_copies'] = 10;
$config['tmpdir'] = dirname(__FILE__)."/tmp/";
$config['tmpdelete'] = 60 * 60 * 24 * 2; // 2 days


// Check writable
if (!is_writable($config['tmpdir'])) {
    die($config['tmpdir']." must me writeable!");
}

// Clean tempfiles
$tmpfiles = scandir($config['tmpdir']);
foreach($tmpfiles as $tmpfile) {
	$file = $config['tmpdir'].$tmpfile;
	if(is_file($file)) {
		if(time() - filemtime($file) >= $config['tmpdelete']) { // 2 days
			unlink($file);
		}
    } 
}

if(isset($_GET['download'])) {
	
	$filename = trim($_GET['download']);
	$filename = str_replace("/", "", $filename);
	
	$download = $config['tmpdir']."/".$filename;
	
	// Process download
    if(file_exists($download)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($download).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($download));
        flush(); // Flush system output buffer
        readfile($download);
        exit;
    }
	
} elseif(isset($_GET['ajax'])) {
	
	header('Content-Type: application/json');
	
	if(isset($_GET['debug'])) {
		$debug = true;
	}
	
	$form_action = trim($_POST['action']);
	$form_text = trim($_POST['text']);
	$form_text2 = trim($_POST['text2']);
	$form_text3 = trim($_POST['text3']);
	$form_text4 = trim($_POST['text4']);
	$form_barcode1 = trim($_POST['barcode1']);
	$form_barcode2 = trim($_POST['barcode2']);
	$form_copies = intval($_POST['copies']);
	$form_template = trim($_POST['template']);
	$form_logo = ($_POST['logo'] == "yes" ? true : false);
	
	if($form_text == "" && $form_barcode1 == "") {
		$errormsg = "Text fehlt!";
	} elseif(strlen($form_text) > 25 || strlen($form_text2) > 25 || strlen($form_text3) > 25 || strlen($form_text4) > 25) {
		$errormsg = "Text zu lang!";
	} elseif(strlen($form_barcode1) > 15) {
		$errormsg = "Text für Barcode zu lang! Max. 10 Zeichen.";
	} elseif(strlen($form_barcode2) > 13) {
		$errormsg = "Text für Barcode lang! EAN hat 13 Zeichen.";
	} elseif($form_copies <= 0 || $form_copies > $config['max_copies']) {
		$errormsg = "Zu viele oder wenig Exemplare. Maximal 10!";
	} elseif($form_template == "") {
		$errormsg = "Template fehlt!";
	} else {
		$errormsg = "";
	}
	
	if($errormsg == "") {

	
		$temp_file = $config['tmpdir'].tempfile('web2dymo', 'pdf', $config['tmpdir']);
		
		require('libs/fpdf/fpdf.php');

		switch($form_template) {
			default: //tmp1
				$pdf = new FPDF('L','mm',array(54,25));
				for($i=1;$i<=$form_copies;$i++) {
					$pdf->addPage('L');
					$pdf->SetFont('Arial','B',16);
					$pdf->Text(2, 10, 'Dauerleihgabe');
					$pdf->Text(2, 20, iconv('UTF-8', 'windows-1252', $form_text));
					if($form_logo) {
						$pdf->Image("assets/wwlabs-150x150.png", 42, 2, 10, 10);
					}
				}
			break;
			case "tmp2":
				$pdf = new FPDF('L','mm',array(54,25));
				for($i=1;$i<=$form_copies;$i++) {
					$pdf->addPage('L');
					$pdf->SetFont('Arial','B',16);
					$pdf->Text(2, 10, iconv('UTF-8', 'windows-1252', $form_text));
					$pdf->Text(2, 20, iconv('UTF-8', 'windows-1252', $form_text2));
					if($form_logo) {
						$pdf->Image("assets/wwlabs-150x150.png", 42, 2, 10, 10);
					}
				}
			break;
			case "tmp3":
				$pdf = new FPDF('L','mm',array(88,36));
				for($i=1;$i<=$form_copies;$i++) {
					$pdf->addPage('L');
					$pdf->SetFont('Arial','B',23);
					$pdf->Text(5, 15, 'Dauerleihgabe');
					$pdf->Text(5, 28, iconv('UTF-8', 'windows-1252', $form_text));
					if($form_logo) {
						$pdf->Image("assets/wwlabs-150x150.png", 65, 2, 20, 20);
					}
				}
			break;
			case "tmp4":
				$pdf = new FPDF('L','mm',array(88,36));
				for($i=1;$i<=$form_copies;$i++) {
					$pdf->addPage('L');
					$pdf->SetFont('Arial','B',16);
					$pdf->Text(5, 10, iconv('UTF-8', 'windows-1252', $form_text));
					$pdf->Text(5, 17, iconv('UTF-8', 'windows-1252', $form_text2));
					$pdf->Text(5, 24, iconv('UTF-8', 'windows-1252', $form_text3));
					$pdf->Text(5, 31, iconv('UTF-8', 'windows-1252', $form_text4));
					if($form_logo) {
						$pdf->Image("assets/wwlabs-150x150.png", 70, 2, 15, 15);
					}
				}
			break;
			case "tmp5":
			$generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
			$barcode = $generator->getBarcode(iconv('UTF-8', 'windows-1252', $form_barcode1), $generator::TYPE_CODE_128, 6);
			$barcodefile = $config['tmpdir'].tempfile('web2dymo', 'png', $config['tmpdir']);
			file_put_contents($barcodefile, $barcode);
			$pdf = new FPDF('L','mm',array(88,36));
			for($i=1;$i<=$form_copies;$i++) {
				$pdf->addPage('L');
				$pdf->SetFont('Arial','B',14);
				$pdf->Image($barcodefile, 5, 5, 78, 18);
				$pdf->Text(5, 30, iconv('UTF-8', 'windows-1252', $form_barcode1));
				if($form_logo) {
					$pdf->Image("assets/wwlabs-150x150.png", 75, 25, 8, 8);
				}
			}
			break;
			case "tmp6":
			$generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
			$barcode = $generator->getBarcode(iconv('UTF-8', 'windows-1252', $form_barcode2), $generator::TYPE_EAN_13, 4);
			$barcodefile = $config['tmpdir'].tempfile('web2dymo', 'png', $config['tmpdir']);
			file_put_contents($barcodefile, $barcode);
			$pdf = new FPDF('L','mm',array(88,36));
			for($i=1;$i<=$form_copies;$i++) {
				$pdf->addPage('L');
				$pdf->SetFont('Arial','B',16);
				$pdf->Text(5, 10, iconv('UTF-8', 'windows-1252', $form_text));
				$pdf->Image($barcodefile, 5, 13, 50, 12);
				$pdf->SetFont('Arial','B',12);
				$pdf->Text(5, 30, iconv('UTF-8', 'windows-1252', "EAN: ".$form_barcode2));
				if($form_logo) {
					$pdf->Image("assets/wwlabs-150x150.png", 70, 18, 15, 15);
				}
			}
		}
		
		switch($form_action) {
			case "download":
				$pdf->Output('F', $temp_file);
				
				echo json_encode(array('okay'=>true, 'html'=>'Download begins...', 'location'=>'index.php?download='.basename($temp_file)));
			break;
			
			case "preview":
				$pdf->Output('F', $temp_file);
				
				// create Imagick object
				$imagick = new Imagick();
				$imagick->setRegistry('temporary-path', $config['tmpdir']);
				
				// Reads image from PDF
				$imagick->readImage($temp_file);
				$imagick->setResolution(300, 300);
				$imagick->setImageFormat("png");
				
				// Tune Image
				//$imagick->borderImage('black', $imagick->getImageWidth(), $imagick->getImageHeight());
				
				// Create Output
				$output = $imagick->getimageblob();
				$base64 = 'data:image/png;base64,' . base64_encode($output);
				 	
				
				echo json_encode(array('okay'=>true, 'html'=>'<img src="'.$base64.'" />'));
			break;
			
			case "print":
				$pdf->Output('F', $temp_file);
				
				$exec = "lp -d dymo ".$temp_file;
				exec($exec);
					
				echo json_encode(array('okay'=>true, 'html'=>'Dokument wurde gedruckt!', 'debug'=>$exec));
			break;
		}


	} else {
		echo json_encode(array('okay'=>true, 'error'=>true, 'html'=>$errormsg));
	}

	//echo "hallo";
	
} else {

	echo '<!DOCTYPE html>
	<html lang="de">
	<head>
		<meta charset="utf-8"/>
		<title>Web2Dymo</title>
		<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link href="assets/style.css" rel="stylesheet">
	</head>
	<body>
		<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
			<a class="navbar-brand" href="#">Web2Dymo</a>
		</nav>
	<div id="main">
		<div class="container">
			<div class="row">
				<div class="col-sm">
					<div class="card">
						<h5 class="card-header">Input</h5>
						<div class="card-body">
							<form class="form">
								<div class="form-group">
									<label for="template">Template</label>
									<select class="form-control" name="template">
										<option value="tmp1">Dauerleihgabe (54x25mm)</option>
										<option value="tmp2">Freitext (54x25mm, zwei Zeilen)</option>
										<option value="tmp3">Dauerleihgabe (88x36mm)</option>
										<option value="tmp4">Freitext (88x36mm, vier Zeilen)</option>
										<option value="tmp5">Barcode (88x36, CODE 128)</option>
										<option value="tmp6">Barcode (88x36, EAN-13)</option>
									</select>
								</div>
								<div class="form-group" id="text1">
									<label for="name">Text</label>
									<input type="text" class="form-control" name="text" placeholder="Texteingabe">
								</div>
								<div class="form-group hidden" id="text2">
									<label for="name">Text 2</label>
									<input type="text" class="form-control" name="text2" placeholder="Texteingabe">
								</div>
								<div class="form-group hidden" id="text3">
									<label for="name">Text 3</label>
									<input type="text" class="form-control" name="text3" placeholder="Texteingabe">
								</div>
								<div class="form-group hidden" id="text4">
									<label for="name">Text 4</label>
									<input type="text" class="form-control" name="text4" placeholder="Texteingabe">
								</div>
								<div class="form-group hidden" id="barcode1">
									<label for="name">Barcode</label>
									<input type="text" class="form-control" name="barcode1" placeholder="Texteingabe">
								</div>
								<div class="form-group hidden" id="barcode2">
									<label for="name">EAN</label>
									<input type="text" class="form-control" name="barcode2" placeholder="Texteingabe">
								</div>
								<div class="form-group">
									<label for="copies">Copies</label>
									<select class="form-control" name="copies">';
										for($i=1;$i<=$config['max_copies'];$i++) {
											echo '<option value="'.$i.'">'.$i.'</option>';
										}
									echo '</select>
								</div>
								<div class="form-group">
									<label for="logo">Logo</label>
									<select class="form-control" name="logo">
										<option value="yes">Yes</option>
										<option value="no">No</option>
									</select>
								</div>
								<div class="form-group">
									<button type="button" class="btn btn-primary" name="print">Print</print> 
									<button type="button" class="btn btn-primary" name="preview">Preview</print>
									<button type="button" class="btn btn-primary" name="download">Download</print>
								</div>
							</form>
						</div>
					</div>
				</div>
				<div class="col-sm">
					<div class="card">
						<h5 class="card-header">Preview</h5>
						<div class="card-body">
							<div id="preview"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
	<script src="assets/script.js"></script>
	</body>
</html>';

}

function tempfile($prefix, $sufix, $dir) {
	while (true) {
		$filename = uniqid($prefix, true).".".$sufix;
		if (!file_exists($dir.$filename)) break;
	}
	return $filename;
}
	

?>

