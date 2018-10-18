<?php

namespace Webkul\UVDesk\SupportCenterBundle\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Query;

class TranslatedArticle extends EntityRepository
{
    public function getTranslatedArticleByArticle($article, Company $company)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('ta')->from($this->getEntityName(), 'ta')
            ->andWhere('ta.article = :article')
            ->andWhere('ta.companyId = :companyId')
            ->setParameter('article', $article->getId())
            ->setParameter('companyId', $company->getId());
        $results = $qb->getQuery()->getArrayResult();
        
        // index data by locale name like en
        $formattedData = [];
        foreach($results as $result) {
            $formattedData[$result['locale']] = $result;
        }
        return $formattedData;
    }

    public function getAllTranslatedHistoryByArticle($articleId)
    {
        // $article = $this->getEntityManager()->getRepository('WebkulSupportCenterBundle:Article')->findOneBy(['id' => $articleId]);

        // $results = [];
        // if($article) {
        //     $qb = $this->getEntityManager()->createQueryBuilder();
        //     $qb->select('DISTINCT tah.id,  tah.translatedArticleId, tah.content ,tah.content, tah.dateAdded , ta.locale as locale')
        //         ->from('WebkulSupportCenterBundle:TranslatedArticleHistory', 'tah')
        //         ->leftJoin('WebkulSupportCenterBundle:TranslatedArticle', 'ta', 'WITH', 'tah.translatedArticleId = ta.id')
        //         ->leftJoin('Webkul\UserBundle\Entity\User','u','WITH', 'tah.userId = u.id')
        //         ->leftJoin('u.data', 'ud')
        //         ->andWhere('ud.companyId = :companyId')
        //         ->addSelect("CONCAT(ud.firstName,' ',ud.lastName) AS name")
        //         ->leftJoin('ta.article', 'ar')
        //         ->andWhere('ta.article = :article')
        //         ->setParameter('article', $article)
        //         ->andWhere('ta.companyId = :companyId')
        //         ->setParameter('companyId', $companyId)
        //         ->andwhere('ud.userRole IN (:roleId)')
        //         ->setParameter('roleId', [1, 2, 3])
        //         ;
        //     $qb->orderBy('tah.dateAdded', Criteria::DESC);
        //     $results = $qb->getQuery()->getArrayResult();
        // }
            dump("reached in translateHistoryByArticle");die;
        // return $results;
    }
}
