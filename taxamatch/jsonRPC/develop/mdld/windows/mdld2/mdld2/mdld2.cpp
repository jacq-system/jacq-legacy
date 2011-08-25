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

#include <sys/types.h>

#include <sys/timeb.h>


#include "levenshtein.h"

#include <stdio.h>
#include <math.h>
#include <omp.h>
#include <iomanip>
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

int main(int argc, char *argv[]) {
  if (argc != 2) {                 // Test for correct number of arguments  
    cerr << "Usage: " << argv[0] << " <Server Port> " << endl;
    exit(1);
  }

  unsigned short echoServPort = atoi(argv[1]);    // First arg:  local port  

  try {
	
	std::string line;
	int k=0,j=0;
	size_t pos;
	
	
	std::ifstream inFile("genus_names.txt");
	for(countwords=0;getline(inFile,line);countwords++);
	
	lev=new levu[countwords];
	
	std::ifstream inFile2("genus_names.txt");
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
  // NOT REACHED

  return 0;
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
			std::string res;
		
			
			
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
				
				if(lev[i].word.find(search,0)!=-1){
					len=255;
				}
				
				std::size_t d=mdld_utf8(search, lev[i].word, 2, len);
				

				if( d < len){
					//cout << lev[i].word <<"			"<<lev[i].id<<"			"<<d<<"\n";
					std::ostringstream outStream;
					
					outStream<<d<<"	"<< std::setprecision(4) << ((1.0 -  (double)d/ (double)(std::max(std::max((int)a,1),(int)search.size())))  *100);
					if(len==255){
						outStream<<"	1";
					}else{
						outStream<<"	0";
					}

					res=res+lev[i].word +"	"+lev[i].id+"	"+outStream.str()+"\n";
					
				}
		  
      }
   }


	ftime(&endt);
	end = clock();
	
	//std::cout << "tt"<<res<<"\n";

	int difft = (int) (1000.0 * (endt.time - startt.time) + (endt.millitm - startt.millitm)); 
	double sec=(end-start)/CLOCKS_PER_SEC;

	std::cout <<"time: "<<difft<<"ms\n time2:"<<sec<<" ticks "<< std::endl;



   if  (nThreads == NUM_THREADS) {
      printf_s("%d OpenMP threads were used.\n", NUM_THREADS);
   }
   else {
      printf_s("Expected %d OpenMP threads, but %d were used.\n",
               NUM_THREADS, nThreads);
   }


			char* s = const_cast<char*>(res.c_str());
			int c=strlen(s);

			if(c==0){
				res="No data found. --"+search+"-- ";
				char* s = const_cast<char*>(res.c_str());
				int c=strlen(s);
				sock->send(s,c);
			}else{
				sock->send(s,c);
			}
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