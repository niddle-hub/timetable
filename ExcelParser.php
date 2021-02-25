<?php
require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * ExcelParser wrapper class
 */
class ExcelParser
{
	/**
	* Initialize variables
	* @param string $FilePath
	* @return void
	*/
	public function __construct(string $FilePath)
	{
		$this->FilePath = $FilePath;
		$this->Spreadsheet = IOFactory::load($this->FilePath);
		$this->WorksheetData = $this->Spreadsheet->getSheet(0);
		$this->ActiveSheet = $this->Spreadsheet->getActiveSheet();
		$this->CellsCollection = $this->Spreadsheet->getActiveSheet()->getCellCollection();
		$this->HigestRow = $this->CellsCollection->getHighestRow();
		$this->HigestColumn = $this->CellsCollection->getHighestColumn();
		$this->WeekDays = [1=>'Пн','Вт','Ср','Чт','Пт','Сб','Вс'];
 		$this->SubjectTimes = [1=>'[08:30-10:05]','[10:15-11:50]','[12:00-13:35]','[14:00-15:35]','[15:45-17:20]','[17:30-19:05]','[19:15-20:50]'];
 	}

	/**
	* Getting timetable from excel
	* @param string $group_name
	* @param string $user_date
	* @return bool | string
	*/
	public function GetTimetable(string $group_name, string $user_date)
	{
		for ($i = 1; $i < $this->HigestRow; $i++) {
			$Row = $this->WorksheetData->getCell('A'.$i)->getValue();
			if ($Row == $user_date){
				$Location = $this->WorksheetData->getCell('A'.$i)->getCoordinate();
				$Digit = substr($Location, 1);
				break;
			}
			if ($i >= $this->HigestRow) return "Нет данных\n";
		}
		for ($letter = 'A'; $letter != $this->HigestColumn; $letter++){
			$temp_name = $this->WorksheetData->getCell($letter."4")->getValue();
			$temp_name = mb_strtoupper($temp_name);
			if($temp_name == $group_name){
				$TimeTable = [];
				for($i = 1; $i < 8; $i++){
					$list = $this->WorksheetData->getCell($letter.$Digit)->getValue();
					$Digit++;
					if (empty($list)) $list='-';
					array_push($TimeTable,$i.'&#8419; '.$this->SubjectTimes[$i]."\n".str_replace("3(", "3 (", $list)."\n");
				}
				$UpLineString = "Расписание на ".$Row." (".$this->WeekDays[date('w', strtotime($user_date))].")\n";
				$OutputTimetable = $UpLineString.str_repeat(".", strlen($UpLineString))."\n".implode('',$TimeTable);
				return $OutputTimetable;
			}
		}
	}
	
	/**
	* Getting dates from excel
	* @return bool | string
	*/
	public function GetDates()
	{
		$HigestRowTimes = $this->HigestRow - 6;
		$min_date = $this->WorksheetData->getCell('A5')->getValue();
		$max_date = $this->WorksheetData->getCell('A'.$HigestRowTimes)->getValue();
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
		for($letter='C'; $letter != $this->HigestColumn; $letter++){
			$GroupName = $this->ActiveSheet->getCell($letter."4")->getValue();
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