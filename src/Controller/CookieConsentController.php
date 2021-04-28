<?php

namespace App\Controller;

use App\Entity\CookieConsent;
use Doctrine\DBAL\DBALException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;

class CookieConsentController extends AbstractController
{
    function setConsent(EntityManagerInterface $em, $domain, $frontofficeVersion, $cookiePolicyVersion) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $identification = $this->getIdentification();

        $query = new CookieConsent();
        $query->setDate();
        $query->setIp($ip);
        $query->setDomain($domain);
        $query->setUserAgent($identification);
        $query->setFrontofficeVersion($frontofficeVersion);
        $query->setCookiePolicyVersion($cookiePolicyVersion);

        try {
            $em->persist($query);
            $em->flush();
        } catch (DBALException $e) {
            // Error
        }
    }

    function getIdentification() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $userAgent = htmlspecialchars($userAgent);
        $userAgent = base64_encode($userAgent);

        return $userAgent;
    }
}