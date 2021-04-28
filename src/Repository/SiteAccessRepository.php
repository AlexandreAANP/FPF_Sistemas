<?php

namespace App\Repository;

use App\Entity\SiteAccess;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SiteAccess|null find($id, $lockMode = null, $lockVersion = null)
 * @method SiteAccess|null findOneBy(array $criteria, array $orderBy = null)
 * @method SiteAccess[]    findAll()
 * @method SiteAccess[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SiteAccessRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteAccess::class);
    }

    public function findTodayAccess($domain, $userAgent, $ip, $date): array
    {
        $date = substr($date, 0, strpos($date, ' '));

        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT id, view FROM site_access WHERE ip = "' . $ip . '" AND `domain` = "' . $domain . '" AND DATE(`date`) = DATE("' . $date . '") AND user_agent = "' . $userAgent . '" LIMIT 1';
        $query = $conn->prepare($sql);
        $query->execute();

        return $query->fetchAll();
    }

    public function updateSiteAccess($id): bool
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'UPDATE `site_access` SET view = view + 1 WHERE id = ' . $id;
        $query = $conn->prepare($sql);
        return $query->execute();
    }
}