<?php
/**
 * Created by PhpStorm.
 * User: gioele
 * Date: 18/03/15
 * Time: 16:45
 */

namespace Srs;

class NamingUtils {
    public function objectToDashedArray($object){
        // convert object to array
        $arr = (array) $object;
        // convert each array's key to dashed one
        return $this->dashedKeys($arr);
    }

    /**
     * Convert camelCase type array's keys to dashed+lowercase type array's keys
     * @param   array   $array          array to convert
     * @param   array   $arrayHolder    parent array holder for recursive array
     * @return  array   dashed array
     */
    public function dashedKeys($array, $arrayHolder = array()) {
        $underscoreArray = !empty($arrayHolder) ? $arrayHolder : array();
        foreach ($array as $key => $val) {
            $newKey = strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $key));
            if (!is_array($val)) {
                $underscoreArray[$newKey] = $val;
            } else {
                $underscoreArray[$newKey] = $this->dashedKeys($val, $underscoreArray[$newKey]);
            }
        }
        return $underscoreArray;
    }
}