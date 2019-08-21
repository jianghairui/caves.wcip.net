<?php /*a:3:{s:67:"/mnt/www.caves.vip/application/admin/view/member/recharge_list.html";i:1557197684;s:53:"/mnt/www.caves.vip/application/admin/view/layout.html";i:1539060499;s:60:"/mnt/www.caves.vip/application/admin/view/public/footer.html";i:1537872047;}*/ ?>
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
        width:40px;height:40px;border-radius: 50%;
    }
</style>
<nav class="breadcrumb"><i class="Hui-iconfont">&#xe67f;</i> 首页 <span class="c-gray en">&gt;</span> 订单管理 <span class="c-gray en">&gt;</span> 订单列表 <a class="btn btn-success radius r" style="line-height:1.6em;margin-top:3px" href="javascript:location.replace(location.href);" title="刷新" ><i class="Hui-iconfont">&#xe68f;</i></a></nav>
<div class="page-container">
    <section id="formAjax">
        <div class="text-c">
            <button onclick="removeIframe()" class="btn btn-primary radius">关闭选项卡</button>
            日期范围：
            <input type="text" onfocus="WdatePicker({ maxDate:'#F{ $dp.$D(\'logmax\')||\'%y-%M-%d\' }' })" id="logmin" value="<?php echo htmlentities(app('request')->get('logmin')); ?>" class="input-text Wdate" style="width:120px;">
            -
            <input type="text" onfocus="WdatePicker({ minDate:'#F{ $dp.$D(\'logmin\') }' })" id="logmax" value="<?php echo htmlentities(app('request')->get('logmax')); ?>" class="input-text Wdate" style="width:120px;">
            <input type="text" name="search" value="<?php echo htmlentities(app('request')->get('search')); ?>" id="search" placeholder=" 订单名称或手机号" style="width:250px" class="input-text">
            <button name="" id="search-btn" class="btn btn-success" type="button"><i class="Hui-iconfont">&#xe665;</i> 查找订单</button>
        </div>
    </section>
    <div class="cl pd-5 bg-1 bk-gray mt-20">
        <span class="r">共有数据：<strong><?php echo htmlentities($page['count']); ?></strong> 条</span> </div>
    <table class="table table-border table-bordered table-bg">
        <thead>
        <tr class="text-c">
            <th width="30">#</th>
            <th width="160">订单号</th>
            <th width="200">微信端单号</th>
            <th width="60">价格</th>
            <th>充值类目</th>
            <th width="120">下单时间</th>
            <th width="50">订单状态</th>
            <th width="120">操作</th>
        </tr>
        </thead>
        <tbody>
        <form id="multi-check">
            <?php foreach($list as $li): ?>
            <tr class="text-c userinfo">
                <td rowspan="2"><?php echo htmlentities($li['id']); ?></td>
                <td><?php echo htmlentities($li['order_sn']); ?></td>
                <td><?php echo htmlentities($li['trans_id']); ?></td>
                <td><?php echo htmlentities($li['price']); ?></td>
                <td><?php echo htmlentities($li['title']); ?></td>
                <td><?php echo htmlentities(date("Y-m-d H:i:s",!is_numeric($li['create_time'])? strtotime($li['create_time']) : $li['create_time'])); ?></td>
                <td>
                    <?php switch($li['status']): case "0": ?>
                    <span class="label label-defaunt radius">待支付</span>
                    <?php break; case "1": ?>
                    <span class="label label-primary radius">待发货</span>
                    <?php break; case "2": ?>
                    <span class="label label-success radius">已完成</span>
                    <?php break; default: endswitch; ?>
                </td>
                <td class="td-manage">
                    <a title="查看详情" href="javascript:;" onclick="detail('订单详情','<?php echo url("Member/rechargeDetail",array("id"=>$li["id"])); ?>')">
                    <i class="Hui-iconfont">&#xe6df;</i>
                    </a>
                    <?php if($li['send'] == 0 && $li['status'] == 1): ?>
                    <a title="发货" href="javascript:;" onclick="order_send('订单发货','<?php echo url("Member/orderSend",array("id"=>$li["id"])); ?>','600','400')"">
                        <i class="Hui-iconfont">&#xe669;</i>
                    </a>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td colspan="5">
                    <b style="margin-left: 15px;">收货地址</b>: <?php echo htmlentities($li['address']); ?> &nbsp;&nbsp;&nbsp;&nbsp;(收货人: <?php echo htmlentities($li['name']); ?>)
                </td>
                <td colspan="2">
                    <b style="margin-left: 15px;">收货手机号</b>: <?php echo htmlentities($li['tel']); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </form>

        <tr>
            <td colspan="7" id="page" class="text-r"></td>
        </tr>
        </tbody>
    </table>
</div>
<!--请在下方写此页面业务相关的脚本-->
<script type="text/javascript" src="/lib/My97DatePicker/4.8/WdatePicker.js"></script>
<script type="text/javascript" src="/lib/datatables/1.10.0/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="/lib/laypage/1.2/laypage.js"></script>
<script type="text/javascript">

    var url = '<?php echo url("Member/rechargeList"); ?>' + '?<?php echo $page["query"];?>';
    var curr = '<?php echo htmlentities($page['curr']); ?>',totalPage='<?php echo htmlentities($page['totalPage']); ?>';
    if(totalPage > 1) {
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
    }


    $("#search-btn").click(function () {
        var logmin = $("#formAjax").find("#logmin").val();
        var logmax = $("#formAjax").find("#logmax").val();
        var search = $("#formAjax").find("#search").val();
        var str = '';
        if(logmin.length != '') {
            str += '&logmin=' + logmin
        }
        if(logmax.length != '') {
            str += '&logmax=' + logmax
        }
        if(search.length != '') {
            str += '&search=' + search
        }
        window.location.href = '<?php echo url("Member/rechargeList"); ?>' + '?' + str;
    });

    /*查看订单信息*/
    function detail(title,url){
        var index = layer.open({
            type: 2,
            title: title,
            content: url
        });
        layer.full(index);
    }

    /*发货*/
    function order_send(title,url,w,h){
        layer_show(title,url,w,h);
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
