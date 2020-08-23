<?php
include 'db.php';
include './vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class excel{

	public function getTimetable (string $group_name, string $user_date, bool $week=false) {

		$FilePath = './config/xtable/raspisanie.xlsx';
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($FilePath);
		$worksheetData = $spreadsheet->getSheet(0);

		$weekDays=[1=>'Пн','Вт','Ср','Чт','Пт','Сб','Вс'];
		$subjectTimes=[1=>'[08:30-10:05]','[10:15-11:50]','[12:00-13:35]','[14:00-15:35]','[15:45-17:20]','[17:30-19:05]','[19:15-20:50]'];

		$cells = $spreadsheet->getActiveSheet()->getCellCollection();
		$maxrow = $cells->getHighestRow();
		for($i=1;$i<=$maxrow;$i++){
			$Row = $worksheetData->getCell('A'.$i)->getValue();
			if($Row==$user_date){
				$Location= $worksheetData->getCell('A'.$i)->getCoordinate();
				$digit=substr($Location, 1);
				break;
			}
			if($i>=$maxrow) return "Нет данных\n";
		}
		$maxcol = $cells->getHighestColumn();
		for($letter='A'; $letter != $maxcol; $letter++){
			$temp_name = $worksheetData->getCell($letter."4")->getValue();
			if(mb_strtoupper($temp_name)==$group_name){
				$TimeTable = [];
				for($i=1;$i<8;$i++){
					$list= $worksheetData->getCell($letter.$digit)->getValue();
					$digit++;
					if (empty($list)) $list='-';
					array_push($TimeTable,$i.'&#8419; '.$subjectTimes[$i]."\n".str_replace("3(", "3 (", $list)."\n");
				}
				$str="Расписание на ".$Row." (".$weekDays[date('w', strtotime($user_date))].")\n";
				return $str.str_repeat(".", strlen($str))."\n".implode('',$TimeTable);
			}
		}
	}

	public function getTimes(){

		$FilePath = './config/xtable/raspisanie.xlsx';
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($FilePath);
		$worksheetData = $spreadsheet->getSheet(0);

		$cells = $spreadsheet->getActiveSheet()->getCellCollection();
		$hr = ($cells->getHighestRow())-6;
		$min_date = $worksheetData->getCell('A5')->getValue();
		$max_date = $worksheetData->getCell('A'.$hr)->getValue();

		return "от $min_date до $max_date";
	}

	public function getCellCoordinateByValue (string $value) {

		$FilePath = './config/xtable/raspisanie.xlsx';
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($FilePath);

		$worksheet = $spreadsheet->getActiveSheet();
		$finds=[];
		for ($row = 1; $row <= $worksheet->getHighestRow(); ++$row) {
	    	for ($col = 'A'; $col != $worksheet->getHighestColumn(); ++$col) {
	             $tempValue=$worksheet->getCell($col . $row)->getValue();
	             if($tempValue==$value){
	             	$Location= $worksheet->getCell($col . $row)->getCoordinate();
					array_push($finds , $Location);
	             }
	         }
	     }
		if(empty($finds)){
			return "Не найдено";
		}
		else return $finds;
	}

	public function getGroupsList() {

		$FilePath = './config/xtable/raspisanie.xlsx';
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($FilePath);

		$worksheet = $spreadsheet->getActiveSheet();
		$maxcol = $worksheet->getHighestColumn();
		$groups=[];
		for($letter='C'; $letter != $maxcol; $letter++){
			$value = $worksheet->getCell($letter."4")->getValue();
			if($value!=''){
				array_push($groups, "🔹".mb_strtoupper($value));
			}
		}
		asort($groups);
		return $groups;
	}
}