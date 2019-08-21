<?php /*a:3:{s:63:"/mnt/www.caves.vip/application/admin/view/banner/slideshow.html";i:1552896616;s:53:"/mnt/www.caves.vip/application/admin/view/layout.html";i:1539060499;s:60:"/mnt/www.caves.vip/application/admin/view/public/footer.html";i:1537872047;}*/ ?>
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
<style>
    .thumbnail{ width:192px;height: 108px;background-size: cover;background-position: center;position: relative}
</style>
<nav class="breadcrumb"><i class="Hui-iconfont">&#xe67f;</i> 首页 <span class="c-gray en">&gt;</span> 轮播图 <span class="c-gray en">&gt;</span> 图片列表 <a class="btn btn-success radius r" style="line-height:1.6em;margin-top:3px" href="javascript:location.replace(location.href);" title="刷新" ><i class="Hui-iconfont">&#xe68f;</i></a></nav>
<div class="page-container">
    <div class="text-c">
        <form class="Huiform" method="post" action="" target="_self" id="formAjax">
            <input type="text" placeholder="图片描述" name="title" value="" class="input-text" style="width:200px">
            <input type="text" placeholder="链接地址" name="url" value="" class="input-text" style="width:200px">
            <span class="btn-upload form-group">
                <input class="input-text" type="text" id="uploadfile" readonly style="width:100px">
                <a href="javascript:;" class="btn btn-primary uploadfile">
                    <i class="Hui-iconfont">&#xe642;</i> 上传图片(不超过512K)
                </a>
                <input type="file" id="file" multiple name="file" class="input-file">
			</span>
            <button type="submit" class="btn btn-success" id="" name="">
                <i class="Hui-iconfont">&#xe600;</i> 添加
            </button>
        </form>
    </div>
    <div class="mt-20">
        <table class="table table-border table-bordered table-bg table-sort">
            <thead>
            <tr class="text-c">
                <th width="70">ID</th>
                <th width="80">排序</th>
                <th width="200">图片</th>
                <th width="200">具体描述</th>
                <th>链接地址</th>
                <th width="60">状态</th>
                <th width="100">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($list as $li): ?>
            <tr class="text-c">
                <td><?php echo htmlentities($li['id']); ?></td>
                <td><input type="text" class="input-text text-c sort" value="<?php echo htmlentities($li['sort']); ?>"></td>
                <td>
                    <div id="cover" class="thumbnail" style="background-image: url('/<?php echo htmlentities($li['pic']); ?>')"></div>
                </td>
                <td class="text-c"><?php echo htmlentities($li['title']); ?></td>
                <td class="text-l"><?php echo htmlentities($li['url']); ?></td>
                <td class="td-status"><?php if($li['status'] == 1): ?><span class="label label-success radius">已启用</span><?php else: ?><span class="label label-defaunt radius">已禁用</span><?php endif; ?></td>
                <td class="f-14 product-brand-manage">
                    <?php if($li['status'] == 1): ?>
                    <a style="text-decoration:none" class="ml-5" style="text-decoration:none" onClick="slide_stop(this,<?php echo htmlentities($li['id']); ?>)" href="javascript:;" title="禁用">
                        <i class="Hui-iconfont">&#xe6de;</i>
                    </a>
                    <?php else: ?>
                    <a style="text-decoration:none" class="ml-5" style="text-decoration:none" onClick="slide_start(this,<?php echo htmlentities($li['id']); ?>)" href="javascript:;" title="启用">
                        <i class="Hui-iconfont">&#xe603;</i>
                    </a>
                    <?php endif; ?>
                    <a style="text-decoration:none" class="ml-5" onClick="slide_edit('轮播图编辑','<?php echo url("Banner/slidemod",array("id"=>$li["id"])); ?>')" href="javascript:;" title="编辑">
                        <i class="Hui-iconfont">&#xe6df;</i>
                    </a>
                    <a style="text-decoration:none" class="ml-5" onClick="slide_del(this,'<?php echo htmlentities($li['id']); ?>')" href="javascript:;" title="删除">
                        <i class="Hui-iconfont">&#xe6e2;</i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>

            </tbody>
        </table>
    </div>
</div>

<!--请在下方写此页面业务相关的脚本-->
<script type="text/javascript" src="/lib/My97DatePicker/4.8/WdatePicker.js"></script>
<script type="text/javascript" src="/lib/datatables/1.10.0/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="/lib/jquery.validation/1.14.0/jquery.validate.js"></script>
<script type="text/javascript" src="/lib/jquery.validation/1.14.0/validate-methods.js"></script>
<script type="text/javascript" src="/lib/laypage/1.2/laypage.js"></script>
<script type="text/javascript" src="/lib/checkfile.js"></script>
<script type="text/javascript">

    $(function () {
        var isclick = true

        $("#formAjax").validate({
            rules:{
                title:{
                    required:true,
                    maxlength:100
                },
                url:{
                    maxlength:255
                },
            },
            onkeyup:false,
            focusCleanup:true,
            success:"valid",
            submitHandler:function(form){
                if($("#file").val() == '') {
                    layer.msg('请上传图片',{icon:2,time:1000});
                    return false
                }
                if(isclick === true) {
                    isclick = false
                    $(form).ajaxSubmit({
                        type: 'post',
                        url: "<?php echo url('Banner/slideadd'); ?>" ,
                        success: function(data){
                            console.log(data)
                            if(data.code == 1) {
                                layer.msg('添加成功!',{icon:1,time:1000});
                                setTimeout("window.location.reload()", 1000)
                            }else {
                                layer.msg(data.data,{icon:2,time:1000});
                                isclick = true
                            }

                        },
                        error: function(XmlHttpRequest, textStatus, errorThrown){
                            layer.msg('error!',{icon:5,time:1000});
                            isclick = true
                        }
                    });
                }
            }
        });

        $(".uploadfile").click(function () {
            $("#file").click();
        })

        $("#file").change(function(e){
            var img = e.target.files[0];
            if (typeof(img) == "undefined") {
                var src = ''
            }else {
                if(checkfile('file') !== true) {
                    $("#file").val('');
                    $("#uploadfile").val('');
                    return false;
                }
                var src = window.URL.createObjectURL(img);
                $("#uploadfile").val(img.name)
            }
        });

        $(".sort").bind('input propertychange', function() {
            var id = $(this).parent().prev().text();
            var sort = $(this).val();
            var rule = /^[0-9]{0,10}$/;
            if(!rule.test(sort)) {
                // $(this).val('')
                alert('排序必须为数字,且不超过10位');
                return;
            }
            $.ajax({
                url:"<?php echo url('Banner/sortSlide'); ?>",
                type:"post",
                data:{id:id,sort:sort},
                success:function(data) {
                    console.log(data)
                },
                error: function(data) {
                    console.log('system error')
                }
            })
        });

    });
    /*图片-编辑*/
    function slide_edit(title,url){
        var index = layer.open({
            type: 2,
            title: title,
            content: url
        });
        layer.full(index);
    }

    /*图片-删除*/
    function slide_del(obj,id){
        layer.confirm('确认要删除吗？',function(index){
            $.ajax({
                url: "<?php echo url('Banner/slide_del'); ?>",
                type: 'POST',
                dataType: 'json',
                data:{slideid:id},
                success: function(data){
                    console.log(data)
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

    /*图片-禁用*/
    function slide_stop(obj,id){
        layer.confirm('确认要禁用吗？',function(index){
            $.ajax({
                url:"<?php echo url('Banner/slide_stop'); ?>",
                type:'post',
                dataType:'json',
                data:{slideid:id},
                success:function(data) {
                    console.log(data)
                    if(data.code == 1) {
                        $(obj).parents("tr").find(".product-brand-manage").prepend('<a style="text-decoration:none" class="ml-5" onClick="slide_start(this,'+id+')" href="javascript:;" title="启用"><i class="Hui-iconfont">&#xe603;</i></a>');
                        $(obj).parents("tr").find(".td-status").html('<span class="label label-defaunt radius">已禁用</span>');
                        $(obj).remove();
                        layer.msg('已禁用!',{icon: 1,time:1000});
                    }else {
                        layer.msg(data.message,{icon:2,time:1000})
                    }
                },
                error:function(data) {
                    layer.msg('请求失败!',{icon:5,time:1000})
                },
            })

        });
    }

    /*图片-启用*/
    function slide_start(obj,id){
        layer.confirm('确认要启用吗？',function(index){
            $.ajax({
                url:"<?php echo url('Banner/slide_start'); ?>",
                type:'post',
                dataType:'json',
                data:{slideid:id},
                success:function(data) {
                    console.log(data)
                    if(data.code == 1) {
                        $(obj).parents("tr").find(".product-brand-manage").prepend('<a style="text-decoration:none" class="ml-5" onClick="slide_stop(this,'+id+')" href="javascript:;" title="禁用"><i class="Hui-iconfont">&#xe6de;</i></a>');
                        $(obj).parents("tr").find(".td-status").html('<span class="label label-success radius">已启用</span>');
                        $(obj).remove();
                        layer.msg('已启用!',{icon: 1,time:1000});
                    }else {
                        layer.msg(data.message,{icon:2,time:1000})
                    }
                },
                error:function(data) {
                    layer.msg('请求失败!',{icon:5,time:1000})
                },
            })
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
