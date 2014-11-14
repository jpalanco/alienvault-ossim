<?php
/*******************************************************************************
** OSSIM Forensics Console
** Copyright (C) 2009 OSSIM/AlienVault
** Copyright (C) 2004 BASE Project Team
** Copyright (C) 2000 Carnegie Mellon University
**
** (see the file 'base_main.php' for license details)
**
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
** Built upon work by the BASE Project Team <kjohnson@secureideas.net>
*/


//locale
DEFINE('_LOCALESTR1', 'eng_ENG.ISO8859-1'); //NEW
DEFINE('_LOCALESTR2', 'eng_ENG.utf-8'); //NEW
DEFINE('_LOCALESTR3', 'english'); //NEW
DEFINE('_STRFTIMEFORMAT', '%a %B %d, %Y %H:%M:%S'); //NEW - see strftime() sintax
//common phrases
DEFINE('_CHARSET', 'utf-8');
DEFINE('_TITLE', '基本安全分析引擎 (BASE) ' . $BASE_installID);
DEFINE('_FRMLOGIN', '用户登录:');
DEFINE('_FRMPWD', '密码:');
DEFINE('_SOURCE', '来源');
DEFINE('_SOURCENAME', '来源名称');
DEFINE('_DEST', '目的');
DEFINE('_DESTNAME', '目的名称');
DEFINE('_SORD', '来源或目的');
DEFINE('_EDIT', '编辑');
DEFINE('_DELETE', '删除');
DEFINE('_ID', 'ID');
DEFINE('_NAME', '名称');
DEFINE('_INTERFACE', '界面');
DEFINE('_FILTER', '过滤器');
DEFINE('_DESC', '描述');
DEFINE('_LOGIN', '登录');
DEFINE('_ROLEID', '角色 ID');
DEFINE('_ENABLED', '启用');
DEFINE('_SUCCESS', '已成功');
DEFINE('_SENSOR', '探测器');
DEFINE('_SENSORS', 'Sensors'); //NEW
DEFINE('_SIGNATURE', '特征');
DEFINE('_TIMESTAMP', '时间戳');
DEFINE('_NBSOURCEADDR', '来源&nbsp;地址');
DEFINE('_NBDESTADDR', '目标&nbsp;地址');
DEFINE('_NBLAYER4', '第&nbsp;4&nbsp;层协议');
DEFINE('_PRIORITY', '优先级');
DEFINE('_EVENTTYPE', '事件类型');
DEFINE('_JANUARY', '一月');
DEFINE('_FEBRUARY', '二月');
DEFINE('_MARCH', '三月');
DEFINE('_APRIL', '四月');
DEFINE('_MAY', '五月');
DEFINE('_JUNE', '六月');
DEFINE('_JULY', '7月');
DEFINE('_AUGUST', '八月');
DEFINE('_SEPTEMBER', '九月');
DEFINE('_OCTOBER', '十月');
DEFINE('_NOVEMBER', '十一月');
DEFINE('_DECEMBER', '十二月');
DEFINE('_LAST', '最后');
DEFINE('_FIRST', 'First'); //NEW
DEFINE('_TOTAL', 'Total'); //NEW
DEFINE('_ALERT', '警告数');
DEFINE('_ADDRESS', '地址');
DEFINE('_UNKNOWN', '未知');
DEFINE('_AND', 'AND'); //NEW
DEFINE('_OR', 'OR'); //NEW
DEFINE('_IS', 'is'); //NEW
DEFINE('_ON', 'on'); //NEW
DEFINE('_IN', 'in'); //NEW
DEFINE('_ANY', 'any'); //NEW
DEFINE('_NONE', 'none'); //NEW
DEFINE('_HOUR', 'Hour'); //NEW
DEFINE('_DAY', 'Day'); //NEW
DEFINE('_MONTH', 'Month'); //NEW
DEFINE('_YEAR', 'Year'); //NEW
DEFINE('_ALERTGROUP', 'Alert Group'); //NEW
DEFINE('_ALERTTIME', 'Alert Time'); //NEW
DEFINE('_CONTAINS', 'contains'); //NEW
DEFINE('_DOESNTCONTAIN', 'does not contain'); //NEW
DEFINE('_SOURCEPORT', 'source port'); //NEW
DEFINE('_DESTPORT', 'dest port'); //NEW
DEFINE('_HAS', 'has'); //NEW
DEFINE('_HASNOT', 'has not'); //NEW
DEFINE('_PORT', 'Port'); //NEW
DEFINE('_FLAGS', 'Flags'); //NEW
DEFINE('_MISC', 'Misc'); //NEW
DEFINE('_BACK', 'Back'); //NEW
DEFINE('_DISPYEAR', '{ year }'); //NEW
DEFINE('_DISPMONTH', '{ month }'); //NEW
DEFINE('_DISPHOUR', '{ hour }'); //NEW
DEFINE('_DISPDAY', '{ day }'); //NEW
DEFINE('_DISPTIME', '{ time }'); //NEW
DEFINE('_ADDADDRESS', 'ADD Addr'); //NEW
DEFINE('_ADDIPFIELD', 'ADD IP Field'); //NEW
DEFINE('_ADDTIME', 'ADD TIME'); //NEW
DEFINE('_ADDTCPPORT', 'ADD TCP Port'); //NEW
DEFINE('_ADDTCPFIELD', 'ADD TCP Field'); //NEW
DEFINE('_ADDUDPPORT', 'ADD UDP Port'); //NEW
DEFINE('_ADDUDPFIELD', 'ADD UDP Field'); //NEW
DEFINE('_ADDICMPFIELD', 'ADD ICMP Field'); //NEW
DEFINE('_ADDPAYLOAD', 'ADD Payload'); //NEW
DEFINE('_MOSTFREQALERTS', 'Most Frequent Alerts'); //NEW
DEFINE('_MOSTFREQPORTS', 'Most Frequent Ports'); //NEW
DEFINE('_MOSTFREQADDRS', 'Most Frequent IP addresses'); //NEW
DEFINE('_LASTALERTS', 'Last Alerts'); //NEW
DEFINE('_LASTPORTS', 'Last Ports'); //NEW
DEFINE('_LASTTCP', 'Last TCP Alerts'); //NEW
DEFINE('_LASTUDP', 'Last UDP Alerts'); //NEW
DEFINE('_LASTICMP', 'Last ICMP Alerts'); //NEW
DEFINE('_QUERYDB', 'Query DB'); //NEW
DEFINE('_QUERYDBP', 'Query+DB'); //NEW - Equals to _QUERYDB where spaces are '+'s.
//Should be something like: DEFINE('_QUERYDBP',str_replace(" ", "+", _QUERYDB));
DEFINE('_SELECTED', 'Selected'); //NEW
DEFINE('_ALLONSCREEN', 'ALL on Screen'); //NEW
DEFINE('_ENTIREQUERY', 'Entire Query'); //NEW
DEFINE('_OPTIONS', 'Options'); //NEW
DEFINE('_LENGTH', 'length'); //NEW
DEFINE('_CODE', 'code'); //NEW
DEFINE('_DATA', 'data'); //NEW
DEFINE('_TYPE', 'type'); //NEW
DEFINE('_NEXT', 'Next'); //NEW
DEFINE('_PREVIOUS', 'Previous'); //NEW
//Menu items
DEFINE('_HOME', '首页');
DEFINE('_SEARCH', '搜索');
DEFINE('_AGMAINT', '警告群组维护');
DEFINE('_USERPREF', '用户参数设置');
DEFINE('_CACHE', '缓存 & 状态');
DEFINE('_ADMIN', '管理');
DEFINE('_GALERTD', '绘出警告数据');
DEFINE('_GALERTDT', '绘出警告侦测时间');
DEFINE('_USERMAN', '用户管理');
DEFINE('_LISTU', '用户列表');
DEFINE('_CREATEU', '建立用户');
DEFINE('_ROLEMAN', '角色管理');
DEFINE('_LISTR', '角色列表');
DEFINE('_CREATER', '建立角色');
DEFINE('_LISTALL', '列出所有');
DEFINE('_CREATE', '创建');
DEFINE('_VIEW', '显示');
DEFINE('_CLEAR', '清空');
DEFINE('_LISTGROUPS', '列出群组');
DEFINE('_CREATEGROUPS', '建立群组');
DEFINE('_VIEWGROUPS', '显示群组');
DEFINE('_EDITGROUPS', '编辑群组');
DEFINE('_DELETEGROUPS', '删除群组');
DEFINE('_CLEARGROUPS', '清空群组');
DEFINE('_CHNGPWD', '修改密码');
DEFINE('_DISPLAYU', '显示用户');
//base_footer.php
DEFINE('_FOOTER', ' (由 <A class="largemenuitem" 
href="mailto:base@secureideas.net">Kevin Johnson</A> 和 <A 
class="largemenuitem" 
href="http://sourceforge.net/project/memberlist.php?group_id=103348">BASE 
项目组</A><BR> 基于 Roman Danyliw 的ACID 构建)');
//index.php --Log in Page
DEFINE('_LOGINERROR', '用户不存在或者您输入的密码错误<br>请重新输入');
// base_main.php
DEFINE('_MOSTRECENT', '最近 ');
DEFINE('_MOSTFREQUENT', '最常出现 ');
DEFINE('_ALERTS', ' 警告数:');
DEFINE('_ADDRESSES', ' 地址');
DEFINE('_SOURCEIP', 'Source IP'); //NEW
DEFINE('_DESTIP', 'Destination IP'); //NEW
DEFINE('_ANYPROTO', '任何协议');
DEFINE('_UNI', '单项');
DEFINE('_LISTING', '列表');
DEFINE('_TALERTS', '今日警告数: ');
DEFINE('_L24ALERTS', '最近24小时警告数: ');
DEFINE('_L72ALERTS', '最近72小时警告数: ');
DEFINE('_UNIALERTS', ' 单项警告数');
DEFINE('_LSOURCEPORTS', '最后来源端口: ');
DEFINE('_LDESTPORTS', '最后目标端口: ');
DEFINE('_FREGSOURCEP', '出现频率最高源端口: ');
DEFINE('_FREGDESTP', '出现频率最高目的端口: ');
DEFINE('_QUERIED', '查询自');
DEFINE('_USEALERTDB', 'Use Alert Database'); //NEW
DEFINE('_USEARCHIDB', 'Use Archive Database'); //NEW
DEFINE('_TRAFFICPROBPRO', 'Traffic Profile by Protocol'); //NEW
DEFINE('_DATABASE', '数据库:');
DEFINE('_SCHEMAV', '模式版本:');
DEFINE('_TIMEWIN', '时间间隔:');
DEFINE('_NOALERTSDETECT', '没检测到警告');
//base_auth.inc.php
DEFINE('_ADDEDSF', '添加成功');
DEFINE('_NOPWDCHANGE', '无法修改您的密码: ');
DEFINE('_NOUSER', '用户不存在!');
DEFINE('_OLDPWD', '输入的旧密码与记录不符!');
DEFINE('_PWDCANT', '无法修改您的密码: ');
DEFINE('_PWDDONE', '您的密码已经被修改!');
DEFINE('_ROLEEXIST', '角色已经存在');
DEFINE('_ROLEIDEXIST', '角色 ID 已经存在');
DEFINE('_ROLEADDED', '角色添加成功');
//base_roleadmin.php
DEFINE('_UPDATEROLE', 'Update Role'); //NEW
DEFINE('_ROLEADMIN', 'BASE 角色管理');
DEFINE('_FRMROLEID', '角色 ID:');
DEFINE('_FRMROLENAME', '角色名称:');
DEFINE('_FRMROLEDESC', '说明:');
//base_useradmin.php
DEFINE('_SUBMITQUERY', 'Submit Query'); //NEW
DEFINE('_UPDATEUSER', 'Update User'); //NEW
DEFINE('_USERADMIN', 'BASE 用户管理');
DEFINE('_FRMFULLNAME', '全名:');
DEFINE('_FRMROLE', '角色:');
DEFINE('_FRMUID', '用户 ID:');
//admin/index.php
DEFINE('_BASEADMIN', 'BASE 管理');
DEFINE('_BASEADMINTEXT', '请从左边选择一个选项.');
//base_action.inc.php
DEFINE('_NOACTION', '没有指定对警告的动作');
DEFINE('_INVALIDACT', ' 是一个不合法的动作');
DEFINE('_ERRNOAG', '当没有指定 AG 时不能增加警告');
DEFINE('_ERRNOEMAIL', '由于没有指定 email 
地址而无法通过邮件发送警告');
DEFINE('_ACTION', '动作');
DEFINE('_CONTEXT', '上下文');
DEFINE('_ADDAGID', '添加到 AG(通过 ID)');
DEFINE('_ADDAG', '添加新AG');
DEFINE('_ADDAGNAME', '添加到 AG (通过名称)');
DEFINE('_CREATEAG', '建立 AG (通过名称)');
DEFINE('_CLEARAG', '从 AG 清空');
DEFINE('_DELETEALERT', '删除警告');
DEFINE('_EMAILALERTSFULL', '通过邮件发送警告 (完全)');
DEFINE('_EMAILALERTSSUMM', '通过邮件发送警告 (摘要)');
DEFINE('_EMAILALERTSCSV', '通过邮件发送警告 (csv)');
DEFINE('_ARCHIVEALERTSCOPY', '把警告存档 (复制)');
DEFINE('_ARCHIVEALERTSMOVE', '把警告存档 (移动)');
DEFINE('_IGNORED', '忽略 ');
DEFINE('_DUPALERTS', ' 重复警告');
DEFINE('_ALERTSPARA', ' 警告');
DEFINE('_NOALERTSSELECT', '没有选择警告或者');
DEFINE('_NOTSUCCESSFUL', '没有成功');
DEFINE('_ERRUNKAGID', '指定的 AG ID 是未知的 (AG 可能不存在)');
DEFINE('_ERRREMOVEFAIL', '移除新 AG 失败');
DEFINE('_GENBASE', '由 BASE 生成');
DEFINE('_ERRNOEMAILEXP', '导出错误: 无法发送导出警告到');
DEFINE('_ERRNOEMAILPHP', '检查 PHP 里的电子邮件设置.');
DEFINE('_ERRDELALERT', '删除警告错误');
DEFINE('_ERRARCHIVE', '存档错误:');
DEFINE('_ERRMAILNORECP', '发送电子邮件错误: 没有指定接收方');
//base_cache.inc.php
DEFINE('_ADDED', '增加 ');
DEFINE('_HOSTNAMESDNS', ' 主机到 IP DNS 缓存');
DEFINE('_HOSTNAMESWHOIS', ' 主机到 Whois 缓存');
DEFINE('_ERRCACHENULL', '缓存错误: 找到空事件列?');
DEFINE('_ERRCACHEERROR', '事件缓存错误:');
DEFINE('_ERRCACHEUPDATE', '无法更新事件缓存');
DEFINE('_ALERTSCACHE', ' 条警告到警告缓存');
//base_db.inc.php
DEFINE('_ERRSQLTRACE', '无法打开 SQL 记录文件');
DEFINE('_ERRSQLCONNECT', '连接数据库错误 :');
DEFINE('_ERRSQLCONNECTINFO', '<P>检查 <I>base_conf.php</I> 
中的数据库连接变量
              <PRE>
               = $alert_dbname   : MySQL 中存储警告的数据库名
               = $alert_host     : 存放数据库的主机
               = $alert_port     : 数据库所用端口
               = $alert_user     : 访问数据库的用户名
               = $alert_password : 用户名对应的密码
              </PRE>
              <P>');
DEFINE('_ERRSQLPCONNECT', '(p)连接数据库错误:');
DEFINE('_ERRSQLDB', '数据库错误:');
DEFINE('_DBALCHECK', '检查数据库抽象库于');
DEFINE('_ERRSQLDBALLOAD1', '<P><B>加载数据库抽象库错误: </B> 从 
');
DEFINE('_ERRSQLDBALLOAD2', '<P>检查 DB 抽象库变量 
<CODE>$DBlib_path</CODE> 在 <CODE>base_conf.php</CODE>
            <P>
            下面的使用的数据库是ADODB, 可以从这里下载:
             <A 
HREF="http://adodb.sourceforge.net/">http://adodb.sourceforge.net/</A>');
DEFINE('_ERRSQLDBTYPE', '指定的数据库类型无效');
DEFINE('_ERRSQLDBTYPEINFO1', '变量 <CODE>\$DBtype</CODE> 在 
<CODE>base_conf.php</CODE> 被设置成无法认出的数据库类型 ');
DEFINE('_ERRSQLDBTYPEINFO2', '只支持以下数据库系统: <PRE>
                MySQL         : \'mysql\'
                PostgreSQL    : \'postgres\'
                MS SQL Server : \'mssql\'
                Oracle        : \'oci8\'
             </PRE>');
//base_log_error.inc.php
DEFINE('_ERRBASEFATAL', 'BASE 严重错误:');
//base_log_timing.inc.php
DEFINE('_LOADEDIN', '执行耗时');
DEFINE('_SECONDS', '秒');
//base_net.inc.php
DEFINE('_ERRRESOLVEADDRESS', '无法解析地址');
//base_output_query.inc.php
DEFINE('_SHORTJAN', 'Jan'); //NEW
DEFINE('_SHORTFEB', 'Feb'); //NEW
DEFINE('_SHORTMAR', 'Mar'); //NEW
DEFINE('_SHORTAPR', 'Apr'); //NEW
DEFINE('_SHORTMAY', 'May'); //NEW
DEFINE('_SHORTJUN', 'Jun'); //NEW
DEFINE('_SHORTJLY', 'Jly'); //NEW
DEFINE('_SHORTAUG', 'Aug'); //NEW
DEFINE('_SHORTSEP', 'Sep'); //NEW
DEFINE('_SHORTOCT', 'Oct'); //NEW
DEFINE('_SHORTNOV', 'Nov'); //NEW
DEFINE('_SHORTDEC', 'Dec'); //NEW
DEFINE('_DISPSIG', '{ signature }'); //NEW
DEFINE('_DISPANYCLASS', '{ any Classification }'); //NEW
DEFINE('_DISPANYPRIO', '{ any Priority }'); //NEW
DEFINE('_DISPANYSENSOR', '{ any Sensor }'); //NEW
DEFINE('_DISPADDRESS', '{ adress }'); //NEW
DEFINE('_DISPFIELD', '{ field }'); //NEW
DEFINE('_DISPPORT', '{ port }'); //NEW
DEFINE('_DISPENCODING', '{ encoding }'); //NEW
DEFINE('_DISPCONVERT2', '{ Convert To }'); //NEW
DEFINE('_DISPANYAG', '{ any Alert Group }'); //NEW
DEFINE('_DISPPAYLOAD', '{ payload }'); //NEW
DEFINE('_DISPFLAGS', '{ flags }'); //NEW
DEFINE('_SIGEXACTLY', 'exactly'); //NEW
DEFINE('_SIGROUGHLY', 'roughly'); //NEW
DEFINE('_SIGCLASS', 'Signature Classification'); //NEW
DEFINE('_SIGPRIO', 'Signature Priority'); //NEW
DEFINE('_SHORTSOURCE', 'Source'); //NEW
DEFINE('_SHORTDEST', 'Dest'); //NEW
DEFINE('_SHORTSOURCEORDEST', 'Src or Dest'); //NEW
DEFINE('_NOLAYER4', 'no layer4'); //NEW
DEFINE('_INPUTCRTENC', 'Input Criteria Encoding Type'); //NEW
DEFINE('_CONVERT2WS', 'Convert To (when searching)'); //NEW
DEFINE('_QUERYRESULTSHEADER', '查询结果输出头');
//base_signature.inc.php
DEFINE('_ERRSIGNAMEUNK', '未知的Sig名');
DEFINE('_ERRSIGPROIRITYUNK', '未知的Sig优先级');
DEFINE('_UNCLASS', '未分类');
//base_state_citems.inc.php
DEFINE('_DENCODED', '数据编码为');
DEFINE('_NODENCODED', '(没有数据转换，在数据库本地编码假定标准)');
//base_state_common.inc.php
DEFINE('_PHPERRORCSESSION', 'PHP 错误：检测到一个用户PHP会话. 
但是, BASE 没有被指名使用这些定制处理器.把 
<CODE>use_user_session=1</CODE>设置到  <CODE>base_conf.php</CODE>');
DEFINE('_PHPERRORCSESSIONCODE', 'PHP 错误: 配置了一个用户定制的 
PHP 
会话，但是<CODE>user_session_path</CODE>里提供的供应处理器代码不可用。');
DEFINE('_PHPERRORCSESSIONVAR', 'PHP 错误: 配置了一个用户定制的 
PHP 会话，但是在 BASE 
里没有制定这个处理器的工具。如果需要一个定制会话处理器，在<CODE>base_conf.php</CODE>里设置<CODE>user_session_path</CODE> 
变量.');
DEFINE('_DISPACTION',' {action}'); //NEW
DEFINE('_PHPSESSREG', '会话已注册');
//base_state_criteria.inc.php
DEFINE('_REMOVE', '移除');
DEFINE('_FROMCRIT', '从准则');
DEFINE('_ERRCRITELEM', '不可用的准则元素');
//base_state_query.inc.php
DEFINE('_VALIDCANNED', '可用的固定查询列表');
DEFINE('_DISPLAYING', '显示');
DEFINE('_DISPLAYINGTOTAL', '显示警告 %d-%d 总数 %s ');
DEFINE('_NOALERTS', '没有警告。');
DEFINE('_QUERYRESULTS', '查询结果');
DEFINE('_QUERYSTATE', '查询状态');
//base_ag_common.php
DEFINE('_ERRAGNAMESEARCH', '指定的 AG 名搜索不可用，请重试！');
DEFINE('_ERRAGNAMEEXIST', '指定的 AG 不存在。');
DEFINE('_SAVECHANGES', 'Save Changes'); //NEW
DEFINE('_CONFIRMDELETE', 'Confirm Delete'); //NEW
DEFINE('_CONFIRMCLEAR', 'Confirm Clear'); //NEW
DEFINE('_ERRAGIDSEARCH', '指定的 AG ID 搜索不可用。请重试！');
DEFINE('_ERRAGLOOKUP', '查找 AG ID 错误');
DEFINE('_ERRAGINSERT', '插入新 AG 错误');
//base_ag_main.php
DEFINE('_AGMAINTTITLE', '警告组(AG) 维护');
DEFINE('_ERRAGUPDATE', '更新 AG 错误');
DEFINE('_ERRAGPACKETLIST', '从 AG 删除包列表错误 :');
DEFINE('_ERRAGDELETE', '删除 AG 错误');
DEFINE('_AGDELETE', '删除成功');
DEFINE('_AGDELETEINFO', '信息已删除');
DEFINE('_ERRAGSEARCHINV', '输入的搜索准则不可用。请重试！');
DEFINE('_ERRAGSEARCHNOTFOUND', '通过该准则无法找到 AG。');
DEFINE('_NOALERTGOUPS', '没有警告组');
DEFINE('_NUMALERTS', '# 警告');
DEFINE('_ACTIONS', '操作');
DEFINE('_NOTASSIGN', '还未分配');
//base_common.php
DEFINE('_PORTSCAN', '端口扫描通信');
//base_db_common.php
DEFINE('_ERRDBINDEXCREATE', '无法为之创建索引');
DEFINE('_DBINDEXCREATE', '成功为之创建索引');
DEFINE('_ERRSNORTVER', '这可能是个旧版本。只支持 Snort 1.7-beta0 
或者更新的版本创建的警告数据库');
DEFINE('_ERRSNORTVER1', '基础数据库');
DEFINE('_ERRSNORTVER2', '显示不完全/不可用');
DEFINE('_ERRDBSTRUCT1', '数据库版本可用，但是BASE DB 结构');
DEFINE('_ERRDBSTRUCT2', '不是当前的。使用<A 
HREF="base_db_setup.php">安装页面</A>来配置和优化 DB.');
DEFINE('_CHARTTYPE', 'Chart Type:'); //NEW
DEFINE('_CHARTTYPES', '{ chart type }'); //NEW
DEFINE('_CHARTPERIOD', 'Chart Period:'); //NEW
DEFINE('_PERIODNO', 'no period'); //NEW
DEFINE('_PERIODWEEK', '7 (a week)'); //NEW
DEFINE('_PERIODDAY', '24 (whole day)'); //NEW
DEFINE('_PERIOD168', '168 (24x7)'); //NEW
DEFINE('_CHARTSIZE', 'Size: (width x height)'); //NEW
DEFINE('_PLOTMARGINS', 'Plot Margins: (left x right x top x bottom)'); //NEW
DEFINE('_PLOTTYPE', 'Plot type:'); //NEW
DEFINE('_TYPEBAR', 'bar'); //NEW
DEFINE('_TYPELINE', 'line'); //NEW
DEFINE('_TYPEPIE', 'pie'); //NEW
DEFINE('_CHARTHOUR', '{hora}'); //NEW
DEFINE('_CHARTDAY', '{dia}'); //NEW
DEFINE('_CHARTMONTH', '{mês}'); //NEW
DEFINE('_GRAPHALERTS', 'Graph Alerts'); //NEW
DEFINE('_AXISCONTROLS', 'X / Y AXIS CONTROLS'); //NEW
DEFINE('_ERRPHPERROR', 'PHP 错误');
DEFINE('_ERRPHPERROR1', '不兼容版本');
DEFINE('_ERRVERSION', '版本号');
DEFINE('_ERRPHPERROR2', '的PHP 太老。请升级到 4.0.4 
版本或更新。');
DEFINE('_ERRPHPMYSQLSUP', '<B>PHP 安装不完全</B>: 
<FONT>用来读取警告数据库的先决条件
               MySQL 支持没有安装到 PHP里。
               请重新编译 PHP 
添加必须的库(<CODE>--with-mysql</CODE>)</FONT>');
DEFINE('_ERRPHPPOSTGRESSUP', '<B>PHP 安装不完全</B>: 
<FONT>用来读取警告数据库的先决条件 PostgreSQL 支持
               没有安装到 PHP 里。
               请重新编译 PHP 
添加必须的库(<CODE>--with-pgsql</CODE>)</FONT>');
DEFINE('_ERRPHPMSSQLSUP', '<B>PHP 安装不完全</B>: 
<FONT>用来读取警告数据库的先决条件 MS SQL 服务支持
                   没有安装到 PHP 里。
                   请重新编译 PHP 
添加必须的库(<CODE>--enable-mssql</CODE>)</FONT>');
DEFINE('_ERRPHPORACLESUP', '<B>PHP 安装不完全</B>: 
<FONT>用来读取警告数据库的先决条件 Oracle 支持
                   没有安装到 PHP 里。
                   请重新编译 PHP 
添加必须的库(<CODE>--with-oci8</CODE>)</FONT>');
//base_graph_form.php
DEFINE('_CHARTTITLE', '图表标题:');
DEFINE('_CHRTTYPEHOUR', '时间 (小时) vs. 警告的数量');
DEFINE('_CHRTTYPEDAY', '时间 (天) vs. 警告的数量');
DEFINE('_CHRTTYPEWEEK', '时间 (周) vs. 警告的数量');
DEFINE('_CHRTTYPEMONTH', '时间 (月) vs. 警告的数量');
DEFINE('_CHRTTYPEYEAR', '时间 (年) vs. 警告的数量');
DEFINE('_CHRTTYPESRCIP', '源 IP 地址 vs. 警告的数量');
DEFINE('_CHRTTYPEDSTIP', '目的 IP 地址 vs. 警告的数量');
DEFINE('_CHRTTYPEDSTUDP', '目的 UDP 端口 vs. 警告的数量');
DEFINE('_CHRTTYPESRCUDP', '源 UDP 端口 vs. 警告的数量');
DEFINE('_CHRTTYPEDSTPORT', '目的 TCP 端口 vs. 警告的数量');
DEFINE('_CHRTTYPESRCPORT', '源 TCP 端口 vs. 警告的数量');
DEFINE('_CHRTTYPESIG', 'Sig. 分类 vs. 警告的数量');
DEFINE('_CHRTTYPESENSOR', '监测器 vs. 警告的数量');
DEFINE('_CHRTBEGIN', '图表开始:');
DEFINE('_CHRTEND', '图表结束:');
DEFINE('_CHRTDS', '数据源:');
DEFINE('_CHRTX', 'X 轴');
DEFINE('_CHRTY', 'Y 轴');
DEFINE('_CHRTMINTRESH', '最小临界值');
DEFINE('_CHRTROTAXISLABEL', '旋转轴标签(90 度)');
DEFINE('_CHRTSHOWX', '显示 X 轴分格线');
DEFINE('_CHRTDISPLABELX', '显示 X 轴标签每');
DEFINE('_CHRTDATAPOINTS', '数据点');
DEFINE('_CHRTYLOG', 'Y 轴对数');
DEFINE('_CHRTYGRID', '显示 Y 轴分格线');
DEFINE('_GRAPHALERTDATA', 'Graph Alert Data'); //NEW
//base_graph_main.php
DEFINE('_CHRTTITLE', 'BASE 图表');
DEFINE('_ERRCHRTNOTYPE', '没有指定图表类型');
DEFINE('_ERRNOAGSPEC', '没有指定 AG。使用所有警告。');
DEFINE('_CHRTDATAIMPORT', '开始导入数据');
DEFINE('_CHRTTIMEVNUMBER', '时间 vs. 警告的数量');
DEFINE('_CHRTTIME', '时间');
DEFINE('_CHRTALERTOCCUR', '警告事件');
DEFINE('_CHRTSIPNUMBER', '源 IP vs. 警告的数量');
DEFINE('_CHRTSIP', '源 IP 地址');
DEFINE('_CHRTDIPALERTS', '目的 IP vs. 警告的数量');
DEFINE('_CHRTDIP', '目的 IP 地址');
DEFINE('_CHRTUDPPORTNUMBER', 'UDP 端口 (目的) vs. 警告的数量');
DEFINE('_CHRTDUDPPORT', '目的 UDP 端口');
DEFINE('_CHRTSUDPPORTNUMBER', 'UDP 端口 (源) vs. 警告的数量');
DEFINE('_CHRTSUDPPORT', '源 UDP 端口');
DEFINE('_CHRTPORTDESTNUMBER', 'TCP 端口 (目的) vs. 警告的数量');
DEFINE('_CHRTPORTDEST', '目的 TCP 端口');
DEFINE('_CHRTPORTSRCNUMBER', 'TCP 端口 (源) vs. 警告的数量');
DEFINE('_CHRTPORTSRC', '源 TCP 端口');
DEFINE('_CHRTSIGNUMBER', '特征分类 vs. 警告的数量');
DEFINE('_CHRTCLASS', '分类');
DEFINE('_CHRTSENSORNUMBER', '监测器 vs. 警告的数量');
DEFINE('_CHRTHANDLEPERIOD', '处理时间 如果需要');
DEFINE('_CHRTDUMP', '转储数据 ... (只写每');
DEFINE('_CHRTDRAW', '绘制图形');
DEFINE('_ERRCHRTNODATAPOINTS', '没有可绘的数据点');
//base_maintenance.php
DEFINE('_MAINTTITLE', '维护');
DEFINE('_MNTPHP', 'PHP 安装:');
DEFINE('_MNTCLIENT', '客户端:');
DEFINE('_MNTSERVER', '服务器端:');
DEFINE('_MNTSERVERHW', '服务器硬件:');
DEFINE('_MNTPHPVER', 'PHP 版本:');
DEFINE('_MNTPHPAPI', 'PHP API:');
DEFINE('_MNTPHPLOGLVL', 'PHP 记录级别:');
DEFINE('_MNTPHPMODS', '已加载的模块:');
DEFINE('_MNTDBTYPE', 'DB 类型:');
DEFINE('_MNTDBALV', 'DB 抽象版本:');
DEFINE('_MNTDBALERTNAME', '警告数据库名:');
DEFINE('_MNTDBARCHNAME', '存档数据库名:');
DEFINE('_MNTAIC', '警告信息缓存:');
DEFINE('_MNTAICTE', '全部的事件:');
DEFINE('_MNTAICCE', '缓存的事件:');
DEFINE('_MNTIPAC', 'IP 地址缓存');
DEFINE('_MNTIPACUSIP', '单项源 IP:');
DEFINE('_MNTIPACDNSC', 'DNS 缓存:');
DEFINE('_MNTIPACWC', 'Whois 缓存:');
DEFINE('_MNTIPACUDIP', '单项目的 IP:');
//base_qry_alert.php
DEFINE('_QAINVPAIR', '不可用 (sid,cid) 对');
DEFINE('_QAALERTDELET', '警告已删除');
DEFINE('_QATRIGGERSIG', '触发特征');
DEFINE('_QANORMALD', 'Normal Display'); //NEW
DEFINE('_QAPLAIND', 'Plain Display'); //NEW
DEFINE('_QANOPAYLOAD', 'Fast logging used so payload was discarded'); //NEW
//base_qry_common.php
DEFINE('_QCSIG', '特征');
DEFINE('_QCIPADDR', 'IP 地址');
DEFINE('_QCIPFIELDS', 'IP 字段');
DEFINE('_QCTCPPORTS', 'TCP 端口');
DEFINE('_QCTCPFLAGS', 'TCP 标志');
DEFINE('_QCTCPFIELD', 'TCP 字段');
DEFINE('_QCUDPPORTS', 'UDP 端口');
DEFINE('_QCLAYER4CRIT', 'Layer 4 Criteria'); //NEW
DEFINE('_QCUDPFIELDS', 'UDP 字段');
DEFINE('_QCICMPFIELDS', 'ICMP 位');
DEFINE('_QCDATA', '数据');
DEFINE('_QCERRCRITWARN', '准则警告:');
DEFINE('_QCERRVALUE', '一个值为');
DEFINE('_QCERRFIELD', '一个字段为');
DEFINE('_QCERROPER', '一个操作为');
DEFINE('_QCERRDATETIME', '一个日期/时间值为');
DEFINE('_QFRMSORTNONE', 'none'); //NEW
DEFINE('_QCERRPAYLOAD', '一个封包内容为');
DEFINE('_QCERRIP', '一个 IP 地址为');
DEFINE('_QCERRIPTYPE', '一个 IP 地址类型为');
DEFINE('_QCERRSPECFIELD', ' 
已作为协议字段输入，但是没有指定特殊字段。');
DEFINE('_QCERRSPECVALUE', '已选择表明应该是一个准则，但是没有指定匹配什么值。');
DEFINE('_QCERRBOOLEAN', '输入的多协议字段准则之间没有布尔运算符(例如 
AND, OR)。');
DEFINE('_QCERRDATEVALUE', '被选择说明某些 日期/时间 
准则应该被匹配，但是没有指定值。');
DEFINE('_QCERRINVHOUR', '(无效的小时数) 
指定的时间没有输入日期准则。');
DEFINE('_QCERRDATECRIT', '被选择说明某些 日期/时间 
准则应该被匹配，但是没有指定值。');
DEFINE('_QCERROPERSELECT', '被输入但是没有选择运算符。');
DEFINE('_QCERRDATEBOOL', '输入的多个 日期/时间 
准则之间没有布尔运算符 (例如 AND, OR)。');
DEFINE('_QCERRPAYCRITOPER', '作为一个 payload 
准则字段而输入，但是没有指定操作符(例如 has, has 
not)。');
DEFINE('_QCERRPAYCRITVALUE', '被选择来表示 payload 
应该是一个准则，但是没有指定要匹配的值。');
DEFINE('_QCERRPAYBOOL', '在输入的多数据 payload 
准则之间没有布尔操作符 (例如 AND, OR)。');
DEFINE('_QCMETACRIT', 'Meta 准则');
DEFINE('_QCIPCRIT', 'IP 准则');
DEFINE('_QCPAYCRIT', 'Payload 准则');
DEFINE('_QCTCPCRIT', 'TCP 准则');
DEFINE('_QCUDPCRIT', 'UDP 准则');
DEFINE('_QCICMPCRIT', 'ICMP 准则');
DEFINE('_QCERRINVIPCRIT', '无效的 IP 地址准则');
DEFINE('_QCERRCRITADDRESSTYPE', '作为一个准则的值而输入，但是地址的类型 
(例如： 源, 目的) 没有被指定。');
DEFINE('_QCERRCRITIPADDRESSNONE', '表示一个 IP 
地址应该是一个准则，但是没有指定应该匹配的地址。');
DEFINE('_QCERRCRITIPADDRESSNONE1', '被选择 (at #');
DEFINE('_QCERRCRITIPIPBOOL', '输入的多 IP 地址准则在IP 
地址之间没有布尔操作符 (e.g. AND, OR) ');
//base_qry_form.php
DEFINE('_QFRMSORTORDER', '排列顺序');
DEFINE('_QFRMTIMEA', '时间戳 (递增)');
DEFINE('_QFRMTIMED', '时间戳 (递减)');
DEFINE('_QFRMSIG', '特征');
DEFINE('_QFRMSIP', '源 IP');
DEFINE('_QFRMDIP', '目标 IP');
//base_qry_sqlcalls.php
DEFINE('_QSCSUMM', '摘要统计');
DEFINE('_QSCTIMEPROF', '时间配置文件');
DEFINE('_QSCOFALERTS', '的警告');
DEFINE('_PSTOTALHOSTS', 'Total Hosts Scanned'); //NEW
DEFINE('_PSDETECTAMONG', '%d unique alerts detected among %d alerts on %s'); //NEW
DEFINE('_PSALLALERTSAS', 'all alerts with %s/%s as'); //NEW
DEFINE('_PSSHOW', 'show'); //NEW
DEFINE('_PSEXTERNAL', 'external'); //NEW
//base_stat_alerts.php
DEFINE('_ALERTTITLE', '警告列表');
//base_stat_common.php
DEFINE('_SCCATEGORIES', '分类:');
DEFINE('_SCSENSORTOTAL', '监测器/总共:');
DEFINE('_SCTOTALNUMALERTS', '警告总数量:');
DEFINE('_SCSRCIP', '源 IP 地址:');
DEFINE('_SCDSTIP', '目标 IP 地址:');
DEFINE('_SCUNILINKS', '单项 IP 连接');
DEFINE('_SCSRCPORTS', '源端口: ');
DEFINE('_SCDSTPORTS', '目标端口: ');
DEFINE('_SCSENSORS', '探测器');
DEFINE('_SCCLASS', '分类');
DEFINE('_SCUNIADDRESS', '单项地址: ');
DEFINE('_OCCURRENCES', 'Occurrences'); //NEW
DEFINE('_SCSOURCE', '源');
DEFINE('_SCDEST', '目标');
DEFINE('_SCPORT', '端口');
//base_stat_ipaddr.php
DEFINE('_PSEVENTERR', '端口扫描事件错误: ');
DEFINE('_PSEVENTERRNOFILE', '没有在 \$portscan_file 
变量里指定文件.');
DEFINE('_PSEVENTERROPENFILE', '无法打开端口扫描事件文件');
DEFINE('_PSDATETIME', '日期/时间');
DEFINE('_PSSRCIP', '源 IP');
DEFINE('_PSDSTIP', '目的 IP');
DEFINE('_BSTPROFILEBY', 'Profile by'); //NEW
DEFINE('_TIMEON', 'on'); //NEW
DEFINE('_TIMEBETWEEN', 'between'); //NEW
DEFINE('_PROFILEALERT', 'Profile Alert'); //NEW
DEFINE('_PSSRCPORT', '源 端口');
DEFINE('_PSDSTPORT', '目的 端口');
DEFINE('_PSTCPFLAGS', 'TCP 标志');
DEFINE('_PSTOTALOCC', '总共<BR> 发生');
DEFINE('_PSNUMSENSORS', '监测器数量');
DEFINE('_PSFIRSTOCC', '首次<BR> 发生');
DEFINE('_PSLASTOCC', '最后<BR> 发生');
DEFINE('_PSUNIALERTS', '单项警告');
DEFINE('_PSPORTSCANEVE', '端口扫描事件');
DEFINE('_PSREGWHOIS', '注册查询 (whois) 在');
DEFINE('_PSNODNS', '没有 DNS 解析企图');
DEFINE('_PSNUMSENSORSBR', '多少个 <BR>监测器');
DEFINE('_PSOCCASSRC', '发生频率 <BR>按源统计');
DEFINE('_PSOCCASDST', '发生频率 <BR>按目的统计');
DEFINE('_PSWHOISINFO', 'Whois 信息');
//base_stat_iplink.php
DEFINE('_SIPLTITLE', 'IP 连接数');
DEFINE('_SIPLSOURCEFGDN', '源域名');
DEFINE('_SIPLDESTFGDN', '目标域名');
DEFINE('_SIPLDIRECTION', '方向');
DEFINE('_SIPLPROTO', '协议');
DEFINE('_SIPLUNIDSTPORTS', '单项目标端口');
DEFINE('_SIPLUNIEVENTS', '单项事件');
DEFINE('_SIPLTOTALEVENTS', '总事件');
//base_stat_ports.php
DEFINE('_UNIQ', '单项');
DEFINE('_DSTPS', '目标端口');
DEFINE('_SRCPS', '源端口');
//base_stat_sensor.php
DEFINE('SPSENSORLIST', '监测器列表');
//base_stat_time.php
DEFINE('_BSTTITLE', '警告的事件配置文件');
DEFINE('_BSTTIMECRIT', '时间准则');
DEFINE('_BSTERRPROFILECRIT', '<FONT><B>没有指定配置文件准则!</B>  
点击 "小时", "日", 或 
"月"来选择统计聚合的间隔粒度。</FONT>');
DEFINE('_BSTERRTIMETYPE', '<FONT><B>要传送的时间参数的类型没有指定!</B> 
  选择 "on", 来指定一个单点日期, 或者选择 "between" 
来指定一个时间间隔。</FONT>');
DEFINE('_BSTERRNOYEAR', '<FONT><B>没有设置"年"参数!</B></FONT>');
DEFINE('_BSTERRNOMONTH', '<FONT><B>没有设置"月"参数!</B></FONT>');
DEFINE('_BSTERRNODAY', '<FONT><B>没有设置"日"参数!</B></FONT>');
//base_stat_uaddr.php
DEFINE('_UNISADD', '单项源地址');
DEFINE('_SUASRCIP', '源 IP 地址');
DEFINE('_SUAERRCRITADDUNK', '准则错误: 位置地址类型 -- 
假定目标地址');
DEFINE('_UNIDADD', '单词目标地址');
DEFINE('_SUADSTIP', '目标 IP 地址');
DEFINE('_SUAUNIALERTS', '单项&nbsp;警告');
DEFINE('_SUASRCADD', '源&nbsp;地址');
DEFINE('_SUADSTADD', '目标&nbsp;地址');
//base_user.php
DEFINE('_BASEUSERTITLE', 'BASE 用户参数设置');
DEFINE('_BASEUSERERRPWD', '您的密码不能为空或者两次密码不相同!');
DEFINE('_BASEUSEROLDPWD', '旧密码:');
DEFINE('_BASEUSERNEWPWD', '新密码:');
DEFINE('_BASEUSERNEWPWDAGAIN', '再次确认新密码:');
DEFINE('_LOGOUT', '注销');
?>

