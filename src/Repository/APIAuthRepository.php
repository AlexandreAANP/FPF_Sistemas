<?php

namespace App\Repository;

use App\Entity\APIAuth;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
//use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method APIAuth|null find($id, $lockMode = null, $lockVersion = null)
 * @method APIAuth|null findOneBy(array $criteria, array $orderBy = null)
 * @method APIAuth[]    findAll()
 * @method APIAuth[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class APIAuthRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, APIAuth::class);
    }

}
