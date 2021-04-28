<?php

namespace App\Repository;

use App\Entity\CookieConsent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CookieConsent|null find($id, $lockMode = null, $lockVersion = null)
 * @method CookieConsent|null findOneBy(array $criteria, array $orderBy = null)
 * @method CookieConsent[]    findAll()
 * @method CookieConsent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CookieConsentRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, CookieConsent::class);
    }
}