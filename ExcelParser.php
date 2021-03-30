<?php
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Collection\Cells;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * ExcelParser class
 */
class ExcelParser
{
    private string $FilePath;
    private Spreadsheet $Spreadsheet;
    private Worksheet $WorksheetData;
    /**
     * @var Worksheet
     */
    private Worksheet $ActiveSheet;
    private Cells $CellsCollection;
    private int $HighestRow;
    private string $HighestColumn;
    /**
     * @var string[]
     */
    private array $WeekDays;
    /**
     * @var string[]
     */
    private array $SubjectTimes;

    /**
     * Initialize variables
     * @param string $FilePath
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function __construct(string $FilePath)
    {
        $this->FilePath = $FilePath;
        $this->Spreadsheet = IOFactory::load($this->FilePath);
        $this->WorksheetData = $this->Spreadsheet->getSheet(0);
        $this->ActiveSheet = $this->Spreadsheet->getActiveSheet();
        $this->CellsCollection = $this->Spreadsheet->getActiveSheet()->getCellCollection();
        $this->HighestRow = $this->CellsCollection->getHighestRow();
        $this->HighestColumn = $this->CellsCollection->getHighestColumn();
        $this->WeekDays = [1 => 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
        $this->SubjectTimes = [1 => '[08:30-10:05]', '[10:15-11:50]', '[12:00-13:35]', '[14:00-15:35]', '[15:45-17:20]', '[17:30-19:05]', '[19:15-20:50]'];
    }

    /**
     * Getting timetable from excel
     * @param string $group_name
     * @param string $user_date
     * @return bool | string
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function GetTimetable(string $group_name, string $user_date): bool|string
    {
        $Digit = 0;
        $Row = "";
        for ($i = 1; $i < $this->HighestRow; $i++) {
            $Row = $this->WorksheetData->getCell('A' . $i)->getValue();
            if ($Row == $user_date) {
                $Location = $this->WorksheetData->getCell('A' . $i)->getCoordinate();
                $Digit = substr($Location, 1);
                break;
            }
            if ($i >= $this->HighestRow) return "Нет данных\n";
        }
        for ($letter = 'A'; $letter != $this->HighestColumn; $letter++) {
            $temp_name = $this->WorksheetData->getCell($letter . "4")->getValue();
            $temp_name = mb_strtoupper($temp_name);
            if ($temp_name == $group_name) {
                $TimeTable = [];
                for ($i = 1; $i < 8; $i++) {
                    $list = $this->WorksheetData->getCell($letter . $Digit)->getValue();
                    $Digit++;
                    if (empty($list)) $list = '-';
                    array_push($TimeTable, $i . '&#8419; ' . $this->SubjectTimes[$i] . "\n" . str_replace("3(", "3 (", $list) . "\n");
                }
                $UpLineString = "Расписание на " . $Row . " (" . $this->WeekDays[date('w', strtotime($user_date))] . ")\n";
                return $UpLineString . str_repeat(".", strlen($UpLineString)) . "\n" . implode('', $TimeTable);
            }
        }
        return false;
    }

    /**
     * Getting dates from excel
     * @return bool | string
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function GetDates(): bool|string
    {
        $HighestRowTimes = $this->HighestRow - 6;
        $min_date = $this->WorksheetData->getCell('A5')->getValue();
        $max_date = $this->WorksheetData->getCell('A' . $HighestRowTimes)->getValue();
        $TimePeriod = "от $min_date до $max_date";

        return empty($TimePeriod) ? false : $TimePeriod;
    }

    /**
     * Getting dates from excel
     * @return bool | array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function GetGroupsList(): bool|array
    {
        $groups = [];
        for ($letter = 'C'; $letter != $this->HighestColumn; $letter++) {
            $GroupName = $this->ActiveSheet->getCell($letter . "4")->getValue();
            if (!empty($GroupName)) {
                array_push($groups, "🔹" . mb_strtoupper($GroupName));
            }
        }
        if (empty($groups))
            return false;
        asort($groups);
        return $groups;
    }
}