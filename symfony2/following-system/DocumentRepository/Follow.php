<?php

namespace IMS\CoreBundle\DocumentRepository\Follow;

use Doctrine\ODM\MongoDB\DocumentRepository;

class Follow extends DocumentRepository
{

    const ROOT_ALIAS = 'f';

    /**
     * Count followers for an account (user)
     * @param integer $id Id of a user, whose followers we're going to count
     * @return integer User followers QTY
     */
    public function countUserFollowers($id)
    {
        return $this->createQueryBuilder(self::ROOT_ALIAS)
                        ->field('itemId')->equals($id)
                        ->field('type')->equals(\IMS\CoreBundle\Document\Follow\Follow::TYPE_USER)
                        ->getQuery()
                        ->count()
        ;
    }

    /**
     * Count followers for a listing
     * @param integer $id Id of a listing, whose followers we're going to count
     * @return integer Listing followers QTY
     */
    public function countListingFollowers($id)
    {
        return $this->createQueryBuilder(self::ROOT_ALIAS)
                        ->field('itemId')->equals($id)
                        ->field('type')->in(array(
                            \IMS\CoreBundle\Document\Follow\Follow::TYPE_TOOL,
                            \IMS\CoreBundle\Document\Follow\Follow::TYPE_SERVICE
                        ))
                        ->getQuery()
                        ->count()
        ;
    }

    /**
     * Count following users qty for the account (user)
     * @param integer $id Id of a user, whose following users we're going to count
     * @return integer User following users QTY
     */
    public function countFollowingUsers($id)
    {
        return $this->createQueryBuilder(self::ROOT_ALIAS)
                        ->field('followerId')->equals($id)
                        ->field('type')->equals(\IMS\CoreBundle\Document\Follow\Follow::TYPE_USER)
                        ->getQuery()
                        ->count()
        ;
    }

    /**
     * Count following services qty for the account (user)
     * @param integer $id Id of a user, whose following services we're going to count
     * @return integer User following services QTY
     */
    public function countFollowingServices($id)
    {
        return $this->createQueryBuilder(self::ROOT_ALIAS)
                        ->field('followerId')->equals($id)
                        ->field('type')->equals(\IMS\CoreBundle\Document\Follow\Follow::TYPE_SERVICE)
                        ->getQuery()
                        ->count()
        ;
    }

    /**
     * Count following listings qty for the account (user)
     * @param integer $id Id of a user, whose following listings we're going to count
     * @return integer User following listings QTY
     */
    public function countFollowingTools($id)
    {
        return $this->createQueryBuilder(self::ROOT_ALIAS)
                        ->field('followerId')->equals($id)
                        ->field('type')->equals(\IMS\CoreBundle\Document\Follow\Follow::TYPE_TOOL)
                        ->getQuery()
                        ->count()
        ;
    }

    /**
     * Find a following of the listing by the user
     * @param integer $followerId
     * @param integer $listingId
     * @return mixed[NULL|\IMS\CoreBundle\Document\Follow\Follow]
     */
    public function findUserFollowListing($followerId, $listingId)
    {
        return $this->createQueryBuilder(self::ROOT_ALIAS)
                        ->field('followerId')->equals($followerId)
                        ->field('itemId')->equals($listingId)
                        ->field('type')->in(array(
                            \IMS\CoreBundle\Document\Follow\Follow::TYPE_TOOL,
                            \IMS\CoreBundle\Document\Follow\Follow::TYPE_SERVICE
                        ))
                        ->getQuery()
                ->getSingleResult()
        ;
    }

    /**
     * Finds following users ids for the account (user)
     * 
     * @param integer $id   Id of a user, whose following users ids we're going to found
     * @return array        User following users ids
     */
    public function findFollowingUsersIds($id)
    {
        return $this->createQueryBuilder(self::ROOT_ALIAS)
                ->distinct('itemId')
                ->field('followerId')->equals($id)
                ->field('type')->equals(\IMS\CoreBundle\Document\Follow\Follow::TYPE_USER)
                ->getQuery()
                ->toArray()
        ;
    }
    
    /**
     * Find following listings ids for the account (user)
     * 
     * @param integer $id   Id of a user, whose following listings ids we're going to found
     * @return array        User following listings ids
     */
    public function findFollowingListingsIds($id)
    {
        return $this->createQueryBuilder(self::ROOT_ALIAS)
                        ->distinct('itemId')
                        ->field('followerId')->equals($id)
                        ->field('type')->in(array(
                            \IMS\CoreBundle\Document\Follow\Follow::TYPE_TOOL,
                            \IMS\CoreBundle\Document\Follow\Follow::TYPE_SERVICE
                        ))
                        ->getQuery()
                        ->toArray()
        ;
    }
    
    /**
     * Finds followed users ids for the account (user)
     * 
     * @param integer $id   Id of a user, whose followed users ids we're going to found
     * @return array        User following users ids
     */
    public function findFollowedUsersIds($id)
    {
        return $this->createQueryBuilder(self::ROOT_ALIAS)
                ->distinct('followerId')
                ->field('itemId')->equals($id)
                ->field('type')->equals(\IMS\CoreBundle\Document\Follow\Follow::TYPE_USER)
                ->getQuery()
                ->toArray()
        ;
    }
    
    /**
     * Count following listings qty for the account (user)
     * @param integer $id Id of a user, whose following services we're going to count
     * @return integer User following listings QTY
     */
    public function countFollowingListings($id)
    {
        return $this->createQueryBuilder(self::ROOT_ALIAS)
                        ->field('followerId')->equals($id)
                        ->field('type')->in(array(
                            \IMS\CoreBundle\Document\Follow\Follow::TYPE_TOOL,
                            \IMS\CoreBundle\Document\Follow\Follow::TYPE_SERVICE
                        ))
                        ->getQuery()
                        ->count()
        ;
    }
    
    /**
     * Delete followed/following documents relate to this account
     * @param integer $id Id of a user, whose follow documents we're going to remove
     */
    public function deleteFollowsByUserId($id)
    {
        $qb = $this->createQueryBuilder(self::ROOT_ALIAS);
        
        $qb->addAnd($qb->expr()             
                ->addOr($qb->expr()->field('followerId')->equals($id))
                ->addOr($qb->expr()
                        ->addAnd($qb->expr()->field('itemId')->equals($id))
                        ->addAnd($qb->expr()->field('type')->equals(\IMS\CoreBundle\Document\Follow\Follow::TYPE_USER))
                )
        );
        
        $qb
                ->remove()
                ->getQuery()
                ->execute()
        ;
    }
    
    /**
     * Delete following documents relate to this listing
     * @param integer $id Id of a listing, which following documents we're going to remove
     */
    public function deleteFollowsByListingId($id)
    {
        $this->createQueryBuilder(self::ROOT_ALIAS)
                ->field('itemId')->equals($id)
                ->field('type')->in(array(
                    \IMS\CoreBundle\Document\Follow\Follow::TYPE_TOOL,
                    \IMS\CoreBundle\Document\Follow\Follow::TYPE_SERVICE
                ))
                ->remove()
                ->getQuery()
                ->execute()
        ;
    }
}
