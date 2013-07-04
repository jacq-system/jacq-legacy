#!/bin/bash

# database access information
DBUSER=script
DBPASS=
# directory for creating the backups
BACKUP_DIR=/programms/db_backup
# directory which the archive will be created in
ARCHIVE_DIR=/programms/db_backup

#
# Function Section
#

# helper function for printing messages with timestamp
function dEcho {
	LOG_TIMESTAMP=`/bin/date +"%Y-%m-%d %H:%M:%S"`
	echo "[${LOG_TIMESTAMP}] ${1}"
}

#
# Main Start
#
DATE_PREFIX=`/bin/date +%Y%m%d`

# create backup dir
dEcho "Creating backup directory"
BACKUP_DIR=${BACKUP_DIR}/${DATE_PREFIX}_dbBackup/
/bin/mkdir ${BACKUP_DIR}

# Fetch list of databases
dEcho "Fetching list of databases"
DATABASES=`/usr/bin/mysql -u ${DBUSER} -e "show databases" --skip-column-names --batch --raw --password=${DBPASS}`

# cycle through databases and create a own dump file
for DB in $DATABASES
do
	# create backup for this database
	BACKUP_FILE=${BACKUP_DIR}/${DB}.sql
	dEcho "Backuping ${DB}"
	/usr/bin/mysqldump -R -u ${DBUSER} --password=${DBPASS} ${DB} > ${BACKUP_FILE}
done

# create archive of database files
dEcho "Creating archive"
/bin/tar -cvjf ${ARCHIVE_DIR}/${DATE_PREFIX}_dbBackup.tar.bz2 ${BACKUP_DIR}

# cleanup
dEcho "Removing backup directory"
/bin/rm -r ${BACKUP_DIR}

# done
dEcho "Backup done"
