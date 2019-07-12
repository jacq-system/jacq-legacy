var xajaxRequestUri="ajax/dev-searchServer.php";
var xajaxDebug=false;
var xajaxStatusMessages=false;
var xajaxWaitCursor=true;
var xajaxDefinedGet=0;
var xajaxDefinedPost=1;
var xajaxLoaded=false;
var clicked;

function xajax_getCollection(){return xajax.call("getCollection", arguments, 1);}
function xajax_getCountry(){return xajax.call("getCountry", arguments, 1);}
function xajax_getProvince(){return xajax.call("getProvince", arguments, 1);}

function isEmpty(s) {
  for (var i=0; i<s.length;i++) {
    var c = s.charAt(i);
    if ((c != ' ') && (c != '\n') && (c != '\t')) return false;
  }
  return true;
}

function check() {
  if (isEmpty(document.f.family.value) &&
      isEmpty(document.f.taxon.value) &&
      isEmpty(document.f.HerbNummer.value) &&
      isEmpty(document.f.Sammler.value) &&
      isEmpty(document.f.SammlerNr.value) &&
      isEmpty(document.f.source_name.value) &&
      isEmpty(document.f.collection.value) &&
      isEmpty(document.f.taxon_alt.value) &&
      isEmpty(document.f.series.value) &&
      isEmpty(document.f.geo_general.value) &&
      isEmpty(document.f.geo_region.value) &&
      isEmpty(document.f.nation_engl.value) &&
      isEmpty(document.f.provinz.value) &&
      isEmpty(document.f.CollNummer.value) &&
      isEmpty(document.f.series.value) &&
      !document.f.type.checked &&
      !document.f.images.checked) {
    var msg = "You haven't stated any search criteria.\n" +
              "So the searching may need a while!\n" +
              "Search anyway?\n";
    return confirm(msg);
  }
  return true;
}

/**
*   Document Ready
**/
$(document).ready(function(){
  /**
  *   Materialize CSS initializations
  **/
  $('.tabs').tabs();
  $('select').formSelect();
  $('.collapsible').collapsible();
  $('.modal').modal({startingTop: '0%', endingTop: '20%', opacity: 0.2, inDuration: 200, outDuration: 200});

  /**
  *   Menu routing
  **/
  $("#navbar .nav-content li>a").click(function(){
    window.location.href = $(this).attr("href");
  });

  /**
  *   Progress bars
  **/
  $(".progress").hide();
  
  /**
  *   Search Form Handling
  **/
  $("#checkbox_type").change(function(){
    $("[name='type']").val($(this).prop("checked") ? "only" : "all");
  });

  $("#checkbox_images").change(function(){
    $("[name='images']").val($(this).prop("checked") ? "only" : "all");
  });

  $("[name=source_name]").change(function(){
    xajax_getCollection(xajax.getFormValues('ajax_f',0,'source_name'));
    // xajax does not trigger ajaxComplete event and does not provide sth similar.
    setTimeout(function(){
      $("select[name=collection]").formSelect();
    }, 200);
  });

  $("[name=geo_general]").change(function(){
    xajax_getCountry(xajax.getFormValues('ajax_f',0,'geo_'));
    // xajax does not trigger ajaxComplete event and does not provide sth similar.
    setTimeout(function(){
      // Reinitialize text field.
      M.updateTextFields();
      // Reinitialize select.
      $("select[name=nation_engl]").formSelect();
      $("select[name=nation_engl]").change(function(){
        xajax_getProvince(xajax.getFormValues('ajax_f',0,'nation_engl'));
        // xajax does not trigger ajaxComplete event and does not provide sth similar.
        setTimeout(function(){
          // Reinitialize text field.
          M.updateTextFields();
          // Reinitialize select.
          $("select[name=provinz]").formSelect();
        }, 200);
      });
    }, 200);
  });

  $("[name=geo_region]").change(function(){
    xajax_getCountry(xajax.getFormValues('ajax_f',0,'geo_'));
    setTimeout(function(){
      // Reinitialize text field.
      M.updateTextFields();
      // Reinitialize select.
      $("select[name=nation_engl]").formSelect();
      $("select[name=nation_engl]").change(function(){
        xajax_getProvince(xajax.getFormValues('ajax_f',0,'nation_engl'));
        // xajax does not trigger ajaxComplete event and does not provide sth similar.
        setTimeout(function(){
          // Reinitialize text field.
          M.updateTextFields();
          // Reinitialize select.
          $("select[name=provinz]").formSelect();
        }, 200);
      });
    }, 200);
  });

  // Save default text input fields for reset.
  var countryInput =  $("#ajax_nation_engl").html();
  var provinceInput =  $("#ajax_provinz").html();

  $("#ajax_f_reset").click(function(){
    $("input[type='text']").val('')
    $("select").val('');
    $("select").formSelect();
    $("[name=synonym]").prop('checked', true);
    $("#checkbox_type").prop('checked', false);
    $("#checkbox_images").prop('checked', false);
    $("#ajax_nation_engl").html(countryInput);
    $("#ajax_provinz").html(provinceInput);
  });

  $("#ajax_f").submit(function(event){
    event.preventDefault();
    if(check()){
      $('#results').html('');
      $(".progress-search").show();
      var form_data = $('#ajax_f').serialize();
      form_data +="&submit=Search";
      $.ajax({
        url: "index.php",
        type: "POST",
        data: form_data, 
        success: function(result){
          $(".progress-search").hide();
          $('#results').html(result);
        }
      });
    }
  });
});


/**
*   Ajax Complete (Notice: Xajax does not trigger this event.)
**/
$(document).ajaxComplete(function( event, xhr, settings ) {
  /**
  *   Materialize CSS reinitializations
  **/
  $('select').formSelect();

  /**
  *   Progress bars
  **/
  $(".progress").hide();

  /**
  *   Register events for added elements
  **/
  if ( settings.url === "index.php" || settings.url.includes("results.php") ) {
    $(".pagination>li").click(function(){
      var page = $(this).data('value');
      if(page !== null){
        $(".progress-paging").show();
        $.ajax({
          url: "results.php?s=s&page="+page,
          type: "GET", 
          success: function(result){
            $('#results').html(result);
          }
        });
      } 
    });

    $(".resulttax").click(function(){
      var page = $(this).data('value');
      if(page !== null){
        $(".progress-paging").show();
        $.ajax({
          url: "/output/results.php?order=1&s=s&page="+page,
          type: "GET",
          success: function(result){
            $('#results').html(result);
          }
        });
      }
    });

    $(".resultcol").click(function(){
      var page = $(this).data('value');
      if(page !== null){
        $(".progress-paging").show();
        $.ajax({
          url: "/output/results.php?order=2&s=s&page="+page,
          type: "GET",
          success: function(result){
            $('#results').html(result);
          }
        });
      }
    });
    $("select[name=ITEMS_PER_PAGE]").change(function(event){
      $('#results').html('');
      $(".progress-search").show();
      $.ajax({
        url: "results.php?ITEMS_PER_PAGE="+$(this).val(),
        type: "GET",
        success: function(result){
          $('#results').html(result);
        }
      });
    });
  }
});
    