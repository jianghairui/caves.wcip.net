<?php /*a:3:{s:61:"/mnt/www.caves.vip/application/admin/view/shop/cate_list.html";i:1553067389;s:53:"/mnt/www.caves.vip/application/admin/view/layout.html";i:1539060499;s:60:"/mnt/www.caves.vip/application/admin/view/public/footer.html";i:1537872047;}*/ ?>
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8">
    <meta name="renderer" content="webkit|ie-comp|ie-stand">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no" />
    <meta http-equiv="Cache-Control" content="no-siteapp" />
    <link rel="Bookmark" href="/favicon.ico" >
    <link rel="Shortcut Icon" href="/favicon.ico" />
    <!--[if lt IE 9]>
    <script type="text/javascript" src="/lib/html5shiv.js"></script>
    <script type="text/javascript" src="/lib/respond.min.js"></script>
    <![endif]-->
    <link rel="stylesheet" type="text/css" href="/static/h-ui/css/H-ui.min.css" />
    <link rel="stylesheet" type="text/css" href="/static/h-ui.admin/css/H-ui.admin.css" />
    <link rel="stylesheet" type="text/css" href="/lib/Hui-iconfont/1.0.8/iconfont.css" />
    <link rel="stylesheet" type="text/css" href="/static/h-ui.admin/skin/default/skin.css" id="skin" />
    <link rel="stylesheet" type="text/css" href="/static/h-ui.admin/css/style.css" />
    <!--[if IE 6]>
    <script type="text/javascript" src="/lib/DD_belatedPNG_0.0.8a-min.js" ></script>
    <script>DD_belatedPNG.fix('*');</script>
    <![endif]-->

    <!--_footer 作为公共模版分离出去-->
    <script type="text/javascript" src="/lib/jquery/1.9.1/jquery.min.js"></script>
    <script type="text/javascript" src="/lib/layer/2.4/layer.js"></script>
    <script type="text/javascript" src="/static/h-ui/js/H-ui.min.js"></script>
    <script type="text/javascript" src="/static/h-ui.admin/js/H-ui.admin.js"></script>
    <!--/_footer 作为公共模版分离出去-->
    <title></title>
</head>
<body>
<nav class="breadcrumb"><i class="Hui-iconfont">&#xe67f;</i> 首页 <span class="c-gray en">&gt;</span> 分类管理 <span class="c-gray en">&gt;</span> 分类列表 (<?php if($pcate_name): ?><?php echo htmlentities($pcate_name); else: ?>顶级分类<?php endif; ?>)<a class="btn btn-success radius r" style="line-height:1.6em;margin-top:3px" href="javascript:location.replace(location.href);" title="刷新" ><i class="Hui-iconfont">&#xe68f;</i></a></nav>
<div class="page-container">
    <div class="cl pd-5 bg-1 bk-gray mt-20">
        <span class="l">
            <a class="btn btn-primary radius" data-title="添加分类" data-href="article-add.html" onclick="add_info('添加分类','<?php echo url("Shop/cateAdd",array('pid'=>$pid)); ?>')" href="javascript:;"><i class="Hui-iconfont">&#xe600;</i> 添加分类
            </a>
        </span>
    </div>
    <table class="table table-border table-bordered table-bg">
        <thead>
        <tr class="text-c">
            <th width="25">#</th>
            <th width="50">图标</th>
            <th>分类名</th>
            <th width="80">状态</th>
            <th width="100">操作</th>
        </tr>
        </thead>
        <tbody>
        <form id="multi-check">
            <?php foreach($list as $li): ?>
            <tr class="text-c userinfo">
                <td><?php echo htmlentities($li['id']); ?></td>
                <td>
                    <div style="width: 50px;height: 50px;background-image: url('/<?php echo htmlentities($li['icon']); ?>');background-position: center;background-repeat: no-repeat;background-size: cover;"></div>
                </td>
                <td><?php echo htmlentities($li['cate_name']); ?></td>
                <td class="td-status">
                    <?php switch($li['status']): case "0": ?>
                    <span class="label label-defaunt radius">已隐藏</span>
                    <?php break; case "1": ?>
                    <span class="label label-success radius">已显示</span>
                    <?php break; default: endswitch; ?>
                </td>
                <td class="td-manage">
                    <?php if($li['status'] == '1'): ?>
                    <a style="text-decoration:none" onClick="cate_hide(this,<?php echo htmlentities($li['id']); ?>)" href="javascript:;" title="隐藏">
                        <i class="Hui-iconfont">&#xe6de;</i>
                    </a>
                    <?php else: ?>
                    <a style="text-decoration:none" onClick="cate_show(this,<?php echo htmlentities($li['id']); ?>)" href="javascript:;" title="显示">
                        <i class="Hui-iconfont">&#xe603;</i>
                    </a>
                    <?php endif; if($pid == 0): ?>
                    <a style="text-decoration:none" class="ml-5" href="<?php echo url('Shop/cateList',array('pid'=>$li['id'])); ?>" title="查看子分类">
                    <i class="Hui-iconfont">&#xe681;</i>
                    </a>
                    <?php endif; ?>
                    <a style="text-decoration:none" onclick="add_info('修改分类','<?php echo url("Shop/cateDetail",array("id"=>$li["id"])); ?>')"  class="ml-5" href="javascript:;" title="修改分类">
                    <i class="Hui-iconfont">&#xe6df;</i>
                    </a>
                    <a style="text-decoration:none" class="ml-5" onClick="cate_del(this,<?php echo htmlentities($li['id']); ?>)" href="javascript:;" title="删除">
                        <i class="Hui-iconfont">&#xe6e2;</i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </form>
        </tbody>
    </table>
</div>
<!--请在下方写此页面业务相关的脚本-->
<script type="text/javascript" src="/lib/My97DatePicker/4.8/WdatePicker.js"></script>
<script type="text/javascript" src="/lib/datatables/1.10.0/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="/lib/laypage/1.2/laypage.js"></script>
<script type="text/javascript">

    /*添加分类*/
    function add_info(title,url){
        var index = layer.open({
            type: 2,
            title: title,
            content: url
        });
        layer.full(index);
    }

    /*下架*/
    function cate_hide(obj,id){
        layer.confirm('确认要隐藏吗？',function(index){
            $.ajax({
                url:"<?php echo url('Shop/cateHide'); ?>",
                type:'post',
                dataType:'json',
                data:{id:id},
                success:function(data) {
                    console.log(data)
                    if(data.code == 1) {
                        $(obj).parents("tr").find(".td-manage").prepend('<a style="text-decoration:none" onClick="cate_show(this,'+id+')" href="javascript:;" title="显示"><i class="Hui-iconfont">&#xe603;</i></a>');
                        $(obj).parents("tr").find(".td-status").html('<span class="label label-defaunt radius">已隐藏</span>');
                        $(obj).remove();
                        layer.msg('已隐藏!',{icon: 1,time:1000});
                    }else {
                        layer.msg(data.data,{icon:2,time:1000})
                    }
                },
                error:function(data) {
                    layer.msg('请求失败!',{icon:5,time:1000})
                },
            })

        });
    }
    /*上架*/
    function cate_show(obj,id){
        layer.confirm('确认要显示吗？',function(index){
            $.ajax({
                url:"<?php echo url('Shop/cateShow'); ?>",
                type:'post',
                dataType:'json',
                data:{id:id},
                success:function(data) {
                    console.log(data);
                    if(data.code == 1) {
                        $(obj).parents("tr").find(".td-manage").prepend('<a style="text-decoration:none" onClick="cate_hide(this,'+id+')" href="javascript:;" title="隐藏"><i class="Hui-iconfont">&#xe6de;</i></a>');
                        $(obj).parents("tr").find(".td-status").html('<span class="label label-success radius">已显示</span>');
                        $(obj).remove();
                        layer.msg('已显示!',{icon: 1,time:1000});
                    }else {
                        layer.msg(data.data,{icon:2,time:1000})
                    }
                },
                error:function(data) {
                    layer.msg('请求失败!',{icon:5,time:1000})
                },
            })

        });
    }
    /*分类-删除*/
    function cate_del(obj,id){
        layer.confirm('确认要删除吗？',function(index){
            $.ajax({
                url: "<?php echo url('Shop/cateDel'); ?>",
                type: 'POST',
                dataType: 'json',
                data:{id:id},
                success: function(data){
                    console.log(data);
                    if(data.code == 1) {
                        $(obj).parents("tr").remove();
                        layer.msg('已删除!',{icon:1,time:1000});
                    }else {
                        layer.msg('删除失败!',{icon:2,time:1000});
                    }
                },
                error:function(data) {
                    console.log(data.msg);
                },
            });
        });
    }


</script>
<!--请在下方写此页面业务相关的脚本-->
<script type="text/javascript" src="/lib/jquery.contextmenu/jquery.contextmenu.r2.js"></script>
<script type="text/javascript">
    /*个人信息*/
    function myselfinfo(){
        layer.open({
            type: 1,
            area: ['300px','200px'],
            fix: false, //不固定
            maxmin: true,
            shade:0.4,
            title: '查看信息',
            content: '<div>管理员信息</div>'
        });
    }


</script>
</body>
</html>
