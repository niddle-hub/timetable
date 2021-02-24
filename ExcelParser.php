<?php

include 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet;

/**
 * Excel parser class
 */
class ExcelParser
{
	private $FilePath;
	private $Spreadsheet;
	private $WorksheetData;
	private $WeekDays;
	private $SubjectTimes;
	private $CellsCollection;
	private $ActiveSheet;
	private $HigestRow;
	private $HigestColumn;

	public function __construct (string $FilePath)
	{
		$this->FilePath = $FilePath;
		$this->Spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($this->$FilePath);
		$this->WorksheetData = $Spreadsheet->getSheet(0);
		$this->WeekDays = [1=>'Пн','Вт','Ср','Чт','Пт','Сб','Вс'];
		$this->SubjectTimes = [1=>'[08:30-10:05]','[10:15-11:50]','[12:00-13:35]','[14:00-15:35]','[15:45-17:20]','[17:30-19:05]','[19:15-20:50]'];
		$this->CellsCollection = $Spreadsheet->getActiveSheet()->getCellCollection();
		$this->ActiveSheet = $Spreadsheet->getActiveSheet();
		$this->HigestRow = $CellsCollection->getHighestRow();
		$this->HigestColumn = $CellsCollection->getHighestColumn();
	}

	/**
	* Getting timetable from excel
	* @param string $group_name
	* @param string $user_date
	* @return bool | string
	*/
	public function GetTimetable(string $group_name, string $user_date)
	{
		for ($i = 1; $i < $HigestRow; $i++) {
			$Row = $WorksheetData->getCell('A'.$i)->getValue();
			if ($Row == $user_date){
				$Location = $WorksheetData->getCell('A'.$i)->getCoordinate();
				$Digit = substr($Location, 1);
				break;
			}
			if ($i >= $HigestRow) return false;
		}
		for ($letter = 'A'; $letter != $HigestColumn; $letter++){
			$temp_name = $WorksheetData->getCell($letter."4")->getValue();
			$temp_name = mb_strtoupper($temp_name);
			if($temp_name == $group_name){
				$TimeTable = [];
				for($i = 1; $i < 8; $i++){
					$list = $WorksheetData->getCell($letter.$digit)->getValue();
					$digit++;
					if (empty($list)) $list='-';
					array_push($TimeTable,$i.'&#8419; '.$SubjectTimes[$i]."\n".str_replace("3(", "3 (", $list)."\n");
				}
				$UpLineString = "Расписание на ".$Row." (".$WeekDays[date('w', strtotime($user_date))].")\n";
				$OutputTimetable = $UpLineString.str_repeat(".", strlen($UpLineString))."\n".implode('',$TimeTable);
				return $OutputTimetable;
			}
			if ($letter >= $HigestColumn) return false;
		}
		return false;
	}

	/**
	* Getting dates from excel
	* @return bool | string
	*/
	public function GetDates()
	{
		$HigestRowTimes = $HigestRow - 6;
		$min_date = $WorksheetData->getCell('A5')->getValue();
		$max_date = $WorksheetData->getCell('A'.$HigestRowTimes)->getValue();
		$TimePeriod = "от $min_date до $max_date";

		return empty($TimePeriod) ? false: $TimePeriod ;
	}

	/**
	* Getting dates from excel
	* @return bool | array
	*/
	public function GetGroupsList()
	{
		$groups=[];
		for($letter='C'; $letter != $HigestColumn; $letter++){
			$GroupName = $ActiveSheet->getCell($letter."4")->getValue();
			if(!empty($GroupName)){
				array_push($groups, "🔹".mb_strtoupper($GroupName));
			}
		}
		if (!empty($groups)) {
			asort($groups);
			return $groups;
		} else return false;
	}
}