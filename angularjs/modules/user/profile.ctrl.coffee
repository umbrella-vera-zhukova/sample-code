###
USER PROFILE CONTROLLERS
###

trdProfile = angular.module "trdProfile", []

trdProfile.config [
    "$stateProvider"
    config = ($stateProvider) ->
        $stateProvider
        .state("profile",
            url: "/profile/:username"
            abstract: true
            views:
                "@":
                    templateUrl: 'modules/profile/templates/profile.tpl.html'
                "search@":
                    templateUrl: "templates/search/trend-search-form.tpl.html"
        )
        .state("profile.items",
            url: "/items"
            controller: "trdProfile.ctrlItemList"
            templateUrl: "modules/trend/templates/item/list.tpl.html"
        )
        .state("profile.trends",
            url: "/trends"
            controller: "trdProfile.ctrlTrendList"
            templateUrl: "modules/trend/templates/trend/list.tpl.html"
        )
        .state("profile.followers",
            url: "/followers"
            controller: "trdProfile.ctrlFollowerList"
            templateUrl: "modules/profile/templates/followers.tpl.html"
        )
        .state("profile.followings",
            url: "/following"
            controller: "trdProfile.ctrlFollowingList"
            templateUrl: "modules/profile/templates/followings.tpl.html"
        )
]

trdProfile.controller "trdProfile.ctrlUserbar", [
    "$scope"
    "$state"
    "$stateParams"
    "user"
    "userCacheManager"
    CtrlUserbar = ($scope, $state, $stateParams, user, userCacheManager) ->
        $scope.oUser = null
        # watch for state updating
        $scope.$on "$stateChangeSuccess", (event, toState) ->
            $scope.oUser = userCacheManager.fnGetOne $stateParams.username
            oPromise = userCacheManager.fnUpdateOne $stateParams.username
            oPromise.then (result)->
               $scope.oUser = result
]

trdProfile.controller "trdProfile.ctrlSidebar", [
    "$scope"
    "$state"
    "$stateParams"
    "user"
    "userCacheManager"
    CtrlSidebar = ($scope, $state, $stateParams, user, userCacheManager) ->
        $scope.oUser = null
        # watch for state updating
        $scope.$on "$stateChangeSuccess", (event, toState) ->
            $scope.oUser = userCacheManager.fnGetOne $stateParams.username
            oPromise = userCacheManager.fnUpdateOne $stateParams.username
            oPromise.then (result)->
               $scope.oUser = result
]

trdProfile.controller "trdProfile.ctrlItemList", [
    "$scope"
    "$stateParams"
    "rscItem"
    "page"
    "cfpLoadingBar"
    "TRD_LIST"
    CtrlProfileItems = ($scope, $stateParams, rscItem, page, cfpLoadingBar, TRD_LIST) ->
        $scope.items = []
        # for internal pagination
        $scope.page = 1
        # for external youtube pagination
        $scope.nextPageToken = null
        # need to initialize property for watching by preloader directive
        $scope.isLoading = false

        $scope.hasMore = undefined

        # set page title options
        page.fnUpdateTitleWith({'userName':$stateParams.username})

        $scope.fnLoadData = ()->
            $scope.isLoading = true
            cfpLoadingBar.start()

            rscItem.queryByUser
                page: $scope.page
                limit: TRD_LIST.LIMIT
                username: $stateParams.username
            , (data, getResponseHeaders) ->
                $scope.hasMore = if getResponseHeaders("x-page-next") == "false" then false else true
                $scope.isLoading = false
                cfpLoadingBar.complete()
                $scope.items = $scope.items.concat(data) if data.length > 0
                $scope.itemType = 'trendii'

        # init items loading pass object to dialog/modal at a certain state
        $scope.fnLoadData()

        $scope.fnShowMore = ()->
            return if $scope.isLoading or !$scope.hasMore
            $scope.page += 1
            $scope.fnLoadData()
]

trdProfile.controller "trdProfile.ctrlTrendList", [
    "$scope"
    "$stateParams"
    "page"
    "trendCacheManager"
    "cfpLoadingBar"
    "TRD_LIST"
    CtrlProfileTrends = ($scope, $stateParams, page, trendCacheManager, cfpLoadingBar, TRD_LIST) ->
        $scope.trends = []
        # for internal pagination
        $scope.page = 1
        # for external youtube pagination
        $scope.nextPageToken = null
        # need to initialize property for watching by preloader directive
        $scope.isLoading = false

        $scope.hasMore = undefined

        # set page title options
        page.fnUpdateTitleWith({'userName':$stateParams.username})

        $scope.fnLoadData = ()->
            return if $scope.isLoading

            $scope.isLoading = true
            cfpLoadingBar.start()
            oPromise = trendCacheManager.fnGetProfileList
                page: $scope.page
                limit: TRD_LIST.LIMIT
                username: $stateParams.username
            oPromise.then (result)->
                $scope.isLoading = false
                cfpLoadingBar.complete()
                $scope.trends = result.trends
                $scope.hasMore = result.hasMore
                $scope.page = result.page

        $scope.fnShowMore = ()->
            return if $scope.isLoading or $scope.hasMore is false
            
            $scope.isLoading = true
            cfpLoadingBar.start()
            
            $scope.page += 1
            unless trendCacheManager.fnIsProfileListExist({username: $stateParams.username})
                $scope.page = 1

            oPromise = trendCacheManager.fnProfileListConcat
                page: $scope.page
                limit: TRD_LIST.LIMIT
                username: $stateParams.username
            oPromise.then (result)->
                $scope.isLoading = false
                cfpLoadingBar.complete()
                $scope.trends = if $scope.page is 1 then result.trends else $scope.trends.concat(result.trends)
                $scope.hasMore = result.hasMore
                $scope.page = result.page

        # init items loading
        $scope.fnLoadData()
]


trdProfile.controller "trdProfile.ctrlFollowerList", [
    "$scope"
    "$stateParams"
    "rscUser"
    "page"
    "cfpLoadingBar"
    "TRD_LIST"
    CtrlProfileFollowers = ($scope, $stateParams, rscUser, page, cfpLoadingBar, TRD_LIST) ->
        $scope.users = []
        # for internal pagination
        $scope.page = 1
        # for external youtube pagination
        $scope.nextPageToken = null
        # need to initialize property for watching by preloader directive
        $scope.isLoading = false

        $scope.hasMore = undefined

        # set page title options
        page.fnUpdateTitleWith({'userName':$stateParams.username})

        $scope.fnLoadData = ()->
            $scope.isLoading = true
            cfpLoadingBar.start()

            rscUser.getFollowers
                username: $stateParams.username
                page: $scope.page
                limit: TRD_LIST.LIMIT
            , (data, getResponseHeaders) ->
                $scope.hasMore = if getResponseHeaders("x-page-next") == "false" then false else true
                $scope.isLoading = false
                cfpLoadingBar.complete()
                $scope.users = $scope.users.concat(data) if data.length > 0

        # init items loading pass object to dialog/modal at a certain state
        $scope.fnLoadData()

        $scope.fnShowMore = ()->
            return if $scope.isLoading or !$scope.hasMore
            $scope.page += 1
            $scope.fnLoadData()
]

trdProfile.controller "trdProfile.ctrlFollowingList", [
    "$scope"
    "$stateParams"
    "rscUser"
    "page"
    "cfpLoadingBar"
    "TRD_LIST"
    CtrlProfileFollowings = ($scope, $stateParams, rscUser, page, cfpLoadingBar, TRD_LIST) ->
        $scope.users = []
        # for internal pagination
        $scope.page = 1
        # for external youtube pagination
        $scope.nextPageToken = null
        # need to initialize property for watching by preloader directive
        $scope.isLoading = false

        $scope.hasMore = undefined

        # set page title options
        page.fnUpdateTitleWith({'userName':$stateParams.username})

        $scope.fnLoadData = ()->
            $scope.isLoading = true
            cfpLoadingBar.start()

            rscUser.getFollowings
                username: $stateParams.username
                page: $scope.page
                limit: TRD_LIST.LIMIT
            , (data, getResponseHeaders) ->
                $scope.hasMore = if getResponseHeaders("x-page-next") == "false" then false else true
                $scope.isLoading = false
                cfpLoadingBar.complete()
                $scope.users = $scope.users.concat(data) if data.length > 0

        # init items loading pass object to dialog/modal at a certain state
        $scope.fnLoadData()

        $scope.fnShowMore = ()->
            return if $scope.isLoading or !$scope.hasMore
            $scope.page += 1
            $scope.fnLoadData()
]
