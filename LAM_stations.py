# -*- coding: utf-8 -*-
"""
Created on Thu Sep 14 10:31:38 2017

@author: Zan
"""

import requests   # Load LAM data and parse JSON result


# Helper functions

# List available measurement stations with some metadata
def list_stations():
    list_url = 'https://tie.digitraffic.fi/api/v1/metadata/tms-stations?lastUpdated=false&state=active'
    resp = requests.get(url=list_url)
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
            descr += str(feature[u'geometry'][u'coordinates'])
        except:
            descr += 'NA'

	try:
	        print descr
	except:
		print "Unicode error"



# List available cameras with some metadata
def list_cameras():
    list_url = 'https://tie.digitraffic.fi/api/v1/metadata/camera-stations?lastUpdated=false'
    resp = requests.get(url=list_url)
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


list_stations()
