<?php /*a:3:{s:60:"/mnt/www.caves.vip/application/admin/view/system/syslog.html";i:1554111479;s:53:"/mnt/www.caves.vip/application/admin/view/layout.html";i:1539060499;s:60:"/mnt/www.caves.vip/application/admin/view/public/footer.html";i:1537872047;}*/ ?>
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
<nav class="breadcrumb"><i class="Hui-iconfont">&#xe67f;</i> 首页
    <span class="c-gray en">&gt;</span>
    系统管理
    <span class="c-gray en">&gt;</span>
    系统日志
    <a class="btn btn-success radius r" style="line-height:1.6em;margin-top:3px" href="javascript:location.replace(location.href);" title="刷新" ><i class="Hui-iconfont">&#xe68f;</i></a>
</nav>
<div class="page-container">
    <form id="formAjax">
        <div class="text-c"> 日期范围：
            <input type="text" onfocus="WdatePicker({ maxDate:'#F{ $dp.$D(\'logmax\')||\'%y-%M-%d\' }' })" id="logmin" value="<?php echo htmlentities(app('request')->get('logmin')); ?>" class="input-text Wdate" style="width:120px;">
            -
            <input type="text" onfocus="WdatePicker({ minDate:'#F{ $dp.$D(\'logmin\') }' })" id="logmax" value="<?php echo htmlentities(app('request')->get('logmax')); ?>" class="input-text Wdate" style="width:120px;">
            <input type="text" name="search" id="search" value="<?php echo htmlentities(app('request')->get('search')); ?>" placeholder="用户名或日志名称" style="width:250px" class="input-text">
            <button id="search-btn" class="btn btn-success" type="button"><i class="Hui-iconfont">&#xe665;</i> 搜日志</button>
        </div>
    </form>
    <div class="cl pd-5 bg-1 bk-gray mt-20">
        <span class="r">共有数据：<strong><?php echo htmlentities($page['count']); ?></strong> 条</span>
    </div>
    <table class="table table-border table-bordered table-bg table-hover table-sort">
        <thead>
        <tr class="text-c">
            <th width="80">ID</th>
            <th>操作</th>
            <th width="17%">用户名</th>
            <th width="120">客户端IP</th>
            <th width="120">时间</th>
            <th width="100">操作类型</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($list as $li): ?>
        <tr class="text-c">
            <td><?php echo htmlentities($li['id']); ?></td>
            <td><?php echo htmlentities($li['detail']); ?></td>
            <td><?php echo htmlentities($li['username']); ?></td>
            <td><?php echo htmlentities($li['ip']); ?></td>
            <td><?php echo htmlentities(date('Y-m-d H:i:s',!is_numeric($li['create_time'])? strtotime($li['create_time']) : $li['create_time'])); ?></td>
            <td>
                <?php switch($li['type']): case "0": ?>登录<?php break; case "1": ?>增<?php break; case "2": ?>删<?php break; case "3": ?>改<?php break; default: ?>其他
                <?php endswitch; ?>
            </td>
        </tr>
        <?php endforeach; ?>

        <tr class="text-r">
            <td colspan="9" id="page">

            </td>
        </tr>
        </tbody>
    </table>
    <div id="pageNav" class="pageNav"></div>
</div>

<!--请在下方写此页面业务相关的脚本-->
<script type="text/javascript" src="/lib/My97DatePicker/4.8/WdatePicker.js"></script>
<script type="text/javascript" src="/lib/datatables/1.10.0/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="/lib/laypage/1.2/laypage.js"></script>
<script>
    var url = '<?php echo url("System/syslog"); ?>' + '?<?php echo $page["query"];?>'
    var curr = '<?php echo htmlentities($page['curr']); ?>'
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
        var logmin = $("#formAjax").find("#logmin").val()
        var logmax = $("#formAjax").find("#logmax").val()
        var search = $("#formAjax").find("#search").val()
        var str = ''
        if(logmin.length != '') {
            str += 'logmin=' + logmin
        }
        if(logmax.length != '') {
            str += '&logmax=' + logmax
        }
        if(search.length != '') {
            str += '&search=' + search
        }
        window.location.href = '<?php echo url("System/syslog"); ?>' + '?' + str;
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
