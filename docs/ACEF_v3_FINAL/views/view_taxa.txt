CREATE
 ALGORITHM=UNDEFINED
 DEFINER=root@localhost SQL SECURITY DEFINER 
VIEW 
 view_taxon AS 

select 
 ts.taxonID AS taxonID,
 ts.synID AS synID,
 ts.basID AS basID,
 ts.genID AS genID,
 ts.annotation AS annotation,
 ts.external AS external,
 tg.genus AS genus,
 tg.DallaTorreIDs AS DallaTorreIDs,
 tg.DallaTorreZusatzIDs AS DallaTorreZusatzIDs,
 tag.author AS author_g,
 tf.family AS family,
 tsc.category AS category,
 tst.status AS status,
 tst.statusID AS statusID,
 tr.rank AS rank,
 tr.tax_rankID AS tax_rankID,
 tr.rank_abbr AS rank_abbr,
 ta.author AS author,
 ta.authorID AS authorID,
 ta.Brummit_Powell_full AS Brummit_Powell_full,
 ta1.author AS author1,
 ta1.authorID AS authorID1,
 ta1.Brummit_Powell_full AS bpf1,
 ta2.author AS author2,
 ta2.authorID AS authorID2,
 ta2.Brummit_Powell_full AS bpf2,
 ta3.author AS author3,
 ta3.authorID AS authorID3,
 ta3.Brummit_Powell_full AS bpf3,
 ta4.author AS author4,
 ta4.authorID AS authorID4,
 ta4.Brummit_Powell_full AS bpf4,
 ta5.author AS author5,
 ta5.authorID AS authorID5,
 ta5.Brummit_Powell_full AS bpf5,
 te.epithet AS epithet,
 te.epithetID AS epithetID,
 te1.epithet AS epithet1,
 te1.epithetID AS epithetID1,
 te2.epithet AS epithet2,
 te2.epithetID AS epithetID2,
 te3.epithet AS epithet3,
 te3.epithetID AS epithetID3,
 te4.epithet AS epithet4,
 te4.epithetID AS epithetID4,
 te5.epithet AS epithet5,
 te5.epithetID AS epithetID5

from 
 herbarinput.tbl_tax_species ts

 left join herbarinput.tbl_tax_authors ta on ta.authorID = ts.authorID
 left join herbarinput.tbl_tax_authors ta1 on ta1.authorID = ts.subspecies_authorID
 left join herbarinput.tbl_tax_authors ta2 on ta2.authorID = ts.variety_authorID
 left join herbarinput.tbl_tax_authors ta3 on ta3.authorID = ts.subvariety_authorID
 left join herbarinput.tbl_tax_authors ta4 on ta4.authorID = ts.forma_authorID
 left join herbarinput.tbl_tax_authors ta5 on ta5.authorID = ts.subforma_authorID
 
 left join herbarinput.tbl_tax_epithets te on te.epithetID = ts.speciesID
 left join herbarinput.tbl_tax_epithets te1 on te1.epithetID = ts.subspeciesID
 left join herbarinput.tbl_tax_epithets te2 on te2.epithetID = ts.varietyID
 left join herbarinput.tbl_tax_epithets te3 on te3.epithetID = ts.subvarietyID
 left join herbarinput.tbl_tax_epithets te4 on te4.epithetID = ts.formaID
 left join herbarinput.tbl_tax_epithets te5 on te5.epithetID = ts.subformaID
 
 left join herbarinput.tbl_tax_status tst on tst.statusID = ts.statusID
 left join herbarinput.tbl_tax_rank tr on tr.tax_rankID = ts.tax_rankID
 left join herbarinput.tbl_tax_genera tg on tg.genID = ts.genID
 left join herbarinput.tbl_tax_authors tag on tag.authorID = tg.authorID
 left join herbarinput.tbl_tax_families tf on tf.familyID = tg.familyID
 left join herbarinput.tbl_tax_systematic_categories tsc on tf.categoryID = tsc.categoryID