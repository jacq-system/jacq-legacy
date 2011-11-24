function PostIt(method, params, callback){
	$.post(
		dinit['AjaxUrl'],
		{'method': method, 'params':params},
		function(data){
			//p(data,3);
			if(data.ob!=undefined){
				$("#dwarning").html("Some not fetched error occured: "+data.ob);
				$("#dialog-warning").dialog({
					resizable: false,
					modal: false,
					buttons: {"OK": function() {$( this ).dialog( "close" );}}
				});
			}
			
			if(data.info!=undefined){
				$("#dinformation").html("Some info: "+data.info);
				$("#dialog-information").dialog({
					resizable: false,
					modal: false,
					buttons: {"OK": function() {$( this ).dialog( "close" );}}
				});
			}
			
			if(data.error!=undefined || data.res==undefined ){
				if(data.error!=undefined){
					$("#derror").html(data.error);
				}else if(data.res==undefined){
					$("#derror").html("res undefined");
				}else{
					$("#derror").html("error");
				}
				
				$("#dialog-error").dialog({
					resizable: false,
					modal: false,
					buttons: {"OK": function() {$( this ).dialog( "close" );}}
				});
				return;
				
			}
			
			callback(data.res);
		}, 
		'json'
	);
}