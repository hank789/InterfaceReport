<section class="content-header">
    <h1>
        性能监控
        <small>接口监控</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> 性能监控</a></li>
        <li class="active">接口监控</li>
    </ol>
</section>
<section class="content">
	<div class="row clearfix">
        <div class="col-md-12 column">
		<div class="row clearfix">
			<ul><?php echo $module_str;?></ul>
		</div>

		<?php if($err_msg){?>
			<div class="alert alert-dismissable alert-danger">
				 <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
				<strong><?php echo $err_msg;?></strong> 
			</div>
		<?php }?>
		<?php if($module && $interface){?>
			<div class="row clearfix">
				<div class="col-md-12 column text-center">
					<?php echo $date_btn_str;?>
				</div>
			</div>
			<div class="row clearfix">
				<div class="col-md-12 column height-400" id="req-container" >
				</div>
			</div>
			<div class="row clearfix">
				<div class="col-md-12 column height-400" id="time-container" >
				</div>
			</div>
			<?php if($module && $interface){?>
			<script>
			Highcharts.setOptions({
				global: {
					useUTC: false
				}
			});
				$('#req-container').highcharts({
					chart: {
						type: 'spline'
					},
					title: {
						text: '<?php echo "$date $interface_name";?>  请求量曲线'
					},
					subtitle: {
						text: ''
					},
					xAxis: {
						type: 'datetime',
						dateTimeLabelFormats: { 
							hour: '%H:%M'
						}
					},
					yAxis: {
						title: {
							text: '请求量(次/5分钟)'
						},
						min: 0
					},
					tooltip: {
						formatter: function() {
							return '<p style="color:'+this.series.color+';font-weight:bold;">'
							+ this.series.name + 
							'</p><br /><p style="color:'+this.series.color+';font-weight:bold;">时间：' + Highcharts.dateFormat('%m月%d日 %H:%M', this.x) + 
							'</p><br /><p style="color:'+this.series.color+';font-weight:bold;">数量：'+ this.y + '</p>';
						}
					},
					credits: {
						enabled: false,
					},
					series: [{
						name: '成功曲线',
						data: [
							<?php echo $success_series_data;?>
						],
						lineWidth: 2,
						marker:{
							radius: 1
						},
						
						pointInterval: 300*1000
					},
					{
						name: '失败曲线',
						data: [
							<?php echo $fail_series_data;?>
						],
						lineWidth: 2,
						marker:{
							radius: 1
						},
						pointInterval: 300*1000,
						color : '#9C0D0D'
					}]
				});
				$('#time-container').highcharts({
					chart: {
						type: 'spline'
					},
					title: {
						text: '<?php echo "$date $interface_name";?>  请求耗时曲线'
					},
					subtitle: {
						text: ''
					},
					xAxis: {
						type: 'datetime',
						dateTimeLabelFormats: { 
							hour: '%H:%M'
						}
					},
					yAxis: {
						title: {
							text: '平均耗时(单位：毫秒)'
						},
						min: 0
					},
					tooltip: {
						formatter: function() {
							return '<p style="color:'+this.series.color+';font-weight:bold;">'
							 + this.series.name + 
							 '</p><br /><p style="color:'+this.series.color+';font-weight:bold;">时间：' + Highcharts.dateFormat('%m月%d日 %H:%M', this.x) + 
							 '</p><br /><p style="color:'+this.series.color+';font-weight:bold;">平均耗时：'+ this.y + '</p>';
						}
					},
					credits: {
						enabled: false,
					},
					series: [{
						name: '成功曲线',
						data: [
							<?php echo $success_time_series_data;?>
						],
						lineWidth: 2,
						marker:{
							radius: 1
						},
						pointInterval: 300*1000
					},
					{
						name: '失败曲线',
						data: [
								<?php echo $fail_time_series_data;?>
						],
						lineWidth: 2,
						marker:{
							radius: 1
						},
						pointInterval: 300*1000,
						color : '#9C0D0D'
					}]
				});
			</script>
			<?php }?>
			<table class="table table-hover table-condensed table-bordered">
				<thead>
					<tr>
						<th>时间</th><th>调用总数</th><th>平均耗时(ms)</th><th>成功调用总数</th><th>成功平均耗时(ms)</th><th>失败调用总数</th><th>失败平均耗时(ms)</th><th>成功率</th>
					</tr>
				</thead>
				<tbody>
				<?php echo $table_data;?>
				</tbody>
			</table>
			<?php }?>
        </div>
	</div>
</section>
