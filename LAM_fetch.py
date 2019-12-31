# -*- coding: utf-8 -*-
"""
Created on Thu Sep 14 10:31:38 2017

@author: Zan
"""

import requests   # Load LAM data and parse JSON result
from calendar import timegm   # Handle timestamps
from dateutil import parser   # Parse timestamp string to date
from collections import deque  # Simple way to roll the database file to certain length

tout = 5.0 # timeout for http get

# Helper functions

# List available measurement stations with some metadata
def list_stations():
    list_url = 'https://tie.digitraffic.fi/api/v1/metadata/tms-stations?lastUpdated=false&state=active'
    resp = requests.get(url=list_url, timeout=tout)
    data = resp.json()
    
    print 'ID;Name;Direction1;Direction2;Coordinates'
    for feature in data[u'features']:
	descr = str(feature[u'id']) + ';'
        try:
            descr += feature[u'properties'][u'names'][u'fi'] + ';'
        except:
            descr += 'NA;'
        try:
            descr += feature[u'properties'][u'direction1Municipality'] + ';'
        except:
            descr += 'NA;'
        try:
            descr += feature[u'properties'][u'direction2Municipality'] + ';'
        except:
            descr += 'NA;'
        try:
            descr += str(feature[u'properties'][u'coordinatesETRS89'])
        except:
            descr += 'NA'

        print descr



# List available cameras with some metadata
def list_cameras():
    list_url = 'https://tie.digitraffic.fi/api/v1/metadata/camera-stations?lastUpdated=false'
    resp = requests.get(url=list_url, timeout=tout)
    data = resp.json()
    #print data
    print 'ID;Name;Direction;Coordinates;URL'
    for feature in data[u'features']:
        try:
            for preset in feature[u'properties'][u'presets']:
                if preset[u'inCollection']: # Only list active ones
                    print str(feature[u'id']) + ';' + feature[u'properties'][u'names'][u'fi'] + ';' + preset[u'presentationName'] + ';' + \
                        str(feature[u'properties'][u'coordinatesETRS89']) + ';' + preset[u'imageUrl']
        except:
            # If some fields are missing, forget it
            pass


# configuration
update_interval = 5     # Minutes
store_length = 500        # Weeks
store_rows = int(store_length * 7 * 24 * 60 / update_interval)


tms_list = ['23153', '23137', '23107', '23165', '23145', '23159', '23123', '23138', '23005', '23152', '23151', '23164', '23197', '23147', '23004', '23146']     # List of sensor locations
value_list = ['OHITUKSET_5MIN_LIUKUVA_SUUNTA1', 'OHITUKSET_5MIN_LIUKUVA_SUUNTA2', \
             'KESKINOPEUS_5MIN_LIUKUVA_SUUNTA1', 'KESKINOPEUS_5MIN_LIUKUVA_SUUNTA2']

base_url = 'https://tie.digitraffic.fi/api/v1/data/tms-data/' # Base URL for sensor data



for tms in tms_list:
    url = base_url + tms

    # Request sensor data 
    resp = requests.get(url=url, timeout=tout)
    data = resp.json()

    # Read the update timestamp for this sensor
    updated = data[u'dataUpdatedTime']
    updated_date = parser.parse(updated)
    datetuple = updated_date.timetuple()
    update_unixtime = timegm(datetuple)     # Convert back with datetime.utcfromtimestamp
    update_datestring = updated_date.strftime('%Y;%m;%d;%H;%M')

    data_string = str(update_unixtime) + ';' +update_datestring + ';'


    # Read requested sensor values
    for station in data[u'tmsStations']:
        # First store all sensors in a dictionary
        sensor_data = {}
        for sensor in station[u'sensorValues']:
            sensor_data[sensor[u'name']] = sensor[u'sensorValue']
        

        # Create the data row for this station containing only requested values

        for key in value_list:
            if not sensor_data.has_key(key):
                data_string += '0.0;'
            else:
                data_string += str(sensor_data[key]) + ';'

        data_string += '\r\n'
        
        # Dataset should contain only 1 station, so use the first only
        break
    
    #print data_string

    # Open the file for this sensor
    sensor_filename = 'data/' + tms + '.txt'    
    tms_data = None

    # Read existing rows into deque to roll oldest out
    with open(sensor_filename, 'r') as tmsfile:
        tms_data = deque(tmsfile, store_rows + 1)       # + 1 for headers
        tms_data.append(data_string)
        tms_data.popleft()                              # Pop headers out
    
    # Then write data back
    header_line = 'Timestamp;Year;Month;Day;Hour;Minute;'
    for key in value_list:
        header_line += str(key) + ';'
    header_line += '\r\n'
    
    with open(sensor_filename, 'w') as tmsfile:
        tmsfile.write(header_line)
        tmsfile.writelines(tms_data)

