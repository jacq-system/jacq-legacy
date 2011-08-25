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


.selectedtr td{
 background-color:#f5eea8;
}

.inpyear{
 background-color:#abe7a8;
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
	//for($i=1;$i<=2;$i++){
	//	$obj2=$obj['a'.$i];
	//	$abk=substr($obj2['fname'],0,1);
		/*$query="
SELECT

person_ID,
p_abbrev,
p_familyname,
p_firstname,

IF(p_abbrev='{$obj2['abr']}',1,0) as a1,
IF(p_abbrev='{$obj2['fname']}',1,0) as a2,
IF(p_abbrev='{$obj2['abr']} {$obj2['fname']}',1,0) as a3,
IF(SUBSTRING(p_firstname,0,1)='{$abk}',1,0) as a4

FROM
tbl_person
WHERE

p_familyname='{$obj2['fname']}'
";*/	
		
		$r1=array('et al','. .','..');
		$r2='';
		
		$obj['a1']['abr']=trim(str_replace($r1,$r2,$obj['a1']['abr']));
		$obj['a2']['abr']=trim(str_replace($r1,$r2,$obj['a2']['abr']));
		
		$checkcount=7;
		
		
		
		$rr=str_replace($obj['y'],'',$obj['a']);
		$len=strlen($rr);
		
		//echo "UPDATE  herbar_view.scrutiny SET date='{$obj['y']}', author='{$rr}' WHERE scrutiny='{$obj['a']}'; \n";continue;
		
		$checks='';
		$checks.="IF($len <= CHAR_LENGTH(autor)+1 and $len >= CHAR_LENGTH(autor)-1 ,10,0) as c1,";
		$checks.="IF(autor='{$rr}',10,0) as c2,";
		$checks.=" IF( mdld('{$rr}',autor, 3, 4)<4,10,0) as c3,  ";
		
		//echo $rr;
		$chk=30;
		$chc=3;
		//print_r($obj);
		
		$where=" or  mdld('{$rr}',autor, 3, 4)<4";
		
		if(strlen($obj['a1']['fname'])>0){
			$where.=" or ( autor LIKE '%{$obj['a1']['fname']}%'";
			
			if(strlen($obj['a1']['abr'])>0){
				$where.=" and autor LIKE '%{$obj['a1']['abr']}%' ";
				$checks.="IF(INSTR(autor,'{$obj['a1']['abr']}' )>0 ,1,0) as c4,";
				$chk+=1;
				$chc+=1;
			}
			
			$where.=" ) ";
			
			$checks.="IF(INSTR(autor,'{$obj['a1']['fname']}' )>0 ,1,0) as c5,";
			$chk+=1;
			$chc+=1;
		}
		if(strlen($obj['a2']['fname'])>0){
			$where.=" or ( autor LIKE '%{$obj['a2']['fname']}%'";
			
			if(strlen($obj['a2']['abr'])>0){
				$where.=" and autor LIKE '%{$obj['a2']['abr']}%' ";
				$checks.="IF(INSTR(autor,'{$obj['a2']['abr']}' )>0 ,1,0) as c6,";
				$chk+=1;
				$chc+=1;
			}
			
			$where.=" ) ";
			
			$checks.="IF(INSTR(autor,'{$obj['a2']['fname']}' )>0 ,1,0) as c7,";
			$chk+=1;
			$chc+=1;
			
		}

		
		
		
		$query="
SELECT

autorID,
autor,
{$checks}
autorsystbot




FROM
tbl_lit_authors
WHERE
1=0 
$where
limit 1000
";
//echo $query;

		//print_r($obj);
		
		
		$res = mysql_query($query);
		$res3=array();
		while($row = mysql_fetch_array($res)){
			
			$su=0;
			for($j=1;$j<=$checkcount;$j++){
				
				if(isset($row['c'.$j])){
					$t=$row['c'.$j];
					$su+=$t;
				}
			}
			$t1=max(substr_count($row['autor'], ','),substr_count($row['autor'], '&'));
			$t2=max(substr_count($obj['a'], ','),substr_count($obj['a'], '&')) ;
			
			if(  $t1==$t2  ){
				$su++;
			}
			
			
			$res3[$su][]=$row;
			
		}
		$chk+=1;
		$chc+=1;

		
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
					
		$checked=false;
		foreach ($res3 as $dist => $row1) {
			$co=count($row1);
			$rat=$dist/$chk;
			
			$pr=number_format($rat* 100, 1).'%';
			$trc='';
			if($rat>0.95)$trc=' class="at"';
			echo<<<EOF
<tr class="at2" ><td></td><td colspan="5" ><span{$trc}><b>{$pr}</b></span>, <small>{$chc} checks, {$dist}/{$chk} scores</small>, <b>{$co} results</b> matched. </td></tr>
EOF;
			
			
				foreach ($row1 as  $k=>$row) {
					
					$s='';
					if($dist>0){
						$s='';
						for($j=1;$j<=$checkcount;$j++){
							if(isset($row['c'.$j])){
								$s.=" c{$j}:".$row['c'.$j].",";
							}
						}
						$s=substr($s,0,-1);
						echo<<<EOF
				
<tr><td></td><td></td><td colspan="5"></td></tr>

EOF;
					}
					
					$c='';$cl='hoverd';$cl2='';
					//if(isset($result[$obj['id']]) && isset($result[$obj['id']][$i]) && $result[$obj['id']][$i]==$row['person_ID']){
					$a=(isset($result[$obj['id']]) && isset($result[$obj['id']][$i]));
					
					if(  (!$a && $co==1 && !$checked &&  ( ($dist-$next[$dist]>=10) || ($next[$dist]==0 && $rat>0.95 ) ) )  ||  ($a && $result[$obj['id']][$i]==$row['autorID']) ){
						$c=' checked';
						$cl.=" selectedtr";
						$checked=true;
						$cl2=' class="at"';
					}

					
					echo<<<EOF
				
<tr class="{$cl}"><td><input type="radio" name="check_{$obj['id']}_{$i}" value="{$row['autorID']}"{$c} jump="{$z}"></td><td></td><td>{$row['autorID']}</td><td><span{$cl2}><b>{$row['autor']}</b></span></td><td><i><small>{$s}</small></i></td><td></td></tr>


EOF;

					
				}
			
		}
		
		$c='';$cl='hoverd';$cl2='';
		$a=(isset($result[$obj['id']]) && isset($result[$obj['id']][$i]));
		if( ($a && $result[$obj['id']][$i]==$row['autorID']) || count($res3)==0 ){
			$c=' checked';
			$cl.=" selectedtr";
			$cl2=' class="at"';
		}
					
		echo<<<EOF
				
<tr class="{$cl}"><td><input type="radio" name="check_{$obj['id']}_{$i}" value="{$rr}"{$c} jump="{$z}"></td><td></td><td>Nothing</td><td><span{$cl2}><b>Nothing (check manually)</b></span></td><td></td><td></td></tr>


EOF;
		
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