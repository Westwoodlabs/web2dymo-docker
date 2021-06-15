<?php

include "libs/autoload.php";

$config['max_copies'] = 10;
$config['tmpdir'] = sys_get_temp_dir()."/";
$config['tmpdelete'] = 60 * 60 * 24 * 2; // 2 days
$config['printermodels'] = array("dymo320"=>"Dymo LabelWriter 320", "dymo450"=>"Dymo LabelWriter 450");


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
	$form_tab = trim($_POST['tab']);
	
	if(isset($_GET['debug'])) {
		$debug = true;
	}

	if($form_action == "template") {
		$data = array(
			"sizes" => array(
				"small" => "Klein 54x25mm [11352]",
				"medium" => "Mittel 88x36mm [99012]"
			),
			"templates"=>array(
				"small" => array(
					"11352_1"=>array(
						"name"=>"Dauerleihgabe",
						"lines"=>1
					),
					"11352_2"=>array(
						"name"=>"Freitext (zwei Zeilen)",
						"lines"=>2
					)
				),
				"medium" => array(
					"99012_1"=>array(
						"name"=>"Dauerleihgabe",
						"lines"=>1
					),
					"99012_2"=>array(
						"name"=>"Freitext (eine Zeilen)",
						"lines"=>1
					),
					"99012_3"=>array(
						"name"=>"Freitext (zwei Zeilen)",
						"lines"=>1
					),
					"99012_4"=>array(
						"name"=>"Freitext (vier Zeilen)",
						"lines"=>4
					),
					"99012_5"=>array(
						"name"=>"FBarcode (CODE 128)",
						"lines"=>1
					),
					"99012_6"=>array(
						"name"=>"Barcode (EAN-13)",
						"lines"=>1
					)
				)
			)
		);

		echo json_encode($data);

	} else {
	
		
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
					$printermodel = "dymo320";
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
					$printermodel = "dymo320";
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
					$printermodel = "dymo450";
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
					$printermodel = "dymo450";
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
				$printermodel = "dymo450";
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
				$printermodel = "dymo450";
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

					if($config['printermodels'][$printermodel] != "") {
						$pdf->Output('F', $temp_file);
					
						$exec = "lp -d ".$printermodel." ".$temp_file;
						exec($exec);
						
						echo json_encode(array('okay'=>true, 'html'=>'Label will be printed shortly on <b>'.$config['printermodels'][$printermodel].'</b>!<br />Please wait a moment...', 'debug'=>$exec));
				
					} else {
						echo json_encode(array('okay'=>true, 'html'=>'Error im template file. Printer not found!', 'debug'=>$exec));
				
					}
					break;
				
			}


		} else {
			echo json_encode(array('okay'=>true, 'error'=>true, 'html'=>$errormsg));
		}
	}

		//echo "hallo";
	
} else {

	echo '<!DOCTYPE html>
	<html lang="de">
	<head>
		<meta charset="utf-8"/>
		<title>Web2Dymo - Dymo Label Printer Webinterface</title>
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
		<link href="assets/style.css" rel="stylesheet">
		<link rel="shortcut icon" href="assets/favicon.ico">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
	</head>
	<body>
		<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
			<a class="navbar-brand" href="#">Web2Dymo - Dymo Label Printer Webinterface</a>
		</nav>
	<div id="main">
		<div class="container">
			<div class="row">
				<div class="col-sm">
					<div class="card">
						<div class="card-header">
							<ul class="nav nav-tabs card-header-tabs" id="myTab" role="tablist">
								<li class="nav-item" role="presentation">
									<a class="nav-link active" id="text-tab" data-toggle="tab" href="#text" role="tab" aria-controls="test" aria-selected="true">Text input</a>
								</li>
								<li class="nav-item" role="presentation">
									<a class="nav-link" id="upload-tab" data-toggle="tab" href="#upload" role="tab" aria-controls="upload" aria-selected="false">Upload</a>
								</li>
							</ul>
						</div>
						<div class="card-body">
							<form class="form">
								<div class="tab-content">
									<div class="tab-pane active" id="text" role="tabpanel" aria-labelledby="text-tab">
									<div class="form-group">
											<label for="size">Size</label>
											<select class="form-control" name="size" id="size" disabled>
												<option>Loading...</option>
											</select>
										</div>
										<div class="form-group">
											<label for="template">Template</label>
											<select class="form-control" name="template" id="template"disabled>
												<option>Select size...</option>
											</select>
										</div>
										<div id="textinputs"></div>
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
										<div class="form-group" style="margin-bottom:0">
											<button type="button" class="btn btn-primary" name="print">Print</print> 
											<button type="button" class="btn btn-primary" name="preview">Preview</print>
											<button type="button" class="btn btn-primary" name="download">Download</print>
										</div>
									</div>
									<div class="tab-pane" id="upload" role="tabpanel" aria-labelledby="upload-tab">
										<div class="form-group">
											<label for="file">Choose a file</label>
											<input type="file" name="file" id="file" class="form-control"/>
										</div>
										<div class="form-group">
											<label for="template">Printer</label>
											<select class="form-control" name="template">';

											foreach ($config['printermodels'] as $model => $name) {
												echo '<option value="'.$name.'">'.$name.'</option>';
											}

											echo '</select>
										</div>
										<div class="form-group" style="margin-bottom:0">
											<button type="button" class="btn btn-primary" name="print">Print</print> 
											<button type="button" class="btn btn-primary" name="preview">Preview</print>
										</div>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
				<div class="col-sm">
					<div class="card mb-3">
						<h5 class="card-header">Output</h5>
						<div class="card-body">
							<div id="loader" style="display:none"><svg xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.0" width="24px" height="24px" viewBox="0 0 128 128" xml:space="preserve"><rect x="0" y="0" width="100%" height="100%" fill="#FFFFFF" /><path fill="#000000" fill-opacity="1" d="M64.4 16a49 49 0 0 0-50 48 51 51 0 0 0 50 52.2 53 53 0 0 0 54-52c-.7-48-45-55.7-45-55.7s45.3 3.8 49 55.6c.8 32-24.8 59.5-58 60.2-33 .8-61.4-25.7-62-60C1.3 29.8 28.8.6 64.3 0c0 0 8.5 0 8.7 8.4 0 8-8.6 7.6-8.6 7.6z"><animateTransform attributeName="transform" type="rotate" from="0 64 64" to="360 64 64" dur="1800ms" repeatCount="indefinite"></animateTransform></path></svg></div>
							<div id="preview"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-terminal"><polyline points="4 17 10 11 4 5"></polyline><line x1="12" y1="19" x2="20" y2="19"></line></svg></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<footer class="footer">
      <div class="container text-center">
       <span class="text-muted">&copy; 2020 Fab!an for <a href="//westwoodlabs.de">Westwoodlabs e.V.</a></span>
      </div>
    </footer>
	<script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
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

