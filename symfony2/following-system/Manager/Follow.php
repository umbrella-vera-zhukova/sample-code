<?php

namespace IMS\_CoreBundle\Handler;

/**
 * Service to manage Follows all over the project
 */
class FollowManager
{

    /**
     * @var \Doctrine\Bundle\DoctrineBundle\Registry
     */
    protected $_doctrine;

    /**
     * @var \Doctrine\Bundle\MongoDBBundle\ManagerRegistry
     */
    protected $_mongodb;

    public function __construct(\Doctrine\Bundle\DoctrineBundle\Registry $doctrine, \Doctrine\Bundle\MongoDBBundle\ManagerRegistry $mongodb)
    {
        $this->_doctrine = $doctrine;
        $this->_mongodb = $mongodb;
    }

    /**
     * Follow the user executes the next actions:
     *      - creates a new decument in the follow mondoDB collection
     *      - updates follow QTYs for follower
     *      - updates follow QTYs for followed
     *      - updates reputation for follower
     *      - updates reputation for followed
     * 
     * Note: if user is already following the user - do nothing 
     * Note: user cannot follow himself
     * @param \IMS\_CoreBundle\Entity\General\Account $follower
     * @param \IMS\_CoreBundle\Entity\General\Account $followed
     * @return boolean Whether a new following is created
     */
    public function followUser(\IMS\_CoreBundle\Entity\General\Account $follower, \IMS\_CoreBundle\Entity\General\Account $followed)
    {
        // user cannot follow himself
        if ($follower->getId() == $followed->getId())
            return false;

        $dm = $this->_mongodb->getManager();

        if ($this->ifUserFollowsUser($follower->getId(), $followed->getId()))
            return false;

        // save the following
        $follow = new \IMS\_CoreBundle\Document\Follow\Follow();
        $follow
                ->setType(\IMS\_CoreBundle\Document\Follow\Follow::TYPE_USER)
                ->setFollowerId($follower->getId())
                ->setItemId($followed->getId())
        ;
        $dm->persist($follow);
        $dm->flush();

        // update follow QTYs for follower
        \IMS\ServiceBundle\PHPResque\Job\Follow\UpdateUserQtyFollowJob::attach(array(
            'id' => $follower->getId(),
        ));

        // update follow QTYs for followed
        \IMS\ServiceBundle\PHPResque\Job\Follow\UpdateUserQtyFollowJob::attach(array(
            'id' => $followed->getId(),
        ));

        // update reputation of follower
        \IMS\ServiceBundle\PHPResque\Job\Account\UpdateReputationJob::attach(array(
            'id' => $follower->getId(),
            'action' => ReputationHandler::ACTION_USER_FOLLOW,
        ));

        // update reputation of followed
        \IMS\ServiceBundle\PHPResque\Job\Account\UpdateReputationJob::attach(array(
            'id' => $followed->getId(),
            'action' => ReputationHandler::ACTION_USER_FOLLOWED,
        ));

        // log the activity
        \IMS\ServiceBundle\PHPResque\Job\Activity\Event\FollowUserJob::attach(array(
            'follower' => $follower->getId(),
            'followed' => $followed->getId(),
        ));

        return true;
    }

    /**
     * Unfollow the user executes the next actions:
     *      - deletes a follow document from mondoDB collection
     *      - updates follow QTYs for follower
     *      - updates follow QTYs for followed
     *      - updates reputation for follower
     *      - updates reputation for followed
     * 
     * @param \IMS\_CoreBundle\Entity\General\Account $follower
     * @param \IMS\_CoreBundle\Entity\General\Account $followed
     * @return boolean Whether following was removed
     */
    public function unfollowUser(\IMS\_CoreBundle\Entity\General\Account $follower, \IMS\_CoreBundle\Entity\General\Account $followed)
    {
        $dm = $this->_mongodb->getManager();

        if (!$follow = $this->ifUserFollowsUser($follower->getId(), $followed->getId()))
            return false;

        // delete the following
        $dm->remove($follow);
        $dm->flush();

        // update follow QTYs for follower
        \IMS\ServiceBundle\PHPResque\Job\Follow\UpdateUserQtyFollowJob::attach(array(
            'id' => $follower->getId(),
        ));

        // update follow QTYs for followed
        \IMS\ServiceBundle\PHPResque\Job\Follow\UpdateUserQtyFollowJob::attach(array(
            'id' => $followed->getId(),
        ));

        // update reputation of follower
        \IMS\ServiceBundle\PHPResque\Job\Account\UpdateReputationJob::attach(array(
            'id' => $follower->getId(),
            'action' => ReputationHandler::ACTION_USER_UNFOLLOW,
        ));

        // update reputation of followed
        \IMS\ServiceBundle\PHPResque\Job\Account\UpdateReputationJob::attach(array(
            'id' => $followed->getId(),
            'action' => ReputationHandler::ACTION_USER_UNFOLLOWED,
        ));
        
        return true;
    }

    /**
     * Follow the listing executes the next actions:
     *      - creates a new decument in the follow mondoDB collection
     *      - updates follow QTYs for follower
     *      - updates follow QTYs for followed listing owner (is exists)
     *      - updates reputation for follower
     *      - updates reputation for followed listing owner (is exists)
     *      - log the activity
     * 
     * Note: if user is already following the listing - do nothing 
     * @param \IMS\_CoreBundle\Entity\General\Account $follower
     * @param \IMS\_CoreBundle\Entity\General\MarketItem\Listing $listing
     * @return boolean Whether a new following is created
     */
    public function followListing(\IMS\_CoreBundle\Entity\General\Account $follower, \IMS\_CoreBundle\Entity\General\MarketItem\Listing $listing)
    {
        $dm = $this->_mongodb->getManager();

        switch ($listing->getDiscr())
        {
            case 'service':
                $type = \IMS\_CoreBundle\Document\Follow\Follow::TYPE_SERVICE;
                break;
            case 'tool':
                $type = \IMS\_CoreBundle\Document\Follow\Follow::TYPE_TOOL;
                break;
            default:
                throw new \Doctrine\ORM\Mapping\MappingException("Discriminator {$listing->getDiscr()} does not exist in discriminator map of IMS Listing Entity.");
        }

        if ($this->ifUserFollowsListing($follower->getId(), $listing->getId()))
            return false;

        // save the following
        $follow = new \IMS\_CoreBundle\Document\Follow\Follow();
        $follow
                ->setType($type)
                ->setFollowerId($follower->getId())
                ->setItemId($listing->getId())
        ;
        $dm->persist($follow);
        $dm->flush();

        // update follow QTYs for follower
        \IMS\ServiceBundle\PHPResque\Job\Follow\UpdateUserQtyFollowJob::attach(array(
            'id' => $follower->getId(),
        ));

        // update reputation of follower
        \IMS\ServiceBundle\PHPResque\Job\Account\UpdateReputationJob::attach(array(
            'id' => $follower->getId(),
            'action' => ReputationHandler::ACTION_LISTING_FOLLOW,
        ));

        if ($listing->getOwner())
        {
            // update follow QTYs for followed listing owner
            \IMS\ServiceBundle\PHPResque\Job\Follow\UpdateUserQtyFollowJob::attach(array(
                'id' => $listing->getOwner()->getId(),
            ));

            // update reputation of followed listing owner
            \IMS\ServiceBundle\PHPResque\Job\Account\UpdateReputationJob::attach(array(
                'id' => $listing->getOwner()->getId(),
                'action' => ReputationHandler::ACTION_LISTING_FOLLOWED,
            ));
        }

        // update followers qty for listing
        \IMS\ServiceBundle\PHPResque\Job\Follow\UpdateListingQtyFollowJob::attach(array(
            'id' => $listing->getId(),
        ));
        
        // log the activity
        \IMS\ServiceBundle\PHPResque\Job\Activity\Event\FollowListingJob::attach(array('follower' => $follower->getId(), 'listing' => $listing->getId()));

        return true;
    }

    /**
     * Unfollow the listing executes the next actions:
     *      - deletes a follow document from mondoDB collection
     *      - updates follow QTYs for follower
     *      - updates follow QTYs for followed listing owner (if exists)
     *      - updates reputation for follower
     *      - updates reputation for followed listing owner (if exists)
     * 
     * @param \IMS\_CoreBundle\Entity\General\Account $follower
     * @param \IMS\_CoreBundle\Entity\General\MarketItem\Listing $listing
     * @return boolean Whether following was removed
     */
    public function unfollowListing(\IMS\_CoreBundle\Entity\General\Account $follower, \IMS\_CoreBundle\Entity\General\MarketItem\Listing $listing)
    {
        $dm = $this->_mongodb->getManager();

        switch ($listing->getDiscr())
        {
            case 'service':
                $type = \IMS\_CoreBundle\Document\Follow\Follow::TYPE_SERVICE;
                break;
            case 'tool':
                $type = \IMS\_CoreBundle\Document\Follow\Follow::TYPE_TOOL;
                break;
            default:
                throw new \Doctrine\ORM\Mapping\MappingException("Discriminator {$listing->getDiscr()} does not exist in discriminator map of IMS Listing Entity.");
        }

        if (!$follow = $this->ifUserFollowsListing($follower->getId(), $listing->getId()))
            return false;

        // delete the following
        $dm->remove($follow);
        $dm->flush();

        // update follow QTYs for follower
        \IMS\ServiceBundle\PHPResque\Job\Follow\UpdateUserQtyFollowJob::attach(array(
            'id' => $follower->getId(),
        ));

        // update reputation of follower
        \IMS\ServiceBundle\PHPResque\Job\Account\UpdateReputationJob::attach(array(
            'id' => $follower->getId(),
            'action' => ReputationHandler::ACTION_LISTING_UNFOLLOW,
        ));

        if ($listing->getOwner())
        {
            // update follow QTYs for followed
            \IMS\ServiceBundle\PHPResque\Job\Follow\UpdateUserQtyFollowJob::attach(array(
                'id' => $listing->getOwner()->getId(),
            ));

            // update reputation of followed
            \IMS\ServiceBundle\PHPResque\Job\Account\UpdateReputationJob::attach(array(
                'id' => $listing->getOwner()->getId(),
                'action' => ReputationHandler::ACTION_LISTING_UNFOLLOWED,
            ));
        }
        
        // update followers qty for listing
        \IMS\ServiceBundle\PHPResque\Job\Follow\UpdateListingQtyFollowJob::attach(array(
            'id' => $listing->getId(),
        ));

        return true;
    }

    /**
     * Update all follow QTYs for user (followers, following listings, following users)
     * @param mixed[integer|\IMS\_CoreBundle\Entity\General\Account] $account
     */
    public function updateQtyFollow($account)
    {
        if (!$account instanceof \IMS\_CoreBundle\Entity\General\Account)
        {
            $account = $this->_doctrine->getRepository('IMSCoreBundle:General\Account')->find($account);
        }

        if (!$account)
            return;

        $followRepo = $this->_mongodb->getRepository('IMSCoreBundle:Follow\Follow');

        $account
                ->setQtyFollowers($followRepo->countUserFollowers($account->getId()))
                ->setQtyFollowingUsers($followRepo->countFollowingUsers($account->getId()))
                ->setQtyFollowingServices($followRepo->countFollowingServices($account->getId()))
                ->setQtyFollowingTools($followRepo->countFollowingTools($account->getId()))
        ;
        $this->_doctrine->getManager()->flush();
    }

    /**
     * Checks if the user follows the user
     * @param integer $followerId
     * @param integer $followedId
     * @return mixed[false|\IMS\_CoreBundle\Document\Follow\Follow]
     */
    public function ifUserFollowsUser($followerId, $followedId)
    {
        if ($res = $this->_mongodb->getRepository('IMSCoreBundle:Follow\Follow')->findOneBy(array(
            'followerId' => $followerId,
            'itemId' => $followedId,
            'type' => \IMS\_CoreBundle\Document\Follow\Follow::TYPE_USER)
                ))
            return $res;

        return false;
    }

    /**
     * Checks if the user follows the user
     * @param integer $followerId
     * @param integer $listingId
     * @return boolean
     */
    public function ifUserFollowsListing($followerId, $listingId)
    {
        if ($res = $this->_mongodb->getRepository('IMSCoreBundle:Follow\Follow')->findUserFollowListing($followerId, $listingId))
            return $res;

        return false;
    }

    /**
     * Clear follows related to account
     * @param integer $accountId
     */
    public function deleteUserFollows($accountId)
    {
        $this->_mongodb->getRepository('IMSCoreBundle:Follow\Follow')->deleteFollowsByUserId($accountId);
    }
    
}
