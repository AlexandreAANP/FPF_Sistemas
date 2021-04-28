<?php

namespace App\Controller;

use App\Entity\SiteAccess;
use Doctrine\DBAL\DBALException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;

class SiteAccessController extends AbstractController
{
    function setView(EntityManagerInterface $em, $domain, $siteAccessId = 0) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $identification = $this->getIdentification();

        if ($siteAccessId) {
            $em->getRepository(SiteAccess::class)->updateSiteAccess($siteAccessId);
        } else {
            $query = new SiteAccess();
            $query->setDate();
            $query->setIp($ip);
            $query->setDomain($domain);
            $query->setUserAgent($identification);
            $query->setView(1);

            try {
                $em->persist($query);
                $em->flush();
            } catch (DBALException $e) {
                // Error
            }
        }
    }

    function getIdentification() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $userAgent = htmlspecialchars($userAgent);
        $userAgent = base64_encode($userAgent);

        return $userAgent;
    }
}