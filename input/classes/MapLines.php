<?PHP

namespace Jacq;

class MapLines
{
    public function execFunction($function, $params): void
    {
        if (method_exists($this, $function)) {
            $res = call_user_func_array(array($this, $function), array($params));
        } else {
            $res = array('sucess' => false);
        }
        $res = json_encode($res);

        echo $res;
	}

    public function getMapLines($p, $emptyRightIsZero = false, $onlyRightCollumn = false): array
    {
        $new = array();
        foreach($p as $k => $v){
            if (preg_match('/acmap_r_(\d+)Index/', $k, $matches) == 1){
                $x = $matches[1];
                $leftID  = $p['acmap_l_' . $x . 'Index'] ?? '';
                $rightID = $p['acmap_r_' . $x . 'Index'] ?? '';

//                $leftVal=isset($p['ajax_acmap_l_'.$x])?$p['ajax_acmap_l_'.$x]:'';
//                $rightVal=isset($p['ajax_acmap_r_'.$x])?$p['ajax_acmap_r_'.$x]:'';

                if ($onlyRightCollumn) {
                    if (is_numeric($rightID)) {
                        $new[$rightID] = $x;
                        continue;
                    }
                } else {
                    if (is_numeric($leftID) && $rightID == '' && $emptyRightIsZero) {
                        $new[$leftID][0] = $x;
                        continue;
                    } elseif (is_numeric($leftID) && is_numeric($rightID)) {
                        $new[$leftID][$rightID] = $x;
                        continue;
                    }
                }
                if (($leftID == '' || $leftID == '0') && ($rightID == '' || $rightID == '0')) {
                    continue;
                }
                $new['error'][] = array($x, $leftID, $rightID);
            }
        }
        return $new;
    }
}
