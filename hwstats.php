<?php
// hwstats v0.1 by @aaviator42 / 2023-01-28
// License: AGPLv3
// https://github.com/aaviator42/hwstats

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Get the current CPU usage (in percent)
$cpu_usage = getServerLoad();
$cpu_usage = number_format($cpu_usage, 2);

// Get the current RAM usage (in percent)
$free = shell_exec('free -tm');
$free = (string)trim($free);
$free_arr = explode("\n", $free);
$mem = explode(" ", $free_arr[1]);
$mem = array_filter($mem);
$mem = array_merge($mem);
$total_ram = $mem[1];
$used_ram = $mem[2];
// +5 as buffer because under-calculates sometimes.
$memory_usage =(($used_ram / $total_ram) * 100) + 5;
$memory_usage = number_format($memory_usage, 2);


// Get the current CPU temperature (in Celsius)
// Collect 3 samples and take max value
// You might  need to change sensor names below. 
// You might find 'lm-sensors' useful 
$cpu_temp[0] = shell_exec("cat /sys/class/thermal/thermal_zone2/temp");
$cpu_temp[0] = (float) $cpu_temp[0] / 1000;

$cpu_temp[1] = shell_exec("cat /sys/class/thermal/thermal_zone2/temp");
$cpu_temp[1] = (float) $cpu_temp[1] / 1000;

$cpu_temp[2] = shell_exec("cat /sys/class/thermal/thermal_zone2/temp");
$cpu_temp[2] = (float) $cpu_temp[2] / 1000;

$cpu_temp_c = max($cpu_temp);


// Get network stats
// requires 'vnstat' 
$netstats = shell_exec("vnstat -tr 2 -i wlp3s0 --json");
$netstats = json_decode($netstats, true);
$net_rx_rate = $netstats["rx"]["ratestring"];
$net_tx_rate = $netstats["tx"]["ratestring"];

echo 
"--CPU--
Usage: 	$cpu_usage%
Temp:  	" . $cpu_temp_c ."°C

--RAM--
Usage:  $memory_usage%

--Network--
Rx:    	$net_rx_rate
Tx:    	$net_tx_rate
";








//------
// https://www.php.net/manual/en/function.sys-getloadavg.php#118673

function _getServerLoadLinuxData()
{
	if (is_readable("/proc/stat"))
	{
		$stats = @file_get_contents("/proc/stat");

		if ($stats !== false)
		{
			// Remove double spaces to make it easier to extract values with explode()
			$stats = preg_replace("/[[:blank:]]+/", " ", $stats);

			// Separate lines
			$stats = str_replace(array("\r\n", "\n\r", "\r"), "\n", $stats);
			$stats = explode("\n", $stats);

			// Separate values and find line for main CPU load
			foreach ($stats as $statLine)
			{
				$statLineData = explode(" ", trim($statLine));

				// Found!
				if
				(
					(count($statLineData) >= 5) &&
					($statLineData[0] == "cpu")
				)
				{
					return array(
						$statLineData[1],
						$statLineData[2],
						$statLineData[3],
						$statLineData[4],
					);
				}
			}
		}
	}

	return null;
}

// Returns server load in percent (just number, without percent sign)
function getServerLoad()
{
	$load = null;

	if (stristr(PHP_OS, "win"))
	{
		$cmd = "wmic cpu get loadpercentage /all";
		@exec($cmd, $output);

		if ($output)
		{
			foreach ($output as $line)
			{
				if ($line && preg_match("/^[0-9]+\$/", $line))
				{
					$load = $line;
					break;
				}
			}
		}
	}
	else
	{
		if (is_readable("/proc/stat"))
		{
			// Collect 2 samples - each with 1 second period
			// See: https://de.wikipedia.org/wiki/Load#Der_Load_Average_auf_Unix-Systemen
			$statData1 = _getServerLoadLinuxData();
			sleep(1);
			$statData2 = _getServerLoadLinuxData();

			if
			(
				(!is_null($statData1)) &&
				(!is_null($statData2))
			)
			{
				// Get difference
				$statData2[0] -= $statData1[0];
				$statData2[1] -= $statData1[1];
				$statData2[2] -= $statData1[2];
				$statData2[3] -= $statData1[3];

				// Sum up the 4 values for User, Nice, System and Idle and calculate
				// the percentage of idle time (which is part of the 4 values!)
				$cpuTime = $statData2[0] + $statData2[1] + $statData2[2] + $statData2[3];

				// Invert percentage to get CPU time, not idle time
				$load = 100 - ($statData2[3] * 100 / $cpuTime);
			}
		}
	}

	return $load;
}

