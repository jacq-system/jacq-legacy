function round(value,precision) {
    var x = Math.pow(10,precision);
    return (Math.round(value*x)/x);
}


function QuToDezKoord(Quadrant, dezPrecision = null) {
    var koordOutput = {dezLong: null, dezLat: null}
    var dezLat = 0;
    var dezLong = 0;
    Quadrant = Quadrant.trim();    
    Quadrant = Quadrant.replace(" ","");
    Quadrant = Quadrant.replace("-","/");
        console.log(Quadrant);
    
   //var QuadrantValid = Quadrant.match(/^([0-9]{4}|[0-9]{6})\/[1-4]{1}$/) // the ground field can have 4 or 6 digits
      
    // if only ground field is given => appends "/0" to the string
    if (Quadrant.match(/^([0-9]{4}|[0-9]{6})$/)) Quadrant += "/0";
        
    
    if ( Quadrant.match(/^([0-9]{4}|[0-9]{6})\/[0-4]{1}$/)) { // checks if ground field has 4 digits and quadrant one digit
        var QuArr = Quadrant.split("/")
        console.log(QuArr);
        // Latitude
       // (5+(45/60)+(((CONVERT(SUBSTRING(gf,3,2),UNSIGNED INTEGER)*10)/60)-(2.5/60)))
         if (QuArr[0].match(/^[0-9]{4}/) && QuArr[1].match(/^(1|3)$/)) { dezLat = 5+(45/60)+(((parseInt(QuArr[0].substr(2,2))*10)/60)-(2.5/60)); }
         if (QuArr[0].match(/^[0-9]{4}/) && QuArr[1].match(/^(2|4)$/)) { dezLat = 5+(45/60)+(((parseInt(QuArr[0].substr(2,2))*10)/60)+(2.5/60)); }
         if (QuArr[0].match(/^[0-9]{4}/) && QuArr[1].match(/^(0)$/))   { dezLat = 5+(45/60)+ ((parseInt(QuArr[0].substr(2,2))*10)/60); }
         if (QuArr[0].match(/^[0-9]{6}/) && QuArr[1].match(/^(1|3)$/)) { dezLat = 5+(45/60)+(((parseInt(QuArr[0].substr(3,3))*10)/60)-(2.5/60)); }
         if (QuArr[0].match(/^[0-9]{6}/) && QuArr[1].match(/^(2|4)$/)) { dezLat = 5+(45/60)+(((parseInt(QuArr[0].substr(3,3))*10)/60)+(2.5/60)); }
         if (QuArr[0].match(/^[0-9]{6}/) && QuArr[1].match(/^(0)$/))   { dezLat = 5+(45/60)+ ((parseInt(QuArr[0].substr(3,3))*10)/60); }
        
        // Longitude
        if (QuArr[0].match(/^[0-9]{4}/) && QuArr[1].match(/^(1|2)$/)) { dezLong = 55+(57/60)-(((parseInt(QuArr[0].substr(0,2))*6)/60)-(1.5/60)); }
        if (QuArr[0].match(/^[0-9]{4}/) && QuArr[1].match(/^(3|4)$/)) { dezLong = 55+(57/60)-(((parseInt(QuArr[0].substr(0,2))*6)/60)+(1.5/60)); }
        if (QuArr[0].match(/^[0-9]{4}/) && QuArr[1].match(/^(0)$/))   { dezLong = 55+(57/60)- ((parseInt(QuArr[0].substr(0,2))*6)/60); }
        if (QuArr[0].match(/^[0-9]{6}/) && QuArr[1].match(/^(1|2)$/)) { dezLong = 55+(57/60)-(((parseInt(QuArr[0].substr(0,3))*6)/60)-(1.5/60)); }
        if (QuArr[0].match(/^[0-9]{6}/) && QuArr[1].match(/^(3|4)$/)) { dezLong = 55+(57/60)-(((parseInt(QuArr[0].substr(0,3))*6)/60)+(1.5/60)); }
        if (QuArr[0].match(/^[0-9]{6}/) && QuArr[1].match(/^(0)$/))   { dezLong = 55+(57/60)- ((parseInt(QuArr[0].substr(0,3))*6)/60); }
        
        
        if (dezPrecision) { // rounds coordinates to given precision
            koordOutput.dezLong = round(dezLong,dezPrecision); 
            koordOutput.dezLat = round(dezLat,dezPrecision);
        }
        else {
            koordOutput.dezLong = dezLong;
            koordOutput.dezLat = dezLat;
        }
    }
    
    //  document.getElementById("test").innerHTML = dezLong;
     // document.getElementById("test1").innerHTML = dezLat;
      
      console.log(koordOutput);
      return koordOutput;
}


function DezKoordToDegMinSec(dezLong,dezLat,secPrecision=1) {
     var koordOutput = {degLong: null,
                        minLong: null,
                        secLong: null,
                        cardLong: null,
                        degLat: null,
                        minLat: null,
                        secLat: null,
                        cardLat: null}
        
        // if coordinates entered as string => replace comma seperator ...
        if (typeof dezLong === 'string') {
            dezLong = dezLong.replace(",",".");
            dezLong = parseFloat(dezLong);
        }
        
        if (typeof dezLat === 'string') {
            dezLat = dezLat.replace(",",".");        
            dezLat = parseFloat(dezLat);
        }
        

    if (dezLong) {
       if (dezLong < 0) { koordOutput.cardLong = 'S' }
       else { koordOutput.cardLong = 'N' }       
       koordOutput.degLong = Math.trunc(dezLong);
       koordOutput.minLong = Math.trunc((dezLong-koordOutput.degLong)*60);
       koordOutput.secLong = (((dezLong-koordOutput.degLong)*60)-koordOutput.minLong)*60;
       koordOutput.secLong = round(koordOutput.secLong,secPrecision);
       // if rounded value > 59 => correct (other) values:
       if (koordOutput.secLong == 60) {
           koordOutput.secLong = 0;
           if (koordOutput.minLong > 59) { koordOutput.minLong += 1; }
           else {
               koordOutput.minLong = 0;
               koordOutput.degLong += 1;
           }
       }
    }
    
    if (dezLat) {
       if (dezLat < 0) { koordOutput.cardLat = 'W' }
       else { koordOutput.cardLat = 'E' }
       koordOutput.degLat = Math.trunc(dezLat);
       koordOutput.minLat = Math.trunc((dezLat-koordOutput.degLat)*60);
       koordOutput.secLat = (((dezLat-koordOutput.degLat)*60)-koordOutput.minLat)*60;       
       koordOutput.secLat = round(koordOutput.secLat,secPrecision);
    // if rounded value > 59 => correct (other) values:    
       if (koordOutput.secLat == 60) {
           koordOutput.secLat = 0;
           if (koordOutput.minLat > 59) { koordOutput.minLat += 1; }
           else {
               koordOutput.minLat = 0;
               koordOutput.degLat += 1;
           }
       }
           
    }
       
    console.log(koordOutput);
    return koordOutput;
}


function DegMinSectoDezKoord(koordDMS,dezPrecision) {
    var koordOutput = {dezLong: null, dezLat: null}
    var koordSplitArr1 = null;
    var cardLat = 1;
    var cardLong = 1;
    var degLong = null,
    var minLong = null,
    var secLong = null,
    var cardLong = null,
    var degLat = null,
    var minLat: null,
    var secLat: null,
    var cardLat: null}
    
   // console.log(koordDMS);
   // koordDMS = decodeURIComponent(koordDMS); // should be mabe replaced with something better to decode html-entities ...
    console.log(koordDMS);
    
    koordDMS = koordDMS.toUpperCase();
   
    koordDMS = koordDMS.replace("O","E");
    koordDMS = koordDMS.replace("EAST","E");
    koordDMS = koordDMS.replace("OST","E");
    koordDMS = koordDMS.replace("NORD","N");
    koordDMS = koordDMS.replace("NORTH","N");
    koordDMS = koordDMS.replace("SOUTH","S");
    koordDMS = koordDMS.replace("SÜD","S");
    koordDMS = koordDMS.replace("SUED","S");
    koordDMS = koordDMS.replace("WEST","W");
    koordDMS = koordDMS.replace("DEG","°");
    koordDMS = koordDMS.replace('"','″');
    koordDMS = koordDMS.replace("´´","″");
    koordDMS = koordDMS.replace("``","″");
    koordDMS = koordDMS.replace("′′","″");
    koordDMS = koordDMS.replace("‶","″");
    koordDMS = koordDMS.replace("ʺ","″");
    koordDMS = koordDMS.replace("'","′");
    koordDMS = koordDMS.replace("´","′");
    koordDMS = koordDMS.replace("`","′");
    koordDMS = koordDMS.replace("‵","′");
    koordDMS = koordDMS.replace("ʹ","′");
    koordDMS = koordDMS.replace(",",".");
    koordDMS = koordDMS.replace(" ","");
    koordDMS = koordDMS.replace(" ","");
    koordDMS = koordDMS.replace(" ","");
    koordDMS = koordDMS.replace(" ","");
    koordDMS = koordDMS.replace("E.","E");
    koordDMS = koordDMS.replace(".E","E");
    koordDMS = koordDMS.replace("N.","N");
    koordDMS = koordDMS.replace(".N","N");
    koordDMS = koordDMS.replace("S.","S");
    koordDMS = koordDMS.replace(".S","S");
    koordDMS = koordDMS.replace("W.","W");
    koordDMS = koordDMS.replace(".W","W");
    koordDMS = koordDMS.replace("′′","″");
    console.log(koordDMS);
   
    if (koordDMS.match(/^[NS]{0,1}([0-9]{1,3}°[0-9]{1,2}′[0-9]{1,2}[.0-9]{0,}″)[NS]{0,1}[EW]{0,1}([0-9]{1,3}°[0-9]{1,2}′[0-9]{1,2}[.0-9]{0,}″)[EW]{0,1}$/)) {
        console.log('DGMS - N vorne');
        if (koordDMS.match(/N/)) { cardLat = 1; }
        if (koordDMS.match(/S/)) { cardLat = -1; }
        if (koordDMS.match(/E/)) { cardLong = 1; }
        if (koordDMS.match(/W/)) { cardLong = -1; }
        console.log(cardLat);
        console.log(cardLong);
       
        koordDMS = koordDMS.replace("E","");
        koordDMS = koordDMS.replace("W","");
        koordDMS = koordDMS.replace("N","");
        koordDMS = koordDMS.replace("S","");
        
        koordSplitArr1 = koordDMS.split("″");
        console.log(koordSplitArr1);
        
        
    }    
    else if (koordDMS.match(/^[EW]{0,1}([0-9]{1,3}°[0-9]{1,2}′[0-9]{1,2}[.0-9]{0,}″)[EW]{0,1}[NS]{0,1}([0-9]{1,3}°[0-9]{1,2}′[0-9]{1,2}[.0-9]{0,}″)[NS]{0,1}$/)) {
        console.log('DGMS - N hinten');
    }
    
    
   
       
    console.log(koordOutput);
    return koordOutput;
}


