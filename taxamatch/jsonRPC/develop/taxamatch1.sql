--==============================================================================
-- PL/SQL Package: taxamatch1
-- Version: 1.0
-- Date: 24 November 2008
-- Purpose: Perform exact and fuzzy matching on a species name, or single genus name
-- Written by / Copyright (C): Tony Rees, November 2008 (Tony.Rees@csiro.au)
-- Background information: refer http://www.cmar.csiro.au/datacentre/biodiversity.htm#taxamatch
-- Known limitations: refer associated README file
-- Please notify any bugs, or possible improvements to Tony.Rees@csiro.au, general comments
--   to the TAXAMATCH developers wiki, accessible from the above page (username and pwd required,
--   available on request).
--
--------------------------------------------------------------------------------
-- LICENSE INFORMATION:
--------------------------------------------------------------------------------
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License <http://www.gnu.org/licenses/> for more details.
--------------------------------------------------------------------------------

-- --------------------------------------------------------------
-- Package specification
-- --------------------------------------------------------------
prompt
prompt Creating package taxamatch1
prompt
create or replace package taxamatch1 is
  function reduce_spaces (str varchar2 :=null) return varchar2;
  function normalize (str varchar2 :=null) return varchar2;
  function near_match (str varchar2 :=null, word_type varchar2 :=null) return varchar2;
  function mdld(p_str1 varchar2 default null, p_str2 varchar2 default null,
    p_block_limit number default null) return number;
  function ngram (source_string varchar2 := null, target_string varchar2 := null,
    n_used integer := 1) return number;
  function normalize_auth (str varchar2 :=null) return varchar2;
  function compare_auth (auth1 varchar2 := null, auth2 varchar2 := null) return number;
  procedure taxamatch (searchtxt varchar2 :=null, search_mode varchar2 := 'normal', debug varchar2 := null);
end taxamatch1;
/
show errors

-- --------------------------------------------------------------
-- package body
-- --------------------------------------------------------------
prompt
prompt Creating package body taxamatch1
prompt
create or replace package body taxamatch1 is

-- --------------------------------------------------------------
-- reusable components:
-- --------------------------------------------------------------
bl_chars constant varchar2(3) := ' ' || chr(10) || chr(13);
-----------------------------------------------------
function reduce_spaces (str varchar2 :=null) return varchar2 is
  temp varchar2(32767) := '';
begin
  temp := rtrim(ltrim(str));
  -- reduce multiple spaces to single
  while temp like '%  %' loop
    temp := replace(temp,'  ',' ');
  end loop;
  return temp;
end reduce_spaces;

-- --------------------------------------------------------------
-- ancillary functions:
-- --------------------------------------------------------------

------------------------------------
-- Function: normalize
-- Purpose: Produces normalized version of an input string (scientific name components)
-- Author: Tony Rees (Tony.Rees@csiro.au)
-- Date created: June 2007-November 2008
-- Inputs: input string as str (this version presumes genus, genus+species, or
--   genus+species+authority)
-- Outputs: normalized version of input string, for match purposes
-- Remarks:
--   (1) Removes known text elements e.g.
--      'aff.', 'cf.', 'subsp.', subgenera if enclosed in brackets, etc. as desired
--   (2) Removes accented and non A-Z characters other than full stops (in scientific name portions)
--   (3) Returns uppercase scientific name (genus + species only) plus unaltered (presumed) authority
--        - examples;
--         - Anabaena cf. flos-aquae Ralfs ex Born. et Flah. => ANABAENA FLOSAQUAE Ralfs ex Born. et Flah.
--         - Abisara lem�e-pauli => ABISARA LEMEEPAULI
--         - Fuc/us Vesiculos2us => FUCUS VESICULOSUS
--         - Buffo ignicolor Lac�p�de, 1788 => BUFFO IGNICOLOR Lac�p�de, 1788
--         - Barbatia (Mesocibota) bistrigata (Dunker, 1866) => BARBATIA BISTRIGATA (Dunker, 1866)
--   (4) Thus version does not handle genus+author, or genus+species+infraspecies (second" good" term is presumed
--     to be species epithet, anything after is considered to be start of the authority),
--     however could be adapted further as required
--   (5) There is a separate function "normalize_auth" for normalizing authorities when required
--     (e.g. for authority comparisons)
------------------------------------
function normalize (str varchar2 :=null) return varchar2 is
  temp varchar2(32767) := '';
  first_str_part varchar2(500);
  second_str_part varchar2(500);
  temp_genus varchar2(100) := '';
  temp_species varchar2(100) := '';
  temp_genus_species varchar2(200) := '';
  temp_authority varchar2(300) := '';
  -- function to elimate all but chars A-Z (uppercase), spaces, and full stops
  --  (used on scientific name components only, not authorities)
  function good_chars (str varchar2 := null) return varchar2 is
    result       varchar2(32767) := '';
    a_char       varchar2(1);
    function char_test(a_char varchar2) return varchar2 is
      begin
      if (ascii(a_char) between 65 and 90) or ascii(a_char) = 32 or ascii(a_char) = 46 then
        return a_char;
      else
        return null;
      end if;
    end char_test;
  begin
    if str is null then
       return null;
    else
      for i in 1..length(str) loop
        a_char := substr(str, i, 1);
        result := result||char_test(a_char);
      end loop;
      return result;
    end if;
  end good_chars;
begin
  if str is null or str = '' or ltrim(rtrim(str, bl_chars),bl_chars) is null then
    return '';
  else  -- trim any leading, trailing spaces or line feeds
    temp := (ltrim(rtrim(str, bl_chars),bl_chars));
  end if;
  if temp is null or temp = '' then
    return '';
  else
    -- replace any HTML ampersands
    if temp like '%'||'&'||'amp;%' then
      temp := replace(temp,'%'||'&'||'amp;%','&');
    end if;
    if temp like '%'||'&'||'AMP;%' then
     temp := replace(temp,'%'||'&'||'AMP;%','&');
    end if;
    -- remove any content in angle brackets (e.g. html tags - <i>, </i>, etc.)
    if temp like '%<%>%' then
      first_str_part := null;
      second_str_part := null;
      while temp like '%<%>%' loop
        first_str_part := substr(temp,1,instr(temp,'<',1,1)-1);
        second_str_part := substr(temp,instr(temp,'>',1,1)+1);
        temp := replace(first_str_part||' '||second_str_part,'  ',' ');
      end loop;
    end if;
    -- if second term (only) is in round brackets, presume it is a subgenus or a comment and remove it
    -- examples: Barbatia (Mesocibota) bistrigata (Dunker, 1866) => Barbatia bistrigata (Dunker, 1866)
    --           Barbatia (?) bistrigata (Dunker, 1866) => Barbatia bistrigata (Dunker, 1866)
    -- (obviously this will not suit genus + author alone, where first part of authorname is in brackets,
    --  however this is very rare?? and in any case we are not supporting genus+authority in this version)
    if temp like '% (%)%' and instr(temp,'(',1,1) = instr(temp,' ',1,1)+1 then
      first_str_part := substr(temp,1,instr(temp,' ',1,1)-1);
      second_str_part := ltrim(substr(temp,instr(temp,')',1,1)+1));
      temp := first_str_part||' '||second_str_part;
    end if;
    -- if second term (only) is in square brackets, presume it is a comment and remove it
    -- example: Aphis [?] ficus Theobald, [1918] => Aphis ficus Theobald, [1918]
    if temp like '% [%]%' and instr(temp,'[',1,1) = instr(temp,' ',1,1)+1 then
      first_str_part := substr(temp,1,instr(temp,' ',1,1)-1);
      second_str_part := ltrim(substr(temp,instr(temp,']',1,1)+1));
      temp := first_str_part||' '||second_str_part;
    end if;
    -- drop indicators of questionable ids - presume all are lowercase for now (could extend as needed)
    if temp like '% cf %' then
      temp := replace(temp,' cf ',' ');
    end if;
    if temp like '% cf. %' then
      temp := replace(temp,' cf. ',' ');
    end if;
    if temp like '% near %' then
      temp := replace(temp,' near ',' ');
    end if;
    if temp like '% aff. %' then
      temp := replace(temp,' aff. ',' ');
    end if;
    if temp like '% sp.%' then
      temp := replace(temp,' sp.',' ');
    end if;
    if temp like '% spp.%' then
      temp := replace(temp,' spp.',' ');
    end if;
    if temp like '% spp %' then
      temp := replace(temp,' spp ',' ');
    end if;
    -- eliminate or close up any stray spaces introduced by the above
    temp := reduce_spaces(temp);
    -- now presume first element is genus, second (if present) is species, remainder
    --   (if present) is authority
    -- look for genus name
    if temp like '% %' then
      temp_genus := substr(temp,1,instr(temp,' ',1,1)-1);
      temp := substr(temp,instr(temp,' ',1,1)+1);
    elsif temp not like '% %' and length(temp) >0 then
      temp_genus := temp;
      temp := '';
    end if;
    -- look for species epithet and authority
    if temp like '% %' then
      temp_species := substr(temp,1,instr(temp,' ',1,1)-1);
      temp_authority := substr(temp,instr(temp,' ',1,1)+1);
    elsif temp not like '% %' and length(temp) >0 then
      temp_species := temp;
    end if;
    -- now can treat genus and species together
    temp_genus_species := upper(rtrim(temp_genus||' '||temp_species));

    -- Diacritical marks are removed here, however for authorities they should be kept
    -- replace any accented characters, drop any non A-Z chars other than
    --  full stops and spaces
    temp_genus_species := translate(temp_genus_species,'��������������������������',
      'AEIOUAEIOUAEIOUAEIOUANOACO');
    -- replace selected ligatures here (Genus names can contain �, OE ligature)
    if temp_genus_species like '%�%' then
      temp_genus_species := replace(temp_genus_species,'�','AE');
    end if;
    if temp_genus_species like '%'||chr(140)||'%' then
      temp_genus_species := replace(temp,'chr(140)','OE');
    end if;
    -- now drop any chars other than A-Z, space, and full stop
    temp_genus_species := ltrim(rtrim(good_chars(temp_genus_species)));
    -- reduce any new multiple internal spaces to single space, if present
    temp_genus_species := reduce_spaces(temp_genus_species);
    return rtrim(temp_genus_species||' '||temp_authority);
  end if;
end normalize;

--------------------------------------------------------
-- Function: near_match
-- Purpose: Produces "Rees 2007 near match" version of an input string
-- Author: Tony Rees (Tony.Rees@csiro.au)
-- Date created: June 2007
-- Inputs: input string as str, word type as word_type (permitted values of word type are
--  'genus_only', 'epithet_only', or null (latter presumes binomen or trinomen)
-- Outputs: transformed version of input string, as phonetic key for near match purposes
-- Remarks:
--   (1) includes calls to external function "normalize" (performs some normalization of text strings,
--      includes removal of known text elements e.g.
--      'aff.', 'cf.', 'subsp.', subgenera if enclosed in brackets, etc. as desired)
--   (2) Includes additional ending normalization on epithets (but not genus name)
--   (3) Presumes authority information has already been stripped (i.e., not supplied)
--------------------------------------------------------
function near_match(str varchar2 :=null, word_type varchar2 :=null) return varchar2 is
  temp varchar2(32767) := '';
  this_word varchar2(200);
  word_no integer := 1;
  result varchar2(32767) := '';
  function treat_word(str2 varchar2 :=null, strip_endings varchar2 :=null) return varchar2 is
    temp2 varchar2(32767) := '';
    start_letter varchar2(1) := '';
    l number (3);
    next_char varchar2(1) := '';
    result2 varchar2(32767) := '';
    begin
      if str2 is null or str2 = '' or ltrim(rtrim(str2, bl_chars),bl_chars) is null then
        return '';
      else
      temp2 := normalize(str2);
      -- Do some selective replacement on the leading letter/s only:
      if temp2 like 'AE%' then
        temp2 := 'E'||substr(temp2,3);
      elsif temp2 like 'CN%' then
        temp2 := 'N'||substr(temp2,3);
      elsif temp2 like 'CT%' then
        temp2 := 'T'||substr(temp2,3);
      elsif temp2 like 'CZ%' then
        temp2 := 'C'||substr(temp2,3);
      elsif temp2 like 'DJ%' then
        temp2 := 'J'||substr(temp2,3);
      elsif temp2 like 'EA%' then
        temp2 := 'E'||substr(temp2,3);
      elsif temp2 like 'EU%' then
        temp2 := 'U'||substr(temp2,3);
      elsif temp2 like 'GN%' then
        temp2 := 'N'||substr(temp2,3);
      elsif temp2 like 'KN%' then
        temp2 := 'N'||substr(temp2,3);
      elsif temp2 like 'MC%' then
        temp2 := 'MAC'||substr(temp2,3);
      elsif temp2 like 'MN%' then
        temp2 := 'N'||substr(temp2,3);
      elsif temp2 like 'OE%' then
        temp2 := 'E'||substr(temp2,3);
      elsif temp2 like 'QU%' then
        temp2 := 'Q'||substr(temp2,3);
      elsif temp2 like 'PS%' then
        temp2 := 'S'||substr(temp2,3);
      elsif temp2 like 'PT%' then
        temp2 := 'T'||substr(temp2,3);
      elsif temp2 like 'TS%' then
        temp2 := 'S'||substr(temp2,3);
      elsif temp2 like 'WR%' then
        temp2 := 'R'||substr(temp2,3);
      elsif temp2 like 'X%' then
        temp2 := 'Z'||substr(temp2,2);
      end if;
      -- Now keep the leading character, then do selected "soundalike" replacements. The
      -- following letters are equated: AE, OE, E, U, Y and I; IA and A are equated;
      -- K and C;  Z and S; and H is dropped. Also, A and O are equated, MAC and MC are equated, and SC and S.
      start_letter :=substr(temp2,1,1);  -- quarantine the leading letter
      temp2 := substr(temp2,2);  -- snip off the leading letter
      -- now do the replacements
      temp2 := replace(temp2, 'AE', 'I');
      temp2 := replace(temp2, 'IA', 'A');
      temp2 := replace(temp2, 'OE', 'I');
      temp2 := replace(temp2, 'OI', 'A');
      temp2 := replace(temp2, 'SC', 'S');
      temp2 := translate(temp2, 'EOUYKZH', 'IAIICS');
      --add back the leading letter
      temp2 := start_letter||temp2;
      -- now drop any repeated characters (AA becomes A, BB or BBB becomes B, etc.)
      l := length(temp2);
      for i in 1..l loop
        next_char := substr(temp2, i, 1);
        if i = 1 then
          result2 := next_char;
        elsif next_char = substr(result2,-1) then
          null;
        else
          result2 := result2||next_char;
        end if;
      end loop;
      if length(result2) >4 and strip_endings ='Y' then
      -- deal with variant endings -is (includes -us, -ys, -es), -im (was -um), -as (-os)
      -- at end of string or word: translate all to -a
      result2 := result2||' ';
      if result2 like '%IS %' then
        result2 := replace(result2, 'IS ','A ');
      end if;
      if result2 like '%IM %' then
        result2 := replace(result2, 'IM ','A ');
      end if;
      if result2 like '%AS %' then
        result2 := replace(result2, 'AS ','A ');
      end if;
      -- strip off last space again
      result2 := rtrim(result2);
      end if;
      return result2;
    end if;
  end treat_word;
begin
   if str is null or str = '' or ltrim(rtrim(str, bl_chars),bl_chars) is null then
      return '';
    else
      temp := upper(str);
    if word_type = 'genus_only' then
      result := treat_word(temp);
    elsif word_type = 'epithet_only' then
      result := treat_word(temp, 'Y');
    else
      -- add a trailing space (otherwise will loop forever!)
      temp := temp||' ';
      while length(temp) >1 loop
        -- snip off words and treat consecutively
        this_word := substr(temp,1,instr(temp,' ',1,1)-1);
        if word_no = 1 then -- presume genus name, do not treat species endings, etc.
          result := result||' '||treat_word(this_word);
        else
          result := result||' '||treat_word(this_word,'Y');
        end if;
        temp := substr(temp,instr(temp,' ',1,1)+1);
        word_no := word_no +1;
      end loop;
    end if;
    return ltrim(result);
  end if;
end near_match;

-------------------------------------------------------------
-- Function: mdld
-- Purpose: Perform Modified Damerau-Levenshtein Distance test on two input strings, supporting block
--   transpositions of multiple characters
-- Author: Barbara Boehmer and Tony Rees (baboehme@hotmail.com, Tony.Rees@csiro.au)
-- Date created: March 2008
-- Inputs: string 1 as p_str1, string 2 as p_str2, numeric limit on length of transposed block to be
--   searched for as p_block_limit
-- Outputs: computed edit distance between the input strings (0=identical on this measure, 1..n=increasing
--   dissimilarity)
-- Remarks:
--   (1) Block limit must be 1 or greater. If set to 1, functions as standard Damerau-Levenshtein
--     Distance (DLD) test; for MDLD, setting block limit to a moderately low value (e.g. 3) will
--     avoid excessive run times
--   (2) extension of B. Boehmer�s original (2002) PL/SQL Levenshtein Distance function, available at
--     http://www.merriampark.com/ldplsql.htm.
-------------------------------------------------------------
FUNCTION mdld
  (p_str1              VARCHAR2 DEFAULT NULL,
   p_str2              VARCHAR2 DEFAULT NULL,
   p_block_limit       NUMBER   DEFAULT NULL)
  RETURN NUMBER
IS
  v_str1_length        PLS_INTEGER := NVL (LENGTH (p_str1), 0);
  v_str2_length        PLS_INTEGER := NVL (LENGTH (p_str2), 0);
  v_temp_str1          VARCHAR2 (32767);
  v_temp_str2          VARCHAR2 (32767);
  TYPE mytabtype IS    TABLE OF NUMBER INDEX BY BINARY_INTEGER;
  TYPE myarray IS      TABLE OF mytabtype INDEX BY BINARY_INTEGER;
  v_my_columns         myarray;
  v_empty_column       mytabtype;
  v_this_cost          PLS_INTEGER := 0;
  v_temp_block_length  PLS_INTEGER;
BEGIN
  IF p_str2 = p_str1 THEN
    RETURN 0;
  ELSIF v_str1_length = 0 OR v_str2_length = 0 THEN
    RETURN GREATEST (v_str1_length, v_str2_length);
  ELSIF v_str1_length = 1 AND v_str2_length = 1 AND p_str2 != p_str1 THEN
    RETURN 1;
  ELSE
    v_temp_str1 := p_str1;
    v_temp_str2 := p_str2;
    -- first trim common leading characters
    WHILE SUBSTR (v_temp_str1, 1, 1) = SUBSTR (v_temp_str2, 1, 1) LOOP
       v_temp_str1 := SUBSTR (v_temp_str1, 2);
       v_temp_str2 := SUBSTR (v_temp_str2, 2);
    END LOOP;
    -- then trim common trailing characters
    WHILE SUBSTR (v_temp_str1, -1, 1) = SUBSTR (v_temp_str2, -1, 1) LOOP
       v_temp_str1 := SUBSTR (v_temp_str1, 1, LENGTH (v_temp_str1) - 1);
       v_temp_str2 := SUBSTR (v_temp_str2, 1, LENGTH (v_temp_str2) - 1);
    END LOOP;
    v_str1_length := NVL (LENGTH (v_temp_str1), 0);
    v_str2_length := NVL (LENGTH (v_temp_str2), 0);
    -- then calculate standard Levenshtein Distance
    IF v_str1_length = 0 OR v_str2_length = 0 THEN
      RETURN GREATEST (v_str1_length, v_str2_length);
    ELSIF v_str1_length = 1 AND v_str2_length = 1 AND p_str2 != p_str1 THEN
      RETURN 1;
    ELSE
      -- create columns
      FOR s in 0 .. v_str1_length LOOP
        v_my_columns (s) := v_empty_column;
      END LOOP;
      -- enter values in first (leftmost) column
      FOR t in 0 .. v_str2_length LOOP
        v_my_columns (0) (t) := t;
      END LOOP;
      -- populate remaining columns
      FOR s in 1 .. v_str1_length LOOP
        v_my_columns (s) (0) := s  ;
        -- populate each cell of one column:
        FOR t in 1 .. v_str2_length LOOP
          -- calculate cost
          IF SUBSTR (v_temp_str1, s, 1) = SUBSTR (v_temp_str2, t, 1) THEN
            v_this_cost := 0;
          ELSE
            v_this_cost := 1;
          END IF;
          -- extension to cover multiple single, double, triple, etc character transpositions
          -- that includes caculation of original Levenshtein distance when no transposition found
          v_temp_block_length := LEAST ( (v_str1_length / 2), (v_str2_length / 2), NVL (p_block_limit, 1));
          WHILE v_temp_block_length >= 1 LOOP
            IF s >= (v_temp_block_length * 2) AND
               t >= (v_temp_block_length * 2) AND
               SUBSTR (v_temp_str1, s - ( (v_temp_block_length * 2) - 1), v_temp_block_length) =
                 SUBSTR (v_temp_str2, t - (v_temp_block_length - 1), v_temp_block_length) AND
               SUBSTR (v_temp_str1, s - (v_temp_block_length - 1), v_temp_block_length) =
                 SUBSTR (v_temp_str2, t - ( (v_temp_block_length * 2) - 1), v_temp_block_length) THEN
               -- transposition found
               v_my_columns (s) (t) := LEAST
                                         (v_my_columns (s) (t - 1) + 1,
                                          v_my_columns (s - 1) (t) + 1,
                                          (v_my_columns (s - (v_temp_block_length * 2)) (t - (v_temp_block_length * 2))
                                           + v_this_cost + (v_temp_block_length - 1)));
               v_temp_block_length := 0;
            ELSIF v_temp_block_length = 1 THEN
              -- no transposition
              v_my_columns (s) (t) := LEAST (v_my_columns (s) (t - 1) + 1,
                                             v_my_columns (s - 1) (t) + 1,
                                             v_my_columns (s - 1) (t - 1) + v_this_cost);
            END IF;
            v_temp_block_length := v_temp_block_length - 1;
          END LOOP;
        END LOOP;
      END LOOP;
    END IF;
    RETURN v_my_columns (v_str1_length) (v_str2_length);
  END IF;
END mdld;

----------------------------------
-- Function: ngram
-- Purpose: Perform n-gram comparison of two input strings
-- Author: Tony Rees (Tony.Rees@csiro.au)
-- Date created: March 2008
-- Inputs: string 1 as source_string, string 2 as target_string, required value of n to be
--   incorporated for as n_used
-- Outputs: computed similarity between the input strings, on 0-1 scale (1=identical on this measure, 0=no similarity)
-- Remarks:
--   (1) Input parameter n_used determines whether the similarity is calculated using unigrams (n=1),
--   bigrams (n=2), trigrams (n=3), etc; defaults to n=1 if not supplied.
--   (2) Input strings are padded with (n-1) spaces, to avoid under-weighting of terminal characters.
--   (3) Repeat instances of any n-gram substring in the same input string are treated as new substrings,
--   for comparison purposes (up to 9 handled in this implementation)
--   (4) Is case sensitive (should translate input strings to same case externally to render case-insensitive)
--   (5) Similarity is calculated using Dice�s coefficient.
----------------------------------
function ngram (source_string varchar2 := null, target_string varchar2 := null, n_used integer := 1) return number
is
this_source_string varchar2(32767);
this_target_string varchar2(32767);
this_ngram varchar2(100);
source_ngram_string varchar2(32767);
target_ngram_string varchar2(32767);
temp_number integer := null;
padding varchar2(10) := null;
match_count integer := 0;
result number;
begin
  -- get number of spaces for padding ends of strings (need n_used -1 at each end)
  temp_number := n_used;
  while temp_number >1 loop
    padding := padding||' ';
    temp_number := temp_number -1;
  end loop;
  this_source_string := padding||source_string||padding;
  this_target_string := padding||target_string||padding;
  -- build strings of n-grams plus occurrence counts
  while length(this_source_string) >=n_used loop
    this_ngram := substr(this_source_string,1,n_used);
    -- look for up to 9 repeats here
    if source_ngram_string like '%'||this_ngram||'(8)%' then
      source_ngram_string := source_ngram_string||this_ngram||'(9)';
    elsif source_ngram_string like '%'||this_ngram||'(7)%' then
      source_ngram_string := source_ngram_string||this_ngram||'(8)';
    elsif source_ngram_string like '%'||this_ngram||'(6)%' then
      source_ngram_string := source_ngram_string||this_ngram||'(7)';
    elsif source_ngram_string like '%'||this_ngram||'(5)%' then
      source_ngram_string := source_ngram_string||this_ngram||'(6)';
    elsif source_ngram_string like '%'||this_ngram||'(4)%' then
      source_ngram_string := source_ngram_string||this_ngram||'(5)';
    elsif source_ngram_string like '%'||this_ngram||'(3)%' then
      source_ngram_string := source_ngram_string||this_ngram||'(4)';
    elsif source_ngram_string like '%'||this_ngram||'(2)%' then
      source_ngram_string := source_ngram_string||this_ngram||'(3)';
    elsif source_ngram_string like '%'||this_ngram||'(1)%' then
      source_ngram_string := source_ngram_string||this_ngram||'(2)';
    else
      source_ngram_string := source_ngram_string||this_ngram||'(1)';
    end if;
    this_source_string := substr(this_source_string, 2);
  end loop;
  while length(this_target_string) >=n_used loop
    this_ngram := substr(this_target_string,1,n_used);
    -- look for up to 9 repeats here
    if target_ngram_string like '%'||this_ngram||'(8)%' then
      target_ngram_string := target_ngram_string||this_ngram||'(9)';
    elsif target_ngram_string like '%'||this_ngram||'(7)%' then
      target_ngram_string := target_ngram_string||this_ngram||'(8)';
    elsif target_ngram_string like '%'||this_ngram||'(6)%' then
      target_ngram_string := target_ngram_string||this_ngram||'(7)';
    elsif target_ngram_string like '%'||this_ngram||'(5)%' then
      target_ngram_string := target_ngram_string||this_ngram||'(6)';
    elsif target_ngram_string like '%'||this_ngram||'(4)%' then
      target_ngram_string := target_ngram_string||this_ngram||'(5)';
    elsif target_ngram_string like '%'||this_ngram||'(3)%' then
      target_ngram_string := target_ngram_string||this_ngram||'(4)';
    elsif target_ngram_string like '%'||this_ngram||'(2)%' then
      target_ngram_string := target_ngram_string||this_ngram||'(3)';
    elsif target_ngram_string like '%'||this_ngram||'(1)%' then
      target_ngram_string := target_ngram_string||this_ngram||'(2)';
    else
      target_ngram_string := target_ngram_string||this_ngram||'(1)';
    end if;
    this_target_string := substr(this_target_string, 2);
  end loop;
  -- now check common substrings
  -- count ngrams in source string that also occur in target string
  while length(source_ngram_string) >1 loop
    this_ngram := substr(source_ngram_string,1,n_used+3);
    if target_ngram_string like '%'||this_ngram||'%' then
      match_count := match_count+1;
    end if;
    source_ngram_string := substr(source_ngram_string,n_used+4);
  end loop;
  -- calculate similarity and round to 4 decimal places
  result := round((2*match_count)/(length(target_string)+length(source_string)+(n_used-1)+(n_used-1)),4);
  return result;
end ngram;

----------------------------------
-- Function: normalize_auth
-- Purpose: Produce a normalized version of authority of a taxon name
-- Author: Tony Rees (Tony.Rees@csiro.au)
-- Date created: March 2008
-- Inputs: authority string as str
-- Outputs: normalized version of str
-- Remarks:
--   (1) Performs authority expension of known abbreviated authornames using
--    table "auth_abbrev_test1" (must be available and populated with relevant content)
--   (2) Recognises "and", "et", "&" as equivalents (special case for "et al.") - all normalized to ampersand
--   (3) Recognises (e.g.) "Smith 1980" and "Smith, 1980" as equivalents - comma is removed in these cases
--   (4) Recognises (e.g.) "F. J. R. Taylor, 1980" and "F.J.R. Taylor, 1980" as equivalents -
--         extra space after full stops is ignored in these cases
--   (5) Returns uppercase string, diacritical marks intact
----------------------------------
function normalize_auth (str varchar2 :=null) return varchar2 is
   temp varchar2(32767) := '';
   this_word varchar2(50) := '';
   elapsed_chars varchar2(32767) := '';
   this_auth_full varchar2(200) := null;
 begin
  if str is null or str = '' or ltrim(rtrim(str, bl_chars),bl_chars) is null then
    return '';
  else
    -- trim any leading, trailing spaces or line feeds
    temp := ltrim(rtrim(str, bl_chars),bl_chars);
  end if;
  if temp is null or temp = '' then
    return '';
  else
    -- treat some special cases here (probably more to come)
    if temp = 'L.' then
      temp := 'Linnaeus';
    elsif temp like '(L.)%' then
      temp := '(Linnaeus)'||substr(temp,5);
    elsif temp like 'L., 1%' or temp like 'L. 1%' then
      temp := 'Linnaeus'||substr(temp,3);
    elsif temp like '(L., 1%' or temp like '(L. 1%' then
      temp := '(Linnaeus'||substr(temp,4);
    elsif temp = 'DC' or temp = '(DC)' then
      temp := replace(temp, 'DC', 'de Candolle');
    elsif temp = 'D.C.' or temp = '(D.C.)' then
      temp := replace(temp, 'D.C.', 'de Candolle');
    end if;
    -- add space after full stops, except at end (NB, will also add spece before some close brackets)
    temp := rtrim(replace(temp,'.','. '));
    --normalise "et", "and" to ampersand (et al. is a special case)
    if temp like '% et al%' then
      temp := replace(temp,' et al','zzzzz');
    end if;
    temp := replace(temp,' et ',' '||'&'||' ');
    temp := replace(temp,' and ',' '||'&'||' ');
    if temp like '%zzzzz%' then
      temp := replace(temp,'zzzzz',' et al');
    end if;
    --remove commas before dates (only)
    if temp like '%, 17%' then
      temp := replace(temp,', 17',' 17');
    end if;
    if temp like '%, 18%' then
      temp := replace(temp,', 18',' 18');
    end if;
    if temp like '%, 19%' then
      temp := replace(temp,', 19',' 19');
    end if;
    if temp like '%, 20%' then
      temp := replace(temp,', 20',' 20');
    end if;
    -- reduce multiple internal spaces to single space
    if temp like '%  %' then
      temp := reduce_spaces(temp);
    end if;
    if temp like '% -%' then
      temp := replace(temp, ' -','-');
    end if;
    -- expand abbreviated authors section
    -- snip into "words" at trailing space
    -- add a trailing space (else will loop forever!)
    temp := temp||' ';
    while length(temp) > 1 loop
      this_word := substr(temp,1,instr(temp,' ',1)-1);
      temp := substr(temp,instr(temp,' ',1)+1);
      if this_word like '(%' then
        elapsed_chars := elapsed_chars||'(';
        this_word := substr(this_word,2);
      end if;
      if this_word like '%.' and length(this_word) >2 then
        begin
          select auth_full
          into this_auth_full
          from auth_abbrev_test1
          where auth_abbr = this_word
          and auth_full != '-'
          and rownum=1;
        exception
          when no_data_found then null;
        end;
        if this_auth_full is not null then
          this_word := this_auth_full;
          this_auth_full := null;
        end if;
      end if;
      elapsed_chars := elapsed_chars||this_word||' ';
    end loop;
    if elapsed_chars like '% )%' then
      elapsed_chars := replace(elapsed_chars,' )',')');
    end if;
    return upper(ltrim(rtrim(elapsed_chars)));
  end if;
end normalize_auth;

----------------------------------
-- Function: compare_auth
-- Purpose: Compares two authority strings
-- Author: Tony Rees (Tony.Rees@csiro.au)
-- Date created: March 2008
-- Inputs: authority string 1 as auth1, authority string 1 as auth2
-- Outputs: Numeric similarity value of the 2 strings using weighted n-gram analysis,
--    on 0-1 scale (1 = identical - typically after normalization; 0 = no similarity)
-- Remarks:
--   (1) Invokes function "normalize_auth" on both strings, to compare only normalized
--         versions of the same
--   (2) Returns blend of 2/3 bigram, 1/3 trigram similarity (bigrams better correspond
--         to intuitivesimilarity, however are insensitive to word order, i.e. "Smith et
--         Jones" = "Jones et Smith" without some trigram contribution)
--   (3) Returns blend of 50% similarity with, and 50% without, stripping of diacritical
--         marks - so that the contribution of the latter is reduced but not eliminated
--   (4) Is case insensitive (i.e. "de Saedeleer" = "De Saedeleer", etc.)
--   (5) Threshold between low / possible / good match is in the area of
--         0-0.3 / 0.3-0.5 / 0.5+.
----------------------------------
function compare_auth(auth1 varchar2 := null, auth2 varchar2 := null) return number is
new_auth1 varchar2(500);
new_auth2 varchar2(500);
new_auth1b varchar2(500);
new_auth2b varchar2(500);
temp_auth_match1 number;
temp_auth_match2 number;
this_auth_match number;
begin
  if auth1 is null or auth2 is null then
    return null;
  else
    new_auth1 := normalize_auth(auth1);
    new_auth2 := normalize_auth(auth2);
    if new_auth1 = new_auth2 then
      this_auth_match := 1;
    else
      -- create second versions without diacritical marks
      new_auth1b := translate(new_auth1,'��������������������������',
          'AEIOUAEIOUAEIOUAEIOUANOACO');
      new_auth2b := translate(new_auth2,'��������������������������',
          'AEIOUAEIOUAEIOUAEIOUANOACO');
      -- weighted ngram comparison, use 67% n=2, 33% n=3
      -- use mean of versions with and without diacritical marks (to lessen their effect by 50%)
      temp_auth_match1 := ((2 * ngram(new_auth1,new_auth2,2))
        + ngram(new_auth1,new_auth2,3))/3;
      temp_auth_match2 := ((2 * ngram(new_auth1b,new_auth2b,2))
        + ngram(new_auth1b,new_auth2b,3))/3;
      this_auth_match := (temp_auth_match1 + temp_auth_match2) /2;
    end if;
    return round(this_auth_match,4);
  end if;
end compare_auth;
------------------------------------


-- --------------------------------------------------------------
-- The TAXAMATCH algorithm itself (includes calls to subsidiary functions as above)
-- --------------------------------------------------------------

--==============================================================================
-- Procedure: taxamatch (demo version)
-- Purpose: Perform exact and fuzzy matching on a species name, or single genus name
-- Written by: Tony Rees, November 2008 (Tony.Rees@csiro.au)
-- Input: - genus, genus+species, or genus+species+authority (in this version), as "searchtxt"
--        - "search_mode" to control search mode: currently normal (default) / rapid / no_shaping
--        - "debug" - print internal parameters used if not null
-- Outputs: list of genera and species that match (or near match) input terms, with associated
--   ancillary info as desired
-- Remarks:
--   (1) This demo version is configured to access base data in three tables:
--          - genlist_test1 (genus info); primary key (PK) is genus_id
--          - splist_test1 (species info); PK is species_id, has genus_id as foreign key (FK)
--              (= link to relevant row in genus table)
--          - auth_abbrev_test1 (authority abbreviations - required by subsidiary function
--            "normalize_auth". Refer README file for relevant minimum table definitions.
--       If authority comparisons are not required, calls to "normalize_auth" can be disabled and
--         relevant function commented out, removing need for third table.
--       (In a production system, table and column names can be varied as desired so long as
--         code is altered at relevant points, also could be re-configured to hold all genus+species
--         info together in a single table with minor re-write).
--   (2) Writes to and reads back from pre-defined global temporary tables
--      "genus_id_matches" and "species_id_matches", new instances of these are automatically
--      created for each session (i.e., do not need clearing at procedure end). Refer
--      README file for relevant table definitions.
--   (3) NB if called from SQL+ window - e.g. via a command such as:
--      "execute taxamatch1.taxamatch('Aluteres scriptus (Osbeck)')" -
--      must "set serveroutput on" for the session in order to see the result
--   (4) This demo version simply outputs result to SQL+ screen, a production version would
--      do something else with the result e.g. format as HTML, write to file or database table,
--      generate XML, etc. etc.
--   (5) When result shaping is on in this version, a relevant message displayed as required
--      for developer feedback, if more distant results are being masked (in producton version,
--       possibly would not do this)
--   (6) Requires the following subsidiary functions (supplied elsewhere in this package):
--         - normalize
--         - normalize_auth
--         - reduce_spaces
--         - ngram
--         - compare_auth
--         - near_match
--         - mdld
--   (7) Accepts "+" as input separator in place of space (e.g. "Homo+sapiens"), e.g. for calling
--         via a HTTP GET request as needed.
--==============================================================================
procedure taxamatch (searchtxt varchar2 :=null, search_mode varchar2 := 'normal', debug varchar2 := null)
is
text_str varchar2(32767);
this_search_genus varchar2(50);
this_search_species varchar2(50);
this_authority varchar2(200);
this_near_match_genus varchar2(50);
this_genus_start varchar2(3);
this_genus_end varchar2(3);
this_genus_length integer;
this_near_match_species varchar2(50);
this_species_length integer;
genera_tested integer :=0;  -- used here for reporting in debug mode only, but could return to user if desired
species_tested integer :=0;  -- used here for reporting in debug mode only, but could return to user if desired
gen_phonetic_flag varchar2(1) := null;
sp_phonetic_flag varchar2(1) := null;
temp_genus_ED integer;
temp_species_ED integer;
temp_genus_id varchar2(15) := null;
temp_authority varchar2(200);
auth_similarity number;
species_found varchar2(1) := null;
temp_species_count integer;
err_msg varchar2(50) := null;
cursor genus_cur  is  -- select candidate genera for genus edit distance test;
                      --   includes genus pre-filter
  select distinct A.genus_id, A.genus, A.near_match_genus, A.search_genus_name
  from genlist_test1 A, splist_test1 B
  where
    A.near_match_genus = this_near_match_genus  -- exact match, or phonetic match
    or
    (
     (search_mode is null or (search_mode is not null and search_mode != 'rapid'))
      and
      A.gen_length between (this_genus_length-2) and (this_genus_length+2)
      and
      (
       (least(this_genus_length, A.gen_length) <4 and (A.search_genus_name like substr(this_genus_start,1,1)||'%' or
         A.search_genus_name like '%'||substr(this_genus_end,-1,1)))
       or
       (least(this_genus_length, A.gen_length) = 4 and (A.search_genus_name like substr(this_genus_start,1,2)||'%' or
         A.search_genus_name like '%'||substr(this_genus_end,-2,2)))
       or
       (least(this_genus_length, A.gen_length) = 5 and (A.search_genus_name like substr(this_genus_start,1,2)||'%' or
         A.search_genus_name like '%'||substr(this_genus_end,-3,3)))
       or
       (least(this_genus_length, A.gen_length) >5 and (A.search_genus_name like this_genus_start||'%' or
         A.search_genus_name like '%'||this_genus_end))
      )
     )
    or
    (this_near_match_species is not null
    and A.gen_length between (this_genus_length-3) and (this_genus_length+3)
    and B.near_match_species = this_near_match_species  -- exact match, or phonetic match on species epithet
    and A.genus_id = B.genus_id)
  group by A.genus_id, A.genus, A.near_match_genus, A.search_genus_name
  order by A.genus;
cursor species_cur(gen_id varchar2) is  -- select candidate species for species edit distance test;
                                        --   includes species pre-filter
  select distinct A.species_id, A.species, A.search_species_name, A.near_match_species, B.near_match_genus||' '||
    A.near_match_species near_match_gen_sp, B.genus||' '||A.species genus_species
  from splist_test1 A, genlist_test1 B
  where A.genus_id = gen_id
  and B.genus_id = gen_id
  and sp_length between (this_species_length-4) and (this_species_length+4)
  group by A.species_id, A.species, A.search_species_name, A.near_match_species, B.near_match_genus||' '||
    A.near_match_species, B.genus||' '||A.species
  order by A.species;
cursor genus_result_cur(this_ed varchar2 := null) is  -- select final near match genera for ranking,
                                                      --   presentation etc.
  select distinct genus_id, genus, genus_ed, phonetic_flag
  from genus_id_matches  -- global temporary table, used to "park" genus matches for subsequent re-sorting
  where this_ed is null or
  (this_ed = '0' and genus_ed = 0) or
  (this_ed = 'P' and genus_ed >0 and phonetic_flag = 'Y') or
  (this_ed != 'P' and phonetic_flag is null and to_number(this_ed) >0 and
    genus_ed = to_number(this_ed))
  group by genus_id, genus, genus_ed, phonetic_flag
  order by genus_ed, genus;
cursor species_result_cur(this_ed varchar2 := null) is  -- select final near match species for ranking,
                                                        --   presentation etc.
  select distinct species_id, genus_species, genus_ed, species_ed, gen_sp_ed, phonetic_flag
  from species_id_matches  -- global temporary table, used to "park" species matches for subsequent re-sorting
  where this_ed is null or
  (this_ed = '0' and gen_sp_ed = 0) or
  (this_ed = 'P' and gen_sp_ed >0 and phonetic_flag = 'Y') or
  (this_ed != 'P' and phonetic_flag is null and to_number(this_ed) >0 and
    gen_sp_ed = to_number(this_ed))
  group by species_id, genus_species, genus_ed, species_ed, gen_sp_ed, phonetic_flag
  order by species_ed, genus_ed, genus_species;
begin
    -- accept "+" as separator if supplied, tranform to space
    if searchtxt like '%+%' then
      text_str := replace(searchtxt,'+',' ');
    else
      text_str := searchtxt;
    end if;
    if text_str is null or text_str = '' or ltrim(rtrim(text_str, bl_chars),bl_chars) is null then
      err_msg := 'No or blank input string supplied';
      goto exit_sub;
    end if;
    -- possibly would typically do normalization outside this procedure (as it is
    --   potentially useful for other purposes too, however it is handled here
    --   for the purpose of this demo package
    text_str := normalize(text_str);  -- includes stripping of presumed non-relevant content
                 -- including subgenera, comments, cf's, aff's, etc... to leave
                 -- presumed genus + species + authority (in this instance), with
                 -- genus and species in uppercase
    -- now parse into component parts at first and second spaces
    if text_str like '%'||' '||'%' then
      this_search_genus := substr(text_str,1,instr(text_str,' ',1)-1);
      text_str := rtrim(substr(text_str,instr(text_str,' ',1)+1));
    else
      this_search_genus := text_str;
      text_str := null;
    end if;
    if text_str like '%'||' '||'%' then
      this_search_species := substr(text_str,1,instr(text_str,' ',1)-1);
      this_authority := rtrim(substr(text_str,instr(text_str,' ',1)+1));
    else
      this_search_species := text_str;
    end if;
    this_near_match_genus := near_match(this_search_genus);
    this_genus_start := substr(this_search_genus,1,3);
    this_genus_end := substr(this_search_genus,-3,3);
    this_genus_length := length(this_search_genus);
    if this_search_species is not null then
      this_near_match_species := near_match(this_search_species, 'epithet_only');
      this_species_length := length(this_search_species);
    end if;
    -- now look for exact or near matches on genus
    -- first select candidate genera for edit distance (MDLD) test
    for drec in genus_cur loop -- includes the genus pre-filter (main portion)
      -- test candidate genera for edit distance, keep if satisfies post-test criteria
      genera_tested := genera_tested +1;
      -- do the genus edit distance test
      temp_genus_ED := mdld(drec.search_genus_name,this_search_genus,2);
      -- add the genus post-filter
      if (temp_genus_ED <= 3 and
          -- min. 51% "good" chars
          least(length(drec.search_genus_name),this_genus_length) > (temp_genus_ED*2) and
          -- first char must match for ED 2+
          (temp_genus_ED <2 or substr(drec.search_genus_name,1,1) = substr(this_search_genus,1,1))
         )
      or
         drec.near_match_genus = this_near_match_genus
      then
        -- accept as exact or near match; append to genus results table
        if drec.near_match_genus = this_near_match_genus then
          gen_phonetic_flag := 'Y';
        else
          gen_phonetic_flag := null;
        end if;
        begin
          insert into genus_id_matches(genus_id, genus, genus_ed, phonetic_flag)
            values (drec.genus_id, drec.genus, temp_genus_ED, gen_phonetic_flag);
        end;
        if this_search_species is not null then
          -- test species that are children of this genus
          for drec1 in species_cur(drec.genus_id) loop -- includes the species pre-filter
            species_tested := species_tested +1;
            -- do the species edit distance test
            temp_species_ED := mdld(drec1.search_species_name,this_search_species,4);
            -- add the species post-filter
            if drec1.near_match_species = this_near_match_species
              or
              (temp_genus_ED + temp_species_ED <=4 and
               (
                temp_species_ED <= 4 and
                -- min. 50% "good" chars
                least(length(drec1.species),this_species_length) >= (temp_species_ED*2) and
                -- first char must match for ED2+
                (temp_species_ED <2 or drec1.search_species_name like substr(this_search_species,1,1)||'%')
                 and
                -- first 3 chars must match for ED4
                (temp_species_ED <4 or drec1.search_species_name like substr(this_search_species,1,3)||'%') and
                (temp_genus_ED+temp_species_ED <=4)
               )
              )
              then
              -- accept as exact or near match, append to species results table
              -- if phonetic match, set relevant flag
              if drec.near_match_genus = this_near_match_genus and
                drec1.near_match_species = this_near_match_species then
                sp_phonetic_flag := 'Y';
              else
                sp_phonetic_flag := null;
              end if;
              begin
              insert into species_id_matches(species_id, genus_species,
                genus_ed, species_ed, gen_sp_ed, phonetic_flag)
              values (drec1.species_id, drec1.genus_species,
                temp_genus_ED, temp_species_ED,
                temp_genus_ED+temp_species_ED, sp_phonetic_flag);
              end;
            end if;
          end loop;
        end if;
      end if;
    end loop;
    -----------------------------------------------------------------
    --  Result generation section (including ranking, result shaping,
    --    and authority comparison) - for demo purposes only
    --  NB, in a production system this would be replaced by something
    --    more appropriate, e.g. write to a file or database table,
    --    generate a HTML page for web display,
    --    generate XML response, etc. etc.
    -----------------------------------------------------------------
    -- genus exact, phonetic, and other near matches
    dbms_output.put_line('---------');
    dbms_output.put_line('** Input name: '||searchtxt||' **');
    dbms_output.put_line('---------');
    dbms_output.put_line('Genus exact matches:');
    for drec in genus_result_cur('0') loop
      -- select ancillary info here as desired
      --   (authority only is shown in this example,
      --   but would most likely be more as available in a production system)
      begin
        select authority
        into temp_authority
        from genlist_test1
        where genus_id = drec.genus_id;
      end;
      dbms_output.put_line(' * '||drec.genus||' '||temp_authority||' (ID: '||drec.genus_id||')');
    end loop;
    dbms_output.put_line('---------');
    dbms_output.put_line('Genus phonetic matches:');
    for drec in genus_result_cur('P') loop
      -- select ancillary info here as desired (authority only is shown in this example)
      begin
        select authority
        into temp_authority
        from genlist_test1
        where genus_id = drec.genus_id;
      end;
      dbms_output.put_line(' * '||drec.genus||' '||temp_authority||' (ID: '||drec.genus_id||')');
    end loop;
    dbms_output.put_line('---------');
    dbms_output.put_line('Other genus near matches:');
    -- (note, I am not quoting genus ED here, but could do if desired...)
    for drec in genus_result_cur('1') loop
      -- select ancillary info here as desired (authority only is shown in this example)
      begin
        select authority
        into temp_authority
        from genlist_test1
        where genus_id = drec.genus_id;
      end;
      dbms_output.put_line(' * '||drec.genus||' '||temp_authority||' (ID: '||drec.genus_id||')');
    end loop;
    for drec in genus_result_cur('2') loop
      begin
        select authority
        into temp_authority
        from genlist_test1
        where genus_id = drec.genus_id;
      end;
      dbms_output.put_line(' * '||drec.genus||' '||temp_authority||' (ID: '||drec.genus_id||')');
    end loop;
    -----------------------------------------------------------------
    --  NB, could apply (cosmetic) genus result shaping here if desired, i.e.
    --    only show ED 3 if no ED 1,2 hits... - however all shown in this demo
    -----------------------------------------------------------------
    for drec in genus_result_cur('3') loop
      begin
        select authority
        into temp_authority
        from genlist_test1
        where genus_id = drec.genus_id;
      end;
      dbms_output.put_line(' * '||drec.genus||' '||temp_authority||' (ID: '||drec.genus_id||')');
    end loop;
    if this_search_species is not null then
      -- species exact, phonetic, and other near matches
      dbms_output.put_line('---------');
      dbms_output.put_line('Species exact matches:');
      for drec in species_result_cur('0') loop
        -- select ancillary info here as desired
        --   (authority only is shown in this example,
        --   but would most likely be more as available in a production system)
        begin
          select authority
          into temp_authority
          from splist_test1
          where species_id = drec.species_id;
        end;
        if this_authority is not null then
          auth_similarity := compare_auth(this_authority, temp_authority);
          dbms_output.put_line(' * '||drec.genus_species||' '||temp_authority||' (ID: '||drec.species_id||')' ||
          ' ED '||to_char(drec.genus_ed)||','||to_char(drec.species_ed)||' auth. similarity='||to_char(auth_similarity));
        else
          dbms_output.put_line(' * '||drec.genus_species||' '||temp_authority||' (ID: '||drec.species_id||')' ||
          ' ED '||to_char(drec.genus_ed)||','||to_char(drec.species_ed));
        end if;
      end loop;
      dbms_output.put_line('---------');
      dbms_output.put_line('Species phonetic matches:');
      for drec in species_result_cur('P') loop
        species_found := 'Y';
        begin
          select authority
          into temp_authority
          from splist_test1
          where species_id = drec.species_id;
        end;
        if this_authority is not null then
          auth_similarity := compare_auth(this_authority, temp_authority);
          dbms_output.put_line(' * '||drec.genus_species||' '||temp_authority||' (ID: '||drec.species_id||')' ||
          ' ED '||to_char(drec.genus_ed)||','||to_char(drec.species_ed)||' auth. similarity='||to_char(auth_similarity));
        else
          dbms_output.put_line(' * '||drec.genus_species||' '||temp_authority||' (ID: '||drec.species_id||')' ||
          ' ED '||to_char(drec.genus_ed)||','||to_char(drec.species_ed));
        end if;
      end loop;
      dbms_output.put_line('---------');
      dbms_output.put_line('Other species near matches:');
      for drec in species_result_cur('1') loop
        species_found := 'Y';
        begin
          select authority
          into temp_authority
          from splist_test1
          where species_id = drec.species_id;
        end;
        if this_authority is not null then
          auth_similarity := compare_auth(this_authority, temp_authority);
          dbms_output.put_line(' * '||drec.genus_species||' '||temp_authority||' (ID: '||drec.species_id||')' ||
          ' ED '||to_char(drec.genus_ed)||','||to_char(drec.species_ed)||' auth. similarity='||to_char(auth_similarity));
        else
          dbms_output.put_line(' * '||drec.genus_species||' '||temp_authority||' (ID: '||drec.species_id||')' ||
          ' ED '||to_char(drec.genus_ed)||','||to_char(drec.species_ed));
        end if;
      end loop;
      for drec in species_result_cur('2') loop
        species_found := 'Y';
        begin
          select authority
          into temp_authority
          from splist_test1
          where species_id = drec.species_id;
        end;
        if this_authority is not null then
          auth_similarity := compare_auth(this_authority, temp_authority);
          dbms_output.put_line(' * '||drec.genus_species||' '||temp_authority||' (ID: '||drec.species_id||')' ||
          ' ED '||to_char(drec.genus_ed)||','||to_char(drec.species_ed)||' auth. similarity='||to_char(auth_similarity));
        else
          dbms_output.put_line(' * '||drec.genus_species||' '||temp_authority||' (ID: '||drec.species_id||')' ||
          ' ED '||to_char(drec.genus_ed)||','||to_char(drec.species_ed));
        end if;
      end loop;
      -- Here is the result shaping section (only show ED 3 if no ED 1,2 or phonetic matches, only
      --   show ED 4 if no ED 1,2,3 or phonetic matches). By default shaping is on, unless disabled
      --   via the input parameter "search_mode" set to 'no_shaping'.
      --   In this demo we supplement any actual shaping with a message to show that it has been invoked,
      --   to show the system operates correctly.
      if species_found = 'Y' then
        begin
          select count(*)
          into temp_species_count
          from species_id_matches
          where phonetic_flag is null and gen_sp_ed = 3;
        end;
        if temp_species_count >0 and search_mode != 'no_shaping' then
          dbms_output.put_line('---------');
          dbms_output.put_line('(Additional ED 3 near matches are present, currently hidden by result shaping)');
        end if;
      end if;
      if temp_species_count >0 and search_mode = 'no_shaping' then
        for drec in species_result_cur('3') loop
          species_found := 'Y';
          begin
            select authority
            into temp_authority
            from splist_test1
            where species_id = drec.species_id;
          end;
          if this_authority is not null then
            auth_similarity := compare_auth(this_authority, temp_authority);
            dbms_output.put_line(' * '||drec.genus_species||' '||temp_authority||' (ID: '||drec.species_id||')' ||
            ' ED '||to_char(drec.genus_ed)||','||to_char(drec.species_ed)||' auth. similarity='||to_char(auth_similarity));
          else
            dbms_output.put_line(' * '||drec.genus_species||' '||temp_authority||' (ID: '||drec.species_id||')' ||
            ' ED '||to_char(drec.genus_ed)||','||to_char(drec.species_ed));
          end if;
        end loop;
      end if;
      if species_found = 'Y' then
        begin
          select count(*)
          into temp_species_count
          from species_id_matches
          where phonetic_flag is null and gen_sp_ed = 4;
        end;
        if temp_species_count >0  and search_mode != 'no_shaping' then
          dbms_output.put_line('---------');
          dbms_output.put_line('(Additional ED 4 near matches are present, currently hidden by result shaping)');
        end if;
      end if;
      if temp_species_count >0 and search_mode = 'no_shaping' then
        for drec in species_result_cur('4') loop
          species_found := 'Y';
          begin
            select authority
            into temp_authority
            from splist_test1
            where species_id = drec.species_id;
          end;
          if this_authority is not null then
            auth_similarity := compare_auth(this_authority, temp_authority);
            dbms_output.put_line(' * '||drec.genus_species||' '||temp_authority||' (ID: '||drec.species_id||')' ||
            ' ED '||to_char(drec.genus_ed)||','||to_char(drec.species_ed)||' auth. similarity='||to_char(auth_similarity));
          else
            dbms_output.put_line(' * '||drec.genus_species||' '||temp_authority||' (ID: '||drec.species_id||')' ||
            ' ED '||to_char(drec.genus_ed)||','||to_char(drec.species_ed));
          end if;
        end loop;
      end if;
    end if;
    -----------------------------------------------------------------
    --  DIANOSTICS
    --  (print for debugging purposes as needed)
    -----------------------------------------------------------------
    if debug is not null then
      dbms_output.put_line('---------');
      dbms_output.put_line('##########');
      dbms_output.put_line('---------');
      dbms_output.put_line('DEBUG INFO');
      dbms_output.put_line('searchtxt: '||searchtxt);
      dbms_output.put_line('search_mode: '||search_mode);
      dbms_output.put_line('debug: '||debug);
      dbms_output.put_line('this_search_genus: '||this_search_genus);
      dbms_output.put_line('this_search_species: '||this_search_species);
      dbms_output.put_line('this_authority: '||this_authority);
      dbms_output.put_line('this_near_match_genus: '||this_near_match_genus);
      dbms_output.put_line('this_genus_start: '||this_genus_start);
      dbms_output.put_line('this_genus_end: '||this_genus_end);
      dbms_output.put_line('this_genus_length: '||to_char(this_genus_length));
      dbms_output.put_line('this_near_match_species: '||this_near_match_species);
      dbms_output.put_line('this_species_length: '||to_char(this_species_length));
      dbms_output.put_line('No. of genera tested: '||to_char(genera_tested));
      dbms_output.put_line('"GENUS ID MATCHES" TABLE CONTENT:');
      for drec in genus_result_cur loop
        dbms_output.put_line('genus_id: '||drec.genus_id||', genus: '||drec.genus||
          ', genus_ed: '||drec.genus_ed||', phonetic_flag: '||drec.phonetic_flag);
      end loop;
      dbms_output.put_line('No. of species tested: '||to_char(species_tested));
      dbms_output.put_line('"SPECIES ID MATCHES" TABLE CONTENT:');
      for drec in species_result_cur loop
        dbms_output.put_line('species_id: '||drec.species_id||', genus_species: '||drec.genus_species||
          ', genus_ed: '||drec.genus_ed||', species_ed: '||drec.species_ed||', gen_sp_ed: '||
          drec.gen_sp_ed||', phonetic_flag: '||drec.phonetic_flag);
      end loop;
    end if;

    <<exit_sub>>

    if err_msg is not null then
      dbms_output.put_line('ERROR: '||err_msg);
    end if;
    -- clear temporary tables (only required when transaction not comitted, but does not hurt anyway)
    begin
      delete from genus_id_matches;
    end;
    begin
      delete from species_id_matches;
    end;
end taxamatch;
-- --------------------------------------------------------------
-- End package
-- --------------------------------------------------------------
end taxamatch1;
/
show errors