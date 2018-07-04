<?php
require_once realpath(dirname(__FILE__)) . "/../vendor/autoload.php";

$inputFileName = realpath(dirname(__FILE__)) . "/../data/data-account-file.xlsx";

//  Read your Excel workbook
try {
	$inputFileType = PHPExcel_IOFactory::identify($inputFileName);
	$objReader = PHPExcel_IOFactory::createReader($inputFileType);
	$objPHPExcel = $objReader->load($inputFileName);
} catch(Exception $e) {
	die('Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
}

//  Get worksheet dimensions
$sheet = $objPHPExcel->getSheet(0);
$highestRow = $sheet->getHighestRow();
$highestColumn = "Z";

$template = [];
$data = [];
//  Loop through each row of the worksheet in turn
for ($row = 1; $row <= $highestRow; $row++){
	//  Read a row of data into an array
	$rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
		NULL,
		TRUE,
		FALSE);
	//  Insert row data array into your database of choice here
	//print_r($rowData);
	if ($row === 1) {
		$column_names = $rowData[0];
		for($j = 0;$j < sizeof($column_names); $j++) {
			if (!empty($column_names[$j])) {
				$template[$j] = trim($column_names[$j]);
			}
		}
	} else {
		$node = [];
		foreach ($template as $key => $value) {
			$node[$value] = null;
		}

		$row_of_data = $rowData[0];
		foreach ($row_of_data as $da_index => $da_value) {
			if (!empty(trim($da_value))) {
				$lookup = $template[$da_index];
				$node[$lookup] = trim($da_value);
			}
		}
		array_push($data,$node);
	}
}

print_r($data);


