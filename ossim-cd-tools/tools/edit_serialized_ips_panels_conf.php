#!/usr/bin/php -e
<?php
/************************************************
argv 1: dir with serialized data (php)
argv 2: string to replace
argv 3: new string 
************************************************/


// Iterates recursively a unknown dimensional array
function replace_ip_recur( &$arr, $ip_old="x.x.x.x",$ip_new="x.x.x.x",&$n)
{
	foreach($arr as $k=>$v)
	{
		if(is_array($v))
			replace_ip_recur($arr[$k],$ip_old,$ip_new,$n);
		else
		{
			$arr[$k]=str_replace($ip_old,$ip_new,$arr[$k],$q);
			$n+=$q;
		}
	}
}

	if($argc!=4&&$argc!=3) 
		die("Error: Execute with ./$argv[0] /dir_containing_the_serialized_files/ ip_to_replace new_ip\nor with ./$argv[0] /dir_containing_the_serialized_files/ new_ip\nIt will read /home/ossim/dist/.LAST_*\n");
	
	$dir=$argv[1];
	if(is_dir($dir)){
	$is_directory = 1;
	$files=scandir($dir);
	} else {
	$files = array();
	$is_directory = 0;
	array_push($files, $dir);
	}	
	$t=0;
        $save_filename = "/home/ossim/dist/.LAST_IP";

	if($argc==4)
	{
		$ip_original=$argv[2];
		$ip=$argv[3];
	}else
	{
                if(preg_match('/(\d+\.\d+\.\d+\.\d+)/',$argv[2])){
                  $save_filename = "/home/ossim/dist/.LAST_IP";
                } else {
                  $save_filename = "/home/ossim/dist/.LAST_INTERFACE";
		}
		$ip_original=str_replace("\n","",file_get_contents($save_filename));
		$ip=$argv[2];
	}



	foreach( $files as $f )
	{
		if(strcmp($f,".")!=0 && strcmp($f,"..")!=0)
		{
			if($is_directory){
			$content=file_get_contents($dir."/".$f);
			} else {
			$content=file_get_contents($f);
			}
			$data=unserialize($content);

			if($data===false)
				die(_("Bad data found in config file").": '$f'");
			else
			{
				$n=0;
				if($argc==4)
					replace_ip_recur($data,$ip_original,$ip,$n);
				else
					replace_ip_recur($data,$ip_original,$ip,$n);
				
				$t+=$n;
				$save = serialize($data);

				if($is_directory){
				if (!$fd = fopen($dir."/".$f, 'w')) 
					die(_("Could not save config in file ".$dir."/".$f.", invalid perms?").": '$f'");
				} else {
				if (!$fd = fopen($f, 'w')) 
					die(_("Could not save config in file ".$dir."/".$f.", invalid perms?").": '$f'");
				}
				if (!fwrite($fd, $save)) 
					die(_("Could not write to file, disk full?").": '$f'");
				fclose($fd);
			}
		}
	}



	if (!$fd = fopen($save_filename, 'w')) 
		die(_("Could not save $save_filename, invalid perms?"));
	if (!fwrite($fd, $ip)) 
		die(_("Could not write to file $save_filename, disk full?"));
	fclose($fd);
	echo "$t strings replaced in $dir\n";

?>
