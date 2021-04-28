<?php

namespace App\Controller;

use App\Entity\APIAuth;
use Doctrine\DBAL\DBALException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class APIAuthController extends AbstractController
{
    function apiAuthentication($em, $apiUrl, $domain) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $token = $this->generateToken();

        $query = new APIAuth();
        $query->setToken($token);
        $query->setDate();
        $query->setDomain($domain);
        $query->setIp($ip);
        $query->setAPIUrl($apiUrl);
        $query->setDomain($domain);
        $query->setStatus(0);

        try {
            $em->persist($query);
            $em->flush();
        } catch (DBALException $e) {
            dd($e);
            if (is_integer(strpos($e->getMessage(), 'Access denied for user'))) {
                $msg = 'Access denied for user';
                if (is_integer(strpos($_SERVER['HTTP_HOST'], '.test'))) {
                    $msg .= '. Check if you have remote connection configured on MySQL Server';
                }
                echo json_encode(['return' => "error", 'msg' => $msg]);
                exit();
            }
            return $this->json(['return' => "error", 'msg' => 'inserting_table_register']);
        }

        if ($lastId = $query->getId()) {
            return $token;
        } else {
            return null;
        }
    }

    function generateToken() {
        $utc = new \DateTimeZone('UTC');
        $token = new \DateTime("now");
        $token->setTimezone($utc);
        $rnd = rand(1000, 9999);
        $token = $token->format('Y-m-d H:i:s') . ' ' . $rnd;

        return md5($token);
    }
}