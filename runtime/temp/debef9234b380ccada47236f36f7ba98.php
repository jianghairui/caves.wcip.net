<?php /*a:3:{s:62:"/mnt/www.caves.vip/application/admin/view/admin/grouplist.html";i:1540046963;s:53:"/mnt/www.caves.vip/application/admin/view/layout.html";i:1539060499;s:60:"/mnt/www.caves.vip/application/admin/view/public/footer.html";i:1537872047;}*/ ?>
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
<nav class="breadcrumb"><i class="Hui-iconfont">&#xe67f;</i> 首页 <span class="c-gray en">&gt;</span> 管理员管理 <span class="c-gray en">&gt;</span> 角色管理 <a class="btn btn-success radius r" style="line-height:1.6em;margin-top:3px" href="javascript:location.replace(location.href);" title="刷新" ><i class="Hui-iconfont">&#xe68f;</i></a></nav>
<div class="page-container">
    <div class="cl pd-5 bg-1 bk-gray">
        <span class="l">
            <a href="javascript:;" onclick="admin_role_multidel()" class="btn btn-danger radius">
                <i class="Hui-iconfont">&#xe6e2;</i> 批量删除
            </a>
            <a class="btn btn-primary radius" href="javascript:;" onclick="admin_role_add('添加角色','<?php echo url("Admin/groupadd"); ?>','900')">
                <i class="Hui-iconfont">&#xe600;</i> 添加角色
            </a>
        </span>
    </div>
    <table class="table table-border table-bordered table-hover table-bg">
        <thead>
        <tr>
            <th scope="col" colspan="5">角色列表</th>
        </tr>
        <tr class="text-c">
            <th width="25"><input type="checkbox" value="" name=""></th>
            <th width="40">ID</th>
            <th width="200">角色名</th>
            <th>描述</th>
            <th width="70">操作</th>
        </tr>
        </thead>
        <tbody>
        <form id="formAjax">
            <?php foreach($list as $li): ?>
            <tr class="text-c">
                <td><input type="checkbox" value="<?php echo htmlentities($li['id']); ?>" name="check[]"></td>
                <td><?php echo htmlentities($li['id']); ?></td>
                <td><?php echo htmlentities($li['title']); ?></td>
                <td><?php echo htmlentities($li['desc']); ?></td>
                <td class="f-14">
                    <a title="编辑" href="javascript:;" onclick="admin_role_edit('角色编辑','<?php echo url("Admin/groupmod",array("gid"=>$li["id"])); ?>','1')" style="text-decoration:none">
                    <i class="Hui-iconfont">&#xe6df;</i>
                    </a>
                    <a title="删除" href="javascript:;" onclick="admin_role_del(this,<?php echo htmlentities($li['id']); ?>)" class="ml-5" style="text-decoration:none">
                        <i class="Hui-iconfont">&#xe6e2;</i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </form>

        </tbody>
    </table>
</div>
<script type="text/javascript">
    var isclick = true
    /*管理员-角色-添加*/
    function admin_role_add(title,url,w,h){
        layer_show(title,url,w,h);
    }
    /*管理员-角色-编辑*/
    function admin_role_edit(title,url,id,w,h){
        layer_show(title,url,w,h);
    }
    /*管理员-角色-删除*/
    function admin_role_del(obj,id){
        layer.confirm('角色删除须谨慎，确认要删除吗？',function(index){
            if(isclick) {
                isclick = false
                $.ajax({
                    type: 'POST',
                    url: '<?php echo url("Admin/groupdel"); ?>',
                    dataType: 'json',
                    data:{gid:id},
                    success: function(data){
                        if(data.code == 1) {
                            $(obj).parents("tr").remove();
                            layer.msg('已删除!',{icon:1,time:1000});
                        }else {
                            layer.msg(data.data,{icon:2,time:1000});
                        }
                        isclick = true
                    },
                    error:function(data) {
                        console.log(data.msg);
                        layer.msg('接口请求失败',{icon:2,time:1000});
                        isclick = true
                    },
                });
            }
        });
    }

    function admin_role_multidel() {
        layer.confirm('角色删除须谨慎，确认要删除吗？',function(index){
            if(isclick) {
                isclick = false
                $('#formAjax').ajaxSubmit({
                    type: 'post',
                    url: "<?php echo url('Admin/group_multidel'); ?>" ,
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
