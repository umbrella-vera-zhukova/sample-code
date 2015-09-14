###
USER RESOURCE
###
trdRscUser = angular.module "trdRsc.user", [
    "ngResource"
]

trdRscUser.factory "rscUser", [
    "$resource"
    "TRD_API_SETTINGS"
    ($resource, API_SETTINGS) ->
        # Authorization endpoints
        # (they return  X-TokenAuth header and User info)
        sEndpointTokenAuth = "#{API_SETTINGS.ENDPOINT}/auth/token"
        sEndpointGooglePlusAuth = "#{API_SETTINGS.ENDPOINT}/connect/google-plus"
        sEndpointFacebookAuth = "#{API_SETTINGS.ENDPOINT}/connect/facebook"
        # Endpoints to get social connect settings
        sEndpointGPConnectGet = "#{API_SETTINGS.ENDPOINT}/me/social-connect/google-plus"
        sEndpointFBConnectGet = "#{API_SETTINGS.ENDPOINT}/me/social-connect/facebook"
        # Endpoints to create social connections for user
        sEndpointGPConnectAdd = "#{API_SETTINGS.ENDPOINT}/me/social-connect/google-plus"
        sEndpointFBConnectAdd = "#{API_SETTINGS.ENDPOINT}/me/social-connect/facebook"
        # logout endpoint
        sEndpointLogout = "#{API_SETTINGS.ENDPOINT}/auth/logout"
        # forgot password endpoint
        sEndpointForgotPassword = "#{API_SETTINGS.ENDPOINT}/auth/forgot-password"
        # Get User info by X-TokenAuth header
        sEndpointMe = "#{API_SETTINGS.ENDPOINT}/me"
        # Update User info - X-TokenAuth header required
        sEndpointMeUpdate = "#{API_SETTINGS.ENDPOINT}/me"
        # Change user's email
        sEndpointEmailUpdate = "#{API_SETTINGS.ENDPOINT}/me/change-email"
        # Change user's password
        sEndpointPassUpdate = "#{API_SETTINGS.ENDPOINT}/me/change-password"
        # Create new user
        sEndpointJoin = "#{API_SETTINGS.ENDPOINT}/users"
        # Get users list endpoints
        sEndpointUsersByTags = "#{API_SETTINGS.ENDPOINT}/users/by-interests?tags=:tags"
        sEndpointUserByUsername = "#{API_SETTINGS.ENDPOINT}/users/:username"
        sEndpointUserFollowers = "#{API_SETTINGS.ENDPOINT}/users/:username/followers"
        sEndpointUserFollowings = "#{API_SETTINGS.ENDPOINT}/users/:username/followeds"
        # Endpoint to send invites to friend emails
        sEndpointInviteByEmails = "#{API_SETTINGS.ENDPOINT}/me/sent-affiliate-to-friends"

        rscUser = $resource sEndpointTokenAuth, {},
            # User authorization by email & password
            tokenAuth:
                method: "POST"
                headers:
                    "Content-Type": "application/json"

            # Authorize user by google plus
            googlePlusAuth:
                method: "POST"
                headers:
                    "Content-Type": "application/json"
                url: sEndpointGooglePlusAuth
                
            # Authorize user by facebook
            facebookAuth:
                method: "POST"
                headers:
                    "Content-Type": "application/json"
                url: sEndpointFacebookAuth

            # Add google plus connection for User
            addGPConnect:
                method: "POST"
                headers:
                    "Content-Type": "application/json"
                url: sEndpointGPConnectAdd

            # Add facebook connection for User
            addFBConnect:
                method: "POST"
                headers:
                    "Content-Type": "application/json"
                url: sEndpointFBConnectAdd

            # User registration by email & password
            join:
                method: "POST"
                headers:
                    "Content-Type": "application/json"
                url: sEndpointJoin

            # Update current User email
            updateEmail:
                method: "POST"
                headers:
                    "Content-Type": "application/json"
                url: sEndpointEmailUpdate

            # Update current User password
            updatePass:
                method: "POST"
                headers:
                    "Content-Type": "application/json"
                url: sEndpointPassUpdate

             # Update current User data
            updateSelf:
                method: "PUT"
                headers:
                    "Content-Type": "application/json"
                url: sEndpointMeUpdate

            # Get User data by TokenAuth
            getSelf:
                method: "GET"
                url: sEndpointMe

            # Get Google plus connect settings
            getGPConnect:
                method: "GET"
                url: sEndpointGPConnectGet

            # Get Facebook connect settings
            getFBConnect:
                method: "GET"
                url: sEndpointFBConnectGet

            # Get Users list by tags (interests)
            getListByTags:
                method: "GET"
                isArray: true
                params: {tags:null}
                url: sEndpointUsersByTags

            # Get User info by username
            getByUsername:
                method: "GET"
                params: {username:null}
                url: sEndpointUserByUsername

            # Get list of user followers
            getFollowers:
                method: "GET"
                params: {username:null, page:1, limit:10}
                isArray: true
                url: sEndpointUserFollowers

            # Get list of users, that are followed by the user
            getFollowings:
                method: "GET"
                params: {username:null, page:1, limit:10}
                isArray: true
                url: sEndpointUserFollowings

            logout:
                method: "DELETE"
                headers:
                    "Content-Type": "application/json"
                url: sEndpointLogout

            inviteByEmails:
                method: "POST"
                headers:
                    "Content-Type": "application/json"
                url: sEndpointInviteByEmails

            resetPassword:
                method: "POST"
                headers:
                    "Content-Type": "application/json"
                url: sEndpointForgotPassword

        rscUser.prototype.$getFullName = () ->
            if @profile.firstName and @profile.lastName
                return @profile.firstName + ' ' + @profile.lastName
            else if @profile.firstName
                return @profile.firstName
            else if @profile.lastName
                return @profile.lastName
            else
                return null

        rscUser.prototype.$getAvatarThumb = () ->
            if @profile and @profile.avatar then return @profile.avatar.sources.mediumCrop else null

        return rscUser

]