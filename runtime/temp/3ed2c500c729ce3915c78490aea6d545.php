<?php /*a:3:{s:62:"/mnt/www.caves.vip/application/admin/view/shop/order_list.html";i:1554792766;s:53:"/mnt/www.caves.vip/application/admin/view/layout.html";i:1539060499;s:60:"/mnt/www.caves.vip/application/admin/view/public/footer.html";i:1537872047;}*/ ?>
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
    .goods_pics{ width:80px;height: 80px;background-size: cover;background-position: center}
    .order-font{color: #d9534f;}
    .order-font-b{color:#363636;font-weight: bold}
    .order-font-price{color:blue;font-weight: bold}
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
            <span class="select-box inline" style="width: 120px;">
                <select name="status" id="status" class="select">
                    <option value="">全部</option>
                    <option value="0" <?php if($param['status'] === '0'): ?>selected<?php endif; ?>>待付款</option>
                    <option value="1" <?php if($param['status'] === '1'): ?>selected<?php endif; ?>>待发货</option>
                    <option value="2" <?php if($param['status'] === '2'): ?>selected<?php endif; ?>>待收货</option>
                    <option value="3" <?php if($param['status'] === '3'): ?>selected<?php endif; ?>>已完成</option>
                </select>
            </span>
            <span class="select-box inline" style="width: 120px;">
                <select name="refund_apply" id="refund_apply" class="select">
                    <option value="">全部</option>
                    <option value="1" <?php if($param['refund_apply'] === '1'): ?>selected<?php endif; ?>>申请退款</option>
                    <option value="2" <?php if($param['refund_apply'] === '2'): ?>selected<?php endif; ?>>已退款</option>
                </select>
            </span>
            <input type="text" name="search" value="<?php echo htmlentities($param['search']); ?>" id="search" placeholder=" 订单名称或手机号" style="width:200px" class="input-text">
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
            <th width="80">实际支付</th>
            <th>订单总价</th>
            <th width="120">下单时间</th>
            <th width="50">订单状态</th>
            <th width="120">操作</th>
        </tr>
        </thead>
        <tbody>
        <form id="multi-check">
            <?php foreach($list as $li): ?>
            <tr class="text-c userinfo">
                <td rowspan="3"><?php echo htmlentities($li['id']); ?></td>
                <td><?php echo htmlentities($li['pay_order_sn']); ?></td>
                <td><?php echo htmlentities($li['trans_id']); ?></td>
                <td><span class="order-font-price" onclick="mod_price(this,<?php echo htmlentities($li['id']); ?>,<?php echo htmlentities($li['pay_price']); ?>)"><?php echo htmlentities($li['pay_price']); ?></span>元</td>
                <td><span class="order-font-price"><?php echo htmlentities($li['total_price']); ?></span>元 (运费:<span style="color: green"><?php echo htmlentities($li['carriage']); ?></span>元)</td>
                <td><?php echo htmlentities(date("Y-m-d H:i:s",!is_numeric($li['create_time'])? strtotime($li['create_time']) : $li['create_time'])); ?></td>
                <td class="td-status">
                    <span id="pay-status">
                        <?php switch($li['status']): case "0": ?>
                            <span class="label label-defaunt radius">待付款</span>
                        <?php break; case "1": ?>
                            <span class="label label-warning radius">待发货</span>
                        <?php break; case "2": ?>
                            <span class="label label-primary radius">待收货</span>
                        <?php break; case "3": ?>
                            <span class="label label-success radius">已完成</span>
                        <?php break; default: endswitch; ?>
                    </span>
                    <span id="refund-status">
                        <?php switch($li['refund_apply']): case "1": ?>
                            <span class="label label-danger radius">申请退款</span>
                        <?php break; case "2": ?>
                            <span class="label label-danger radius">已退款</span>
                        <?php break; default: endswitch; ?>
                    </span>

                </td>
                <td class="td-manage" rowspan="3">
                    <span>
                        <a title="查看详情" href="javascript:;" onclick="detail('订单详情','<?php echo url("Shop/orderDetail",array("id"=>$li["id"])); ?>')">
                        <i class="Hui-iconfont">&#xe6df;</i>
                        </a>
                    </span>
                    <span id="send-btn">
                        <?php if($li['status'] == 1 && $li['refund_apply'] == 0): ?>
                        <a title="发货" href="javascript:;" onclick="order_send('订单发货','<?php echo url("Shop/orderSend",array("id"=>$li["id"])); ?>','600','400')"">
                        <i class="Hui-iconfont">&#xe669;</i>
                        </a>
                        <?php endif; ?>
                    </span>
                    <span id="refund-btn">
                        <?php if($li['refund_apply'] == 1): ?>
                        <a title="退款" href="javascript:;" onclick="refund(this,<?php echo htmlentities($li['id']); ?>)">
                        <i class="Hui-iconfont">&#xe66b;</i>
                        </a>
                        <?php endif; ?>
                    </span>


                </td>
            </tr>
            <tr>
                <td colspan="6">
                    <?php foreach($li['child'] as $vv): ?>
                    <div style="display: inline-flex">
                        <div id="cover" class="goods_pics" style="background-image: url('/<?php echo htmlentities($vv['cover']); ?>');"></div>
                        <div style="margin: 0 10px;">
                            <div><span class="order-font-b"><?php echo htmlentities($vv['goods_name']); ?></span></div>
                            <div>规格: <span class="order-font"><?php echo htmlentities($vv['attr']); ?></span></div>
                            <div>数量: <span class="order-font"><?php echo htmlentities($vv['num']); ?></span></div>
                            <div>小计: <span class="order-font"><?php echo htmlentities($vv['total_price']); ?>元</span></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </td>
            </tr>
            <tr>
                <td colspan="6">
                    <span style="margin-left: 15px;" onclick="mod_address(this,<?php echo htmlentities($li['id']); ?>,'<?php echo htmlentities($li['address']); ?>')">收货地址: <b><?php echo htmlentities($li['address']); ?></b></span> &nbsp;&nbsp;&nbsp;&nbsp;(收货人: <b><?php echo htmlentities($li['receiver']); ?></b> | 手机号: <b><?php echo htmlentities($li['tel']); ?></b>)
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

    var url = '<?php echo url("Shop/orderList"); ?>' + '?<?php echo $page["query"];?>';
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

    var click_lock = true;
    $("#search-btn").click(function () {
        var logmin = $("#formAjax").find("#logmin").val();
        var logmax = $("#formAjax").find("#logmax").val();
        var search = $("#formAjax").find("#search").val();
        var status = $("#formAjax").find("#status").val();
        var refund_apply = $("#formAjax").find("#refund_apply").val();
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
        if(status.length != '') {
            str += '&status=' + status
        }
        if(refund_apply.length != '') {
            str += '&refund_apply=' + refund_apply
        }
        window.location.href = '<?php echo url("Shop/orderList"); ?>' + '?' + str;
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
    /*修改收货地址*/
    function mod_address(obj,id,old_address) {
        layer.prompt({
            formType: 2,
            value: old_address,
            title: '修改收货地址(最多255个字)',
            maxlength:255,
            area: ['400px', '300px'] //自定义文本域宽高
        }, function(value, index, elem){
            address = value;
            layer.close(index);
            if(click_lock) {
                click_lock = false;
                $.ajax({
                    url:"<?php echo url('Shop/modAddress'); ?>",
                    type:'post',
                    dataType:'json',
                    data:{id:id,address:address},
                    success:function(data) {
                        console.log(data)
                        if(data.code == 1) {
                            $(obj).find("b").text(value);
                            click_lock = true
                        }else {
                            layer.msg(data.data,{icon:2,time:1000})
                        }
                        click_lock = true
                    },
                    error:function(data) {
                        layer.msg('请求失败!',{icon:5,time:1000})
                        click_lock = true
                    }
                })
            }
        });
    }
    /*修改价格*/
    function  mod_price(obj,id,old_price) {
        layer.prompt({
            formType: 3,
            value: old_price,
            title: '修改支付金额',
            maxlength:11
        }, function(value, index, elem){
            pay_price = value;
            if(!(/^\d{1,8}(\.\d{1,2})?$/.test(pay_price))) {
                layer.alert('金额格式不符');
                return false;
            }
            layer.close(index);
            if(click_lock) {
                click_lock = false;
                $.ajax({
                    url:"<?php echo url('Shop/modPrice'); ?>",
                    type:'post',
                    dataType:'json',
                    data:{id:id,pay_price:pay_price},
                    success:function(data) {
                        console.log(data);
                        if(data.code == 1) {
                            $(obj).text(value);
                            click_lock = true
                        }else {
                            layer.msg(data.data,{icon:2,time:1000});
                        }
                        click_lock = true
                    },
                    error:function(data) {
                        layer.msg('请求失败!',{icon:5,time:1000});
                        click_lock = true
                    }
                })
            }
        });
    }

    function refund(obj,id) {
        layer.confirm('确认要退款吗？',function(index){
            $.ajax({
                url: "<?php echo url('Shop/orderRefund'); ?>",
                type: 'POST',
                dataType: 'json',
                data:{id:id},
                success: function(data){
                    console.log(data);
                    if(data.code == 1) {
                        $(obj).parents("tr").find("#refund-status").html('<span class="label label-danger radius">已退款</span>');
                        $(obj).parents("tr").find("#send-btn").remove();
                        $(obj).remove();
                        layer.msg('已退款!',{icon:1,time:1000});
                    }else {
                        layer.msg(data.data,{icon:2,time:1000});
                    }
                },
                error:function(data) {
                    layer.msg('接口请求失败',{icon:5,time:1000});
                }
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
