
#include <iostream>
#include <fstream>
#include <sstream>
#include<stack>

using namespace std;

// unordered_map seems to perform just	as well, cheaper op[] but more
// expensive ctor, overall balances out, might as well use plain map.
#include <map>

#include "levenshtein.h"
#include "bk-tree.h"

void insert_word(bk_tree& tree, std::string word, std::string id){
/*//using namespace std;
//cout << word <<","<<id;
	if(tree.word == ""){
		tree.word = word;
		tree.id = id;
		return;
	}

	//size_t d = levenshtein(tree.word, word);
	size_t d = mdld_utf8(tree.word, word, 2, 255);

	bk_tree* child = tree.children[d];
	if(!child){
		bk_tree* subtree = new bk_tree;
		tree.children[d] = subtree;
		subtree->word = word;
		subtree->id = id;
	}
	else
		insert_word(*child, word,id);*/

}

void save_treeTXT(bk_tree& tree, std::ofstream& ofs){
	/*ofs << tree.word<<" "<<tree.id<<" ";
	if(tree.children.size() > 0){
		ofs << " ( ";
		for(branches::const_iterator i = tree.children.begin(); i != tree.children.end(); i++){
			if( i-> second != 0){
				ofs << i -> first << " ";
				save_treeTXT( *(i->second), ofs);
				ofs << " ";
			}
		}
		ofs << ") ";
	}*/
}

 


std::string load_treeTXT(bk_tree tree[], std::string filename){
	
	ifstream inFile(filename);
	string line;
	int i=0,j=0;
	

	for(i=0;getline(inFile,line);i++){
		string item, word,id;
		size_t pos;

		pos=line.find("	");    // position of "live" in str
		word=line.substr(0,pos);
		id=line.substr(pos+1);
		
		tree[i].word=word;
		tree[i].id=id;
	
	}
	return false;



	/*string word;
	ifs >> word;

	tree.word = word;

	ifs >> word;
	tree.id = word;

	string token;
	ifs >> token;
	if(token == "("){
		size_t weight;
		string nexttoken;
		while(true){
			if(nexttoken == ")"){
				return "";
			}

			stringstream ss;
			if(nexttoken != ""){
				ss << nexttoken;
				ss >> weight;
			}else{
				ifs >> token;
				if(token ==")")
					return "";
				ss << token;
				ss >> weight;
			}
			bk_tree* subtree = new bk_tree;
			tree.children[weight] = subtree;
			nexttoken = load_treeTXT(*subtree, ifs);
		}
	}
	return token;*/

}


bool query_tree( bk_tree  tree[], std::string& word, size_t m, string & res,long size){
	
bk_tree *temp;

	using namespace std;

	long j=0;

	for(j=0;j<size;j++){
		temp=&tree[j];

		size_t d = mdld_utf8(temp->word, word, 2,30);
		//d=20;
		if( d <= m){
			cout << temp->word <<"			"<<temp->id<<"			"<<d<<"\n";
			ostringstream outStream;
			outStream << d;

			res=res+temp->word +"			"+temp->id+"			"+outStream.str()+"\n";
		}

	}
	return false;

	/*
    stack<bk_tree*> trees;
    trees.push(&tree);
	



	while(trees.size()>0){
        
		bk_tree* temp=trees.top();
        trees.pop();

		size_t d;
		
		

		//cout<<"t: "<<temp.word<<endl;

		
		for(size_t i = d - m; i <= d+m; i++){
			if( temp->children[i]){
				trees.push(temp->children[i]);
			}
		}


		for(branches::const_iterator i = temp->children.begin(); i != temp->children.end(); i++){
				trees.push((i->second));
		}

		j++;
    }

	cout <<"j: "<<j;
	return false;*/
}
