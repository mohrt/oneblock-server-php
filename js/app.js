var app = angular.module( 'oneBlockApp', [] );

app.controller( 'MainCtrl', function( $scope ) {
	$scope.nonce = null;
	$scope.init = function()
	{
		if(oneblock_session && oneblock_session.logged_in) {
			$scope.data = oneblock_session;
			$('#logged_out_block').addClass('hide');
			$('#logged_in_block').removeClass('hide');
			return;
		} else {
			$('#logged_in_block').addClass('hide');
			$('#logged_out_block').removeClass('hide');		
			$scope.startTime = new Date().getTime();
		}
			
		$.ajax({
		  type: "GET",
		  url: "/api/login",
		  data: null,
		  success: function(data) {
		    var regExp = /x=([^&]+)/;
		    var matches = regExp.exec(data.challenge);
		    $scope.nonce = matches[1];
			new QRCode($("#login-qr")[0], data.challenge);
			$("#login-url").html(data.challenge);
			$("#login-link").attr("href",data.challenge);
			$scope.interval = setInterval($scope.checkLogin,3000);
		  },
		  contentType: "application/json",
		  dataType: "json"
		});
	}
	$scope.checkLogin = function() {
	    if(new Date().getTime() - $scope.startTime > 300000){
	    	// 5 minutes passed, nonce invalid
			clearInterval($scope.interval);
			return;
    	}	
		$.ajax({
		  type: "POST",
		  url: "/api/check",
		  data: JSON.stringify({"nonce": $scope.nonce}),
		  success: function(data) {
			clearInterval($scope.interval);
			$scope.$apply(function(){
				$scope.data = data;
			});
			$('#logged_out_block').addClass('hide');
			$('#logged_in_block').removeClass('hide');
		  },
		  contentType: "application/json",
		  dataType: "json"
		});
	}
});
