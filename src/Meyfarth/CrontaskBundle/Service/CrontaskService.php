<?php
/**
 * Created by PhpStorm.
 * User: Meyfarth
 * Date: 30/10/14
 * Time: 22:47
 */

namespace Meyfarth\CrontaskBundle\Service;


class CrontaskService {
    const INTERVAL_TYPE_SECONDS = 1;
    const INTERVAL_TYPE_MINUTES = 60;
    const INTERVAL_TYPE_HOURS = 3600;


    const LABEL_INTERVAL_SECONDS = 's';
    const LABEL_INTERVAL_MINUTES = 'min';
    const LABEL_INTERVAL_HOURS = 'h';

    const DATE_FORMAT = 'Y-m-d H:i';


    /**
     * Convert a string to a typeInterval
     * @param $stringInterval
     * @return int
     */
    public static function convertToTypeInterval($stringInterval){
        if(in_array($stringInterval, array('h', 'hour', 'hours'))){
            return self::INTERVAL_TYPE_HOURS;
        }elseif(in_array($stringInterval, array('m', 'min', 'minute', 'minutes'))){
            return self::INTERVAL_TYPE_MINUTES;
        }
        return self::INTERVAL_TYPE_SECONDS;
    }


    public static function convertFromTypeInterval($typeInterval){
        if($typeInterval == self::INTERVAL_TYPE_HOURS){
            return 'hours';
        }elseif($typeInterval == self::INTERVAL_TYPE_MINUTES){
            return 'minutes';
        }else{
            return 'seconds';
        }
    }

    /**
     * Check if the given string is a valid date format
     * @param string $date
     * @param string $format
     * @return bool
     */
    public static function ValidateDate($date, $format = 'Y-m-d H:i:s') {
        $version = explode('.', phpversion());
        if (((int) $version[0] >= 5 && (int) $version[1] >= 2 && (int) $version[2] > 17)) {
            $d = \DateTime::createFromFormat($format, $date);
        } else {
            $d = new \DateTime(date($format, strtotime($date)));
        }

        return $d && $d->format($format) == $date;
    }
}