<?php

namespace App\Service;

class UtilService {
    function getCallingClass() {

        //get the trace
        $trace = debug_backtrace();

        // Get the class that is asking for who awoke it
        $class = $trace[1]['class'];

        // +1 to i cos we have to account for calling this function
        for ($i = 1; $i < count($trace); $i++) {
            if (isset($trace[$i])) {
                if ($class != $trace[$i]['class']) {
                    return $trace[$i]['class'];
                }
            }
        }
    }

    public function base64_encode($str) {
        return base64_encode($str);
    }

    public function base64_decode($str) {
        return base64_decode($str);
    }
}