<!DOCTYPE html>
<html lang="zh">
<head>
  <meta charset="utf-8">
  <title>统计与监控</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

	<!--link rel="stylesheet/less" href="/less/bootstrap.less" type="text/css" /-->
	<!--link rel="stylesheet/less" href="/less/responsive.less" type="text/css" /-->
	<!--script src="/js/less-1.3.3.min.js"></script-->
	<!--append ‘#!watch’ to the browser URL, then refresh the page. -->
	
	<link href="/css/bootstrap.min.css" rel="stylesheet">

  <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
  <!--[if lt IE 9]>
    <script src="js/html5shiv.js"></script>
  <![endif]-->

  <!-- Fav and touch icons -->
  <link rel="apple-touch-icon-precomposed" sizes="144x144" href="img/apple-touch-icon-144-precomposed.png">
  <link rel="apple-touch-icon-precomposed" sizes="114x114" href="img/apple-touch-icon-114-precomposed.png">
  <link rel="apple-touch-icon-precomposed" sizes="72x72" href="img/apple-touch-icon-72-precomposed.png">
  <link rel="apple-touch-icon-precomposed" href="img/apple-touch-icon-57-precomposed.png">
  <link rel="shortcut icon" href="img/favicon.png">

    <script class="common-asset" type="text/javascript" src="/plugins/jQuery/jQuery-2.2.0.min.js"></script>
    <!-- Bootstrap 3.3.6 -->
    <script class="common-asset" type="text/javascript" src="/js/bootstrap.min.js"></script>
</head>
<body>
<div class="container">
	<div class="row clearfix">
		<div class="col-md-4 column">
		</div>
		<div class="col-md-4 column">
		<?php if(!empty($msg)){?>
			<div class="alert alert-dismissable alert-danger">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
				<h4>
					<?php echo $msg;?>
				</h4> 
			</div>
		<?php }?>
			<h1>接口性能统计平台</h1>
			<form role="form" method="POST" action="">
				<div class="form-group">
					 <label>用户名</label><input type="text" name="admin_name" class="form-control" />
				</div>
				<div class="form-group">
					 <label for="exampleInputPassword1">密码</label><input type="password" name="admin_password"  class="form-control" id="exampleInputPassword1" />
				</div>
				<button type="submit" class="btn btn-default">登录</button>
			</form>
		</div>
		<div class="col-md-4 column">
		</div>
	</div>
</div>
<div class="footer"></div>
</body>
</html>
