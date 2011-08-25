#ifndef LEVENSHTEIN_H
#define LEVENSHTEIN_H

size_t levenshtein(std::string& a, std::string& b);

int min3(int a, int b, int c);

int isEqual(const char *s, const char *t, int s_pos, int t_pos, int nr);

size_t mdld_utf8(std::string& s1, std::string& t1, int blocklimit, int limit);

void inc_cnt();
long get_cnt();
void set_cnt(long cnt1);

#endif