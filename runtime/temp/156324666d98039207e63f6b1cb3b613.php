<?php /*a:3:{s:60:"/mnt/www.caves.vip/application/admin/view/shop/cate_add.html";i:1553067410;s:53:"/mnt/www.caves.vip/application/admin/view/layout.html";i:1539060499;s:60:"/mnt/www.caves.vip/application/admin/view/public/footer.html";i:1537872047;}*/ ?>
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
    .thumbnail{ width:200px;height: 200px;background-size: cover;background-position: center;position: relative}
</style>
<article class="page-container">
    <form class="form form-horizontal" id="form-article-add">
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-2">父级分类：</label>
            <div class="formControls col-xs-8 col-sm-9"> <span class="select-box">
				<select class="select" name="pid" id="pid">
					<option value="0">无</option>
                    <?php foreach($list as $li): ?>
					<option value="<?php echo htmlentities($li['id']); ?>" <?php if($li['id']==$pid): ?>selected<?php endif; ?>><?php echo htmlentities($li['cate_name']); ?></option>
                    <?php endforeach; ?>
				</select>
				</span>
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-2">
                <span id="btn-star" class="btn btn-primary btn-uploadstar radius ml-10">分类图标</span>
            </label>
            <div class="formControls col-xs-8 col-sm-9">
                <div class="uploader-thum-container">
                    <div id="fileList" class="uploader-list">
                    </div>
                </div>
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-2"><span class="c-red">*</span>分类名称：</label>
            <div class="formControls col-xs-8 col-sm-9">
                <input type="text" class="input-text" value="" placeholder="" id="cate_name" name="cate_name">
            </div>
        </div>

        <div class="row cl">
            <div class="col-xs-8 col-sm-9 col-xs-offset-4 col-sm-offset-2">
                <button class="btn btn-primary radius" type="submit"><i class="Hui-iconfont">&#xe632;</i> 添加</button>
                <button onClick="layer_close();" class="btn btn-default radius" type="button">&nbsp;&nbsp;取消&nbsp;&nbsp;</button>
            </div>
        </div>
        <input type="file" name="file" id="file" style="display: none;">
    </form>
</article>


<script type="text/javascript" src="/lib/My97DatePicker/4.8/WdatePicker.js"></script>
<script type="text/javascript" src="/lib/jquery.validation/1.14.0/jquery.validate.js"></script>
<script type="text/javascript" src="/lib/jquery.validation/1.14.0/validate-methods.js"></script>
<script type="text/javascript" src="/lib/jquery.validation/1.14.0/messages_zh.js"></script>
<script type="text/javascript" src="/lib/checkfile.js"></script>

<script type="text/javascript">
    $(function(){

        $('.skin-minimal input').iCheck({
            checkboxClass: 'icheckbox-blue',
            radioClass: 'iradio-blue',
            increaseArea: '20%'
        });

        var isclick = true;
        //表单验证
        $("#form-article-add").validate({
            rules:{
                cate_name:{
                    required:true,
                    maxlength:50
                }
            },
            focusCleanup:false,
            success:"valid",
            submitHandler:function(form){
                if($("#file").val() == '') {
                    layer.msg('请上传图片!',{icon:2,time:1000});
                    return false;
                }
                if(isclick === true) {
                    isclick = false;
                    $(form).ajaxSubmit({
                        type: 'post',
                        url: "<?php echo url('Shop/cateAddPost'); ?>" ,
                        success: function(data){
                            // console.log(data);return;
                            if(data.code == 1) {
                                layer.msg('添加成功!',{icon:1,time:1000});
                                setTimeout("window.parent.location.reload()", 1000)
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


        $("#btn-star").click(function(){
            $("#file").click();
        });

        $("#file").change(function(e){
            var img = e.target.files[0];
            if (typeof(img) == "undefined") {
                var src = '';
                $("#fileList").html('');
            }else {
                if(checkfile('file') !== true) {
                    $("#file").val('');
                    $("#fileList").html('');
                    return false;
                }
                var src = window.URL.createObjectURL(img);
                $("#fileList").html('<div id="cover" class="thumbnail" style="background-image: url('+src+');"><div class="file-panel"></div></div>');
            }

        });


    });
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
