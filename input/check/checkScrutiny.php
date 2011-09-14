<?PHP
$path="../inc/";
//$path="../develop/input/inc/";
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>scrutiny.Match</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <style type="text/css">
    #logout { position:absolute; top:1em; right:1em; width:5em; }
    #info { position:absolute; top:1em; left:1em }
.trtop td{
	border-top:1px solid #000

}
.trtop2 td{
	height:50px;
}
.trtop3 td{
	border-top:1px solid #000;
	margin-top:20px;
	 background-color:#d0d0d0;
}
.trtop4 td{
	border-top:1px solid #000;
	 background-color:#808080;
}
.at td{
 background-color:#abe7a8;
}
.at{
 background-color:#abe7a8;
}
.at2 td{
 background-color:#d0d0d0;
}
.highl{
 background-color:#dcf4d9;
}

.selectedtr td{
 background-color:#f5eea8;
}

.inpyear{
 background-color:#f5eea8;
}
  </style>
<script src="<?PHP echo $path;?>/jQuery/jquery.min.js" type="text/javascript"></script>
<script type='text/javascript' charset='UTF-8'>

$(document).ready(function() {
	$('.hoverd').click(function(event) {
		if (event.target.type !== 'radio') {
			$(':radio', this).trigger('click');
		}
	}).hover(
		function(){ $(this).find('td').css('background-color','#ffff99');},
		function(){ $(this).find('td').css('background-color','');}
	);
	
	$('.result').dblclick(function(event) {
		window.location.hash='jump_'+$(this).attr('jump');
	})
	
	$('*:radio').bind('click change', function(){

		if($(this).is(':checked')){
			$(this).parent().parent().addClass('selectedtr');
			if(jQuery.data(this,'c')=='1'){
				jQuery.data( this,'c', '0' );
				window.location.hash='jump_'+$(this).attr('jump');
			}else{
				jQuery.data( this,'c', '1');
			}
			
			
		}
		//nput[name=gender]:radio
		$('input[name='+$(this).attr('name')+']:radio:not(:checked)').each(function() { 
			//alert('d'+$(this).val());
			$(this).parent().parent().removeClass('selectedtr');
		});
	});

});

</script>
</head>

<body>
<form Action="<?PHP echo $_SERVER['PHP_SELF'];?>" Method="POST" name="f" id="sendto">

<?php 

error_reporting(E_ALL);
 ini_set("display_errors", TRUE);
// Todo, 3.8.2011!
// ghomolka

require("$path/variables.php");// develop/input/
require("$path/internMDLDService.php");// develop/input/

if (!mysql_connect($_CONFIG['DATABASE']['INPUT']['host'], $_CONFIG['DATABASE']['INPUT']['readonly']['user'],$_CONFIG['DATABASE']['INPUT']['readonly']['pass']) || !mysql_select_db($_CONFIG['DATABASE']['INPUT']['name'])){
	echo 'no database connection';
	exit;
}
mysql_query("SET character set utf8"); //<= do not use it!






$str=<<<EOF
1	Johnson, D. M. & Murray, N. A. in prep.
2	Rainer, H. in prep.
3	Erkens, R. et al. in prep.
4	He, P. & Chatrou, L. W. 1998
5	Maas, P. J. M. et al. 2003
6	Maas, P. J. M.et al. 1992
7	Maas, P. J. M. & Westra, L. Y. T. 1984 , 1985
8	Kral, R. 1960
9	Johnson, D. M. & Murray, N. A. 1995
10	Maas, P. J. M. et al. in prep.
11	Pirie, M. D. 2005
12	Murray, N. A. 1993
13	Schatz, G. E. in prep.
14	Maas, P. J. M. et al. 1993
15	Fries, R. E. 1934
16	Oliveira, J. & Sales, M. F. 1999
17	Chatrou, L. W. 1998
18	Junikka, J. in prep.
19	Aristeguieta, L. 1969
20	Maas, P. J. M. & Westra, L. Y. T. 2003
21	Fries, R. E. 1936
22	Westra, L. Y. T. 1985
23	Annonaceae WorkingGroup, 2006
24	Boutique, R. 1951
25	Keßler, P. J. A. 1996
26	Ghesquiere, J. 1939
27	Le Thomas, A. 1972
28	Heusden, E. C. van 1994b
29	Setten, A. K. van & Maas, P. J. M. 1990
30	Le Thomas, A. 1969
31	Vollesen, K. 1980a
32	Verdcourt, B. 1971
33	Saunders, R. M. K. et al. in prep.
34	Le Thomas, A. 1968
35	Le Thomas, A. 1965
36	Fries, R. E. 1959
37	Saunders, R. M. K. 2003; Wang, R. J. & Saunders, R. M. K. in press
38	Utteridge, T. M. A. 2000
39	Sinclair, J. 1955
40	Nurmawati, S. 2003
41	Rauschert, S. 1982
42	Kenfack, D. et al. 2003
43	Johnson, D. M. 1989
44	Wang, R. J. & Saunders, R. M. K. in prep.
45	Fries, R. E. 1955
46	Airy-Shaw, A. K. 1939
47	Jessup, L. W. - ex APNI
48	Steenis, C. G. J. van 1964
49	Verdcourt, B. 1969
50	Heusden, E. C. van 1994a
51	Mols, J. B. & Keßler, P. J. A. 2003
52	Heijden, E. van der & Keßler, P. J. A. 1990
53	Backer, C. A. 1911
54	Heusden, E. C. van 1994b, 1996
55	Bân, N. T. 1974
56	Leonardía, A. A. P. & Keßler, P. J. A. 2001
57	Verdcourt, B. 1970
58	Mols, J. B. & Keßler, P. J. A. 2000b
59	Okada, H. & Ueda, K. 1984
60	Vollesen, K. 1980b
61	Diels, F. L. E. 1912
62	Keßler, P. J. A. 1988
63	Steenis, C. G. J. van 1948
64	Su, Y. C. F. et al. 2006
65	Mols, J. B. & Keßler, P. J. A. 2000a
66	Su, Y. C. F. & Saunders, R. M. K. 2006
67	Saunders, R. M. K. in prep.
68	Rainer, H. 2001
69	Maas, P. J. M. et al. 1994
70	Heusden, E. C. van 1997a
71	Verdcourt, B. 1986
72	Heusden, E. C. van 1997b
73	Verdcourt, B. 1956; Le Thomas, A. 1969
74	Saunders, R. M. K. et al. 2004
75	Okada, H. 1996
EOF;

$a=explode("
",$str);

$lines=array();
foreach($a as $b){
	$t=explode("	",$b);
	$lines[$t[0]]=$t[1];
}



if(isset($_POST['update'])){

	$res=array();
	
	foreach($_POST as $k=>$v){
	
		if(strpos($k,'check_')!==false){
			$p=explode('_',$k);
			$result[$p[1]][$p[2]]=$v;
		}
	}
echo<<<EOF
<br>

<b>Results</b><br>
<hr>
<table cellspacing="0" cellpadding="0" id="rowclick1" border="0">
<tr><td  width="70px">intern ID</td><td width="400px">String</td><td width="100px">AuthorID</td><td  width="100px">year</td>
</tr>
EOF;

	foreach($result as $id=>$obj){
		
		if(isset($lines[$id])){
			
			$a=isset($obj[0])?$obj[0]:'';
			$b=isset($_POST['year_'.$id.'_0'])?$_POST['year_'.$id.'_0']:'';
			
			
			echo "<tr class=\"result\" jump=\"{$id}\"><td>{$id}</td><td>".$lines[$id]."</td><td>{$a}</td><td>{$b}</td></tr>";
		}
	}
	echo<<<EOF
</table>
<hr>
<br><br>
<b>Input</b><br>
<hr><br>
EOF;

}


$res2=array();
foreach($lines as $id=>$b){

	$res=array('a1'=>'','a2'=>'','y'=>'');
	$doublea=false;

	$r=$b;
	$res['a']=$b;
	
	if($a=strpos($b,"&")!==false){

		$doublea=true;
		$res['a1']=strstr2($b,"&",1);
		$r=strstr2($b,"&",2);
	}
	
	$found=false;
	foreach(array('in prep','in press','ex APNI') as $search){
		
		if(strpos($r,$search)!==false){
			

		
			$aut=strstr2($r,$search,1);
			if($doublea){
				$res['a2']=$aut;
			}else{
				$res['a1']=$aut;
			}
			
			$res['y']=strstr2($r,$search);
			
			$found=true;
			break;
		}
	}
	if(!$found){
		preg_match('/(\d+)/', $r, $matches,PREG_OFFSET_CAPTURE);
	
		if(isset($matches[0][0])){
		
			$aut=substr($r,0,$matches[0][1]);
			$res['y']=substr($r,$matches[0][1]);
			
			if($doublea){
				$res['a2']=$aut;
			}else{
				$res['a1']=$aut;
			}
			
			//print_r($matches);
			
		}else{
			$aut="???".$r;
			if($doublea){
				$res['a2']=$aut;
			}else{
				$res['a1']=$aut;
			}
			//$res['y']=$r;
		}
	}
	
	if(isset($res['a1']) && strpos($res['a1'],",")!==false){
		$a1=explode(",",$res['a1']);
		$res['a1']=array('fname'=>trim($a1[0]),'abr'=>trim($a1[1]));
	}else{
		$res['a1']=array('fname'=>'','abr'=>'');
	}

	if(isset($res['a2']) && strpos($res['a2'],",")!==false){
		$a2=explode(",",$res['a2']);
		$res['a2']=array('fname'=>trim($a2[0]),'abr'=>trim($a2[1]));
	}else{
		$res['a2']=array('fname'=>'','abr'=>'');
	}
	$res['id']=$id;
	$res2[]=$res;

}


//print_r($res2);


echo<<<EOF

<a name="jump_top"></a>
<table cellspacing="0" cellpadding="0" id="rowclick1" border="0">
<tr>
<td  width="20px"></td><td width="20px"></td><td width="100px">Author ID</td><td  width="400px">author</td><td  width="200px">checks</td><td  width="200px"></td>
</tr>
EOF;


$z=0;
//$lastk = end(array_keys($res2));
//print_r($res2);
//$err=error_reporting(0);
foreach($res2 as $key=>$obj){
	
	
	
	echo<<<EOF
<tr class="trtop2"><td></td><td colspan="5" valign="bottom" ><a name="jump_{$obj['id']}">#{$z}</a></td></tr>

EOF;
	$z++;

	$i=0;
	
		$rr=trim(str_replace(array('-',$obj['y']),'',$obj['a']));
		$parts=preg_split ('/\s|,|&|(\.[\w]+)/',$rr ,20,PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
		$parts2=array();
		
		foreach($parts as $k=>$pp2){
			$parts2[$k]="<span class='highl'>{$pp2}</span>";
		}
		
		$parts5=preg_split ('/-|\s|,|&|;|(\.[\w]+)/',$rr,20,PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
		
		//print_r($parts);
		$len=mb_strlen($rr,"UTF-8");
		$checks="";
		//echo "UPDATE  herbar_view.scrutiny SET date='{$obj['y']}', author='{$rr}' WHERE scrutiny='{$obj['a']}'; \n";continue;
		$checks.="IF($len <= CHAR_LENGTH(a.autor)+1 and $len >= CHAR_LENGTH(a.autor)-1 ,2,0) as check_a_1_2, \n";
		$checks.="IF(a.autor='{$rr}',2,0) as check_a_2_2,\n";
		$checks.=" IF( mdld('{$rr}',a.autor, 3, 4)<4,2,0) as check_a_3_2,\n";
		
		//echo $rr;
		$where="";
		$where1="";
		$where2="";
		
		$x=0;
		foreach($parts5 as $part){
			if(strpos($part,".")===false && strlen($part)>4){
				
				$where1.=" and a.autor LIKE '%{$part}%'";
				$checks.="IF(INSTR(a.autor,'{$part}' )>0 ,1,0) as check_a_4{$x}_1,\n";
				
			}else{
				
				$where2.=" or  a.autor LIKE '%{$part}%'";
				$checks.="IF(INSTR(a.autor,'{$part}' )>0 ,1,0) as check_a_5{$x}_1,\n";
			}
			$x++;
		}
		
		$where=" mdld('{$rr}',a.autor, 3, 4)<4 or ( 1=1 {$where1} and ( 1=0 {$where} {$where2} )) ";

		
		$years="";
		
		$parts7=preg_split ('/-|\s|,|&|;|\./',$obj['y'],20,PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
		
		$x=0;
		foreach($parts7 as $j){
			$years.=" and lit.jahr like '%{$j}%'";
			$checks.="IF(INSTR(lit.jahr,'{$j}' )>0 ,5,0) as  check_l_1{$x}_5,\n";
			$x++;
		}
		
		$query="
SELECT

a.autorID,
a.autor,
{$checks}
autorsystbot,
lit.jahr,
lit.citationID,
CONCAT(lit.titel,', ',lit.suptitel,', ',period.periodical) as 'litinfo'
	

FROM
tbl_lit_authors a
CROSS JOIN tbl_lit lit ON (lit.autorID = a.autorID)
LEFT JOIN  tbl_lit_periodicals period on  period.periodicalID=lit.periodicalID
WHERE

$where
limit 1000
";
		
		$service = new internMDLDService($_OPTIONS['internMDLDService']['url'],$_OPTIONS['internMDLDService']['password']);
		
		try {
			$res = $service->getSQLResults($query);
		}catch (Exception $e) {
			echo "Fehler " . nl2br($e);
		}
		
		if(isset($res['error']) ) {
			echo $res['error'];
			continue;
		}
                
		$res3=array();
		foreach($res as $row){
			$t1=max(substr_count($row['autor'], ','),substr_count($row['autor'], '&'));
			$t2=max(substr_count($rr, ','),substr_count($rr, '&')) ;
			
			if($t1==$t2){
				$row['check_a_6_1']=1;
			}else{
				$row['check_a_6_1']=1;
			}
			
			$listing=array();
			$listing['ges']['m']=0;
			$listing['ges']['r']=0;
			$chc=0;
			foreach($row as $col=>$val){
				
				$result=preg_match('/check_(?P<group>\w+)_(?P<index>\d+)_(?P<max>\d+)/',$col,$m);
				if($result==1){
					$chc++;
					$listing[$m['group']][$m['index']]['m']=$m['max'];
					$listing[$m['group']][$m['index']]['r']=$val;
					
					if(!isset($listing[$m['group']]['ges']['m'])){
						$listing[$m['group']]['ges']['m']=0;
						$listing[$m['group']]['ges']['r']=0;
					}
					$listing[$m['group']]['ges']['m']+=$m['max'];
					$listing[$m['group']]['ges']['r']+=$val;
					
					$listing['ges']['m']+=$m['max'];
					$listing['ges']['r']+=$val;
				}
		
			}
			$row['res']=$listing;
			//echo "-{$obj['a']}-{$row['autorID']}:$t1:$t2-";
			$su=$listing['ges']['r'];
			$res3[$su][]=$row;
			
		}
		
		ksort($res3);
		$res3= array_reverse($res3,1);
		echo<<<EOF
<tr class="trtop4"><td></td><td colspan="5" >ID: <b>{$obj['id']}</b>,  Author: <span class="at"><b>{$rr}</b></span>, Year: <input type="text" class="inpyear" name="year_{$obj['id']}_{$i}" value="{$obj['y']}"></td></tr>
EOF;
		
		if(count($res3)==0){
				echo<<<EOF
<tr><td></td><td></td><td colspan="5">Nothing found</td></tr>
EOF;
		}
			
		
		$next=array();
		$x=0;
		foreach ($res3 as $dist => $row1) {
			if($x!=0){
				$next[$l]=$dist;
			}
			$l=$dist;
			$x=1;
		}
		$next[$l]=0;
		
		$key2=$key+1;
		if(isset($res2[$key2])){
				$z=$res2[$key2]['id'];
		}else{
			$z='';
		}
			
		
		$xx=0;
		$first =true;
		$checked=false;
		foreach ($res3 as $dist => $row1) {
			$co=count($row1);
			//print_r($row1);exit;
			$chk=$row1[0]['res']['ges']['m'];
			
			$rat=$dist/$chk;
			
			
			
			$pr=number_format($rat* 100, 1).'%';
			$trc='';
			if($rat>0.95)$trc=' class="at"';
			echo<<<EOF
<tr class="at2" ><td></td><td colspan="5" ><span{$trc}><b>{$pr}</b></span>, <small>{$chc} checks</small>, <b>{$co} results</b> matched. </td></tr>
EOF;
			
			
				foreach ($row1 as  $k=>$row) {
					
					$ratAuth=$row['res']['a']['ges']['r']/$row['res']['a']['ges']['m'];
					$ratLit=$row['res']['l']['ges']['r']/$row['res']['l']['ges']['m'];
					
					$prA=number_format($ratAuth* 100, 1).'%';
					$prL=number_format($ratLit* 100, 1).'%';
					
					$s='';
					if($dist>0){
						foreach($row['res'] as $group=>$obj11){
							if($group=='ges'){
								$s.="<br> group {$group}: {$obj11['r']}/{$obj11['m']},";
							}else{
								$s.="<br> group {$group}:";
								$t="";
								foreach($obj11 as $index=>$res){
									if($index=='ges'){
										$s.=" {$index}:{$res['r']}/{$res['m']},";
									}else{
										$t.=" {$index}:{$res['r']}/{$res['m']},";
									}
								}
								$s.=$t;
							}
						
						}
						//$s=substr($s,0,-1);
						echo<<<EOF
				
<tr><td></td><td></td><td colspan="5"></td></tr>

EOF;
					}
					
					$sho=$row['autor'];
					
					$c='';$cl='hoverd';$cl2='';
					$a=(isset($result[$obj['id']]) && isset($result[$obj['id']][$i]));
					$c4='';
					if(  ($first && !$a && $co==1 && !$checked && isset($row['citationID']) && strlen($row['citationID'])>0 &&  ( ($rat-$next[$dist]/$chk>=0.3) || ($next[$dist]==0 && $rat>0.90 ) ) )  ||  ($a && $result[$obj['id']][$i]==$row['autorID']) ){
						$c=' checked';
						$cl.=" selectedtr";
						$checked=true;
						$cl2=' class="at"';
						$c4=' ';
						$sho="<span{$cl2}><b>{$row['autor']}</b></span>";
					}else{
						
						
						$sho=str_replace($parts,$parts2,$row['autor']);
					
					}
					$cit= "";
					if(isset($row['citationID']) && strlen($row['citationID'])>0){
					
						$cit= " year: {$row['jahr']}, ID:{$row['citationID']}, info: {$row['litinfo']}";
					}else{
						$jj=implode(",",$parts7);
						$cit= "None found for year: {$jj}";
					}
					$xx++;
//{$c}
					echo<<<EOF
				
<tr class="{$cl}"><td><input type="radio" name="check_{$obj['id']}_{$i}" value="{$row['citationID']}" jump="{$z}"></td><td></td><td><small>Auth:{$prA}, Lit:{$prL}<br>(AuthorID:{$row['autorID']}, CitationID: {$row['citationID']})</small> {$c4}</td><td>  {$sho}</td><td>{$cit}</td><td><i><small>{$s}</small></i></td></tr>


EOF;
					if($xx>50){
						break;
					}

					
				}
				if($xx>50)break;
				$first=false;
		}
		
		$c='';$cl='hoverd';$cl2='';
		$a=(isset($result[$obj['id']]) && isset($result[$obj['id']][$i]));
		if( ($a && $result[$obj['id']][$i]==$row['autorID']) || count($res3)==0 ){
			$c=' checked';
			$cl.=" selectedtr";
			$cl2=' class="at"';
		}
					
		echo<<<EOF
				
<tr class="{$cl}"><td><input type="radio" name="check_{$obj['id']}_{$i}" value="{$rr}"{$c} jump="{$z}"></td><td></td><td>not now</td><td><span{$cl2}><b>check manually not now</b></span></td><td></td><td></td></tr>


EOF;
		if($xx>50){
					
			echo<<<EOF
				
<tr><td></td><td></td><td></td><td><span><b>More than 50 found</b></span></td><td></td><td></td></tr>


EOF;
		}
		
	//}

}
echo<<<EOF
</table>
<br><br>
<input type="submit" name="update" value="Update">
</form>

<br><br><br><br><br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br><br><br><br><br>
EOF;


function strstr2($val,$search,$arg=0,$trim=true,$show=0){

	if($arg==0){
		$res=strstr($val,$search);
	}else if($arg==1){
		$p=strpos($val,$search);
		$res=substr($val,0,$p);
	}else if($arg==2){
		
		$p=strpos($val,$search);
		$res=substr($val,$p+strlen($search));
	}
	if($trim){
		$res=trim($res);
	}
	if($show){
	
		echo "h-$arg-$val-";
	}
	return $res;
}
?>