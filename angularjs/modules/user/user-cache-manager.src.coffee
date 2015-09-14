###
USER CACHE MANAGING SERVICE
Once user data loaded on client-side, cache it for further quick access.
###
trdUserCache = angular.module "trdSvc.userCache", [
]

trdUserCache.service "userCacheFactory", [
    "CacheFactory"
    (CacheFactory)->
        unless CacheFactory.get('userCacheFactory')
            CacheFactory.createCache 'userCacheFactory',
                maxAge: 10 * 60 * 1000 # 10 minutes
                deleteOnExpire: 'aggressive'

        return userCacheFactory = CacheFactory.get('userCacheFactory')
]

trdUserCache.service "userCacheManager", [
    "$rootScope"
    "rscUser"
    "userCacheFactory"
    "$q"
    "$state"
    ($rootScope, rscUser, userCacheFactory, $q, $state)->
        sPreffix = "/user/"

        fnPutToCache = (sUrlId, oUserData)->
            if oUserData
                sUrlId = sPreffix+oUserData.username
                oUserData = new rscUser(oUserData) if oUserData not instanceof rscUser
                userCacheFactory.put(sUrlId, oUserData)
        
        userCacheManager =
            fnGetOne: (sUsername)->
                sUrlId = sPreffix+sUsername
                oUserData = userCacheFactory.get sUrlId
                if oUserData
                    return oUserData

            fnUpdateOne: (sUsername)->
                sUrlId = sPreffix+sUsername
                oUser = @fnGetOne(sUsername)|| new rscUser

                deferred = $q.defer()
                rscUser.getByUsername
                    username: sUsername
                , (oFullUser) ->
                    oExtendedUser = angular.extend(oUser, oFullUser)
                    fnPutToCache(sUrlId, oExtendedUser)
                    deferred.resolve(oExtendedUser)
                deferred.promise

            fnUpdateCacheWithData:(oUserData)->
                if oUserData
                    sUrlId = sPreffix+oUserData.username
                    oUser = @fnGetOne(oUserData.username)|| new rscUser
                    oExtendedUser = angular.extend(oUser, oUserData)
                    fnPutToCache(sUrlId, oExtendedUser)

            fnPutOne: (oUserData)->
                sUrlId = sPreffix+oUserData.username
                fnPutToCache(sUrlId, oUserData)

            fnPutOneIfNoCache: (oUserData)->
                if oUserData
                    sUrlId = sPreffix+oUserData.username
                    oCachedUser = userCacheFactory.get(sUrlId)
                    unless oCachedUser
                        fnPutToCache(sUrlId, oUserData)
                    # Touch user object in cache
                    userCacheFactory.touch(sUrlId)

        userCacheManager
]