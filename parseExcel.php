<?php
include "./vendor/phpoffice/phpexcel/Classes/PHPExcel.php";
class TestParse {
    public function __construct() {
        //parent::__construct('TestParse');
    }

    public function test()
    {
        $fileName = './夏目10月.xlsx';
        $reader = new PHPExcel_Reader_Excel2007();
        $reader->setReadDataOnly(TRUE);

        $reader->setLoadSheetsOnly();
        $excel = $reader->load($fileName);
        $sheet1 = $excel->getSheet(0);
        $maxRow = $sheet1->getHighestRow();

        $column = 2;
        $dataM = array();
        for ($row=2;$row<$maxRow;$row++) {
            if (empty($sheet1->getCellByColumnAndRow($column, $row)->getValue())) continue;
            $dataM[] = $sheet1->getCellByColumnAndRow($column,$row)->getValue();
        }

        $sheet2 = $excel->getSheet(1);
        $maxRow = $sheet2->getHighestRow();
        for ($row=2;$row<$maxRow;$row++) {
            if (empty($sheet2->getCellByColumnAndRow($column, $row)->getValue())) continue;
            $dataM[] = $sheet2->getCellByColumnAndRow($column, $row)->getValue();
        }

        $sheet3 = $excel->getSheet(2);
        $maxRow = $sheet3->getHighestRow();
        $column = 12;
        for ($row=2;$row<$maxRow;$row++) {
            if (empty($sheet3->getCellByColumnAndRow($column, $row)->getValue())) continue;
            $dataM[] = $sheet3->getCellByColumnAndRow($column, $row)->getValue();
        }

        $fileName = './新马港台.xlsx';
        $reader->setLoadSheetsOnly();
        $excel = $reader->load($fileName);
        $sheet = $excel->getSheet(0);
        $maxRow = $sheet->getHighestRow();

        $data1 = array();
        $column = 0;
        for ($row=2;$row<=$maxRow;$row++) {
            $data1[] = $sheet->getCellByColumnAndRow($column,$row)->getValue();
        }

        $fileName = './泰国.xlsx';
        $reader->setLoadSheetsOnly();
        $excel = $reader->load($fileName);
        $sheet = $excel->getSheet(0);
        $maxRow = $sheet->getHighestRow();

        $data2 = array();
        $column = 0;
        for ($row=1;$row<=$maxRow;$row++) {
            $data2[] = $sheet->getCellByColumnAndRow($column,$row)->getValue();
        }

        $data = array_merge($data1, $data2);
        $data = array_unique($data);

        //将数据写入文件
        $output = 'output';
        if (!file_exists($output)) {
            $myFile = fopen($output, "w");
        }

        $objPHPExcel = new PHPExcel();
        $objSheet = $objPHPExcel->getActiveSheet();
        $objSheet->setTitle('channelOrderId');
        $i = 1;
        $data4 = array();
        foreach ($data as $value) {
            if (!in_array($value, $dataM)) {
                fwrite($myFile, $value."\n");
                $objSheet->setCellValue("A".$i, $value);
                $i++;
                $data4[] = $value;
            }
        }
        $i = 1;
        foreach ($dataM as $value) {
            if (!in_array($value, $data)) {
                fwrite($myFile, $value."\n");
                $objSheet->setCellValue("B".$i, $value);
                $i++;
                $data3[] = $value;
            }
        }

        $dir = dirname(__FILE__);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($dir.'/output.xlsx');

        fclose($myFile);
        var_dump(count($data3));
        var_dump(count($data4));
    }
}

TestParse::test();
