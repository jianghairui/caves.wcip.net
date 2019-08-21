<?php /*a:3:{s:62:"/mnt/www.caves.vip/application/admin/view/shop/goods_list.html";i:1554626840;s:53:"/mnt/www.caves.vip/application/admin/view/layout.html";i:1539060499;s:60:"/mnt/www.caves.vip/application/admin/view/public/footer.html";i:1537872047;}*/ ?>
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
    .userinfo>td>img {
        width:80px;height:80px
    }
</style>
<nav class="breadcrumb"><i class="Hui-iconfont">&#xe67f;</i> 首页 <span class="c-gray en">&gt;</span> 商城管理 <span class="c-gray en">&gt;</span> 商品列表 <a class="btn btn-success radius r" style="line-height:1.6em;margin-top:3px" href="javascript:location.replace(location.href);" title="刷新" ><i class="Hui-iconfont">&#xe68f;</i></a></nav>
<div class="page-container">
    <section id="formAjax">
        <div class="text-c">
            <button onclick="removeIframe()" class="btn btn-primary radius">关闭选项卡</button>
            <input type="text" name="search" value="<?php echo htmlentities(app('request')->get('search')); ?>" id="search" placeholder=" 商品名" style="width:250px" class="input-text">
            <button name="" id="search-btn" class="btn btn-success" type="button"><i class="Hui-iconfont">&#xe665;</i> 查找商品</button>
        </div>
    </section>
    <div class="cl pd-5 bg-1 bk-gray mt-20">
        <span class="l">
            <a class="btn btn-primary radius" data-title="添加商品" data-href="article-add.html" onclick="add_info('添加商品','<?php echo url("Shop/goodsAdd"); ?>')" href="javascript:;"><i class="Hui-iconfont">&#xe600;</i> 添加商品
            </a>
        </span>
        <span class="r">共有数据：<strong><?php echo htmlentities($page['count']); ?></strong> 条</span> </div>
    <table class="table table-border table-bordered table-bg">
        <thead>
        <tr class="text-c">
            <th width="25">#</th>
            <th width="100">商品图片</th>
            <th>商品名</th>
            <th width="90">商品价格</th>
            <th width="90">库存</th>
            <th width="80">排序</th>
            <th width="80">是否热销</th>
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
                    <?php $image = unserialize($li['pics'])[0];?>
                    <div style="width: 80px;height: 80px;background-image: url('/<?php echo htmlentities($image); ?>');background-position: center;background-repeat: no-repeat;background-size: 100%"></div>
                </td>
                <td><?php echo htmlentities($li['name']); ?></td>
                <td><?php echo htmlentities($li['price']); ?></td>
                <td><?php echo htmlentities($li['stock']); ?></td>
                <td><?php echo htmlentities($li['sort']); ?></td>
                <td><?php echo htmlentities($li['hot']); ?></td>
                <td class="td-status">
                    <?php if($li['status'] == '1'): ?>
                    <span class="label label-success radius">已上架</span>
                    <?php endif; if($li['status'] == '0'): ?>
                    <span class="label label-defaunt radius">已下架</span>
                    <?php endif; ?>
                </td>
                <td class="td-manage">
                    <?php if($li['status'] == '1'): ?>
                    <a style="text-decoration:none" onClick="goods_hide(this,<?php echo htmlentities($li['id']); ?>)" href="javascript:;" title="下架">
                        <i class="Hui-iconfont">&#xe6de;</i>
                    </a>
                    <?php else: ?>
                    <a style="text-decoration:none" onClick="goods_show(this,<?php echo htmlentities($li['id']); ?>)" href="javascript:;" title="上架">
                        <i class="Hui-iconfont">&#xe603;</i>
                    </a>
                    <?php endif; ?>
                    <a style="text-decoration:none" onclick="add_info('商品信息','<?php echo url("Shop/goodsDetail",array("id"=>$li["id"])); ?>')" class="ml-5" href="javascript:;" title="查看商品">
                    <i class="Hui-iconfont">&#xe6df;</i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </form>

        <tr>
            <td colspan="9" id="page" class="text-r"></td>
        </tr>
        </tbody>
    </table>
</div>
<!--请在下方写此页面业务相关的脚本-->
<script type="text/javascript" src="/lib/My97DatePicker/4.8/WdatePicker.js"></script>
<script type="text/javascript" src="/lib/datatables/1.10.0/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="/lib/laypage/1.2/laypage.js"></script>
<script type="text/javascript">

    var url = '<?php echo url("Shop/goodsList"); ?>' + '?<?php echo $page["query"];?>';
    var curr = '<?php echo htmlentities($page['curr']); ?>',totalPage='<?php echo htmlentities($page['totalPage']); ?>';
    if(totalPage > 1) {
        laypage({
            cont: 'page', //容器。值支持id名、原生dom对象，jquery对象。
            pages: totalPage, //通过后台拿到的总页数
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
    }

    $("#search-btn").click(function () {
        var search = $("#formAjax").find("#search").val();
        var id = $("#formAjax").find("#id").val();
        var str = 'id=' + id;
        if(search.length != '') {
            str += '&search=' + search;
        }
        window.location.href = '<?php echo url("Shop/goodsList"); ?>' + '?' + str;
    });

    /*添加商品*/
    function add_info(title,url){
        var index = layer.open({
            type: 2,
            title: title,
            content: url
        });
        layer.full(index);
    }

    /*下架*/
    function goods_hide(obj,id){
        layer.confirm('确认要下架吗？',function(index){
            $.ajax({
                url:"<?php echo url('Shop/goodsHide'); ?>",
                type:'post',
                dataType:'json',
                data:{id:id},
                success:function(data) {
                    console.log(data)
                    if(data.code == 1) {
                        $(obj).parents("tr").find(".td-status").html('<span class="label label-defaunt radius">已下架</span>');
                        $(obj).parents("tr").find(".td-manage").prepend('<a style="text-decoration:none" onClick="goods_show(this,'+id+')" href="javascript:;" title="上架"><i class="Hui-iconfont">&#xe603;</i></a>');
                        $(obj).remove();
                        layer.msg('已下架!',{icon: 1,time:1000});
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
    function goods_show(obj,id){
        layer.confirm('确认要上架吗？',function(index){
            $.ajax({
                url:"<?php echo url('Shop/goodsShow'); ?>",
                type:'post',
                dataType:'json',
                data:{id:id},
                success:function(data) {
                    console.log(data);
                    if(data.code == 1) {
                        $(obj).parents("tr").find(".td-status").html('<span class="label label-success radius">已上架</span>');
                        $(obj).parents("tr").find(".td-manage").prepend('<a style="text-decoration:none" onClick="goods_hide(this,'+id+')" href="javascript:;" title="下架"><i class="Hui-iconfont">&#xe6de;</i></a>');
                        $(obj).remove();
                        layer.msg('已上架!',{icon: 1,time:1000});
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
