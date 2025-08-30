REM
REM	to use this just enter the sql files in this directory to backup
REM	EXAMPLE:
REM	restore.bat monthly.sql
REM
REM
"C:\Program Files\MySQL\MySQL Server 5.5\bin\mysql.exe" --host hilweb5 --user root --password=t0rtur3d wordpress < C:\MySQL_Backup\%1