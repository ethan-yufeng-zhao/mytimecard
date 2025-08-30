REM
REM	to use this just enter the sql files in this directory to backup
REM	EXAMPLE:
REM	backup.bat monthly.sql
REM
REM
"C:\Program Files (x86)\MySQL\MySQL Server 5.5\bin\mysqldump.exe" --opt --host jfabweb3 --user root --password=t0rtur3d tcs > C:\inetpub\wwwroot\Training_Cert_System\sql_backup\%1