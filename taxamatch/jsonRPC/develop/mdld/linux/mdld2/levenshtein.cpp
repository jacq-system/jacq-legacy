#include <string>
#include <vector>
#include <iostream>
#include <algorithm>
#include "levenshtein.h"

static long  cnt=0;

size_t levenshtein(std::string& a, std::string& b){
	using namespace std;
	size_t asize=a.size(), bsize=b.size();

	//Just need two rows at a time
	vector<size_t> prevrow(bsize+1), thisrow(bsize+1);

	for(size_t i=0;i <= bsize;i++)
		prevrow[i]=i;

	for(size_t i=1;i <= asize;i ++){
		thisrow[0]=i;
		for(size_t j=1;j <= bsize;j++){
			thisrow[j]=min(prevrow[j-1] + size_t(a[i-1] != b[j-1]),
											 1 + min(prevrow[j],thisrow[j-1]) );
		}
		swap(thisrow,prevrow);
	}

	return prevrow[bsize];
}


void inc_cnt(){
	cnt++;
}

void set_cnt(long cnt1){
	cnt=cnt1;
}
long get_cnt(){
	return cnt;
}
int min3(int a, int b, int c){
	if(a<b){
		return (a<c)?a:c;
	}else {
		return (b<c)?b:c;
	}
}

int isEqual(std::string& s, std::string& t, int s_pos, int t_pos, int nr){
	int i, j;

	if(nr<=0) return 0; // error, bad argument

	for(i=nr;i>=1;i--){
		for(j=0;j<4;j++) {
			if(s[s_pos++] != t[t_pos++]) return 0; // not equal
			if((!(s[s_pos] & 0x80) || (s[s_pos] & 0xC0) == 0xC0) && (!(t[t_pos] & 0x80) || (t[t_pos] & 0xC0) == 0xC0)) break;// next char
		}
	}
	return 1;
}

size_t mdld_utf8(std::string& s, std::string& t, int blocklimit, int limit){
	using namespace std;

	int len_s,len_t;
	int *pos_s, *pos_t;
	int  off_t, off_s;
	int *d;


 	long n, m;
	int  i,j,k,l1, l2, cost,  block_length, block_length_init, blk2, best=0;
	size_t ret=0;
/*

Todo:
const_cast<char*> => blast es das auf auf unicode??

×carduocirsium
*/	
	/*char* s = const_cast<char*>(s1.c_str());
	char* t = const_cast<char*>(t1.c_str());
*/

	len_s=s.size();
	len_t=t.size();
	off_s = (len_s + 1) * (len_t + 1);
    off_t = off_s + len_s + 1;
	

	d = new int[(len_s + 2) * (len_t + 2)];
	if(d==NULL) {
		cout << "Failed to allocate memory for mdld function";
		return (size_t)0;
	} 
	
	pos_s=&d[off_s];
	pos_t=&d[off_t];
	//***************************************************************************
	//** mdld step one
	//***************************************************************************
	
	cnt++;

	k=0;
	for(i=0;i<len_s;i++){
		if(!(s[i] & 0x80) ||(s[i] & 0xC0) == 0xC0){
			pos_s[k]=i;
			k++;
		}
	}
	n=k;
	

	k=0;
	for(i=0;i<len_t;i++){
		if(!(t[i] & 0x80) ||(t[i] & 0xC0) == 0xC0){
			pos_t[k]=i;
			k++;
		}
	}
	m=k;
	
	if(n != 0 && m != 0){
		//***********************************************************************
		//** mdld step two
		//***********************************************************************

		l1=n;
		l2=m;
		n++;
		m++;

		// initialize first row to 0..n 
		for(k=0;k<n;k++){
			d[k]=k;
		}

		// initialize first column to 0..m 
		k=n;
		for(i=1;i<m;i++){
			d[k]=i;
			k+=n;
		}

		block_length_init=min3((l1/2),(l2/2), blocklimit);
		for(i=1;i<n;i++){
			k=i;
			best=limit;
			for(j=1;j<m;j++){
				// k =(j * n + i) 
				k += n;
				
				//Step 5
				if(isEqual(s, t, pos_s[i - 1], pos_t[j - 1], 1)){
					cost=0;
				}else{
					cost=1;
				}
				
				//Step 6
				//d[j*n+i]=minimum(d[(j-1)*n+i]+1,d[j*n+i-1]+1,d[(j-1)*n+i-1]+cost);
				block_length=block_length_init;
				while(block_length >= 1){
					blk2=block_length * 2;
					if(    i >= blk2
						&& j >= blk2
						&& isEqual(s, t, pos_s[i - 1 -(blk2 - 1)], pos_t[j - 1 -(block_length - 1)], block_length)
						&& isEqual(s, t, pos_s[i - 1 -(block_length - 1)], pos_t[j - 1 -(blk2 - 1)], block_length) ){

						//d[j*n+i]=min3(d[(j-1)*n+i]+1, d[j*n+i-1]+1, d[(j-blk2)*n+i-blk2]+cost+(block_length-1));
						d[k]=min3(d[k - n] + 1, d[k - 1] + 1, d[k -(n + 1) * blk2] + cost +(block_length - 1));
						block_length=0;
					}else if(block_length == 1){
						// no transposition
						//d[j*n+i]=min3(d[(j-1)*n+i]+1,d[j*n+i-1]+1,d[(j-1)*n+i-1]+cost);
						d[k]=min3(d[k - n] + 1, d[k - 1] + 1, d[k - n - 1] + cost);
					}
					block_length--;
				}
				if(d[k]<best) best=d[k];
			}
			if(best >= limit){
				if (d != NULL) {
					delete [] d;
				}

				return (size_t)limit;
			}
		}
		ret=d[n * m - 1];
		if (d != NULL) {
			delete [] d;
		}
		return ret;
	}
	
	if (d != NULL) {
        delete [] d;
    }


	if(n == 0){
		return (size_t)m;
	}else{
		return (size_t)n;
	}
}