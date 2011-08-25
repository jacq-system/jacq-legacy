#ifndef BK_TREE_H
#define BKTREE_H

#include <map>

#define SIZEG 124900
#define SIZER 10000

struct bk_tree{
	std::string word;
	std::string id;
	//For caching levenshtein distances
	//std::map<std::string, size_t> lev_cache;
	//std::map<size_t, bk_tree*> children;
};

typedef std::map<size_t, bk_tree*> branches;

void insert_word(bk_tree tree[], std::string word, std::string id);
void save_treeTXT(bk_tree& tree, std::ofstream& ofs);
std::string load_treeTXT(bk_tree tree[], std::ifstream& ifs);
bool query_tree( bk_tree  tree[], std::string& word, size_t m,std::string & res,long size);

#endif