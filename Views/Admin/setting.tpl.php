<section class="content-header">
    <h1>
        管理
        <small>设置</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> 管理</a></li>
        <li class="active">设置</li>
    </ol>
</section>
<section class="content">
	<div class="row clearfix">
		<div class="col-md-12 column">
			<?php if($suc_msg){?>
				<div class="alert alert-dismissable alert-success">
				 <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
				 <strong><?php echo $suc_msg;?></strong> 
				</div>
			<?php }elseif($err_msg){?>
				<div class="alert alert-dismissable alert-danger">
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
					<strong><?php echo $err_msg;?></strong> 
				</div>
			<?php }elseif($notice_msg){?>
			<div class="alert alert-dismissable alert-info">
				 <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
				<strong><?php echo $notice_msg;?></strong>
			</div>
			<?php }?>
		</div>
	</div>
	<div class="row clearfix">
		<div class="col-md-3 column">
		</div>
		<div class="col-md-6 column">
			<form class="form-horizontal" role="form" action="/?fn=setting&act=save" method="post">
				<div class="form-group">
					 <label class="col-sm-3 control-label">数据源探测端口</label>
					<div class="col-sm-9">
						<input class="form-control" name="detect_port" value="<?php echo $detect_port;?>"/>
					</div>
				</div>
				<div class="form-group">
					<div class="col-sm-offset-3 col-sm-9">
						 <button type="submit" class="btn btn-default">保存</button>
					</div>
				</div>
			</form>
		</div>
		<div class="col-md-3 column">
		</div>
	</div>
</section>
