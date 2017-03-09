<section class="content-header">
    <h1>
        注册统计
        <small>总览</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> 注册统计</a></li>
        <li class="active">总览</li>
    </ol>
</section>
<section class="content">
<div class="row clearfix">
<div class="col-md-12 column">
<?php if($err_msg){?>
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
<div class="row clearfix">
    <div class="col-md-12 column text-center">
        <?php echo $date_btn_str;?>
    </div>
    <!-- 自动刷新 start -->
    <div style="text-align: right">
        <select name="refresh" id="refresh">
            <?php foreach($refreshSeconds as $second): ?>
                <option value="<?= $second ?>"><?= $second ?>秒自动刷新</option>
            <?php endforeach; ?>
        </select>
    </div><!-- 自动刷新 end -->
</div>

<div class="row clearfix">
    <div class="col-md-12 column height-400" id="req-container" >
    </div>
</div>

<script>
    // 自动刷新 ----------------- start
    var refreshItem;
    function setRefreshSeconds () {
        var seconds =$('#refresh').val();
        if(isNaN(seconds)) seconds = $('#refresh option:eq(0)').val();

        localStorage.setItem('_refreshMainSeconds', seconds);
        return seconds;
    }

    function refreshMain () {
        var seconds = localStorage.getItem('_refreshMainSeconds');
        if (seconds == null || isNaN(seconds)) {	// 首次进入
            seconds = setRefreshSeconds();
        }

        $('#refresh').val(seconds);
        refreshItem && clearInterval(refreshItem);
        refreshItem = setInterval(function(){
            window.location.reload();
        }, seconds * 1000);
    }

    $('#refresh').change(function () {
        setRefreshSeconds();
        refreshMain();
    })

    $(function() {
        refreshMain();
    })
    // 自动刷新 --------------------- end


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
            text: '<?php echo "$date";?>  注册人数曲线'
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
                text: '注册人数(人/5分钟)'
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
            enabled: false
        },
        series: [
            {
                name: '成功次数曲线',
                data: [
                    <?php echo $success_series_data;?>
                ],
                lineWidth: 1.5,
                marker:{
                    radius: 1
                },
                pointInterval: 300*1000,
                color : '#44BB8C'
            },
            {
                name: '失败次数曲线',
                data: [
                    <?php echo $fail_series_data;?>
                ],
                lineWidth: 1.5,
                marker:{
                    radius: 1
                },
                pointInterval: 300*1000,
                color : '#9C0D0D'
            }
        ]
    });
</script>


<table class="table table-striped table-bordered table-hover datatable">
    <thead>
    <tr>
        <th>时间</th><th>注册总人数</th><th>成功人数</th><th>失败人数</th>
    </tr>
    </thead>
    <tbody>
    <?php echo $table_data;?>
    </tbody>
</table>
<script class="common-asset" type="text/css" src="/plugins/datatables/dataTables.bootstrap.css"></script>
<script class="common-asset" type="text/css" src="/plugins/datatables/jquery.dataTables.min.css"></script>
<script class="common-asset" type="text/javascript" src="/plugins/datatables/jquery.dataTables.js"></script>
<script class="common-asset" type="text/javascript" src="/plugins/datatables/dataTables.bootstrap.js"></script>
<script>
    $(document).ready(function() {
        $('.datatable').dataTable({
            'bAutoWidth'    : false,    // 不自动计算宽度
            'bFilter'   : false,    // 不添加筛选过滤功能
            'bInfo': false, // 不显示表格信息(总条数)
            'bLengthChange': false, // 不显示每页显示条数
            'bPaginate' : false, // 不使用分页
            "aaSorting": [[0, "desc"]]
        });
    } );
</script>

</div>
</div>
</section>