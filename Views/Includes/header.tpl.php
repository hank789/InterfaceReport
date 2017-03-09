<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>业务监控平台</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <!-- Tell the iOS browser to disable telephoe number detection -->
    <meta name="format-detection" content="telephone=no">
    <!-- Bootstrap 3.3.6 -->
    <link class="common-asset" rel="stylesheet" href="/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link class="common-asset" rel="stylesheet" href="/css/font-awesome.min.css">
    <!-- Theme style -->
    <link class="common-asset" rel="stylesheet" href="/css/AdminLTE.min.css">
    <!-- AdminLTE Skins. Choose a skin from the css/skins folder instead of downloading all of them to reduce the load. -->
    <link class="common-asset" rel="stylesheet" href="/css/skins/_all-skins.min.css">
    <!-- Morris chart -->
    <link class="common-asset" rel="stylesheet" href="/plugins/morris/morris.css">
    <!-- jvectormap -->
    <link class="common-asset" rel="stylesheet" href="/plugins/jvectormap/jquery-jvectormap-1.2.2.css">
    <!-- Date Picker -->
    <link class="common-asset" rel="stylesheet" href="/plugins/datepicker/datepicker3.css">
    <!-- Daterange picker -->
    <link class="common-asset" rel="stylesheet" href="/plugins/daterangepicker/daterangepicker-bs3.css">
    <!-- bootstrap wysihtml5 - text editor -->
    <!--<link  class="common-asset" rel="stylesheet" href="/assets/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">-->
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script class="common-asset" src="/js/html5shiv.min.js"></script>
    <script class="common-asset" src="/js/respond.min.js"></script>
    <![endif]-->

    <script class="common-asset" type="text/javascript" src="/plugins/jQuery/jQuery-2.2.0.min.js"></script>
    <!-- Bootstrap 3.3.6 -->
    <script class="common-asset" type="text/javascript" src="/js/bootstrap.min.js"></script>
    <!-- AdminLTE App -->
    <script class="common-asset" type="text/javascript" src="/js/app.js"></script>
    <!-- Bootbox.js -->
    <script class="common-asset" type="text/javascript" src="/js/bootbox.min.js"></script><script class="common-asset" type="text/javascript">bootbox.addLocale("CN", {OK: '确定', CANCEL: '取消', CONFIRM: '确认'}); bootbox.setDefaults({locale: "CN", onEscape: true});</script>
    <script class="common-asset" type="text/javascript" src="/js/jquery.notify.js"></script>
    <script class="common-asset" type="text/javascript" src="/js/highcharts.js"></script>

</head>
<body class="hold-transition skin-green-light sidebar-mini">
<div class="wrapper">
    <header class="main-header">
        <!-- Logo -->
        <a onclick="window.nav(this, event); return false;" href="/" class="logo">
            <!-- mini logo for sidebar mini 50x50 pixels -->
            <span class="logo-mini">监控</span>
            <!-- logo for regular state and mobile devices -->
            <span class="logo-lg">业务监控平台</span>
        </a>
        <!-- Header Navbar: style can be found in header.less -->
        <nav class="navbar navbar-static-top">
            <!-- Sidebar toggle button-->
            <a href="javascript:;" class="sidebar-toggle" data-toggle="offcanvas" role="button">
                <span class="sr-only">切换菜单</span>
            </a>
            <div class="navbar-custom-menu">
                <ul class="nav navbar-nav">
                    <li>
                        <a href="javascript:;">
                        </a>
                    </li>
                    <li class="dropdownu user-menu">
                        <a href="javascript:;" class="dropdown-toggle" data-toggle="dropdown">
                            <img src="/img/user2-160x160.jpg" class="user-image" alt="User Image">
                            <span class="hidden-xs"><?php echo @$_SESSION['admin'] ?></span>
                        </a>
                        <ul class="dropdown-menu" style="width:160px;">
                            <li>
                                <a href="/?md=admin&fn=admin&act=detect_server">探测数据源</a>
                            </li>
                            <li>
                                <a href="/?md=admin&fn=admin">数据源管理</a>
                            </li>
                            <li>
                                <a href="/?md=admin&fn=setting">系统设置</a>
                            </li>
                            <?php if(isset($_SESSION['admin'])) {?>
                                <li>
                                    <a href="/?md=user&fn=logout">退出</a>
                                </li>
                            <?php }?>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
    <!-- Left side column. contains the logo and sidebar -->
    <aside class="main-sidebar">
        <!-- sidebar: style can be found in sidebar.less -->
        <section class="sidebar">
            <!-- sidebar menu: : style can be found in sidebar.less -->
            <ul class="sidebar-menu">
                <li class="treeview active">
                    <a href="javascript:;"> <i class="fa fa-home"></i><span>性能监控</span> </a>
                    <ul class="treeview-menu">
                        <li <?php if($_SESSION['md'] == 'Performance' && $_SESSION['fn'] == 'main') { ?> class="active"<?php } ?>><a onclick="window.nav(this, event); return false;" href="/"><i class="fa fa-pie-chart"></i> 总览</a></li>
                        <li <?php if($_SESSION['md'] == 'Performance' && $_SESSION['fn'] == 'statistic') { ?> class="active"<?php } ?>><a onclick="window.nav(this, event); return false;" href="/?md=performance&fn=statistic"><i class="fa fa-area-chart"></i> 接口监控</a></li>
                        <li <?php if($_SESSION['md'] == 'Performance' && $_SESSION['fn'] == 'logger') { ?> class="active"<?php } ?>><a onclick="window.nav(this, event); return false;" href="/?md=performance&fn=logger"><i class="fa fa-area-chart"></i> 错误日志</a></li>
                        <li <?php if($_SESSION['md'] == 'Performance' && $_SESSION['fn'] == 'order') { ?> class="active"<?php } ?>><a onclick="window.nav(this, event); return false;" href="/?md=performance&fn=order"><i class="fa fa-area-chart"></i> 访问排行</a></li>
                    </ul>
                </li>
            </ul>
        </section>
        <!-- /.sidebar -->
    </aside>
    <div class="content-wrapper">