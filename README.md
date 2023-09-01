# hwstats
Monitor server hardware info over HTTP  
`v0.1`: `2023-01-28`

Open `hwstats.html` in a browser. It will fetch hardware infomarmation (CPU usage, CPU temperature, RAM usage, network Tx & Rx) from `hwstats.php` and display it. Values update every second.

You might have to change some sensor info in the `hwstats.php` script depending on your hardware configuration. 
Developed and tested on Debian GNU/Linux.

Requires the `vnstat` package for network info. Use `sudo apt install vnstat` to install.

