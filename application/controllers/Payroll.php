<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Payroll
 * 
 * 
 * @package    CI
 * @subpackage Controller
 * @author     Mahesh Lalwani <mahesh.lalwani59@gmail.com>
 */
class Payroll extends CI_Controller {

    protected $strProcessPeriod = 'all';
    protected $arrMonthCollection = [];
    protected $boolFileProcessed = false;
    protected $strPayrollReportPath;
    protected $strPayrollReportName = 'PayrollReminder.csv';
    protected $intMonthStartIndex = 1;
    protected $intMonthEndIndex = 12;
    protected $strInputRequest;

    public function __construct() {
        try {
            $this->strPayrollReportPath = FCPATH . 'Reports/' . $this->strPayrollReportName;
            $this->_createReportFile();
        } catch (Exception $ex) {
            log_message('error', $ex->getMessage());
            echo GENERAL_EXCEPTION;
        }
    }

    /**
     * 
     * Entry Level Function (index)
     *
     * @param string $params  Input Parameters entered on commandline , default  = all
     * @return string
     */
    public function index($params = 'all') {
        try {
            $this->strInputRequest = $params;
            $this->_processRequest();
            $this->_processPayroll();
            $this->_generateOutPut();
            if ($this->boolFileProcessed) {
                echo '**********Payroll Report Generated**********';
            } else {
                echo '**********Error Processing Payroll Report, Please Contact Support**********';
            }
        } catch (Exception $ex) {
            log_message('error', $ex->getMessage());
            echo GENERAL_EXCEPTION;
        }
    }

    /**
     * 
     * Process Request
     *
     * @desc  validate and process input request
     * @return void
     */
    private function _processRequest() {
        try {
            if (false == is_array($this->strInputRequest) || $this->strInputRequest != 'all') {
                $arrInputRequest = explode(':', $this->strInputRequest);
                if (isset($arrInputRequest[0])) {
                    $intStartIndex = $arrInputRequest[0];
                    $this->intMonthStartIndex = filter_var(
                            $intStartIndex, FILTER_VALIDATE_INT, array(
                        'options' => array(
                            'default' => 1,
                            'min_range' => 1,
                            'max_range' => 12
                        )
                            )
                    );
                }
                if (isset($arrInputRequest[1])) {
                    $intEndIndex = $arrInputRequest[1];
                    $this->intMonthEndIndex = filter_var(
                            $intEndIndex, FILTER_VALIDATE_INT, array(
                        'options' => array(
                            'default' => 12,
                            'min_range' => 1,
                            'max_range' => 12
                        )
                            )
                    );
                }
            }
        } catch (Exception $ex) {
            log_message('error', $ex->getMessage());
            echo GENERAL_EXCEPTION;
        }
    }

    /**
     * 
     * Process Payroll
     *
     * @desc  Process Payroll Dates,bonus dates
     * @return void
     */
    private function _processPayroll() {
        try {
            for ($i = $this->intMonthStartIndex; $i <= $this->intMonthEndIndex; $i++) {
                $this->_setLastWorkingDay($i);
            }
        } catch (Exception $ex) {
            log_message('error', $ex->getMessage());
            echo GENERAL_EXCEPTION;
        }
    }

    /**
     * 
     * SetLastWorkingDay
     *
     * @desc  Set Last working day of requested month
     * @param int $monthNumber Month Number for which lastowrking day,salary date and bonus is requied 
     * @return void
     */
    private function _setLastWorkingDay($monthNumber) {
        try {
            $strMonthName = date("F", mktime(0, 0, 0, $monthNumber, 1, date("Y")));
            $strLastDateOfMonth = date("Y-m-t", mktime(0, 0, 0, $monthNumber, 1, date("Y")));
            $strWeekDay = date('l', strtotime($strLastDateOfMonth));
            if ($strWeekDay == "Saturday") {
                $strLastDateOfMonth = date('Y-m-d', strtotime($strLastDateOfMonth . ' -1 days'));
            } elseif ($strWeekDay == "Sunday") {
                $strLastDateOfMonth = date('Y-m-d', strtotime($strLastDateOfMonth . ' -2 days'));
            }
            $this->arrMonthCollection[$strMonthName] = [
                'month' => $strMonthName,
                'salary_date' => $strLastDateOfMonth
            ];
            $this->_processBonus($monthNumber);
        } catch (Exception $ex) {
            log_message('error', $ex->getMessage());
            echo GENERAL_EXCEPTION;
        }
    }

    /**
     * 
     * Process Bonus
     *
     * @desc  process bonus date for each month
     * @param int $monthNumber Month Number for  which bonus is requied to be processed
     * @return void
     */
    private function _processBonus($monthNumber) {
        try {
            $strMonthName = date("F", mktime(0, 0, 0, $monthNumber, 1, date("Y")));
            $strBonusDate = date("Y-m-d", mktime(0, 0, 0, $monthNumber, BONUS_DATE));
            $strWeekDay = date('l', strtotime($strBonusDate));
            if ($strWeekDay == "Saturday" || $strWeekDay == "Sunday") {
                $strBonusDate = date('Y-m-d', strtotime("next " . NEXT_BONUS_DATE, strtotime($strBonusDate)));
            }
            $this->arrMonthCollection[$strMonthName]['bonus_date'] = $strBonusDate;
        } catch (Exception $ex) {
            log_message('error', $ex->getMessage());
            echo GENERAL_EXCEPTION;
        }
    }

    /**
     * 
     * Generate Output
     *
     * @desc  Generate report File
     * @param int $type Report type format , default is csv
     * @return file
     */
    private function _generateOutPut($type = 'csv') {
        try {
            switch ($type) {
                case "csv":
                    if (count($this->arrMonthCollection)) {
                        if (EXECUTION_MODE == 'cmd') {
                            $fp = fopen($this->strPayrollReportPath, 'w');
                            fputcsv($fp, array('Month', 'Salary Date', 'Bonus Date'));
                            foreach ($this->arrMonthCollection as $key => $arrPayroll) {
                                fputcsv($fp, $arrPayroll);
                            }
                            if (fclose($fp)) {
                                $this->boolFileProcessed = TRUE;
                            }
                        } else {
                            header("Content-Type:application/csv");
                            header("Content-Disposition:attachment;filename=" . $this->strPayrollReportName);
                            $fp = fopen('php://output', 'wb');
                            fputcsv($fp, array('Month', 'Salary Date', 'Bonus Date'));
                            foreach ($this->arrMonthCollection as $key => $arrPayroll) {
                                fputcsv($fp, $arrPayroll);
                            }
                            if (fclose($fp)) {
                                $this->boolFileProcessed = TRUE;
                            }
                        }
                    } else {
                        throw new Exception('Payroll collection is Empty');
                    }
                    break;
            }
        } catch (Exception $ex) {
            log_message('error', $ex->getMessage());
            echo GENERAL_EXCEPTION;
        }
    }

    /**
     * 
     * Create Report File
     *
     * @desc  Create report File
     * @return void
     */
    private function _createReportFile() {
        try {
            if (!file_exists($this->strPayrollReportPath)) {
                $fh = fopen($this->strPayrollReportPath, 'w');
                fclose($fh);
            }
        } catch (Exception $ex) {
            log_message('error', $ex->getMessage());
            echo GENERAL_EXCEPTION;
        }
    }

}
