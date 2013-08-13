function editMapping(){
	$("#editMapping").dialog( "open" );
}

$(function() {
	ACFreudInit();

	$( "#editMapping" ).dialog({
		autoOpen: false,
		height: 800,
		width: 700,
		modal: true,
		bgiframe: true,
		buttons: {
			"Close": function() {
				$( this ).dialog( "close" );
			}
		}
	});
	
	$('#insertLineForm').submit(function(){
		saveMapLines();
		return false;
    });
	$('#searchLineForm').submit(function(){
		searchMapLines();
		return false;
    });
	
	$("#editAccordion").multiOpenAccordion({
		active: [0, 1],
	});
	
	newPaginator(0);
	searchMapLines();
	
	addMapLine('insertLineTable',1,0,0);
	addMapLine('insertLineTable',1,0,0);
				
});

function searchMapLines(searchstringVal){
	if(searchstringVal==undefined){
		if(typeof createMapSearchstring == 'function') { 
			searchString=createMapSearchstring(); 
		}else{
			searchString='';
		}
	}else{
		searchString=searchstringVal;
	}
	newsearch=true;
	pageselectCallback(0);
}

function pageselectCallback(page_index, jq){
	$('#PageInfo2').html('Loading <img align="absmiddle" src="inc/jQuery/css/loading2.gif"> ');
				
	$.ajax({
		type: 'POST',
		dataType: 'json',
		url: serverUrl,
		data: 'function=LoadMapLines&pageIndex='+page_index+searchString+serverParams,
		/*timeout: 1000,*/
		success: function(msg){
			removeAllSearchMapLines();
			$.each(msg.syns,function(index, value) {
				addMapLine('searchLineTable',2,value[0],value[2],value[1],value[3]);
			});
			$('#PageInfo2').html('');
			if(newsearch){
				newPaginator(msg.cf);
				$('#PageInfo').html('all: '+msg.ca+', found: '+msg.cf);
				newsearch=false;
			}
		}
	});
}

function saveMapLines(){
	$('#PageInfo2').html('Saving <img align="absmiddle" src="inc/jQuery/css/loading2.gif"> ');
	
	$.ajax({
		type: 'POST',
		url: serverUrl,
		dataType: 'json',
		data: $('#insertLineForm').serialize()+'&function=SaveMapLines'+serverParams,
		/*timeout: 1000,*/
		success: function(msg){
			$('#PageInfo2').html('');
			s='';
			s1='';
			if(msg.error && msg.error.length>0){
				$.each(msg.error,function(index, value) {
					if(value[3]==1){
						s+=value[1]+'=>'+value[2]+': already in db<br>';
					}else{
						s+=value[1]+'=>'+value[2]+': '+value[3]+'<br>';
					}
				});
			}
			if(msg.successx && msg.successx.length>0){
				s1= msg.successx.length+/*' of '+ (msg.successx.length+msg.error.length) +*/' sucessfully added.<br>';
				$.each(msg.successx,function(index, value) {
					s+=value[1]+'=>'+value[2]+':okay!<br>';
				});
			}
			
			if(s!=''){
				$("#dinformation").html(s1+'<br>'+s);
				$("#dialog-information").dialog({
					resizable: false,
					modal: false,
					width:400,
					buttons:[
						{text: "OK",click: function(){$(this).dialog("close");}}
					]
				});
			}
			
			$('#insertLineTable').find('tr:gt(0)').remove();
			addMapLine('insertLineTable',1,0,0);
			addMapLine('insertLineTable',1,0,0);
			
			searchMapLines('');
		}
	});
}

function deleteSearchedLine2(oid){	
	if(COLS==2){
		leftID=$('#acmap_l_'+oid+'Index').val();
	}
	rightID=$('#acmap_r_'+oid+'Index').val();
	$.ajax({
		type: 'POST',
		dataType: 'json',
		url: serverUrl,
		data: 'function=RemoveMapLine&leftID='+leftID+'&rightID='+rightID+serverParams,
		/*timeout: 1000,*/
		success: function(msg){
			if(msg.success==1){
				removeInputLine(oid);
				alert('deleted');
			}
                        else if( msg.text != "" ) {
                            alert(msg.text);
                        }
		}
	});
			
}

function newPaginator(numentries1){
	$("#Pagination").pagination(numentries1, {
		num_edge_entries: 2,
		num_display_entries: 8,
		callback: pageselectCallback,
		items_per_page:ITEMSPERPAGE
	});
}

function removeAllSearchMapLines(){
	 $('#searchLineTable').find('tr:gt(0)').remove();
}

function deleteSearchedLine(oid){
	left=(COLS==2)?($('#ajax_acmap_l_'+oid).val()+'=>'):'';
	right=$('#ajax_acmap_r_'+oid).val();
	$('#dialog-confirm-t').html(' '+left+right+' ');
	$( "#dialog-confirm" ).dialog({
		resizable: false,
		height:280,
		width:280,
		buttons:{
			"Delete item": function() {
				deleteSearchedLine2(oid);
				$( this ).dialog( "close" );
			},Cancel: function() {
				$( this ).dialog( "close" );
		}},
		modal: false,
	});
}

function addMapLine(idnam,idtype,leftId,rightId,leftName,rightName){
	var code=getACTableCode(idtype,x);
	$('#'+idnam+' tr:last').after(code);

	if(COLS==2){
		if(leftName!=undefined && leftName!=''){
			$('#acmap_l_'+x+'Index').val(leftId);
			$('#ajax_acmap_l_'+x).val(leftName).removeClass('wrongItem');
		}
	}
	if(rightName!=undefined && rightName!=''){
		$('#acmap_r_'+x+'Index').val(rightId);
		$('#ajax_acmap_r_'+x).val(rightName).removeClass('wrongItem');
	}

	
	if(idtype==1){
		if(COLS==2){
			ACFreudPrepare(serverACL,'acmap_l_'+x,((!isNaN(leftId) && leftName==undefined)?leftId:'0'),2,0,1,2);
		}
		ACFreudPrepare(serverACR,'acmap_r_'+x,((!isNaN(rightId) && rightName==undefined)?rightId:'0'),2,0,1,2);

		$('#ajax_acmap_r_'+x).focus(function(){
		//	if($('#'+idnam+' tr:last input:text:first').val().length>0 || ( COLS==1 && $('#'+idnam+' tr:nth-child('++') input:text:first').prev().val().length>0) ){
				addMapLine(idnam,idtype,0,0,'','');
			//}
		});
	}
	x++;
}

function removeInputLine(oid){
	if($('#insertLineTable tr').length>2){
			$('#acmap_tr_'+oid).remove();
	}
}