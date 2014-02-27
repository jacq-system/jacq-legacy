#!/bin/bash

# database access information
DBUSER=script
DBPASS=
# directory for creating the backups
BACKUP_PATH=/programms/db_backup
# directory which the archive will be created in
ARCHIVE_PATH=/programms/db_backup

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
BACKUP_DIR_NAME=${DATE_PREFIX}_dbBackup
BACKUP_DIR=${BACKUP_PATH}/${BACKUP_DIR_NAME}/
/bin/mkdir ${BACKUP_DIR}

# Fetch list of databases
dEcho "Fetching list of databases"
DATABASES=`/usr/bin/mysql -u ${DBUSER} -e "show databases" --skip-column-names --batch --raw --password=${DBPASS}`

# cycle through databases and create a own dump file
for DB in $DATABASES
do
	# skip MySQL related "databases"
	if [ $DB = "information_schema" -o $DB = "performance_schema" ]; then
		continue;
	fi

	# create backup for this database
	BACKUP_FILE=${BACKUP_DIR}/${DB}.sql
	dEcho "Backuping ${DB}"
	/usr/bin/mysqldump -R -u ${DBUSER} --password=${DBPASS} --databases ${DB} > ${BACKUP_FILE}
done

# create archive of database files
dEcho "Creating archive"
ARCHIVE_FILE_NAME=${DATE_PREFIX}_dbBackup.tar.bz2
/bin/tar -C ${BACKUP_PATH} -cvjf ${ARCHIVE_PATH}/${ARCHIVE_FILE_NAME} ${BACKUP_DIR_NAME}

# show list info for archive file
/bin/ls -al ${ARCHIVE_PATH}/${ARCHIVE_FILE_NAME}

# cleanup
dEcho "Removing temporary backup directory"
/bin/rm -r ${BACKUP_DIR}

# done
dEcho "Backup done"
