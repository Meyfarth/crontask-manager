<?php
/**
 * Created by PhpStorm.
 * User: Meyfarth
 * Date: 30/10/14
 * Time: 22:35
 */

namespace Meyfarth\CrontaskBundle\Repository;


use Doctrine\ORM\EntityRepository;

class CrontaskRepository extends EntityRepository {

    /**
     * Get all actives crontasks
     * @return mixed
     */
    public function findAllActives(){
        return $this->createQueryBuilder('c')
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->execute();
    }
} 