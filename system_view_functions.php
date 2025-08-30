<?php
	Function view_row_general($data, $mybaseurl){
		echo("<table style='margin-left:1em; background-color:white;'>");
		foreach($data as $item){
			foreach($item as $columnkey => $columnvalue){
				echo("<tr>");
				echo("<td style='min-width:20em;'><strong>");
				echo($columnvalue->{'display_name'});
				echo("</strong></td>");
				echo("<td style='min-width:25em;'>");
				if(is_array($columnvalue->{'data'})){
					if(count($columnvalue->{'data'}) > 1){
						echo("<ul style='margin:0px;'>");
						foreach($columnvalue->{'data'} as $arrayval){
							if(isset($arrayval) && strlen(trim($arrayval)) > 0){
								echo("<li type='disc'>");
								echo($arrayval);
								echo("</li>");
							}
						}
						echo("</ul>");
					} elseif(count($columnvalue->{'data'}) > 0){
						echo($columnvalue->{'data'}[0]);
					}
				} else {
					echo($columnvalue->{'data'});
				}
				echo("</td>");
				echo("</tr>\n");
			}
			if(count($data) > 1){
				echo("<tr><td colspan='2' style='background-color:#E0E0E0;'>&nbsp;</td></tr>");
			}
		}
		echo("</table>");
	}



	Function view_table_general($data, $mybaseurl){
		echo("<table style='background-color:white;' class='tablesorter sortme'>");
		$firstloop = false;
		foreach($data as $item){
			if($firstloop == false){
				echo("<thead>");
				echo("<tr>");
				foreach($item as $columnkey => $columnvalue){
					echo("<th>");
					echo($columnvalue->{'display_name'});
					echo("</th>");
				}
				echo("</tr>\n");
				echo("</thead>");
				$firstloop = true;
				echo("<tbody>");
			}
			echo("<tr>");
			foreach($item as $columnkey => $columnvalue){
				echo("<td>");
				if(is_array($columnvalue->{'data'})){
					if(count($columnvalue->{'data'}) > 1){
						echo("<ul style='margin:0px;'>");
						foreach($columnvalue->{'data'} as $arrayval){
							if(isset($arrayval) && strlen(trim($arrayval)) > 0){
								echo("<li type='disc'>");
								echo($arrayval);
								echo("</li>");
							}
						}
						echo("</ul>");
					} elseif(count($columnvalue->{'data'}) > 0){
						echo($columnvalue->{'data'}[0]);
					}
				} else {
					echo($columnvalue->{'data'});
				}
				echo("</td>");
			}
			echo("</tr>\n");
		}
		echo("<tbody>");
		echo("</table>");
	}




/*
	Function view_system_summary($system_id, $mybaseurl){
		//TODO: convert this to a JSON report
		$url_data = array();
		$url_data['system_id'] = $system_id;
		$json = json_decode(file_get_contents(request_json_api('/JSON_systemsummary.php?'.http_build_query($url_data))), true);
		echo("<table style='margin-left:1em; margin-top:0px; background-color:white;'>\n");

		echo("<tr>");
		echo("<td style='min-width:20em;'><strong>");
		echo("LDAP Description");
		echo("</strong></td>");
		echo("<td style='min-width:20em;'>");
		echo($json->{'ldap_computers_description'});
		echo("</td>");
		echo("</tr>\n");

		echo("<tr>");
		echo("<td style='min-width:20em;'><strong>");
		echo("Local PC's Description");
		echo("</strong></td>");
		echo("<td style='min-width:20em;'>");
		echo($json->{'os_desc'});
		echo("</td>");
		echo("</tr>\n");

		echo("<tr>");
		echo("<td style='min-width:20em;'><strong>");
		echo("Domain Role");
		echo("</strong></td>");
		echo("<td style='min-width:20em;'>");
		echo($json->{'system_domain_role'});
		echo("</td>");
		echo("</tr>\n");

		echo("<tr>");
		echo("<td style='min-width:20em;'><strong>");
		echo("Registered User");
		echo("</strong></td>");
		echo("<td style='min-width:20em;'>");
		echo($json->{'os_registered_user'});
		echo("</td>");
		echo("</tr>\n");

		echo("<tr>");
		echo("<td style='min-width:20em;'><strong>");
		echo("Domain");
		echo("</strong></td>");
		echo("<td style='min-width:20em;'>");
		echo($json->{'system_domain'});
		echo("</td>");
		echo("</tr>\n");

		echo("<tr>");
		echo("<td style='min-width:20em;'><strong>");
		echo("Chassis");
		echo("</strong></td>");
		echo("<td style='min-width:20em;'>");
		echo($json->{'bios_chassis'});
		echo("</td>");
		echo("</tr>\n");

		echo("<tr>");
		echo("<td style='min-width:20em;'><strong>");
		echo("Model");
		echo("</strong></td>");
		echo("<td style='min-width:20em;'>");
		echo($json->{'system_model'});
		echo("</td>");
		echo("</tr>\n");

		echo("<tr>");
		echo("<td style='min-width:20em;'><strong>");
		echo("Serial Number");
		echo("</strong></td>");
		echo("<td style='min-width:20em;'>");
		echo($json->{'system_serial_number'});
		echo("</td>");
		echo("</tr>\n");

		echo("<tr>");
		echo("<td style='min-width:20em;'><strong>");
		echo("Manufacturer");
		echo("</strong></td>");
		echo("<td style='min-width:20em;'>");
		echo($json->{'system_manufacturer'});
		echo("</td>");
		echo("</tr>\n");

		echo("<tr>");
		echo("<td style='min-width:20em;'><strong>");
		echo("Operating System");
		echo("</strong></td>");
		echo("<td style='min-width:20em;'>");
		echo($json->{'os_name'}." sp".$json->{'os_service_pack'});
		echo("</td>");
		echo("</tr>\n");

		echo("<tr>");
		echo("<td style='min-width:20em;'><strong>");
		echo("Operating System Build");
		echo("</strong></td>");
		echo("<td style='min-width:20em;'>");
		echo($json->{'os_build'});
		echo("</td>");
		echo("</tr>\n");

		if(strlen(trim($json->{'os_Architecture'})) > 0){
			echo("<tr>");
			echo("<td style='min-width:20em;'><strong>");
			echo("Architecture");
			echo("</strong></td>");
			echo("<td style='min-width:20em;'>");
			echo($json->{'os_Architecture'});
			echo("</td>");
			echo("</tr>\n");
		}

		echo("<tr>");
		echo("<td style='min-width:20em;'><strong>");
		echo("System UUID");
		echo("</strong></td>");
		echo("<td style='min-width:20em;'>");
		echo($json->{'system_uuid'});
		echo("</td>");
		echo("</tr>\n");

		echo("<tr>");
		echo("<td style='min-width:20em;'><strong>");
		echo("OS Install Date");
		echo("</strong></td>");
		echo("<td style='min-width:20em;'>");
		echo($json->{'os_install_date'});
		echo("</td>");
		echo("</tr>\n");

		echo("<tr>");
		echo("<td style='min-width:20em;'><strong>");
		echo("IP Address");
		echo("</strong></td>");
		echo("<td style='min-width:20em;'>");
		if(is_array($json->{'ip_address'})){
			if(count($json->{'ip_address'}) > 1){
				echo("<ul style='margin:0;'>");
				foreach($json->{'ip_address'} as $arrayval){
					if(isset($arrayval) && strlen(trim($arrayval)) > 0){
						echo("<li type='disc'>");
						echo($arrayval);
						echo("</li>");
					}
				}
				echo("</ul>");
			} elseif(count($json->{'ip_address'}) > 0){
				echo($json->{'ip_address'}[0]);
			}
		} else {
			echo($json->{'ip_address'});
		}
		echo("</td>");
		echo("</tr>\n");

		echo("<tr>");
		echo("<td style='min-width:20em;'><strong>");
		echo("Subnet");
		echo("</strong></td>");
		echo("<td style='min-width:20em;'>");
		if(is_array($json->{'subnet'})){
			if(count($json->{'subnet'}) > 1){
				echo("<ul style='margin:0;'>");
				foreach($json->{'subnet'} as $arrayval){
					if(isset($arrayval) && strlen(trim($arrayval)) > 0){
						echo("<li type='disc'>");
						echo($arrayval);
						echo("</li>");
					}
				}
				echo("</ul>");
			} elseif(count($json->{'subnet'}) > 0){
				echo($json->{'subnet'}[0]);
			}
		} else {
			echo($json->{'subnet'});
		}
		echo("</td>");
		echo("</tr>\n");

		echo("<tr>");
		echo("<td style='min-width:20em;'><strong>");
		echo("DHCP Enabled");
		echo("</strong></td>");
		echo("<td style='min-width:20em;'>");
		echo($json->{'net_dhcp_enabled'});
		echo("</td>");
		echo("</tr>\n");

		echo("<tr>");
		echo("<td style='min-width:20em;'><strong>");
		echo("System First audited");
		echo("</strong></td>");
		echo("<td style='min-width:20em;'>");
		//date_default_timezone_set('UTC');
		echo(date("m/d/Y g:i:s a", $json->{'system_first_audited'}));
		echo("</td>");
		echo("</tr>\n");

		echo("<tr>");
		echo("<td style='min-width:20em;'><strong>");
		echo("System Last audited");
		echo("</strong></td>");
		echo("<td style='min-width:20em;'>");
		echo(date("m/d/Y g:i:s a", $json->{'system_last_audit_completed_at'}));
		//date_default_timezone_set('America/Los_Angeles');
		echo("</td>");
		echo("</tr>\n");

		echo("<tr>");
		echo("<td style='min-width:20em;'><strong>");
		echo("System Addressable Memory");
		echo("</strong></td>");
		echo("<td style='min-width:20em;'>");
		echo($json->{'system_addressable_memory'}." mb");
		echo("</td>");
		echo("</tr>\n");

		echo("<tr>");
		echo("<td style='min-width:20em;'><strong>");
		echo("Number of memory Slots");
		echo("</strong></td>");
		echo("<td style='min-width:20em;'>");
		echo($json->{'memory_banks'});
		echo("</td>");
		echo("</tr>\n");

		echo("<tr>");
		echo("<td style='min-width:20em;'><strong>");
		echo("Sticks of RAM in system");
		echo("</strong></td>");
		echo("<td style='min-width:20em;'>");
		echo($json->{'sticks_of_ram'});
		echo("</td>");
		echo("</tr>\n");

		echo("<tr>");
		echo("<td style='min-width:20em;'><strong>");
		echo("Total physical memory in system");
		echo("</strong></td>");
		echo("<td style='min-width:20em;'>");
		echo($json->{'total_physical_memory'}." mb");
		echo("</td>");
		echo("</tr>\n");

		echo("</table>");
	}
*/

?>