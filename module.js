var app = angular.module('myApp',[]);
app.controller('telstController', ['$scope',
function($scope){
$scope.doClick = function() {
	alert('bo!');
	}
	}]);
