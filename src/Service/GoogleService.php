<?php
namespace App\Service;

class GoogleService
{
    public function captcha($captcha, $secretKey)
    {
        $captcha = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_STRING);
        if (!$captcha) {
            header('Content-type: application/json');
            echo json_encode(array('return' => 'error', 'msg' => 'Please check the the captcha form'));
            exit;
        }

        // post request to server
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = ['secret' => $secretKey, 'response' => $captcha];

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        $context  = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        $responseKeys = json_decode($response,true);
        header('Content-type: application/json');
        if ($responseKeys["success"]) {
            echo json_encode(array('return' => 'success'));
        } else {
            echo json_encode(array('return' => 'error'));
        }
    }
}