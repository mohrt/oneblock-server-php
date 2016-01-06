<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>1Block</title>

	<link rel="icon" type="image/png" href="img/1blockico.png">

    <!-- Bootstrap Core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="css/1block.css" rel="stylesheet">
    <link href="css/fonts.css" rel="stylesheet">

    <!-- Custom Fonts -->
    <link href="font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    
    <script>var oneblock_session = <?php echo !empty($_SESSION['oneblock']) ? json_encode($_SESSION['oneblock']) : '[]'; ?>;</script>

</head>

<body id="page-top" data-spy="scroll" data-target=".navbar-fixed-top" ng-app="oneBlockApp" ng-controller="MainCtrl" data-ng-init="init()">

    <!-- Navigation -->
    <nav class="navbar navbar-custom navbar-fixed-top" role="navigation">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-main-collapse">
                    <i class="fa fa-bars"></i>
                </button>
                <a class="navbar-brand page-scroll" href="#page-top">
                    <div class="icon-1block-logo" title="Home"></div>
                </a>
            </div>

            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse navbar-right navbar-main-collapse">
                <ul class="nav navbar-nav">
                    <!-- Hidden li included to remove active class from about link when scrolled up past about section -->
                    <li class="hidden">
                        <a href="#page-top"></a>
                    </li>
                    <li>
                        <a class="page-scroll" href="/index.html#about">About</a>
                    </li>
                    <li>
                        <a class="page-scroll" href="/index.html#download">Download</a>
                    </li>
                    <li>
                        <a class="page-scroll" href="/index.html#contact">Contact</a>
                    </li>
                    <li>
                        <a class="page-scroll" href="/demo.html">Demo</a>
                    </li>
                </ul>
            </div>
            <!-- /.navbar-collapse -->
        </div>
        <!-- /.container -->
    </nav>

    <section id="demo" class="container content-section text-center">
		<div class="row">
			<div class="col-md-4 col-md-offset-4">
				<div id="logged_out_block" class="hide">
					<a href="" id="login-link">
					<div id="login-qr"></div>
					<p id="login-url"></p>
					</a>
					<p class="intro-text">Login</p>			    
				</div>
				<div id="logged_in_block" class="hide">
					<p>You have logged in {{ data.logins }} times. <a href="/logout.php">[logout]</a></p>
				</div>
			</div>
		</div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container text-center">
            <p>Copyright &copy; 2015 1Block, all rights reserved.</p>
        </div>
    </footer>

    <!-- jQuery -->
    <script src="js/jquery.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="js/bootstrap.min.js"></script>

    <!-- Plugin JavaScript -->
    <script src="js/jquery.easing.min.js"></script>

    <!-- AngularJS -->
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.3.14/angular.min.js"></script>
    <script src="js/app.js"></script>

    <!-- Custom Theme JavaScript -->
    <script src="js/1block.js"></script>     

    <!-- QR Code Encoder -->
    <script src="js/qrcode.min.js"></script>

</body>

</html>
