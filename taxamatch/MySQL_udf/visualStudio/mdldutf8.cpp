/* MDLD and DLD algorithms for MySQL
 * by Johannes Schachner (joschach at ap4net dot at) 21Sep2009
 *
 * DLD algorithm based on the work of
 * Sean Collins (sean at lolyco.com) 27Aug2008
 * Adapted from Josh Drew's levenshtein code using pseudo
 * code from
 * http://en.wikipedia.org/wiki/Damerau-Levenshtein_distance
 *  - an optimal string alignment algorithm, as opposed to
 *  'edit distance' as per the notes in the wp article
 *
 * Levenshtein Distance Algorithm implementation as MySQL UDF
 * by Joshua Drew for SpinWeb Net Designs, Inc. on 2003-12-28.
 *
 * MDLD algorithm based on the DLD algorithm and on the work of
 * Tony Rees, November 2008 (Tony.Rees@csiro.au)
 *
 * Both algorithms were enhanced by the ability to use unicode
 * strings encoded in UTF-8.
*/

#ifdef STANDARD
#include <string.h>
#ifdef __WIN__
typedef unsigned __int64 ulonglong;
typedef __int64 longlong;
#else
typedef unsigned long long ulonglong;
typedef long long longlong;
#endif /*__WIN__*/
#else
#include <my_global.h>
#include <my_sys.h>
#endif
#include <mysql.h>
#include <m_ctype.h>
#include <m_string.h>

// WENN WIN...
#define DLLEXP __declspec(dllexport) 


#ifdef HAVE_DLOPEN

/******************************************************************************
** function declarations
******************************************************************************/

extern "C" {
    DLLEXP my_bool     dld_init(UDF_INIT *initid, UDF_ARGS *args, char *message);
    DLLEXP void        dld_deinit(UDF_INIT *initid);
    DLLEXP longlong    dld(UDF_INIT *initid, UDF_ARGS *args, char *is_null, char *error);
    DLLEXP my_bool     mdld_init(UDF_INIT *initid, UDF_ARGS *args, char *message);
    DLLEXP void        mdld_deinit(UDF_INIT *initid);
    DLLEXP longlong    mdld(UDF_INIT *initid, UDF_ARGS *args, char *is_null, char *error);
}

/******************************************************************************
** function definitions
******************************************************************************/

int min3(int a, int b, int c)
{
    if (a < b) {
        return (a < c) ? a : c;
    } else {
        return (b < c) ? b : c;
    }
}

int isEqual(const char *s, const char *t, int s_pos, int t_pos, int nr)
{
    int i, j;

    if (nr <= 0) return 0;  // error, bad argument

    for (i = nr; i >= 1; i--) {
        for (j = 0; j < 4; j++) {
            if (s[s_pos++] != t[t_pos++]) return 0;  // not equal
            if ((!(s[s_pos] & 0x80) || (s[s_pos] & 0xC0) == 0xC0) && (!(t[t_pos] & 0x80) || (t[t_pos] & 0xC0) == 0xC0)) break; // next char
        }
    }
    return 1;
}

longlong dld_fast_utf8(const char *s, int len_s, const char *t, int len_t, int *d, int *pos_s, int *pos_t)
{
    longlong n, m;
    int b,c,f,g,h,i,j,k,min, l1, l2, cost, tr;

    /***************************************************************************
    ** dld step one
    ***************************************************************************/

    /* if s or t is a NULL pointer, then the argument to which it points
    ** is a MySQL NULL value; when testing a statement like:
    **  SELECT DLD(NULL, 'test');
    ** the first argument has length zero, but if some row in a table contains
    ** a NULL value which is sent to dld() (or some other UDF), that NULL
    ** argument has the maximum length of the attribute (as defined in the
    ** CREATE TABLE statement); therefore, the argument length is not a
    ** reliable indicator of the argument's existence... checking for a
    ** NULL pointer is the proper course of action
    */

    if (s == NULL) {
        n = 0;
    } else {
        k = 0;
        for (i = 0; i < len_s; i++) {
            if (!(s[i] & 0x80) || (s[i] & 0xC0) == 0xC0) {
                pos_s[k] = i;
                k++;
            }
        }
        n = k;
    }

    if (t == NULL) {
        m = 0;
    } else {
        k = 0;
        for (i = 0; i < len_t; i++) {
            if (!(t[i] & 0x80) || (t[i] & 0xC0) == 0xC0) {
                pos_t[k] = i;
                k++;
            }
        }
        m = k;
    }

    //fprintf(stderr, "len_s=%d / len_t=%d / n=%lld / m=%lld\n", len_s, len_t, n, m);
    if (n != 0 && m != 0) {
        /************************************************************************
        ** dld step two
        ************************************************************************/

        l1 = n;
        l2 = m;
        n++;
        m++;

        /* initialize first row to 0..n */
        for (k = 0; k < n; k++) {
            d[k] = k;
        }

        /* initialize first column to 0..m */
        k = n;
        for (i = 1; i < m; i++) {
            d[k] = i;
            k += n;
        }

        /************************************************************************
        ** dld step three
        ************************************************************************/

        /* throughout these loops, g will be equal to i minus one */
        g = 0;
        for (i = 1; i < n; i++) {
            /*********************************************************************
            ** dld step four
            *********************************************************************/

            k = i;

            /* throughout the for j loop, f will equal j minus one */
            f = 0;
            for (j = 1; j < m; j++) {
                /******************************************************************
                ** dld step five, six, seven
                ******************************************************************/

                /* Seidenari's original was more like:
                ** d[j*n+i] = min(d[(j-1)*n+i]+1,
                **                min(d[j*n+i-1]+1,
                **                    d[(j-1)*n+i-1]+((s[i-1]==t[j-1]) ? 0 : 1)));
                **
                ** thanks to algebra, (most or) all of the redundant calculations
                ** have been removed; hopefully the variables aren't too confusing
                ** :)
                **
                ** NOTE: after I did this, I realized I could have just had the
                ** compiler optimize the calculations for me... dang
                */

                /* h = (j * n + i - n)  = ((j - 1) * n + i) */
                h = k;
                /* k = (j * n + i) */
                k += n;

                /* find the minimum among (the cell immediately above plus one),
                ** (the cell immediately to the left plus one), and (the cell
                ** diagonally above and to the left plus the cost [cost equals
                ** zero if argument one's character at index g equals argument
                ** two's character at index f; otherwise, cost is one])
                ** d[k] = min(d[h] + 1,
                **           min(d[k-1] + 1,
                **               d[h-1] + ((s[g] == t[f]) ? 0 : 1)));
                */

                /* computing the minimum inline is much quicker than making
                ** two function calls (or even one, as Seidenari used)
                **
                ** NOTE: after I did this, I realized I could have just had the
                ** compiler inline the functions
                */

                min = d[h] + 1;
                b = d[k-1] + 1;
                //if (s[g] == t[f]) {
                if (isEqual(s, t, pos_s[g], pos_t[f], 1)) {
                    cost = 0;
                } else {
                    cost = 1;
                    /* transposition */
                    if (i < l1 && j < l2) {
                        //if (s[i] == t[f] && s[g] == t[j]) {
                        if (isEqual(s, t, pos_s[i], pos_t[f], 1) && isEqual(s, t, pos_s[g], pos_t[j], 1)) {
                            tr = d[(h) - 1];
                            if (tr < min) min = tr;
                        }
                    }
                }
                c = d[h - 1] + cost;

                if (b < min) min = b;
                if (c < min) {
                    d[k] = c;
                } else {
                    d[k] = min;
                }
                /* f will be equal to j minus one on the
                ** next iteration of this loop */
                f = j;
            }

            /* g will equal i minus one for the next iteration */
            g = i;
        }

        /* Seidenari's original was:
        ** return (longlong) d[n*m-1]; */

        return (longlong) d[k];
    } else if (n == 0) {
        return m;
    } else {
        return n;
    }
}


longlong mdld_utf8(const char *s, int len_s, const char *t, int len_t, int blocklimit, int limit, int *d, int *pos_s, int *pos_t)
{
    longlong n, m;
    int b,c,f,g,h,i,j,k,min, l1, l2, cost, tr, block_length, block_length_init, blk2, best = 0;;

    /***************************************************************************
    ** mdld step one
    ***************************************************************************/

    /* if s or t is a NULL pointer, then the argument to which it points
    ** is a MySQL NULL value; when testing a statement like:
    **  SELECT MDLD(NULL, 'test', 1, 2);
    ** the first argument has length zero, but if some row in a table contains
    ** a NULL value which is sent to mdld() (or some other UDF), that NULL
    ** argument has the maximum length of the attribute (as defined in the
    ** CREATE TABLE statement); therefore, the argument length is not a
    ** reliable indicator of the argument's existence... checking for a
    ** NULL pointer is the proper course of action
    */

    if (s == NULL) {
        n = 0;
    } else {
        k = 0;
        for (i = 0; i < len_s; i++) {
            if (!(s[i] & 0x80) || (s[i] & 0xC0) == 0xC0) {
                pos_s[k] = i;
                k++;
            }
        }
        n = k;
    }

    if (t == NULL) {
        m = 0;
    } else {
        k = 0;
        for (i = 0; i < len_t; i++) {
            if (!(t[i] & 0x80) || (t[i] & 0xC0) == 0xC0) {
                pos_t[k] = i;
                k++;
            }
        }
        m = k;
    }

    if (n != 0 && m != 0) {
        /************************************************************************
        ** mdld step two
        ************************************************************************/

        l1 = n;
        l2 = m;
        n++;
        m++;

        /* initialize first row to 0..n */
        for (k = 0; k < n; k++) {
            d[k] = k;
        }

        /* initialize first column to 0..m */
        k = n;
        for (i = 1; i < m; i++) {
            d[k] = i;
            k += n;
        }

        block_length_init = min3((l1 / 2), (l2 / 2), blocklimit);
        for(i = 1; i < n; i++) {
            k = i;
            best = limit;
            for(j = 1; j < m; j++) {
                /* k = (j * n + i) */
                k += n;
                //Step 5
                if (isEqual(s, t, pos_s[i - 1], pos_t[j - 1], 1)) {
                    cost = 0;
                } else {
                    cost = 1;
                }
                //Step 6
                //d[j*n+i]=minimum(d[(j-1)*n+i]+1,d[j*n+i-1]+1,d[(j-1)*n+i-1]+cost);
                block_length = block_length_init;
                while (block_length >= 1) {
                    blk2 = block_length * 2;
                    if (   i >= blk2
                        && j >= blk2
                        && isEqual(s, t, pos_s[i - 1 - (blk2 - 1)], pos_t[j - 1 - (block_length - 1)], block_length)
                        && isEqual(s, t, pos_s[i - 1 - (block_length - 1)], pos_t[j - 1 - (blk2 - 1)], block_length) ) {

                        //d[j*n+i] = min3(d[(j-1)*n+i]+1, d[j*n+i-1]+1, d[(j-blk2)*n+i-blk2]+cost+(block_length-1));
                        d[k] = min3(d[k - n] + 1, d[k - 1] + 1, d[k - (n + 1) * blk2] + cost + (block_length - 1));
                        block_length = 0;
                    } else if (block_length == 1) {
                        // no transposition
                        //d[j*n+i]=min3(d[(j-1)*n+i]+1,d[j*n+i-1]+1,d[(j-1)*n+i-1]+cost);
                        d[k] = min3(d[k - n] + 1, d[k - 1] + 1, d[k - n - 1] + cost);
                    }
                    block_length--;
                }
                if (d[k] < best) best = d[k];
            }
            if (best >= limit) return (longlong) limit;
        }
        return (longlong) d[n * m - 1];
    } else if (n == 0) {
        return m;
    } else {
        return n;
    }
}

/******************************************************************************
** purpose:  called once for each SQL statement which invokes MDLD();
**           checks arguments, sets restrictions, allocates memory that
**           will be used during the main DLD() function (the same
**           memory will be reused for each row returned by the query)
** receives: pointer to UDF_INIT struct which is to be shared with all
**           other functions (dld() and dld_deinit()) -
**           the components of this struct are described in the MySQL manual;
**           pointer to UDF_ARGS struct which contains information about
**           the number, size, and type of args the query will be providing
**           to each invocation of dld(); pointer to a char
**           array of size MYSQL_ERRMSG_SIZE in which an error message
**           can be stored if necessary
** returns:  1 => failure; 0 => successful initialization
******************************************************************************/
my_bool dld_init(UDF_INIT *initid, UDF_ARGS *args, char *message)
{
    int *workspace;

    /* make sure user has provided three arguments */
    if (args->arg_count != 2) {
        strcpy(message, "DLD() requires two arguments");
        return 1;
    }
    /* make sure both arguments are strings - they could be cast to strings,
    ** but that doesn't seem useful right now */
    else if (args->arg_type[0] != STRING_RESULT ||
             args->arg_type[1] != STRING_RESULT)
    {
        strcpy(message, "DLD() requires arguments (string, string)");
        return 1;
    }

    /* set the maximum number of digits MySQL should expect as the return
    ** value of the DLD() function */
    initid->max_length = 3;

    /* dld() will not be returning null */
    initid->maybe_null = 0;

    /* attempt to allocate memory in which to calculate distance
    ** and store the character positions (UTF 8) */
    workspace = new int[(args->lengths[0] + 2) * (args->lengths[1] + 2)];

    if (workspace == NULL) {
        strcpy(message, "Failed to allocate memory for dld function");
        return 1;
    }

    /* initid->ptr is a char* which MySQL provides to share allocated memory
    ** among the xxx_init(), xxx_deinit(), and xxx() functions */
    initid->ptr = (char*) workspace;

    return 0;
}

/******************************************************************************
** purpose:  deallocate memory allocated by dld_init(); this func
**           is called once for each query which invokes DLD(),
**           it is called after all of the calls to dld() are done
** receives: pointer to UDF_INIT struct (the same which was used by
**           dld_init() and dld())
** returns:  nothing
******************************************************************************/
void dld_deinit(UDF_INIT *initid)
{
    if (initid->ptr != NULL) {
        delete [] initid->ptr;
    }
}

/******************************************************************************
** purpose:  compute the Levenshtein distance (edit distance) between two
**           strings, detects one-character-transpositions
** receives: pointer to UDF_INIT struct which contains pre-allocated memory
**           in which work can be done; pointer to UDF_ARGS struct which
**           contains the functions arguments and data about them; pointer
**           to mem which can be set to 1 if the result is NULL; pointer
**           to mem which can be set to 1 if the calculation resulted in an
**           error
** returns:  the Levenshtein distance between the two provided strings
******************************************************************************/
longlong dld(UDF_INIT *initid, UDF_ARGS *args, char *is_null, char *error)
{
    /* s is the first user-supplied argument; t is the second
    ** the levenshtein distance between s and t is to be computed */
    const char *s = args->args[0];
    const char *t = args->args[1];

    /* get a pointer to the memory allocated in damlevlim_init() */
    int *d = (int*) initid->ptr;

    int off_s, off_t;

    off_s = (args->lengths[0] + 1) * (args->lengths[1] + 1);
    off_t = off_s + args->lengths[0] + 1;

    return dld_fast_utf8(s, (int) args->lengths[0], t, (int) args->lengths[1], d, &d[off_s], &d[off_t]);
}

/******************************************************************************
** purpose:  called once for each SQL statement which invokes MDLD();
**           checks arguments, sets restrictions, allocates memory that
**           will be used during the main MDLD() function (the same
**           memory will be reused for each row returned by the query)
** receives: pointer to UDF_INIT struct which is to be shared with all
**           other functions (mdld() and mdld_deinit()) -
**           the components of this struct are described in the MySQL manual;
**           pointer to UDF_ARGS struct which contains information about
**           the number, size, and type of args the query will be providing
**           to each invocation of mdld(); pointer to a char
**           array of size MYSQL_ERRMSG_SIZE in which an error message
**           can be stored if necessary
** returns:  1 => failure; 0 => successful initialization
******************************************************************************/
my_bool mdld_init(UDF_INIT *initid, UDF_ARGS *args, char *message)
{
    int *workspace;

    /* make sure user has provided three arguments */
    if (args->arg_count < 3) {
        strcpy(message, "MDLD() requires three or four arguments");
        return 1;
    }
    /* make sure both arguments are strings - they could be cast to strings,
    ** but that doesn't seem useful right now */
    else if (args->arg_type[0] != STRING_RESULT ||
             args->arg_type[1] != STRING_RESULT ||
             args->arg_type[2] != INT_RESULT)
    {
        strcpy(message, "MDLD() requires arguments (string, string, int, [int])");
        return 1;
    }

    /* set the maximum number of digits MySQL should expect as the return
    ** value of the MDLD() function */
    initid->max_length = 3;

    /* mdld() will not be returning null */
    initid->maybe_null = 0;

    /* attempt to allocate memory in which to calculate distance
    ** and store the character positions (UTF 8) */
    workspace = new int[(args->lengths[0] + 2) * (args->lengths[1] + 2)];

    if (workspace == NULL) {
        strcpy(message, "Failed to allocate memory for mdld function");
        return 1;
    }

    /* initid->ptr is a char* which MySQL provides to share allocated memory
    ** among the xxx_init(), xxx_deinit(), and xxx() functions */
    initid->ptr = (char*) workspace;

    return 0;
}

/******************************************************************************
** purpose:  deallocate memory allocated by mdld_init(); this func
**           is called once for each query which invokes MDLD(),
**           it is called after all of the calls to mdld() are done
** receives: pointer to UDF_INIT struct (the same which was used by
**           mdld_init() and mdld())
** returns:  nothing
******************************************************************************/
void mdld_deinit(UDF_INIT *initid)
{
    if (initid->ptr != NULL) {
        delete [] initid->ptr;
    }
}

/******************************************************************************
** purpose:  compute the Levenshtein distance (edit distance) between two
**           strings
** receives: pointer to UDF_INIT struct which contains pre-allocated memory
**           in which work can be done; pointer to UDF_ARGS struct which
**           contains the functions arguments and data about them; pointer
**           to mem which can be set to 1 if the result is NULL; pointer
**           to mem which can be set to 1 if the calculation resulted in an
**           error
** returns:  the Levenshtein distance between the two provided strings
******************************************************************************/
longlong mdld(UDF_INIT *initid, UDF_ARGS *args, char *is_null, char *error)
{
    /* s is the first user-supplied argument; t is the second
    ** the levenshtein distance between s and t is to be computed */
    const char *s = args->args[0];
    const char *t = args->args[1];
    long long blocklimit = *((long long*)args->args[2]);
    long long limit_arg;

    /* get a pointer to the memory allocated in damlevlim_init() */
    int *d = (int*) initid->ptr;

    int off_s, off_t;

    if (args->arg_count == 3 || args->args[3] == NULL) {
        limit_arg = 255;
    } else {
        limit_arg = *((long long*)args->args[3]);
    }

    off_s = (args->lengths[0] + 1) * (args->lengths[1] + 1);
    off_t = off_s + args->lengths[0] + 1;

    if (blocklimit == 0) {
        blocklimit = 1;
    } else if (blocklimit < 0) {
        blocklimit = -blocklimit;
    }

    return mdld_utf8(s, (int) args->lengths[0], t, (int) args->lengths[1], (int) blocklimit, (int) limit_arg, d, &d[off_s], &d[off_t]);
}

#endif /* HAVE_DLOPEN */