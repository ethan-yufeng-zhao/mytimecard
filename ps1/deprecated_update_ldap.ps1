


#$url = 'http://jfabweb2.jfab.aosmd.com/ldap/update_for_training_cert_system_030513/index.php'

#(Invoke-RestMethod $url).results

#$webclient = New-Object Net.Webclient
#[xml]$xml =  $webClient.DownloadString('http://jfabweb2.jfab.aosmd.com/ldap/update_for_training_cert_system_030513/employinfo.xml')
Add-PSSnapin Quest.ActiveRoles.ADManagement

Clear-Host
[xml]$xml = Get-Content 'employinfo.xml'
$xml.employeeinfo.employee | ForEach-Object {

	if($_.samaccountname -eq 'aarciaga'){
	#if($_.samaccountname -eq 'jcubic'){
		$_.personname
		
		$myuser = Get-QADUser -SamAccountName $_.samaccountname -SearchRoot 'OU=Users,OU=JFab,DC=jfab,DC=aosmd,DC=com' `
			-IncludedProperties 'ipPhone', 'distinguishedName'
		
	 	$userhash = @{
			'departmentNumber' = [string]$_.areanumber
			'EmployeeID' = [string]$_.employeenumber
			'employeeType' = [string]$_.employeetype
			'company' = 'Jireh Semiconductor, Inc.'
			'physicalDeliveryOfficeName' = 'Hillsboro'
		}
		
		if($myuser.ipPhone.length -gt 0){ #Updating the phone info
			$extension = $myuser.ipPhone
			if([int]$extension -ge 2000 -and [int]$extension -le 2099){
				$userhash.Add("telephoneNumber", '(503-615) '+$extension)
				$userhash.Add("description", 'HIL, JFab (503-615) '+$extension)
			} elseif([int]$extension -ge 5900 -and [int]$extension -le 6099){
				$userhash.Add("telephoneNumber", '(503-681) '+$extension)
				$userhash.Add("description", 'HIL, JFab (503-681) '+$extension)
			} elseif([int]$extension -ge 6300 -and [int]$extension -le 6399){
				$userhash.Add("telephoneNumber", '(503-681) '+$extension)
				$userhash.Add("description", 'HIL, JFab (503-681) '+$extension)
			} elseif([int]$extension -ge 6800 -and [int]$extension -le 6899){
				$userhash.Add("telephoneNumber", '(503-681) '+$extension)
				$userhash.Add("description", 'HIL, JFab (503-681) '+$extension)
			} else {
				$userhash.Add("telephoneNumber", '')
				$userhash.Add("description", 'HIL, JFab, overhead')
			}
		}
		
		if($_.organization.length -gt 0){ #They have a department name
			$userhash.Add("department", [string]$_.organization)
		}
		if($_.managersamaccountname.length -gt 0){ #They have a manager
			#$mymanager = Get-QADUser -SamAccountName 'wenjun.li' -SearchRoot 'OU=Users,OU=JFab,DC=jfab,DC=aosmd,DC=com'
			$mymanager = Get-QADUser -SamAccountName $_.managersamaccountname -SearchRoot 'OU=Users,OU=JFab,DC=jfab,DC=aosmd,DC=com'
			$userhash.Add("manager", [string]$mymanager.DN)
		}
		$userhash | Write-Output
		Write-Output('')
		
		
		
		#set-QADUser $myuser.DN -ObjectAttributes $userhash
	}
}


#


#Get-QADUser -SamAccountName 'jcubic' -SearchRoot 'OU=Users,OU=JFab,DC=jfab,DC=aosmd,DC=com' | select DN




#$xml | select *

#$jsonroot = $jsonstring | ConvertFrom-JSON
#
#$jsonroot | ForEach-Object { 
#	$temparr = $_ | ConvertFrom-JSON
#	$temparr | select *
#	
#}


#select jcubic
