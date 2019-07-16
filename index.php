<?php

$config['max_copies'] = 10;

if(isset($_GET['download'])) {
	
	$filename = trim($_GET['download']);
	$filename = str_replace("/", "", $filename);
	
	$download = sys_get_temp_dir()."/".$filename;
	
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
	$form_copies = intval($_POST['copies']);
	$form_template = trim($_POST['template']);
	$form_logo = ($_POST['logo'] == "yes" ? true : false);
	
	if($form_text == "") {
		$errormsg = "Text fehlt!";
	} elseif(strlen($form_text) >= 20) {
		$errormsg = "Text zu lang!";
	} elseif($form_copies <= 0 || $form_copies > $config['max_copies']) {
		$errormsg = "Zu viele oder wenig Exemplare. Maximal 10!";
	} elseif($form_template == "") {
		$errormsg = "Template fehlt!";
	}
	
	if($errormsg == "") {

	
		$temp_file = tempnam(sys_get_temp_dir(), 'web2dymo').".pdf";
		
		require('fpdf/fpdf.php');

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
										<option value="tmp1">Dauerleihgabe</option>
										<option value="tmp2">Freitext (zwei Zeilen)</option>
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
	

?>

