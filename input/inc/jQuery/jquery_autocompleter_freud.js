	function p(objarray){
		return alert(pr(objarray));
	}

	function p(objarray,tiefe){
		return alert(pr(objarray,tiefe));
	}

	function pr(objarray){
		return pr(objarray,4);
	}

	function pr(objarray,tiefe){
		return print_r1(objarray,'','',0,tiefe);
	}

	function print_r1(objarray,string,ebene,tiefe,maxtiefe){
		for(i in objarray){
			try{
				if(typeof(objarray[i])=='object' && tiefe<maxtiefe){

					string=print_r1(objarray[i],string,ebene+'['+i+']',tiefe+1,maxtiefe);

				}else{
					//if(typeof(objarray[i])!='function'){
						string+=ebene+'['+i+']='+objarray[i]+"\n" ;
					//}
				}
			}catch(e){}
		}
		return string;
	}


$.ajaxSetup({
	error:function(x,e){
		if(x.status==0){alert('Taxamatch System Information:\nYou are offline!!\n Please Check Your Network.');
		}else if(x.status==404){alert('Taxamatch System Information:\n Requested URL not found.');
		}else if(x.status==500){alert('Taxamatch System Information:\nInternel Server Error.');
		}else if(e=='parsererror'){alert('Taxamatch System Information:\nError.\nParsing JSON Request failed.');
		}else if(e=='timeout'){alert('Taxamatch System Information:\nRequest Time out.');
		}else {alert('Taxamatch System Information:\nUnknow Error.\n'+x.responseText);
		}
	}
});
	
// Select FIRST, taken from web...
(function($){	 
	$(".ui-autocomplete-input").live("autocompleteopen", function() {
		var autocomplete = $(this).data("autocomplete"),
		menu = autocomplete.menu;
	 
		if (!autocomplete.options.selectFirst) {
			return;
		}
		menu.activate($.Event({ type: "mouseenter" }), menu.element.children().first());
	});
}(jQuery));

var ACFreudConfig=[];

function ACFreudInit(){
	
	$.each(ACFreudConfig,function(index, conf) { 
		ACFreudPrepare(conf[0],conf[1],conf[2],conf[3],conf[4],conf[5]);
	});
}

// mustmach: 0 => don't need to match, symbol: !, mustmatch=1: => must match, orange + !,
//  mustmatch=2: => must much + "0" allowed
// Autocompleter
function ACFreudPrepare(serverScript1,nam,startval,mustMatch,acdone,fullfocus,minlength){

	//alert(serverScript+', '+nam+', '+startval+', '+mustMatch+', '+fullfocus+', '+minlength); 
	var $at=$('#ajax_'+nam);
	var $ati=$('#'+nam+'Index');
	
	$at.autocomplete({
		create: function(event, ui) {
			$at.data('autocomplete').requestIndex=0;
		},
		serverScript: serverScript1,
		source: function( request, response ) {
			/*
			// original: 
			// used to prevent race conditions with remote data sources
			var requestIndex = 0;
			//=> doesn't work, if we want to preload all at once... !!!
			
			this.source = function( request, response ) {
			if ( self.xhr ) {
				self.xhr.abort();
			}
			self.xhr = $.ajax({
				url: url,
				data: request,
				dataType: "json",
				autocompleteRequest: ++requestIndex,
				success: function( data, status ) {
					if ( this.autocompleteRequest === requestIndex ) {
						response( data );
					}
				},
				error: function() {
					if ( this.autocompleteRequest === requestIndex ) {
						response( [] );
					}
				}
			});
			*/
			if ( $at.data('autocomplete').xhr ) {
				$at.data('autocomplete').xhr.abort();
			}
			$at.data('autocomplete').xhr = $.ajax({
				url: this.options.serverScript,
				data: request,
				dataType: "json",
				autocompleteRequest: ++$at.data('autocomplete').requestIndex,
				success: function( data, status ) {
					if ( this.autocompleteRequest === $at.data('autocomplete').requestIndex) {
						response( data );
					}
				},
				error: function() {
					if ( this.autocompleteRequest === $at.data('autocomplete').requestIndex ) {
						response( [] );
					}
				}
			});
		},
		search: function(){
			$ati.val('');
		},
		change: function(event, ui) {
			if($ati.val()==''){
				if(mustMatch==2){
					if($at.val()!='0'){
						$at.addClass('wrongItem');
					}
				}else if(mustMatch==1){
					$at.addClass('wrongItem');
				}else if(mustMatch==0){
					$at.addClass('newItem');
				}
			}
		},
		select: function(event, ui){
			if(ui.item.id){
				$ati.val(ui.item.id);
				$at.val(ui.item.value).removeClass('wrongItem newItem');
			}
		},
		open: function(event, ui) {
			if($at.autocomplete("option", "populate")=='1'){
				$at.autocomplete("option","populate","0");
				$at.autocomplete("option","hook",null);
				$at.autocomplete( "close" );
			}
		},
		delay:100,
		minLength:minlength,
		selectFirst: (mustMatch==1)?true:false
	}).data('autocomplete')._renderItem=function(ul, item){
		if($at.autocomplete("option", "populate")=='1'){
			$ati.val(item.id);
			$at.val(item.value);
		}
		return $('<li></li>')
		.data('item.autocomplete', item)
		.append('<a' + ((item.color) ? ' style="background-color:' + item.color + ';">' : '>') + item.label.replace(new RegExp('('+this.term+')',"ig"),'<b>$1</b>') + '</a>')
		.appendTo(ul);
		
	}

	if(fullfocus==1){
		$at.bind({
			click: function() {
				if($(this).data.toggle!='1'){
					$(this).data.toggle='1';
					$(this).select();
				}
			},
			focusout: function() {
				$(this).data.toggle='0';
			}
		});
	}
	if(acdone==0 && (startval!='' && startval!='0' && startval!=0)){
		if(mustMatch==1 && $at.val()!=''){
			$at.addClass('wrongItem');
		}
		searchID(nam, startval);
	}else{
		if(mustMatch==0 && $at.val()!='' && $ati.val()==''){
			$at.addClass('newItem');
		}else if(mustMatch==1 && $at.val()!='' && $ati.val()==''){
			$at.addClass('wrongItem');
		}else if(mustMatch==2 && $at.val()!='' && $at.val()!='0' && $ati.val()==''){
			$at.addClass('wrongItem');
		}
	}

}
function searchID(nam, id){
	$('#ajax_'+nam).autocomplete("option","populate","1").autocomplete("search",'<'+id+'>');
}