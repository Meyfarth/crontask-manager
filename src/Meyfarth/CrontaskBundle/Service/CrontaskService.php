<?php
/**
 * Created by PhpStorm.
 * User: Meyfarth
 * Date: 30/10/14
 * Time: 22:47
 */

namespace Meyfarth\CrontaskBundle\Service;


class CrontaskService {
    const TYPE_INTERVAL_SECONDS = 1;
    const TYPE_INTERVAL_MINUTES = 60;
    const TYPE_INTERVAL_HOURS = 3600;


    const LABEL_INTERVAL_SECONDS = 's';
    const LABEL_INTERVAL_MINUTES = 'min';
    const LABEL_INTERVAL_HOURS = 'h';


    /**
     * Convert a string to a typeInterval
     * @param $stringInterval
     * @return int
     */
    public static function convertToTypeInterval($stringInterval){
        if(in_array($stringInterval, array('h', 'hour', 'hours'))){
            return self::TYPE_INTERVAL_HOURS;
        }elseif(in_array($stringInterval, array('m', 'min', 'minute', 'minutes'))){
            return self::TYPE_INTERVAL_MINUTES;
        }
        return self::TYPE_INTERVAL_SECONDS;
    }
}