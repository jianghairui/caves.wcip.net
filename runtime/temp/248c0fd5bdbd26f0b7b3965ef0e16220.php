<?php /*a:3:{s:61:"/mnt/www.caves.vip/application/admin/view/admin/rulelist.html";i:1554629308;s:53:"/mnt/www.caves.vip/application/admin/view/layout.html";i:1539060499;s:60:"/mnt/www.caves.vip/application/admin/view/public/footer.html";i:1537872047;}*/ ?>
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
<nav class="breadcrumb"><i class="Hui-iconfont">&#xe67f;</i> 首页 <span class="c-gray en">&gt;</span> 管理员管理 <span class="c-gray en">&gt;</span> 权限管理 <a class="btn btn-success radius r" style="line-height:1.6em;margin-top:3px" href="javascript:location.replace(location.href);" title="刷新" ><i class="Hui-iconfont">&#xe68f;</i></a></nav>
<div class="cl pd-5 bg-1 bk-gray mt-20">
    <span class="l">
        <a href="javascript:;" onclick="admin_permission_del()" class="btn btn-danger radius">
            <i class="Hui-iconfont">&#xe6e2;</i> 批量删除</a>
        <a href="javascript:;" onclick="admin_permission_add('添加权限节点','<?php echo url("Admin/ruleadd"); ?>','800','310')" class="btn btn-primary radius">
            <i class="Hui-iconfont">&#xe600;</i> 添加顶级节点</a>
    </span>
</div>

<article class="page-container">
    <form action="" method="post" class="form form-horizontal" id="formAjax">
        <div class="row cl">
            <div class="formControls col-xs-12 col-sm-12">
                <?php foreach($list as $li): ?>
                <dl class="permission-list">
                    <dt>
                        <label>
                            <input type="checkbox" value="<?php echo htmlentities($li['id']); ?>" name="check[]" id="user-Character-1">
                            <?php echo htmlentities($li['title']); ?>(<?php echo htmlentities($li['name']); ?>)
                        </label>
                        <span class="r">
                            <a onclick="admin_permission_add('添加子权限','<?php echo url("Admin/ruleadd",array("pid"=>$li["id"])); ?>','1000','600')">
                                <i class="Hui-iconfont">&#xe600;</i> 添加子节点
                            </a>
                        </span>
                    </dt>
                    <dd>
                        <dl class="cl">
                            <dd>
                                <?php foreach($li['child'] as $v): ?>
                                <label title="<?php echo htmlentities($v['name']); ?>" style="display:inline-block;width: 180px;">
                                    <input type="checkbox" value="<?php echo htmlentities($v['id']); ?>" name="check[]" id="user-Character-1-0-0">
                                    <?php echo htmlentities($v['title']); ?>
                                </label>
                                <?php endforeach; ?>
                            </dd>
                        </dl>
                    </dd>
                </dl>
                <?php endforeach; ?>

            </div>
        </div>
    </form>
</article>


<!--请在下方写此页面业务相关的脚本-->
<script type="text/javascript" src="/lib/jquery.validation/1.14.0/jquery.validate.js"></script>
<script type="text/javascript" src="/lib/jquery.validation/1.14.0/validate-methods.js"></script>
<script type="text/javascript" src="/lib/jquery.validation/1.14.0/messages_zh.js"></script>
<script type="text/javascript">
    $(function(){
        $(".permission-list dt input:checkbox").click(function(){
            $(this).closest("dl").find("dd input:checkbox").prop("checked",$(this).prop("checked"));
        });

    });
    var isclick = true;

    /*管理员-权限-添加*/
    function admin_permission_add(title,url,w,h){
        layer_show(title,url,w,h);
    }

    /*管理员-权限-删除*/
    function admin_permission_del(obj,id){
        layer.confirm('节点删除须谨慎，确认要删除吗？',function(index){
            if(isclick) {
                isclick = false
                $('#formAjax').ajaxSubmit({
                    type: 'post',
                    url: "<?php echo url('Admin/ruledel'); ?>" ,
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
