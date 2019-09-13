# Payroll
CommandLine Utility for Generating Payroll Processing Dates
*******************************************************************************************************************************
1. clone project from Github
	git clone https://github.com/mlalwani6191/Payroll.git


2. Generate Report (Command Line)
	1.php pathto project/index.php payroll/index 
	php /var/www/html/Payroll/index.php payroll/index

	-- This Command will generate report in Report folder which is located at root of project

	2.php pathto project/index.php payroll/index month_number
	php index.php payroll/index 1:6

	-- This will generate report from January(1) to June(6)

3. Generate Report (Browser)
	1.One can execute this utility from browser also, to do that change configuration in Payroll/application/config/constants.php (EXECUTION_MODE = "browser")
	2.Execute the project from browser using (http://localhost/Payroll/)
	3.CSV Report will be generated
