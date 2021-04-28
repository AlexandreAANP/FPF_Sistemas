<?php
namespace App\Service;

class StringFormat
{
    function dateDecode($str, $options = '') {
        $ret = '';
        if (trim($str) && strlen($str) >= 10) {
            $ret = substr($str, 8, 2) . '/' . substr($str, 5, 2) . '/' . substr($str, 0, 4);
            if (strlen($str) > 10) {
                $ret .= substr($str, 10, -3);
            }
            $ret = trim($ret);
        }

        if ($options === 'onlyDate') {
            $ret = substr($ret, 0, strpos($ret, ' '));
        }

        return $ret;
    }

    function dateEncode($str) {
        $ret = '';

        if (trim($str) && $str != '0000-00-00' && $str != '0000-00-00 00:00:00') {
            $hour = '';
            $date = $str;
            if (strlen($str) > 10) {
                $hour = substr($str, strpos($str, ' '));
                $hour = trim($hour);
                $date = substr($str, 0, strpos($str, ' '));
                $date = trim($date);
            }

            $arDate = explode('/', $date);

            if (count($arDate) !== 3) {
                $ret = '0000-00-00';
            }

            $year = $arDate[2];
            if (intval($year) < 100) {
                $year = '20' . intval($year);
            }

            $month = $arDate[1];
            if (intval($month) < 10) {
                $month = '0' . intval($month);

            }
            $day = $arDate[0];
            if (intval($day) < 10) {
                $day = '0' . intval($day);
            }

            if (strlen($year) !== 4 || strlen($month) !== 2 || strlen($day) !== 2 || intval($year) === 0 || intval($month) === 0|| intval($day) === 0) {
                $ret = '0000-00-00';
            }

            if ($ret) {
                $ret = trim($ret);
            } else {
                $ret = $year . '-' . $month . '-' . $day;
            }

            if ($hour) {
                $ret .=  ' ' . trim($hour);
            }
        }

        return $ret;
    }

    function checkFormatDate($str) {
        $str = trim($str);
        if ($str) {

            if (strlen($str) >= 11 && substr($str, 10, 1) === 'T') {
                $str = str_replace('T', ' ', $str);
            }

            $date = '';
            $hour = '';
            if (is_integer(strpos($str, ' '))) {
                $date = substr($str, 0, strpos($str, ' '));
                $hour = substr($str, strpos($str, ' ') + 1);
            } else {
                $date = $str;
            }

            if ($date && is_integer(strpos($date, '/'))) {
                $arDate = explode('/', $date);
                if (count($arDate) === 3 && strlen($arDate[0]) === 2 && strlen($arDate[1]) === 2 && strlen($arDate[2]) === 4) {
                    return 'decoded';
                }

            } else if ($date && is_integer(strpos($date, '-'))) {
                $arDate = explode('-', $date);
                if (count($arDate) === 3 && strlen($arDate[0]) === 4 && strlen($arDate[1]) === 2 && strlen($arDate[2]) === 2) {
                    return 'encoded';
                }
            }

            return 'error';
        }
    }
}
