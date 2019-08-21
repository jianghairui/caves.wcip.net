<?php /*a:3:{s:60:"/mnt/www.caves.vip/application/admin/view/member/detail.html";i:1553580934;s:53:"/mnt/www.caves.vip/application/admin/view/layout.html";i:1539060499;s:60:"/mnt/www.caves.vip/application/admin/view/public/footer.html";i:1537872047;}*/ ?>
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
<link href="/lib/lightbox2/2.8.1/css/lightbox.css" rel="stylesheet" type="text/css" >
<article class="page-container">
    <form class="form form-horizontal" id="form-admin-add">
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-3"><span class="c-red">*</span>真实姓名：</label>
            <div class="formControls col-xs-8 col-sm-9">
                <input type="text" class="input-text" value="<?php echo htmlentities($info['realname']); ?>" placeholder="" name="realname" readonly>
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-3"><span class="c-red">*</span>用户昵称：</label>
            <div class="formControls col-xs-8 col-sm-9">
                <input type="text" class="input-text" value="<?php echo htmlentities($info['nickname']); ?>" name="nickname" readonly>
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-3"><span class="c-red">*</span>账户余额：</label>
            <div class="formControls col-xs-8 col-sm-9">
                <input type="text" class="input-text" value="￥：<?php echo htmlentities($info['balance']); ?>" name="balance" readonly>
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-3"><span class="c-red">*</span>性别：</label>
            <div class="formControls col-xs-8 col-sm-9 skin-minimal">
                <div class="radio-box">
                    <input name="sex" type="radio" id="sex-1" <?php if($info['sex'] == 1): ?>checked<?php endif; ?> >
                    <label for="sex-1">男</label>
                </div>
                <div class="radio-box">
                    <input type="radio" id="sex-2" name="sex" <?php if($info['sex'] == 2): ?>checked<?php endif; ?> >
                    <label for="sex-2">女</label>
                </div>
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-3"><span class="c-red">*</span>手机号：</label>
            <div class="formControls col-xs-8 col-sm-3">
                <input type="text" class="input-text" value="<?php echo htmlentities($info['tel']); ?>13102163019" placeholder="" name="tel" readonly style="border: 0">
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-3"><span class="c-red">*</span>是否认证：</label>
            <div class="formControls col-xs-8 col-sm-9">
                <input type="text" class="input-text" value="<?php echo htmlentities($info['auth']); ?>" placeholder="" name="auth" readonly>
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-3"><span class="c-red">*</span>组织机构：</label>
            <div class="formControls col-xs-8 col-sm-9">
                <input type="text" class="input-text" value="<?php echo htmlentities($info['org']); ?>" placeholder="" name="org" readonly>
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-3"><span class="c-red">*</span>法人代表：</label>
            <div class="formControls col-xs-8 col-sm-9">
                <input type="text" class="input-text" value="<?php echo htmlentities($info['name']); ?>" placeholder="" name="name" readonly>
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-3"><span class="c-red">*</span>身份证号：</label>
            <div class="formControls col-xs-8 col-sm-9">
                <input type="text" class="input-text" value="<?php echo htmlentities($info['identity']); ?>" placeholder="" name="identity" readonly>
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-3">
                身份证正反面：
            </label>
            <div class="formControls col-xs-8 col-sm-9">
                <ul class="cl portfolio-area">
                    <li class="item">
                        <div class="portfoliobox">
                            <input class="checkbox" name="" type="checkbox" value="">
                            <div class="picbox"><a href="/<?php echo htmlentities($info['id_front']); ?>" data-lightbox="gallery" data-title=""><img src="/<?php echo htmlentities($info['id_front']); ?>"></a></div>
                            <div class="textbox"></div>
                        </div>
                    </li>
                    <li class="item">
                        <div class="portfoliobox">
                            <input class="checkbox" name="" type="checkbox" value="">
                            <div class="picbox"><a href="/<?php echo htmlentities($info['id_back']); ?>" data-lightbox="gallery" data-title=""><img src="/<?php echo htmlentities($info['id_back']); ?>"></a></div>
                            <div class="textbox"></div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-3"><span class="c-red">*</span>认证手机号：</label>
            <div class="formControls col-xs-8 col-sm-3">
                <input type="text" class="input-text" value="<?php echo htmlentities($info['role_tel']); ?>" placeholder="" name="role_tel" readonly style="border: 0">
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-3"><span class="c-red">*</span>运营者微信号：</label>
            <div class="formControls col-xs-8 col-sm-9">
                <input type="text" class="input-text" value="<?php echo htmlentities($info['weixin']); ?>" placeholder="" name="weixin" readonly>
            </div>
        </div>
        <?php if($info['role'] == 3): ?>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-3">
                作品6张：
            </label>
            <div class="formControls col-xs-8 col-sm-9">
                <ul class="cl portfolio-area">
                    <?php if($info['works']): $image = unserialize($info['works']);foreach($image as $li): ?>
                    <li class="item">
                        <div class="portfoliobox">
                            <input class="checkbox" name="" type="checkbox" value="">
                            <div class="picbox"><a href="/<?php echo htmlentities($li); ?>" data-lightbox="gallery" data-title=""><img src="/<?php echo htmlentities($li); ?>"></a></div>
                            <div class="textbox"></div>
                        </div>
                    </li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-3">
                资质证明：
            </label>
            <div class="formControls col-xs-8 col-sm-9">
                <ul class="cl portfolio-area">
                    <li class="item">
                        <div class="portfoliobox">
                            <input class="checkbox" name="" type="checkbox" value="">
                            <div class="picbox"><a href="/<?php echo htmlentities($info['license']); ?>" data-lightbox="gallery" data-title=""><img src="/<?php echo htmlentities($info['license']); ?>"></a></div>
                            <div class="textbox"></div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-4 col-sm-3">备注：</label>
            <div class="formControls col-xs-8 col-sm-9">
                <textarea name="desc" cols="" rows="" class="textarea" placeholder="说点什么...最多输入100个字符" datatype="*10-100"><?php echo htmlentities($info['desc']); ?></textarea>
            </div>
        </div>
        <div class="row cl">
            <div class="col-xs-8 col-sm-9 col-xs-offset-4 col-sm-offset-3">
                <?php if($info['auth'] == 1): ?>
                <button onClick="check_pass('<?php echo htmlentities($info['id']); ?>');" class="btn btn-success radius" type="button">&nbsp;&nbsp;通过&nbsp;&nbsp;</button>
                <button onClick="check_reject('<?php echo htmlentities($info['id']); ?>');" class="btn btn-danger radius" type="button">&nbsp;&nbsp;拒绝&nbsp;&nbsp;</button>
                <?php endif; ?>
                <input class="btn btn-primary radius" onclick="layer_close()" type="submit" value="&nbsp;&nbsp;关闭选项卡&nbsp;&nbsp;">
            </div>
        </div>
        <div class="row cl">

        </div>
    </form>
</article>

<!--请在下方写此页面业务相关的脚本-->
<script type="text/javascript" src="/lib/lightbox2/2.8.1/js/lightbox.min.js"></script>
<script type="text/javascript">
    $(function(){
        $(".portfolio-area li").Huihover();
    });

    /*需求审核*/
    function check_pass(id){
        layer.confirm('通过审核？', {
                btn: ['确定','取消'],
                shade: false,
                closeBtn: 0
            },
            function(){
                $.ajax({
                    url:"<?php echo url('Member/rolePass'); ?>",
                    type:'post',
                    dataType:'json',
                    data:{id:id},
                    success:function(data) {
                        console.log(data)
                        if(data.code == 1) {
                            layer.msg('已审核', {icon:1,time:1000},function () {
                                window.parent.location.reload();
                            });
                        }else {
                            layer.msg(data.data,{icon:2,time:1000})
                        }
                    },
                    error:function(data) {
                        layer.msg('请求失败!',{icon:5,time:1000})
                    }
                })

            }
            );
    }


    function check_reject(id) {
        layer.confirm('拒绝通过审核？', {
                btn: ['确定','取消'],
                shade: false,
                closeBtn: 0
            }, function() {
                layer.prompt({
                    formType: 2,
                    value: '内容违规',
                    title: '请输入理由(最多50个字)',
                    maxlength: 50,
                    area: ['400px', '300px'] //自定义文本域宽高
                }, function (value, index, elem) {
                    reason = value;
                    layer.close(index);
                    $.ajax({
                        url: "<?php echo url('Member/roleReject'); ?>",
                        type: 'post',
                        dataType: 'json',
                        data: {id: id, reason: reason},
                        success: function (data) {
                            console.log(data);
                            if (data.code == 1) {
                                layer.msg('已拒绝', {icon: 1, time: 1000},function () {
                                    window.parent.location.reload();
                                });
                            } else {
                                layer.msg(data.data, {icon: 2, time: 1000})
                            }
                        },
                        error: function (data) {
                            layer.msg('请求失败!', {icon: 5, time: 1000})
                        }
                    })
                })
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
