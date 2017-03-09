<link href="/plugins/datatables/dataTables.bootstrap.css" rel="stylesheet">
<section class="content-header">
    <h1>
        性能监控
        <small>接口性能排行</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> 性能监控</a></li>
        <li class="active">接口性能排行</li>
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
            </div>
            <table class="table table-hover table-condensed table-bordered datatable">
                <thead>
                <tr>
                    <th>调用方法</th><th>模块</th><th>调用总数</th><th>平均耗时(ms)</th><th>成功调用总数</th><th>成功平均耗时(ms)</th><th>失败调用总数</th><th>失败平均耗时(ms)</th><th>成功率</th>
                </tr>
                </thead>
                <tbody>
                <?php echo $table_data;?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<script type="text/javascript" src="/plugins/datatables/jquery.dataTables.js"></script>
<script type="text/javascript" src="/plugins/datatables/dataTables.bootstrap.js"></script>
<script>
    $(document).ready(function() {
        $('.datatable').dataTable({
            'bAutoWidth'    : true,    // 不自动计算宽度
            'bFilter'   : true,    // 不添加筛选过滤功能
            'bInfo': true, // 不显示表格信息(总条数)
            'bLengthChange': true, // 不显示每页显示条数
            'bPaginate' : false// 不使用分页
        });
    } );
</script>
