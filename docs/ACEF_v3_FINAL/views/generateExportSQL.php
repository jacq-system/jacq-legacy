<?PHP

$familyNames=array(
	'i30'=>'Annonaceae',
	'i115'=>'Chenopodiaceae',
	'i182'=>'Ebenaceae'
);
header("Content-Type: text/plain"); 
echo generateExportMysql($familyNames);

function generateExportMysql($familyNames,$templatePath=0){

	if(!is_array($familyNames))
		$familyNames=array($familyNames);
	
	if($templatePath==0)$templatePath='./export_sp2000.template.sql';
	
	if(!is_file($templatePath))
		return "Template not found";
	
	$template=file_get_contents($templatePath);
	if(strlen($template)==0)
		return "Template empty";
	
	$ret1='';$ret2='';$ret3='';$ret4='';$ret5='';$ret6='';
	foreach($familyNames as $familyID=>$familyName){
		$temp=<<<EOF



# ===========================================
# Dataexport for: {$familyName}
# ===========================================


	
EOF;
		$temp.=str_replace("IFAMILYNAME",$familyName,$template);
		$ret1.=$temp;
		
		$ret2.=",'{$familyName}'";
		
		if(substr($familyID,0,1)=='i'){
			$ret3.=",'".substr($familyID,1)."'";
			$ret6.=",'".substr($familyID,1)."'";
		}else{
			$ret4.=",'{$familyName}'";
			$ret6.=",'?'";
		}
	}
	
	
	$ret6=substr($ret6,1);
	$ret2=substr($ret2,1);

	$ret7=<<<EOF
# tg.familyID IN ({$ret6}) -- tf.family IN({$ret2})
EOF;

	if(strlen($ret3)>0 || strlen($ret4)>0){
		$ret5="# ( ";
		if(strlen($ret3)>0){
			$ret5.=" tg.familyID IN(".substr($ret3,1).")  ";
			if(strlen($ret4)>0){
				$ret5.=" OR ";
			}
		}
		if(strlen($ret4)>0){
			$ret5.="  tf.family IN(".substr($ret4,1).") ";
		}
		$ret5.=" )";
	}
	
	$ret=<<<EOF
#<pre>
# ===========================================
# http://dev.mysql.com/doc/refman/5.0/en/stored-program-restrictions.html
# no stored procedure for this operation possible in mysql.
# => MYSQL Script to generate...
# ===========================================	
#
{$ret7}
{$ret5}
#

{$ret1}

#</pre>
EOF;
	return $ret;
}