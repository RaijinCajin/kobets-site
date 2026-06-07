#!/bin/bash

FTP_HOST="156.67.75.40"
FTP_USER="u539626633"
FTP_DIR="domains/ko-bets.com/public_html"
LOCAL_FILE="/opt/kobets/website/index.html"
FTP_PASSWORD="69makemesomemoenybitchesQ!" # Replace with actual password or pull from secure source

lftp -u "$FTP_USER,$FTP_PASSWORD" ftp://"$FTP_HOST" -e "set ssl:verify-certificate false; cd "$FTP_DIR"; put "$LOCAL_FILE"; bye"