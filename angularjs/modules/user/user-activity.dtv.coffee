###
# USER ACTIVITY DIRECTIVES (follow, like, etc)
###
trdDtvTrdUsrActivity = angular.module "trdDtv.usrActivity", []

# Directive to manage like/dislike action
# @usage:
# oData is trend or item object
# <span trd-user-liking="oData">
#   <a ng-click="fnLike()"></a>
#   <a ng-click="fnDislike()"></a>
# </span>
trdDtvTrdUsrActivity.directive "trdUserLiking", [
    "$rootScope"
    "rscLike"
    "rscTrend"
    "rscItem"
    "analyticSender"
    "trendCacheManager"
    "TRD_LIKE"
    ($rootScope, rscLike, rscTrend, rscItem, analyticSender, trendCacheManager, TRD_LIKE) ->
        scope:
            oEffectData: "=trdUserLiking"
        transclude: true
        link: (scope, element, attrs, userLikingCtrl, transclude) ->
            return if _.isEmpty(scope.oEffectData)

            scope.oLikeStatus = TRD_LIKE.status
            scope.isLoading = false

            scope.$on 'like-data:updated', (event, oUpdatedData)->
                return if oUpdatedData.type is 'composite' # for composite update by cache
                return if oUpdatedData.id isnt scope.oEffectData.id # not for all items
                if oUpdatedData
                    # update popularity index
                    scope.oEffectData.popularity = oUpdatedData.popularity
                    scope.oEffectData.likeStatus = oUpdatedData.likeStatus

            scope.fnLike = ()->
                # if query already loading - do nothing
                return if scope.isLoading

                scope.isLoading = true
                # Switch status value depend on current like state
                scope.oEffectData.likeStatus = if scope.oEffectData.likeStatus is scope.oLikeStatus.positive then scope.oLikeStatus.neutral else scope.oLikeStatus.positive
                
                userLikingCtrl.fnEffect scope.oEffectData.likeStatus
                , ()->
                    scope.isLoading = false
                , (error)->
                    scope.isLoading = false

            scope.fnDislike = ()->
                # if query already loading - do nothing
                return if scope.isLoading

                scope.isLoading = true
                # Switch status value depend on current like state
                scope.oEffectData.likeStatus = if scope.oEffectData.likeStatus is scope.oLikeStatus.negative then scope.oLikeStatus.neutral else scope.oLikeStatus.negative

                userLikingCtrl.fnEffect scope.oEffectData.likeStatus
                , ()->
                    scope.isLoading = false
                , (error)->
                    scope.isLoading = false

            # solution to use different dynamic templates of buttons and isolate scope
            # http://angular-tips.com/blog/2014/03/transclusion-and-scopes/
            transclude scope, (clone, scope)->
                element.append(clone)

        controller: ($scope, $attrs)->
            # Effect method
            @fnEffect = (iStatus, fnSuccessCallback, fnErrorCallback)->
                # Make query to like trend
                rscLike.effect {}
                # Post data
                , {trend:$scope.oEffectData.id, status:iStatus}
                # If success - get new trend/item data
                , ()->
                    if $scope.oEffectData.type is 'composite'
                        oPromise = trendCacheManager.fnUpdateOne($scope.oEffectData.slug)
                        oPromise.then (result)->
                            $scope.isLoading = false
                            $scope.oEffectData = result
                            # Additional logic
                            fnSuccessCallback()
                    else
                        $scope.oEffectData.$get
                            slug: $scope.oEffectData.slug
                        , (data) ->
                            # update popularity index only
                            $rootScope.$broadcast 'like-data:updated', data
                            if !$rootScope.$$phase then $rootScope.$apply()
                            # Additional logic
                            fnSuccessCallback()
                , (error) ->
                    # Additional logic
                    fnErrorCallback(error)

            return @
]



# Directive to manage follow action
# @usage:
# <div trd-follow user-data="user"></div>
trdDtvTrdUsrActivity.directive "trdFollow", [
    "rscFollow"
    "rscUser"
    "user"
    "translator"
    "analyticSender"
    "userCacheManager"
    "$filter"
    (rscFollow, rscUser, user, translator, analyticSender, userCacheManager, $filter) ->
        #restrict: "E"
        template: "<span ng-if=\"withPlus\" class=\"icon trd-add\"></span> {{followStatus}} <span ng-if=\"withUsername\">{{oFollowUser.username|trdUsername}}</span>"
        scope:
            oFollowUser: "=userData"
            # style can be 'plus' or 'username'
            sStyle: "=style"
        link: (scope, element, attrs) ->
            isFollow = false
            scope.isLoading = false

            scope.$watch "oFollowUser.isFollowed", (isFollowedNew, isFollowedOld) ->
                # Update another button states on page
                if isFollowedNew isnt isFollowedOld
                    if isFollowedNew
                        isFollow = true
                        fnMakeUnfollow()
                    else
                        isFollow = false
                        fnMakeFollow()

            fnMakeFollow = ->
                switch scope.sStyle
                    when 'plus'
                        angular.element(element).addClass('btn-border-blue').removeClass('active')
                        scope.followStatus = translator.getTranslation('BTN.FOLLOW')
                        scope.withPlus = true
                        scope.withUsername = false
                    when 'username'
                        angular.element(element).addClass('btn-lg btn-blue')
                        scope.followStatus = translator.getTranslation('BTN.FOLLOW')
                        scope.withPlus = false
                        scope.withUsername = true

            fnMakeUnfollow = ->
                switch scope.sStyle
                    when 'plus'
                        angular.element(element).removeClass('btn-border-blue').addClass('btn-border-green active')
                        scope.followStatus = translator.getTranslation('BTN.UNFOLLOW')
                        scope.withPlus = false
                        scope.withUsername = false
                    when 'username'
                        angular.element(element).addClass('btn-lg btn-blue')
                        scope.followStatus = translator.getTranslation('BTN.UNFOLLOW')
                        scope.withPlus = false
                        scope.withUsername = true

            # Check if user is current authorize user
            if user.username is scope.oFollowUser.username
                angular.element(element).addClass('hidden')
            # Set initial button class depend on user object
            if scope.oFollowUser.isFollowed
                isFollow = true
                fnMakeUnfollow()
            else
                isFollow = false
                fnMakeFollow()

            element.on 'click', ()->
                # if query already loading - do nothing
                return if scope.isLoading
                scope.isLoading = true
                # Send Event to analytics service
                analyticSender.fnTrackEvent([{'name':'mixpanel', 'event':'follow'}])
                # Switch button state immediately
                if isFollow
                    isFollow = false
                    fnMakeFollow()
                else
                    isFollow = true
                    fnMakeUnfollow()

                # Make query to follow user
                rscFollow.effect {}
                # Post data
                , {user:scope.oFollowUser.id}
                # If success - get new user data
                , ()->
                    oPromise = userCacheManager.fnUpdateOne scope.oFollowUser.username
                    oPromise.then (result)->
                       scope.isLoading = false
                       $scope.oFollowUser = result
                # If error - enable button
                , () ->
                    # Enable click
                    scope.isLoading = false
                    # If some problem - switch button state back
                    if isFollow
                        isFollow = false
                        fnMakeFollow()
                    else
                        isFollow = true
                        fnMakeUnfollow()
                    
]

# Directive to manage notifications output
# @usage:
# <div trd-notification user-data="user"></div>
trdDtvTrdUsrActivity.directive "trdNotification", [
    "rscNotification"
    "user"
    "TRD_NOTIFICATION"
    (rscNotification, user, TRD_NOTIFICATION) ->
        templateUrl: "templates/partials/notification.tpl.html"
        controller: ($scope)->
            fnCheckVisible = () ->
                angular.element('#notify-dropdown').is(':visible')

            $scope.notifications = []
            $scope.events = TRD_NOTIFICATION.event
            $scope.statuses = TRD_NOTIFICATION.status
            $scope.state = {
                show: fnCheckVisible()
            }

            angular.element(document).on 'click', (event)->
                # do nothing if notifications not opened
                if not fnCheckVisible()
                    return
                # do nothing if click by notify-dropdown or notify-icon element
                if angular.element(event.target).parents('#notify-dropdown').length > 0 or event.target.id is 'notify-icon'
                    return
                # and at the last - hide dropdown if click by document area
                $scope.fnHideNotifyDropdown()

            $scope.fnHideNotifyDropdown = ->
                $scope.state.show = false
                if !$scope.$$phase then $scope.$apply()

            $scope.fnLoadNotifications = (event)->
                $scope.state.show = !fnCheckVisible()

                if $scope.state.show
                    rscNotification.queryAll
                        limit: 5
                    , (data, getResponseHeaders) ->
                        $scope.notifications = data
                        # reset User unread notificaitons qty
                        user.fnResetQtyUnreadNotifications()
            
]

