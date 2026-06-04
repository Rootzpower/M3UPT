#!/bin/bash

cd /home/runner/work/M3UPT/M3UPT/iptv-org-epg && npm install

# M3UPT EPG
npm run grab --- --channels=../EPG/m3upt.channels.xml --output=../EPG/epg-m3upt.xml --days=7 --maxConnections=20

# RTP EPG
npm run grab --- --sites=rtp.pt --output=../EPG/epg-rtp-pt.xml --days=7 --maxConnections=20

# SIC EPG
npm run grab --- --sites=opto.sic.pt --output=../EPG/epg-sic-pt.xml --days=7 --maxConnections=20

# TVI EPG
npm run grab --- --sites=tvi.iol.pt --output=../EPG/epg-tvi-pt.xml --days=7 --maxConnections=20

# Vodafone PT EPG
npm run grab --- --sites=vodafone.pt --output=../EPG/epg-vodafone-pt.xml --days=7 --maxConnections=20

# Nos EPG
npm run grab --- --sites=nostv.pt --output=../EPG/epg-nos-pt.xml --days=7 --maxConnections=20

# Vivo Play EPG
npm run grab --- --sites=vivoplay.com.br --output=../EPG/epg-vivoplay-br.xml --days=7 --maxConnections=20

# orangetv.es
npm run grab --- --sites=orangetv.orange.es --output=../EPG/epg-orangetv-orange-es.xml --days=7 --maxConnections=20

# watch.whaletvplus.com
npm run grab --- --sites=watch.whaletvplus.com --output=../EPG/epg-watch-whaletvplus-com.xml --days=7 --maxConnections=20

# Compress EPG xml files only for *.gz format
cd ../EPG
gzip -k -f -9 epg*.xml

# Remove EPG xml files
rm -f epg*.xml epg*.xml.xz

exit 0
