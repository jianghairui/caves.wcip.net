<?php /*a:3:{s:61:"/mnt/www.caves.vip/application/admin/view/shop/goods_add.html";i:1555308954;s:53:"/mnt/www.caves.vip/application/admin/view/layout.html";i:1539060499;s:60:"/mnt/www.caves.vip/application/admin/view/public/footer.html";i:1537872047;}*/ ?>
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
            <label class="form-label col-xs-4 col-sm-2">商品分类：</label>
            <div class="formControls col-xs-8 col-sm-4"> <span class="select-box">
				<select class="select" name="pcate_id" id="pcate_id">
					<option value="">无</option>
                    <?php foreach($list as $li): ?>
					<option value="<?php echo htmlentities($li['id']); ?>"><?php echo htmlentities($li['cate_name']); ?></option>
                    <?php endforeach; ?>
				</select>
				</span>
            </div>
            <div class="formControls col-xs-8 col-sm-4"> <span class="select-box">
				<select class="select" name="cate_id" id="cate_id">

				</select>
				</span>
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-2"><span class="c-red">*</span>商品名：</label>
            <div class="formControls col-xs-8 col-sm-9">
                <input type="text" class="input-text" value="" placeholder="" id="name" name="name" maxlength="50">
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-2">
                <span id="btn-star1" class="btn btn-primary btn-uploadstar radius ml-10"  onclick="document.getElementById('pic_up').click()">上传图片</span>
            </label>
            <div class="formControls col-xs-8 col-sm-9">
                <div class="uploader-thum-container">
                    <div id="fileList" class="uploader-list">

                    </div>

                    <input type="file"  id="pic_up" name="pic_up" style="display:none;">
                </div>
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-2">商品原价(只显示用)：</label>
            <div class="formControls col-xs-8 col-sm-9">
                <input type="text" class="input-text" value="" placeholder="金额格式 0.00" id="origin_price" name="origin_price" oninput="this.value=/^\d{1,8}(\.\d{0,2})?$/.test(this.value) ? this.value : this.value.substring(0,this.value.length-1)">
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-2">商品价格：</label>
            <div class="formControls col-xs-8 col-sm-9">
                <input type="text" class="input-text" value="" placeholder="金额格式 0.00" id="price" name="price" oninput="this.value=/^\d{1,8}(\.\d{0,2})?$/.test(this.value) ? this.value : this.value.substring(0,this.value.length-1)">
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-2">使用规格：</label>
            <div class="formControls col-xs-8 col-sm-10">
                <input type="checkbox" id="use_attr" name="use_attr" value="1" style="width: 20px;height: 20px">
            </div>
        </div>
        <div class="row cl" id="attr_detail" style="display: none">
            <div class="row cl">
                <label class="form-label col-sm-2">规格组：</label>
                <div class="formControls col-sm-4">
                    <input type="text" class="input-text" value="" placeholder=“(如颜色、尺寸)" id="attr" name="attr">
                </div>
                <label class="col-sm-6" id="attr_add"><span class="btn btn-primary">添加+</span></label>
            </div>
            <div class="row cl">
                <label class="form-label col-sm-2"></label>
                <label class="form-label col-sm-2" style="text-align: left" id="attr-value">属性值</label>
                <label class="form-label col-sm-2" style="text-align: left">价格</label>
                <label class="form-label col-sm-2" style="text-align: left">库存</label>
            </div>
        </div>

        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-2">运费：</label>
            <div class="formControls col-xs-8 col-sm-9">
                <input type="text" class="input-text" value="" placeholder="金额格式 0.00" id="carriage" name="carriage" oninput="this.value=/^\d{1,8}(\.\d{0,2})?$/.test(this.value) ? this.value : this.value.substring(0,this.value.length-1)">
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-2">满*元免运费：</label>
            <div class="formControls col-xs-8 col-sm-9">
                <input type="text" class="input-text" value="" placeholder="金额格式 0.00" id="reduction" name="reduction" oninput="this.value=/^\d{1,8}(\.\d{0,2})?$/.test(this.value) ? this.value : this.value.substring(0,this.value.length-1)">
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-2">商家服务：</label>
            <div class="formControls col-xs-8 col-sm-9">
                <input type="text" class="input-text" value="" placeholder="7天无理由退换" id="service" name="service" maxlength="8">
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-2">单位：</label>
            <div class="formControls col-xs-8 col-sm-9">
                <input type="text" class="input-text" value="" placeholder="单位:件" id="unit" name="unit" maxlength="8">
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-2">库存：</label>
            <div class="formControls col-xs-8 col-sm-9">
                <input type="number" class="input-text" value="0" placeholder="输入数字" id="stock" name="stock" maxlength="8">
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-2">排序：</label>
            <div class="formControls col-xs-8 col-sm-9">
                <input type="number" class="input-text" value="0" placeholder="输入数字" id="sort" name="sort" maxlength="8">
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-2">销量：</label>
            <div class="formControls col-xs-8 col-sm-9">
                <input type="number" class="input-text" value="0" placeholder="输入数字" id="sales" name="sales" maxlength="8">
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-2"><span class="c-red">*</span>是否热销：</label>
            <div class="formControls col-xs-8 col-sm-9 skin-minimal">
                <div class="radio-box">
                    <input name="hot" type="radio" id="hot" value="1" >
                    <label for="hot">是</label>
                </div>
                <div class="radio-box">
                    <input name="hot" type="radio" id="cool" value="0" checked>
                    <label for="cool">否</label>
                </div>
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-2"><span class="c-red">*</span>状态：</label>
            <div class="formControls col-xs-8 col-sm-9 skin-minimal">
                <div class="radio-box">
                    <input name="status" type="radio" id="on" value="1" checked>
                    <label for="on">上架</label>
                </div>
                <div class="radio-box">
                    <input name="status" type="radio" id="off" value="0">
                    <label for="off">下架</label>
                </div>
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-2">商品详情：</label>
            <div class="formControls col-xs-8 col-sm-9">
                <textarea id="editor" type="text/plain" name="detail" style="width:480px;height:500px;"></textarea>
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-2">描述：</label>
            <div class="formControls col-xs-8 col-sm-9">
                <textarea name="desc" id="desc" cols="" rows="" maxlength="50" class="textarea"  placeholder="说点什么...最少输入10个字符" dragonfly="true" nullmsg="备注不能为空！" ></textarea>
            </div>
        </div>
        <div class="row cl">
            <div class="col-xs-8 col-sm-9 col-xs-offset-4 col-sm-offset-2">
                <button class="btn btn-primary radius" type="submit"><i class="Hui-iconfont">&#xe632;</i> 添加</button>
                <button onClick="layer_close();" class="btn btn-default radius" type="button">&nbsp;&nbsp;取消&nbsp;&nbsp;</button>
            </div>
        </div>
    </form>
</article>


<script type="text/javascript" src="/lib/My97DatePicker/4.8/WdatePicker.js"></script>
<script type="text/javascript" src="/lib/jquery.validation/1.14.0/jquery.validate.js"></script>
<script type="text/javascript" src="/lib/jquery.validation/1.14.0/validate-methods.js"></script>
<script type="text/javascript" src="/lib/jquery.validation/1.14.0/messages_zh.js"></script>

<script type="text/javascript" src="/lib/ueditor/1.4.3/ueditor.config.js"></script>
<script type="text/javascript" src="/lib/ueditor/1.4.3/ueditor.all.min.js"> </script>
<script type="text/javascript" src="/lib/ueditor/1.4.3/lang/zh-cn/zh-cn.js"></script>
<script type="text/javascript" src="/lib/checkfile.js"></script>
<script type="text/javascript" src="/lib/ajaxfileupload.js"></script>
<script type="text/javascript">
    $(function(){
        var ue = UE.getEditor('editor');

        $('.skin-minimal input').iCheck({
            checkboxClass: 'icheckbox-blue',
            radioClass: 'iradio-blue',
            increaseArea: '20%'
        });

        //表单验证
        $("#form-article-add").validate({
            rules:{
                name:{
                    required:true,
                    maxlength:50
                },
                price:{
                    required:true
                },
                origin_price:{
                    required:true
                },
                carriage:{
                    required:true
                },
                reduction:{
                    required:true
                },
                service:{
                    required:true
                },
                unit:{
                    required:true
                },
                stock:{
                    required:true
                },
                sort:{
                    required:true
                },
                sales:{
                    required:true
                },
                desc:{
                    required:true
                }
            },
            focusCleanup:true,
            success:"valid",
            submitHandler:function(form){
                if($("#pcate_id").val() == null || $("#pcate_id").val() == '') {
                    layer.msg('请选择一个一级分类',{icon:5,time:1000});
                    return false;
                }
                if($("#cate_id").val() == null || $("#pcate_id").val() == '') {
                    layer.msg('请选择一个二级分类',{icon:5,time:1000});
                    return false;
                }
                var attr1 = [],attr2 = [],attr3 = [],isclick = true;
                if ($('#use_attr').prop('checked')) {
                    if($("#attr").val() == '') {
                        alert('规格不能为空');
                        isclick = false;
                    }
                }
                $("input[name='attr1[]']").each(function(i, el) {
                    attr1[i] =$(this).val();
                });
                $("input[name='attr2[]']").each(function(i, el) {
                    if(!(/^\d{1,8}(\.\d{0,2})?$/.test($(this).val()))) {
                        alert('金额格式不符');
                        isclick = false;
                    }
                    attr2[i] =$(this).val();
                });
                $("input[name='attr3[]']").each(function(i, el) {
                    if(!(/^\d{1,8}$/.test($(this).val()))) {
                        alert('数量格式不正符');
                        isclick = false;
                    }
                    attr3[i] =$(this).val();
                });

                // console.log(d);return;
                if(isclick === true) {
                    isclick = false;
                    $(form).ajaxSubmit({
                        type: 'post',
                        url: "<?php echo url('Shop/goodsAddPost'); ?>" ,
                        success: function(data){
                            // console.log(data);isclick=true;return;
                            if(data.code == 1) {
                                layer.msg('添加成功!',{icon:1,time:1000},function () {
                                    window.parent.location.reload();
                                });
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

        $("#pcate_id").change(function () {
            var pid = $(this).val();
            $.ajax({
                url: "<?php echo url('Shop/getCateList'); ?>" ,
                type: 'post',
                data:{pid:pid},
                success: function(data){
                    // console.log(data.data)
                    if(data.code == 1) {
                        var html = '',list = data.data;
                        if(list.length > 0) {
                            for(var i=0; i <list.length;i++) {
                                html += '<option value="'+list[i].id+'">'+list[i].cate_name+'</option>';
                            }
                        }
                        $("#cate_id").html(html);

                    }else {
                        layer.msg('接口异常',{icon:5,time:1000});
                    }
                },
                error: function(res){
                    layer.msg('接口请求失败!',{icon:5,time:1000});
                    isclick = true
                }
            })
        });

        $(document).on("change","#pic_up",function(){
            var limit = 5;
            if($("input[name='pic_url[]']").length > (limit-1)) {
                layer.alert('最多上传'+limit+'张图')
                return false;
            }
            if($("#pic_up").val() != '') {
                ajaxFileUpload();
            }
        });

        $(document).on("click",'.thumbnail',function(){
            var picBox = $(this);
            layer.confirm("是否要删除该图片？",{
                btn:['是','否']
            },function(){
                picBox.remove();
                layer.closeAll();
            });
        });

        function ajaxFileUpload() {
            if(checkfile('pic_up') !== true) {
                $("#pic_up").val('');
                return false;
            }
            $.ajaxFileUpload({
                url: '<?php echo url("Index/uploadImage"); ?>', //用于文件上传的服务器端请求地址
                secureuri: false, //是否需要安全协议，一般设置为false
                fileElementId: 'pic_up', //文件上传域的ID
                dataType: 'json', //返回值类型 一般设置为json
                success: function (data)  //服务器成功响应处理函数
                {
                    // console.log(data.data.path);
                    var src = data.data.path;
                    var html = '<div id="cover" class="thumbnail" style="background-image: url(/'+src+');"><div class="file-panel"><input type="hidden" value="'+src+'" name="pic_url[]"/></div></div>';
                    $("#fileList").append(html);
                }
            });
            return false;
        }

        $(document).on("change","#use_attr",function(){
            if($('#use_attr').is(':checked')) {
                $("#attr_detail").show()
            }else {
                $("#attr_detail").hide()
            }
        });

        $("#attr").on("input",function() {
            $("#attr-value").text("属性值(" + $(this).val() +")")
        });

        $("#attr_add").click(function() {
            var new_value = $("#attr").val();
            if(new_value ==  '') {
                alert('请先设置一个规格');
                return false;
            }
            $("#attr_detail").append(
                '<div class="row cl"><label class="form-label col-sm-2"><a style="text-decoration:none" class="ml-5" title="删除" onclick="javascript:$(this).parent().parent().remove();"><i class="Hui-iconfont">&#xe6e2;</i></a></label><div class="formControls col-sm-2"><input type="text" class="input-text" value="" name="attr1[]"></div><div class="formControls col-sm-2"><input type="text" class="input-text" value="" name="attr2[]"></div><div class="formControls col-sm-2"><input type="number" class="input-text" value="" name="attr3[]"></div></div>'
            );
        })

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
