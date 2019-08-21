<?php /*a:3:{s:62:"/mnt/www.caves.vip/application/admin/view/admin/adminlist.html";i:1540102214;s:53:"/mnt/www.caves.vip/application/admin/view/layout.html";i:1539060499;s:60:"/mnt/www.caves.vip/application/admin/view/public/footer.html";i:1537872047;}*/ ?>
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
<nav class="breadcrumb"><i class="Hui-iconfont">&#xe67f;</i> 首页 <span class="c-gray en">&gt;</span> 管理员管理 <span class="c-gray en">&gt;</span> 管理员列表 <a class="btn btn-success radius r" style="line-height:1.6em;margin-top:3px" href="javascript:location.replace(location.href);" title="刷新" ><i class="Hui-iconfont">&#xe68f;</i></a></nav>
<div class="page-container">
    <form id="formAjax">
        <div class="text-c"> 日期范围：
            <input type="text" onfocus="WdatePicker({ maxDate:'#F{ $dp.$D(\'logmax\')||\'%y-%M-%d\' }' })" id="logmin" value="<?php echo htmlentities(app('request')->get('logmin')); ?>" class="input-text Wdate" style="width:120px;">
            -
            <input type="text" onfocus="WdatePicker({ minDate:'#F{ $dp.$D(\'logmin\') }',maxDate:'%y-%M-%d' })" id="logmax" value="<?php echo htmlentities(app('request')->get('logmax')); ?>" class="input-text Wdate" style="width:120px;">
            <input type="text" class="input-text" style="width:250px" placeholder="输入管理员名称" value="<?php echo htmlentities(app('request')->get('search')); ?>" id="search" name="search">
            <button type="button" class="btn btn-success" id="search-btn" name=""><i class="Hui-iconfont">&#xe665;</i> 搜用户</button>
        </div>
    </form>
    <div class="cl pd-5 bg-1 bk-gray mt-20">
        <span class="l">
            <a href="javascript:;" onclick="admin_multidel()" class="btn btn-danger radius">
                <i class="Hui-iconfont">&#xe6e2;</i> 批量删除
            </a>
            <a href="javascript:;" onclick="admin_add('添加管理员','<?php echo url("Admin/adminadd"); ?>','1000','600')" class="btn btn-primary radius">
                <i class="Hui-iconfont">&#xe600;</i> 添加管理员
            </a>
        </span>
        <span class="r">共有数据：<strong><?php echo htmlentities($page['count']); ?></strong> 条</span>
    </div>
    <table class="table table-border table-bordered table-bg">
        <thead>
        <tr>
            <th scope="col" colspan="10">员工列表</th>
        </tr>
        <tr class="text-c">
            <th width="25"><input type="checkbox" name="" value=""></th>
            <th width="40">ID</th>
            <th width="150">用户名</th>
            <th width="150">姓名</th>
            <th width="90">手机</th>
            <th width="150">邮箱</th>
            <th>角色</th>
            <th width="130">加入时间</th>
            <th width="100">是否已启用</th>
            <th width="100">操作</th>
        </tr>
        </thead>
        <tbody>
        <form id="multidel">
            <?php foreach($list as $li): ?>
            <tr class="text-c">
                <td><input type="checkbox" value="<?php echo htmlentities($li['id']); ?>" name="check[]"></td>
                <td><?php echo htmlentities($li['id']); ?></td>
                <td><?php echo htmlentities($li['username']); ?></td>
                <td><?php echo htmlentities($li['realname']); ?></td>
                <td><?php echo htmlentities($li['tel']); ?></td>
                <td><?php echo htmlentities($li['email']); ?></td>
                <td><?php if($li['username'] === config('superman')): ?>(<span class="Hui-iconfont" style="color:darkgoldenrod">&#xe72c;权力至高无上&#xe72c;</span>)<?php else: ?><?php echo htmlentities($li['title']); endif; ?></td>
                <td><?php echo htmlentities(date('Y-m-d H:i',!is_numeric($li['create_time'])? strtotime($li['create_time']) : $li['create_time'])); ?></td>
                <td class="td-status">
                    <?php switch($li['status']): case "0": ?><span class="label label-default radius">已禁用</span><?php break; case "1": ?><span class="label label-success radius">已启用</span><?php break; default: endswitch; ?>
                </td>
                <td class="td-manage">
                    <?php switch($li['status']): case "0": ?>
                    <a style="text-decoration:none" onClick="admin_start(this,<?php echo htmlentities($li['id']); ?>)" href="javascript:;" title="启用">
                        <i class="Hui-iconfont">&#xe615;</i>
                    </a><?php break; case "1": ?>
                    <a style="text-decoration:none" onClick="admin_stop(this,<?php echo htmlentities($li['id']); ?>)" href="javascript:;" title="停用">
                        <i class="Hui-iconfont">&#xe631;</i>
                    </a><?php break; default: endswitch; ?>
                    <a title="编辑" href="javascript:;" onclick="admin_edit('管理员编辑','<?php echo url("Admin/adminmod",array("id"=>$li['id'])); ?>','1','1000','600')" class="ml-5" style="text-decoration:none">
                    <i class="Hui-iconfont">&#xe6df;</i>
                    </a>
                    <a title="删除" href="javascript:;" onclick="admin_del(this,<?php echo htmlentities($li['id']); ?>)" class="ml-5" style="text-decoration:none">
                        <i class="Hui-iconfont">&#xe6e2;</i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </form>


        <tr>
            <td colspan="10" id="page" class="text-r"></td>
        </tr>

        </tbody>
    </table>
</div>


<!--请在下方写此页面业务相关的脚本-->
<script type="text/javascript" src="/lib/My97DatePicker/4.8/WdatePicker.js"></script>
<script type="text/javascript" src="/lib/datatables/1.10.0/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="/lib/laypage/1.2/laypage.js"></script>
<script type="text/javascript">
    var url = '<?php echo url("Admin/adminlist"); ?>' + '?<?php echo $page["query"];?>';
    var curr = '<?php echo htmlentities($page['curr']); ?>';
    var isclick = true;
    laypage({
        cont: 'page', //容器。值支持id名、原生dom对象，jquery对象。
        pages: '<?php echo htmlentities($page['totalPage']); ?>', //通过后台拿到的总页数
        skip: false, //是否开启跳页
        skin: '#5a98de',
        curr: curr || 1, //当前页
        jump: function(obj, first){ //触发分页后的回调
            // console.log(obj)
            if(!first) {
                window.location.href = url+'&page=' + obj.curr;
            }
        }
    });

    $("#search-btn").click(function () {
        var logmin = $("#formAjax").find("#logmin").val();
        var logmax = $("#formAjax").find("#logmax").val();
        var search = $("#formAjax").find("#search").val();
        var str = '';
        if(logmin.length !== '') {
            str += '&logmin=' + logmin
        }
        if(logmax.length !== '') {
            str += '&logmax=' + logmax
        }
        if(search.length !== '') {
            str += '&search=' + search
        }
        window.location.href = '<?php echo url("Admin/adminlist"); ?>' + '?' + str;
    });

    function admin_add(title,url,w,h){
        layer_show(title,url,w,h);
    }
    /*管理员-删除*/
    function admin_del(obj,id){
        layer.confirm('确认要删除吗？',function(index){
            $.ajax({
                type: 'POST',
                url: "<?php echo url('Admin/adminDel'); ?>",
                dataType: 'json',
                data:{id:id},
                success: function(data){
                    if(data.code == 1) {
                        $(obj).parents("tr").remove();
                        layer.msg('已删除!',{icon:1,time:1000});
                    }else {
                        layer.msg(data.data,{icon:2,time:1000});
                    }

                },
                error:function(data) {
                    console.log(data.msg);
                },
            });
        });
    }

    /*管理员-编辑*/
    function admin_edit(title,url,id,w,h){
        layer_show(title,url,w,h);
    }
    /*管理员-停用*/
    function admin_stop(obj,id){
        layer.confirm('确认要停用吗？',function(index){
            $.ajax({
                url:"<?php echo url('Admin/adminStop'); ?>",
                type:'post',
                dataType:'json',
                data:{id:id},
                success:function(data) {
                    console.log(data)
                    if(data.code == 1) {
                        $(obj).parents("tr").find(".td-manage").prepend('<a onClick="admin_start(this,'+id+')" href="javascript:;" title="启用" style="text-decoration:none"><i class="Hui-iconfont">&#xe615;</i></a>');
                        $(obj).parents("tr").find(".td-status").html('<span class="label label-default radius">已禁用</span>');
                        $(obj).remove();
                        layer.msg('已停用!',{icon: 5,time:1000});
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

    /*管理员-启用*/
    function admin_start(obj,id){
        layer.confirm('确认要启用吗？',function(index){
            $.ajax({
                url:"<?php echo url('Admin/adminStart'); ?>",
                type:'post',
                dataType:'json',
                data:{id:id},
                success:function(data) {
                    console.log(data)
                    if(data.code == 1) {
                        $(obj).parents("tr").find(".td-manage").prepend('<a onClick="admin_stop(this,'+id+')" href="javascript:;" title="停用" style="text-decoration:none"><i class="Hui-iconfont">&#xe631;</i></a>');
                        $(obj).parents("tr").find(".td-status").html('<span class="label label-success radius">已启用</span>');
                        $(obj).remove();
                        layer.msg('已启用!', {icon: 6,time:1000});
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

    function admin_multidel() {
        layer.confirm('管理员删除须谨慎，确认要删除吗？',function(index){
            if(isclick) {
                isclick = false;
                $('#multidel').ajaxSubmit({
                    type: 'post',
                    url: "<?php echo url('Admin/admin_multidel'); ?>" ,
                    success: function(data){
                        console.log(data)
                        if(data.code == 1) {
                            layer.msg('删除成功',{icon:1,time:1000},function () {
                                window.location.reload()
                            });
                        }else {
                            layer.msg(data.data,{icon:2,time:1000});
                            isclick = true
                        }
                    },
                    error: function(msg){
                        //console.log(JSON.parse(msg.responseText))
                        layer.msg('接口请求失败!',{icon:2,time:1000});
                        isclick = true
                    }
                });
            }

        })
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
