

function showImage(sel) {
	target = config['HERBARIMAGEURL']+'/image.php?filename='+sel+"&method=show";
	MeinFenster = window.open(target,"imgBrowser");
	MeinFenster.focus();
}

function showImageObs(sel) {
	target = config['HERBARIMAGEURL']+'/image.php?filename='+sel+"&method=show&obs=1";
	MeinFenster = window.open(target,"imgBrowser");
	MeinFenster.focus();
}

function showIiif(target) {
	MeinFenster = window.open(target,"imgBrowser");
	MeinFenster.focus();
}
/*
function editCollector(sel) {
	target = "editCollector.php?sel=" + encodeURIComponent(sel.value);
	MeinFenster = window.open(target,"editCollector","width=350,height=130,top=50,left=50,scrollbars=yes,resizable=yes");
	MeinFenster.focus();
}
function editCollector2(sel) {
	target = "editCollector2.php?sel=" + encodeURIComponent(sel.value);
	MeinFenster = window.open(target,"editCollector2","width=500,height=130,top=50,left=50,scrollbars=yes,resizable=yes");
	MeinFenster.focus();
}
function searchCollector() {
	MeinFenster = window.open("searchCollector.php","searchCollector","scrollbars=yes,resizable=yes");
	MeinFenster.focus();
}
function searchCollector2() {
	MeinFenster = window.open("searchCollector2.php","searchCollector2","scrollbars=yes,resizable=yes");
	MeinFenster.focus();
}
function editSpecies(sel) {
	target = "editSpecies.php?sel=" + encodeURIComponent(sel.value);
	MeinFenster = window.open(target,"Species",makeOptions());
	MeinFenster.focus();
}
function editVoucher() {
	target = "editVoucher.php?sel=" + document.f.voucher.options[document.f.voucher.selectedIndex].value;
	MeinFenster = window.open(target,"editVoucher","width=500,height=150,top=50,left=50,scrollbars=yes,resizable=yes");
	MeinFenster.focus();
}
function editSeries() {
	target = "editSeries.php?sel=" + document.f.series.options[document.f.series.selectedIndex].value;
	MeinFenster = window.open(target,"editSeries","width=500,height=150,top=50,left=50,scrollbars=yes,resizable=yes");
	MeinFenster.focus();
}
function editSpecimensTypes(sel) {
	target = "listSpecimensTypes.php?ID=" + encodeURIComponent(sel);
	MeinFenster = window.open(target,"listSpecimensTypes","width=800,height=400,top=50,left=50,scrollbars=yes,resizable=yes");
	MeinFenster.focus();
}

function editLabel(sel) {
	target = "editLabel.php?sel=<" + sel + ">";
	MeinFenster = window.open(target,"Labels",makeOptions());
	MeinFenster.focus();
}*/
