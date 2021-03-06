<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="A Components Mix Bootstarp 3 Admin Dashboard Template">
<meta name="author" content="Westilian">
<title>Nuta Cloud - Login Trial</title>
<link rel="stylesheet" href="<?=base_url();?>css/font-awesome.css" type="text/css">
<link rel="stylesheet" href="<?=base_url();?>css/bootstrap.css" type="text/css">
<link rel="stylesheet" href="<?=base_url();?>css/animate.css" type="text/css">
<link rel="stylesheet" href="<?=base_url();?>css/waves.css" type="text/css">
<link rel="stylesheet" href="<?=base_url();?>css/layout.css" type="text/css">
<link rel="stylesheet" href="<?=base_url();?>css/components.css" type="text/css">
<link rel="stylesheet" href="<?=base_url();?>css/plugins.css" type="text/css">
<link rel="stylesheet" href="<?=base_url();?>css/common-styles.css" type="text/css">
<link rel="stylesheet" href="<?=base_url();?>css/pages.css" type="text/css">
<link rel="stylesheet" href="<?=base_url();?>css/responsive.css" type="text/css">
<link rel="stylesheet" href="<?=base_url();?>css/matmix-iconfont.css" type="text/css">
<link href="http://fonts.googleapis.com/css?family=Roboto:400,300,400italic,500,500italic" rel="stylesheet" type="text/css">
<link href="http://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,400,600" rel="stylesheet" type="text/css">
</head>
<body class="login-page">
    <div class="page-container">
        <div class="login-branding">
            <a href="index-2.html"><img src="<?=base_url();?>images/logo-large.png" alt="logo"></a>
        </div>
        <div class="login-container">
            <img class="login-img-card" src="<?=base_url();?>images/avatar/jaman-01.jpg" alt="login thumb" />
            <form class="form-signin" action="<?=base_url();?>cloud/trial" method="post">
                <input type="text" id="inputEmail" class="form-control" placeholder="Your Device ID" name="devid" required autofocus>
                <div id="remember" class="checkbox">
                    <label>
                        <input type="checkbox" class="switch-mini" /> Remember Me
                    </label>
                </div>
                <button class="btn btn-primary btn-block btn-signin" type="submit">Sign In</button>
            </form>
        </div>

        <div class="login-footer">
            &copy; 2015 Nuta Cloud

        </div>

    </div>
    <script src="<?=base_url();?>js/jquery-1.11.2.min.js"></script>
    <script src="<?=base_url();?>js/jquery-migrate-1.2.1.min.js"></script>
    <script src="<?=base_url();?>js/jRespond.min.js"></script>
    <script src="<?=base_url();?>js/bootstrap.min.js"></script>
    <script src="<?=base_url();?>js/nav-accordion.js"></script>
    <script src="<?=base_url();?>js/hoverintent.js"></script>
    <script src="<?=base_url();?>js/waves.js"></script>
    <script src="<?=base_url();?>js/switchery.js"></script>
    <script src="<?=base_url();?>js/jquery.loadmask.js"></script>
    <script src="<?=base_url();?>js/icheck.js"></script>
    <script src="<?=base_url();?>js/bootbox.js"></script>
    <script src="<?=base_url();?>js/animation.js"></script>
    <script src="<?=base_url();?>js/colorpicker.js"></script>
    <script src="<?=base_url();?>js/bootstrap-datepicker.js"></script>
    <script src="<?=base_url();?>js/floatlabels.js"></script>
    <script src="<?=base_url();?>js/smart-resize.js"></script>
    <script src="<?=base_url();?>js/layout.init.js"></script>
    <script src="<?=base_url();?>js/matmix.init.js"></script>
    <script src="<?=base_url();?>js/retina.min.js"></script>


</html>
