# LAM
LAM (finnish: Liikenteen Automaattinen Mittaus, english: TMS or Traffic Measurement System) is a system that provides traffic measurement data from about 500 measurement points across Finland. The data is provided free by [Digitraffic](https://www.digitraffic.fi/en/), by the [Finnish Transport Infrastructure Agency Väylä](https://vayla.fi/web/en/open-data).

This repository was born to provide a real-time estimate of the traffic situation on my commutation route. I wanted to have a way to estimate which route to work/home would be the fastest and when should I leave. The code is very quick'n'dirty but has served me quite well.

## About
The code used in this repository can be divided in three parts:
 1. Python code to access the Digitraffic REST/JSON API to
     a. Fetch the measurement station metadata and to
	 b. Fetch and store the real-time measurement data of the selected stations
 2. PHP code to provide easy access to the stored measurement data
 3. HTML/Javascript code to provide a simple user interface
 
The output when the system is used looks like following:

![Example graph](https://github.com/zanppa/LAM/raw/master/example_graph.png)

A similar graph is shown for all the measurement stations selected. In the graph the X axis is the time of day (hours). The line curves show the traffic speed today and the same weekday for the previous 4 weeks, using the left Y axis. The filled curves show amount of cars per hour for today and previous week using the right Y axis. 

## Main files
### LAM_fetch.py
**LAM_fetch.py** is the main program that fetches the data from Digitraffic. When the program is run, it queries latest data from all desired stations and appends the values to a corresponding file in **data/** directory.

There are few parameters that need to be set:
 - `update_interval` is used to tell the program how often it is supposed to be called.
 - `store_length` tells how many weeks worth of data is stored. This useds the *update_interval* to calculate how many lines to store in the data files (parameter *store_rows*).
 - `tms_list` is an array that tells the station IDs of each station that is queried and stored.
 - `value_list` is an array that tells which variables to store for each station. By default we store:
     * `OHITUKSET_5MIN_LIUKUVA_SUUNTA1` and `OHITUKSET_5MIN_LIUKUVA_SUUNTA2` which are number of passed cars/hour for both measurement directions, 5 minute average value
	 * `KESKINOPEUS_5MIN_LIUKUVA_SUUNTA1` and `KESKINOPEUS_5MIN_LIUKUVA_SUUNTA2` which are average passing speed in km/h for both directions, 5 minute average value
 - `base_url` is the base url where the REST/JSON api is located.
 
The data is stored in **data/** directory so that each station is in its own **ID.txt** file. The file is semicolon separated, format is similar to following lines:

```
Timestamp;Year;Month;Day;Hour;Minute;OHITUKSET_5MIN_LIUKUVA_SUUNTA1;OHITUKSET_5MIN_LIUKUVA_SUUNTA2;KESKINOPEUS_5MIN_LIUKUVA_SUUNTA1;KESKINOPEUS_5MIN_LIUKUVA_SUUNTA2;
1577653745;2019;12;29;21;09;60.0;48.0;76.0;60.0;
1577654051;2019;12;29;21;14;120.0;108.0;69.0;71.0;
```

First 6 columns are fixed and the rest are the variables that were specified earlier.

Note that the data rotation is very naive in that it loads the whole file as lines, then drops first ones if necessary, appends new ones an re-writes the files. It may corrupt the files if the script crashes and also it is very slow on large datasets. I'd rather used SQL or other database but never got to implement that...

### run.sh
Shell script to run the **LAM_fetch.py** every 5 minutes. It uses *watch* for the periodic execution, so the script is intended to be run in a *screen* shell.

### query.php
**query.php** is used by the main user interface web-page to access the stored data. It takes several parameters and returns JSON data that is ready to be plotted on a graph. The parameters (in HTTP GET) are:
 - `id`: the station ID. These are not necessarily the same as the LAM station IDs and are defined in the query.php itself in *$sensors*.
 - `dir`: direction, either 1 or 2. Tells which measurement direction to use. Default=1.
 - `Y`: year to request data from. Default=current year.
 - `m`: month to request data from Default=current month.
 - `d`: day of month to request data from. Default=current day.
 - `filter`: gain of a very simple low-pass filter to apply to data. Between 0 and 1 where 1.0=no filtering. I used 0.7 usually. Default=1.0 (no filter).
 - `n`: how many previous days/weekdays to return (contains current date). Default=5.
 - `period`: return previous days or previous weekdays. 1=previous days, 2=same weekday on previous weeks. Default=2.
 
The data is returned in JSON format that can (almost?) directly be used in for example [Chart.js](https://www.chartjs.org/).

It is a good idea to change the `Access-Control-Allow-Origin` to match where your site is located to prevent cross-site ajax calls, by changing the following line:
```
// It is also good to set the access security - just replace * with the domain you want to be able to reach it.
header('Access-Control-Allow-Origin: *');
```


### index.html
The main user interface. Draws the charts for selected stations and direction. The parameters to pass with HTTP GET are:
 - `filter`: Filtering gain, default=0.3.
 - `dir`: Direction, default=1.
 - `st`: Station ID to draw. Multiple can be provided to draw many graphs.
 
For example, the query string may be
```
./index.html?dir=2&filter=0.8&st=23107&st=23145&st=23197
```
to draw 3 stations in direction 2 and filtering with gain of 0.8.

To change the URL where the **query.php** resides, you need to modify the ajax command:
```
$.ajax({
	type: 'GET',
	url: './query.php?dir=' + st_dir + '&filter=' + filter + '&id=' + stations[index],
```


## Notes
This code has been written very quickly and is intended to be run on local network only. No measures has been taken to sanitize inputs or anything, and there might be exploitable bugs. The code shohuld be used for reference only. Any bugs/security issues can be reported in the github issue tracker.

## Disclaimer
This program is for INFORMATION PURPOSES ONLY. You use this at your own risk, author takes no responsibility for any loss or damage caused by using this program or any information presented.
