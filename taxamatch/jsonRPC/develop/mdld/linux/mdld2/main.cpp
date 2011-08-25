/*
 *   C++ sockets on Unix and Windows
 *   Copyright (C) 2002
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program; if not, write to the Free Software
 *   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */


#include "PracticalSocket.h"  // For Socket, ServerSocket, and SocketException
#include "pthread.h"          // For POSIX threads
#include <iostream>           // For cout, cerr
#include <cstdlib>            // For atoi()
#include <fstream>
#include <sstream>
#include <time.h>
#include <stdio.h>
#include <string.h>
#include <sys/types.h>

#include <sys/timeb.h>


#include "levenshtein.h"

#include <stdio.h>
#include <math.h>
#include <omp.h>
#include <iomanip>


#include <fcntl.h>
#include <errno.h>
#include <unistd.h>
#include <syslog.h>
#include <string.h>
#include <assert.h>
#include <signal.h>


  #include <pthread.h>
const int RCVBUFSIZE = 32;



struct levu{
	std::string word;
	std::string id;
};
static levu *lev;
static int countwords=0;
#define NUM_THREADS 8


void HandleTCPClient(TCPSocket *sock);     // TCP client handling function
void *ThreadMain(void *arg);               // Main program of a thread



// TODO: Change '[daemonname]' to the name of _your_ daemon
#define DAEMON_NAME "taxamatchmdld1"
#define PID_FILE "/var/run/taxamatchmdld1.pid"

/**************************************************************************
    Function: Print Usage

    Description:
        Output the command-line options for this daemon.

    Params:
        @argc - Standard argument count
        @argv - Standard argument array

    Returns:
        returns void always
**************************************************************************/
void PrintUsage(int argc, char *argv[]) {
    if (argc >=1) {
        printf("Usage: %s -h -nn", argv[0]);
        printf("  Options:n");
        printf("      -ntDon't fork off as a daemon.n");
        printf("      -htShow this help screen.n");
        printf("n");
    }
}

/**************************************************************************
    Function: signal_handler

    Description:
        This function handles select signals that the daemon may
        receive.  This gives the daemon a chance to properly shut
        down in emergency situations.  This function is installed
        as a signal handler in the 'main()' function.

    Params:
        @sig - The signal received

    Returns:
        returns void always
**************************************************************************/
void signal_handler(int sig) {

    switch(sig) {
        case SIGHUP:
            syslog(LOG_WARNING, "Received SIGHUP signal.");
            break;
        case SIGTERM:
            syslog(LOG_WARNING, "Received SIGTERM signal.");
            break;
        default:
            syslog(LOG_WARNING, "Unhandled signal (%d) %s", strsignal(sig));
            break;
    }
}

/**************************************************************************
    Function: main

    Description:
        The c standard 'main' entry point function.

    Params:
        @argc - count of command line arguments given on command line
        @argv - array of arguments given on command line

    Returns:
        returns integer which is passed back to the parent process
**************************************************************************/
int main(int argc, char *argv[]) {

#if defined(DEBUG)
    int daemonize = 0;
#else
    int daemonize = 1;
#endif
daemonize = 0;
    // Setup signal handling before we start
    signal(SIGHUP, signal_handler);
    signal(SIGTERM, signal_handler);
    signal(SIGINT, signal_handler);
    signal(SIGQUIT, signal_handler);

   /* int c;
    while( (c = getopt(argc, argv, "nh|help")) != -1) {
        switch(c){
            case 'h':
                PrintUsage(argc, argv);
                exit(0);
                break;
            case 'n':
                daemonize = 0;
                break;
            default:
                PrintUsage(argc, argv);
                exit(0);
                break;
        }
    }*/

    syslog(LOG_INFO, "%s daemon starting up", DAEMON_NAME);

    // Setup syslog logging - see SETLOGMASK(3)
#if defined(DEBUG)
    setlogmask(LOG_UPTO(LOG_DEBUG));
    openlog(DAEMON_NAME, LOG_CONS | LOG_NDELAY | LOG_PERROR | LOG_PID, LOG_USER);
#else
    setlogmask(LOG_UPTO(LOG_INFO));
    openlog(DAEMON_NAME, LOG_CONS, LOG_USER);
#endif

    /* Our process ID and Session ID */
    pid_t pid, sid;

    if (daemonize) {
        syslog(LOG_INFO, "starting the daemonizing process");

        /* Fork off the parent process */
        pid = fork();
        if (pid < 0) {
            exit(EXIT_FAILURE);
        }
        /* If we got a good PID, then
           we can exit the parent process. */
        if (pid > 0) {
            exit(EXIT_SUCCESS);
        }

        /* Change the file mode mask */
        umask(0);

        /* Create a new SID for the child process */
        sid = setsid();
        if (sid < 0) {
            /* Log the failure */
            exit(EXIT_FAILURE);
        }

        /* Change the current working directory */
        if ((chdir("/")) < 0) {
            /* Log the failure */
            exit(EXIT_FAILURE);
        }

        /* Close out the standard file descriptors */
       /* close(STDIN_FILENO);
        close(STDOUT_FILENO);
        close(STDERR_FILENO);*/
    }


syslog(LOG_INFO, "should work");
    if (argc < 2) {                 // Test for correct number of arguments
    cerr << "Usage: " << argv[0] << " <Server Port> " << endl;
    exit(1);
    }

  unsigned short echoServPort = atoi(argv[1]);    // First arg:  local port

  try {

	std::string line;
	int j=0;
	size_t pos;

    std::string filename(argv[2]);

	std::ifstream inFile(argv[2]);
	for(countwords=0;getline(inFile,line);countwords++);

	lev=new levu[countwords];

	std::ifstream inFile2(argv[2]);
	for(j=0;j<countwords && getline(inFile2,line);j++){
		pos=line.find("	");    // position of "live" in str

		lev[j].word=line.substr(0,pos);
		lev[j].id=line.substr(pos+1);
	}
cerr << "Sucessfully read in" << endl;


    TCPServerSocket servSock(echoServPort);   // Socket descriptor for server

    for (;;) {      // Run forever
      // Create separate memory for client argument
      TCPSocket *clntSock = servSock.accept();

      // Create client thread
      pthread_t threadID;              // Thread ID from pthread_create()
      if (pthread_create(&threadID, NULL, ThreadMain,
              (void *) clntSock) != 0) {
        cerr << "Unable to create thread" << endl;
        exit(1);
      }
    }
  } catch (SocketException &e) {
    cerr << e.what() << endl;
    exit(1);
  }
















    syslog(LOG_INFO, "%s daemon exiting", DAEMON_NAME);

    //****************************************************
    // TODO: Free any allocated resources before exiting
    //****************************************************

    exit(0);
}


// TCP client handling function
void HandleTCPClient(TCPSocket *sock) {
  cout << "Handling client ";
  try {
    cout << sock->getForeignAddress() << ":";
  } catch (SocketException &e) {
    cerr << "Unable to get foreign address" << endl;
  }
  try {
    cout << sock->getForeignPort();
  } catch (SocketException &e) {
    cerr << "Unable to get foreign port" << endl;
  }
  //cout << " with thread " << pthread_self() << endl;

	// Send received string and receive again until the end of transmission
	char echoBuffer[RCVBUFSIZE];
	int recvMsgSize;
	while ((recvMsgSize = sock->recv(echoBuffer, RCVBUFSIZE)) > 0) { // Zero means end of transmission

		if(echoBuffer[recvMsgSize-1]=='\n'){

			std::string search(echoBuffer,recvMsgSize-1);

std::ostringstream res;


	int i,nThreads = 0;
	omp_set_num_threads(NUM_THREADS);
	omp_set_dynamic(NUM_THREADS);//memory leak :(
	clock_t start, end;
	struct timeb startt, endt;
	start = clock();
	ftime(&startt);


	#pragma omp parallel default(shared) private(i)
	{

		#pragma omp master
		nThreads = omp_get_num_threads();

		#pragma omp for
		for (i=0; i<countwords; i++) {

				int a=lev[i].word.size();
				if(a%2==1)a++;
				size_t len=min(a/2,4);

				if(lev[i].word.find(search,0)!=-1 && search.size()>3){
					len=255;
				}

				std::size_t d=mdld_utf8(search, lev[i].word, 2, len);


				if( d < len){
					//cout << lev[i].word <<"			"<<lev[i].id<<"			"<<d<<"\n";
					std::ostringstream outStream,res2;

					outStream<<d<<"	"<< std::setprecision(4) << ((1.0 -  (double)d/ (double)(std::max(std::max((int)a,1),(int)search.size())))  *100);
					if(len==255){
						outStream<<"	1";
					}else{
						outStream<<"	0";
					}

					res<<lev[i].word<<"	"<<lev[i].id<<"	"<<outStream.str()<<"\n";

				}

      }
   }


	ftime(&endt);
	end = clock();

	//std::cout << "tt"<<res<<"\n";

	int difft = (int) (1000.0 * (endt.time - startt.time) + (endt.millitm - startt.millitm));
	double sec=(end-start)/CLOCKS_PER_SEC;

	std::cout <<"time: "<<difft<<"ms\n time2:"<<sec<<" ticks "<< std::endl;



    std::cout << "Expected "<<NUM_THREADS<<" OpenMP threads, used"<<nThreads;

			if(res.str().size()==0){
				res<<"No data found. --"<<search<<"-- ";
			}
			const char* s=res.str().c_str();
			int c=strlen(s);
            sock->send(s,c);
			break;


		}
		//
		// Echo message back to client

	}
	cout << "Close Thread ";
	// Destructor closes socket
}

void *ThreadMain(void *clntSock) {
  // Guarantees that thread resources are deallocated upon return
  pthread_detach(pthread_self());

  // Extract socket file descriptor from argument
  HandleTCPClient((TCPSocket *) clntSock);

  delete (TCPSocket *) clntSock;

  return NULL;
}
