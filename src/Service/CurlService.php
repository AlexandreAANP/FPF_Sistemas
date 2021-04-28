<?php

namespace App\Service;

class CurlService {
    function getAPIData($url) {
        if ($url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'BIZ-Origin: frontoffice',
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $return = curl_exec($ch);
            curl_close($ch);

            return $return;
        } else {
            return '';
        }
    }

    function setAPIData($url, $arValues) {
        if ($url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);

            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'BIZ-Origin: frontoffice',
            ]);

            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arValues));

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $return = curl_exec($ch);
            curl_close($ch);

            return $return;
        } else {
            return null;
        }
    }
}