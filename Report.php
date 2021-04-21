<?php
require_once("./config.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Report
{

    private function init_file_if_not_exist()
    {
        if (!is_dir("./reports"))
            mkdir("./reports");

        $file_path = "./reports/" . date("Y-m-d") . ".xlsx";
        if (file_exists($file_path))
            return $file_path;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue("A1", "Start date");
        $sheet->setCellValue("B1", "Start time");
        $sheet->setCellValue("C1", "End date");
        $sheet->setCellValue("D1", "End time");
        $sheet->setCellValue("E1", "Diagnosis");
        $sheet->setCellValue("F1", "Backup used");

        $writer = new Xlsx($spreadsheet);
        $writer->save($file_path);

        return $file_path;
    }

    public function create_report($start_timestamp, $end_timestamp, $diagnosis, $backup_usage)
    {
        $file_path = $this->init_file_if_not_exist();
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
        $sheet = $spreadsheet->getActiveSheet();

        $row = strval($sheet->getHighestRow() + 1);
        $start_date = date("Y-m-d", $start_timestamp);
        $start_time = date("H:M:s", $start_timestamp);
        $end_date = date("Y-m-d", $end_timestamp);
        $end_time = date("H:M:s", $end_timestamp);

        $sheet->setCellValue("A" . $row, $start_date);
        $sheet->setCellValue("B" . $row, $start_time);
        $sheet->setCellValue("C" . $row, $end_date);
        $sheet->setCellValue("D" . $row, $end_time);
        $sheet->setCellValue("E" . $row, $diagnosis);
        $sheet->setCellValue("F" . $row, $backup_usage);

        $writer = new Xlsx($spreadsheet);
        $writer->save($file_path);
    }

}
