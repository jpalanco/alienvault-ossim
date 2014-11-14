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
DEFINE('_LOCALESTR3', '繁體中文'); //NEW
DEFINE('_STRFTIMEFORMAT', '%a %B %d, %Y %H:%M:%S'); //NEW - see strftime() sintax
//common phrases
DEFINE('_CHARSET', 'UTF-8');
DEFINE('_TITLE', '安全基本分析引擎(BASE) ' . $BASE_installID);
DEFINE('_FRMLOGIN', '使用者登入:');
DEFINE('_FRMPWD', '密碼:');
DEFINE('_SOURCE', '來源');
DEFINE('_SOURCENAME', '來源名稱');
DEFINE('_DEST', '目地');
DEFINE('_DESTNAME', '目地名稱');
DEFINE('_SORD', '來源或目地');
DEFINE('_EDIT', '編輯');
DEFINE('_DELETE', '刪除');
DEFINE('_ID', '識別碼');
DEFINE('_NAME', '名稱');
DEFINE('_INTERFACE', '介面');
DEFINE('_FILTER', '過濾器');
DEFINE('_DESC', '說明');
DEFINE('_LOGIN', '登入');
DEFINE('_ROLEID', '角色 ID');
DEFINE('_ENABLED', '啟用');
DEFINE('_SUCCESS', '已成功 ');
DEFINE('_SENSOR', '偵測器');
DEFINE('_SENSORS', '偵測器s'); //NEW
DEFINE('_SIGNATURE', '特徵');
DEFINE('_TIMESTAMP', '時間戳記');
DEFINE('_NBSOURCEADDR', '來源&nbsp;位址');
DEFINE('_NBDESTADDR', '目地&nbsp;位址');
DEFINE('_NBLAYER4', '通訊&nbsp;4&nbsp;層級');
DEFINE('_PRIORITY', '優先等級');
DEFINE('_EVENTTYPE', '事件型態');
DEFINE('_JANUARY', '一月');
DEFINE('_FEBRUARY', '二月');
DEFINE('_MARCH', '三月');
DEFINE('_APRIL', '四月');
DEFINE('_MAY', '五月');
DEFINE('_JUNE', '六月');
DEFINE('_JULY', '七月');
DEFINE('_AUGUST', '八月');
DEFINE('_SEPTEMBER', '九月');
DEFINE('_OCTOBER', '十月');
DEFINE('_NOVEMBER', '十一月');
DEFINE('_DECEMBER', '十二月');
DEFINE('_LAST', '最後');
DEFINE('_FIRST', '最早'); //NEW
DEFINE('_TOTAL', '總數'); //NEW
DEFINE('_ALERT', '警告數');
DEFINE('_ADDRESS', '位址');
DEFINE('_UNKNOWN', '未知');
DEFINE('_AND', 'AND'); //NEW
DEFINE('_OR', 'OR'); //NEW
DEFINE('_IS', 'is'); //NEW
DEFINE('_ON', 'on'); //NEW
DEFINE('_IN', 'in'); //NEW
DEFINE('_ANY', 'any'); //NEW
DEFINE('_NONE', '無'); //NEW
DEFINE('_HOUR', ' 時'); //NEW
DEFINE('_DAY', ' 日'); //NEW
DEFINE('_MONTH', ' 月'); //NEW
DEFINE('_YEAR', ' 年'); //NEW
DEFINE('_ALERTGROUP', '警告群組'); //NEW
DEFINE('_ALERTTIME', '警告時間'); //NEW
DEFINE('_CONTAINS', '包含'); //NEW
DEFINE('_DOESNTCONTAIN', '不包含'); //NEW
DEFINE('_SOURCEPORT', '來源通訊埠'); //NEW
DEFINE('_DESTPORT', '目地通訊埠'); //NEW
DEFINE('_HAS', '有'); //NEW
DEFINE('_HASNOT', '不具有'); //NEW
DEFINE('_PORT', '通訊埠'); //NEW
DEFINE('_FLAGS', '旗標'); //NEW
DEFINE('_MISC', '其他'); //NEW
DEFINE('_BACK', '返回'); //NEW
DEFINE('_DISPYEAR', '{ 年 }'); //NEW
DEFINE('_DISPMONTH', '{ 月 }'); //NEW
DEFINE('_DISPHOUR', '{ 時 }'); //NEW
DEFINE('_DISPDAY', '{ 日 }'); //NEW
DEFINE('_DISPTIME', '{ 時間 }'); //NEW
DEFINE('_ADDADDRESS', '及位址'); //NEW
DEFINE('_ADDIPFIELD', '及 IP 欄位'); //NEW
DEFINE('_ADDTIME', '增加時間'); //NEW
DEFINE('_ADDTCPPORT', '增加 TCP 通訊埠'); //NEW
DEFINE('_ADDTCPFIELD', '增加 TCP 攔位'); //NEW
DEFINE('_ADDUDPPORT', '增加 UDP 通訊埠'); //NEW
DEFINE('_ADDUDPFIELD', '增加 UDP 攔位'); //NEW
DEFINE('_ADDICMPFIELD', '增加 ICMP 攔位'); //NEW
DEFINE('_ADDPAYLOAD', '增加 封包內容'); //NEW
DEFINE('_MOSTFREQALERTS', '最常發生的警告數'); //NEW
DEFINE('_MOSTFREQPORTS', '最常發生的通訊埠'); //NEW
DEFINE('_MOSTFREQADDRS', '最常發生的 IP 位址'); //NEW
DEFINE('_LASTALERTS', '最後的警告'); //NEW
DEFINE('_LASTPORTS', '最後的通訊埠'); //NEW
DEFINE('_LASTTCP', '最後的 TCP 警告'); //NEW
DEFINE('_LASTUDP', '最後的 UDP 警告'); //NEW
DEFINE('_LASTICMP', '最後的 ICMP 警告'); //NEW
DEFINE('_QUERYDB', '查詢 DB'); //NEW
DEFINE('_QUERYDBP', '查詢+DB'); //NEW - Equals to _QUERYDB where spaces are '+'s.
//Should be something like: DEFINE('_QUERYDBP',str_replace(" ", "+", _QUERYDB));
DEFINE('_SELECTED', '所選定的'); //NEW
DEFINE('_ALLONSCREEN', '全部在螢幕上的'); //NEW
DEFINE('_ENTIREQUERY', '進入查詢'); //NEW
DEFINE('_OPTIONS', '選項'); //NEW
DEFINE('_LENGTH', '長度'); //NEW
DEFINE('_CODE', '碼'); //NEW
DEFINE('_DATA', '資料'); //NEW
DEFINE('_TYPE', '型態'); //NEW
DEFINE('_NEXT', '下一個'); //NEW
DEFINE('_PREVIOUS', '前一個'); //NEW
//Menu items
DEFINE('_HOME', '首頁');
DEFINE('_SEARCH', '查詢');
DEFINE('_AGMAINT', '警告群組維護');
DEFINE('_USERPREF', '使用者參數設定');
DEFINE('_CACHE', '快取 & 狀態');
DEFINE('_ADMIN', '管理');
DEFINE('_GALERTD', '繪出警告資料');
DEFINE('_GALERTDT', '繪出警告偵測時間');
DEFINE('_USERMAN', '使用者管理');
DEFINE('_LISTU', '列出使用者');
DEFINE('_CREATEU', '建立使用者');
DEFINE('_ROLEMAN', '角色管理');
DEFINE('_LISTR', '列出角色');
DEFINE('_CREATER', '建立角色');
DEFINE('_LISTALL', '列出全部');
DEFINE('_CREATE', '建立');
DEFINE('_VIEW', '顯示');
DEFINE('_CLEAR', '清除');
DEFINE('_LISTGROUPS', ' 列出群組');
DEFINE('_CREATEGROUPS', ' 建立群組');
DEFINE('_VIEWGROUPS', ' 顯示群組');
DEFINE('_EDITGROUPS', ' 編輯群組');
DEFINE('_DELETEGROUPS', ' 刪除群組');
DEFINE('_CLEARGROUPS', ' 清除群組');
DEFINE('_CHNGPWD', ' 變更密碼');
DEFINE('_DISPLAYU', ' 顯示使用者');
//base_footer.php
DEFINE('_FOOTER', '(by <A class="largemenuitem" href="mailto:base@secureideas.net">Kevin Johnson</A> and the <A class="largemenuitem" href="http://sourceforge.net/project/memberlist.php?group_id=103348">BASE Project Team</A><BR>Built on ACID by Roman Danyliw 中文 <a href="mailto:js547441@ms15.hinet.net">Johnson Chiang</a>  )');
//index.php --Log in Page
DEFINE('_LOGINERROR', '使用者不存在或您的密碼不正確!<br>請再試一次');
// base_main.php
DEFINE('_MOSTRECENT', '最近 ');
DEFINE('_MOSTFREQUENT', '最常出現 ');
DEFINE('_ALERTS', ' 警告數:');
DEFINE('_ADDRESSES', ' 位址');
DEFINE('_ANYPROTO', '任何通訊協定');
DEFINE('_UNI', '單一');
DEFINE('_LISTING', '列表');
DEFINE('_TALERTS', '今天的警告數: ');
DEFINE('_SOURCEIP', '來源 IP'); //NEW
DEFINE('_DESTIP', '目地 IP'); //NEW
DEFINE('_L24ALERTS', '最近 24 小時警告數: ');
DEFINE('_L72ALERTS', '最近 72 小時警告數: ');
DEFINE('_UNIALERTS', ' 單項警告數');
DEFINE('_LSOURCEPORTS', '最近來源通訊埠數: ');
DEFINE('_LDESTPORTS', '最近目地通訊埠數: ');
DEFINE('_FREGSOURCEP', '最常出現來源通訊埠數: ');
DEFINE('_FREGDESTP', '最常出現目地通訊埠數: ');
DEFINE('_QUERIED', '查詢自');
DEFINE('_DATABASE', '資料庫:');
DEFINE('_SCHEMAV', 'Schema 版本:');
DEFINE('_TIMEWIN', '時間間隔:');
DEFINE('_NOALERTSDETECT', '沒有警告被檢測出來');
DEFINE('_USEALERTDB', '使用警告資料庫'); //NEW
DEFINE('_USEARCHIDB', '使用歸檔資料庫'); //NEW
DEFINE('_TRAFFICPROBPRO', '以通訊協定的傳輸概況'); //NEW
//base_auth.inc.php
DEFINE('_ADDEDSF', '新增成功 ');
DEFINE('_NOPWDCHANGE', '無法變更您的密碼: ');
DEFINE('_NOUSER', '使用者不存在!');
DEFINE('_OLDPWD', '輸入的舊密碼與記錄不相符!');
DEFINE('_PWDCANT', '無法變更您的密碼: ');
DEFINE('_PWDDONE', '您的密碼已經變更!');
DEFINE('_ROLEEXIST', '角色已經存在');
DEFINE('_ROLEIDEXIST', '角色識別碼已經存在');
DEFINE('_ROLEADDED', '角色新增成功 ');
//base_roleadmin.php
DEFINE('_ROLEADMIN', '基本角色管理');
DEFINE('_FRMROLEID', '角色識別碼:');
DEFINE('_FRMROLENAME', '角色名稱:');
DEFINE('_FRMROLEDESC', '說明:');
DEFINE('_UPDATEROLE', ' 更新角色'); //NEW
//base_useradmin.php
DEFINE('_USERADMIN', '基本使用者管理');
DEFINE('_FRMFULLNAME', '全名:');
DEFINE('_FRMROLE', '角色:');
DEFINE('_FRMUID', '使用者識別碼:');
DEFINE('_SUBMITQUERY', ' 確認送出查詢'); //NEW
DEFINE('_UPDATEUSER', ' 更新使用者'); //NEW
//admin/index.php
DEFINE('_BASEADMIN', '基本管理');
DEFINE('_BASEADMINTEXT', '請自左方選單選擇.');
//base_action.inc.php
DEFINE('_NOACTION', '在警告數中沒有動作被指定');
DEFINE('_INVALIDACT', ' 是一個不合法的動作');
DEFINE('_ERRNOAG', '當沒有指定AG時不能增加警告數');
DEFINE('_ERRNOEMAIL', '當電子郵件位址沒有指定時不能自郵件傳送警告');
DEFINE('_ACTION', '動作');
DEFINE('_CONTEXT', '內容');
DEFINE('_ADDAGID', '增加 到 警告群組 (以識別碼)');
DEFINE('_ADDAG', '新增-警告群組');
DEFINE('_ADDAGNAME', '新增 到 警告群組 (以名稱)');
DEFINE('_CREATEAG', '建立 警告群組 (以名稱)');
DEFINE('_CLEARAG', '自警告群組清除');
DEFINE('_DELETEALERT', '刪除警告群組數');
DEFINE('_EMAILALERTSFULL', '電子郵件警告 (全部)');
DEFINE('_EMAILALERTSSUMM', '電子郵件警告 (摘要)');
DEFINE('_EMAILALERTSCSV', '電子郵件警告 (csv)');
DEFINE('_ARCHIVEALERTSCOPY', '封存警告 (複製)');
DEFINE('_ARCHIVEALERTSMOVE', '封存警告 (搬移)');
DEFINE('_IGNORED', '已忽略 ');
DEFINE('_DUPALERTS', ' 警告重複');
DEFINE('_ALERTSPARA', ' 警告數');
DEFINE('_NOALERTSSELECT', '沒有警報被選擇或');
DEFINE('_NOTSUCCESSFUL', '沒有成功 ');
DEFINE('_ERRUNKAGID', '未知警告群組識別碼被指定 (警告群組可能不存在)');
DEFINE('_ERRREMOVEFAIL', '移動到新的警告群組失敗');
DEFINE('_GENBASE', '由 BASE 所建立');
DEFINE('_ERRNOEMAILEXP', '匯出錯誤: 無法傳送匯出警告到');
DEFINE('_ERRNOEMAILPHP', '檢查PHP中的電子郵件設定.');
DEFINE('_ERRDELALERT', '刪除警告發生錯誤');
DEFINE('_ERRARCHIVE', '封存錯誤:');
DEFINE('_ERRMAILNORECP', '電子郵件錯誤: 沒有指定收件人');
//base_cache.inc.php
DEFINE('_ADDED', '新增 ');
DEFINE('_HOSTNAMESDNS', ' 主機名稱對應 IP DNS 快取暫存');
DEFINE('_HOSTNAMESWHOIS', ' 主機名稱對應 Whois 快取暫存');
DEFINE('_ERRCACHENULL', '快取暫存發生錯誤: 找到無效事件列?');
DEFINE('_ERRCACHEERROR', '事件快取暫存出現錯誤:');
DEFINE('_ERRCACHEUPDATE', '無法更新快取事件');
DEFINE('_ALERTSCACHE', ' 警告至快取暫存');
//base_db.inc.php
DEFINE('_ERRSQLTRACE', '無法開起 SQL 追蹤檔案');
DEFINE('_ERRSQLCONNECT', '連結到資料庫發生錯誤:');
DEFINE('_ERRSQLCONNECTINFO', '<P>檢察資料庫連線參數 <I>base_conf.php</I>
              <PRE>
               = $alert_dbname   : MySQL 警告資料所要儲存的資料庫名稱 
               = $alert_host     : 要儲存資料庫的主機
               = $alert_port     : 要儲存資料庫使用的通訊埠
               = $alert_user     : 進入資料庫的使用者帳號
               = $alert_password : 使用者密碼
              </PRE>
              <P>');
DEFINE('_ERRSQLPCONNECT', '(p)connecting 到資料庫發生錯誤:');
DEFINE('_ERRSQLDB', '資料庫發生錯誤:');
DEFINE('_DBALCHECK', '檢查資料庫中的提取函式庫');
DEFINE('_ERRSQLDBALLOAD1', '<P><B>載入提取的函式庫中發生錯誤: </B> from ');
DEFINE('_ERRSQLDBALLOAD2', '<P>檢查資料庫中函式庫可用性 <CODE>$DBlib_path</CODE> in <CODE>base_conf.php</CODE>
            <P>
            底層資料庫連結函式數使用 ADODB, 該程式可下載自
            <A HREF="http://adodb.sourceforge.net/">http://adodb.sourceforge.net/</A>');
DEFINE('_ERRSQLDBTYPE', '指定了不合法的資料庫型態');
DEFINE('_ERRSQLDBTYPEINFO1', '變數 <CODE>\$DBtype</CODE> 在 <CODE>base_conf.php</CODE> 被設成不認識的資料庫型態 ');
DEFINE('_ERRSQLDBTYPEINFO2', '只有下列的資料庫被支援: <PRE>
                MySQL         : \'mysql\'
                PostgreSQL    : \'postgres\'
                MS SQL Server : \'mssql\'
                Oracle        : \'oci8\'
             </PRE>');
//base_log_error.inc.php
DEFINE('_ERRBASEFATAL', 'BASE 嚴重錯誤:');
//base_log_timing.inc.php
DEFINE('_LOADEDIN', '載入自');
DEFINE('_SECONDS', '秒');
//base_net.inc.php
DEFINE('_ERRRESOLVEADDRESS', '無法解析位址');
//base_output_query.inc.php
DEFINE('_QUERYRESULTSHEADER', '查詢結果輸出標題');
//base_signature.inc.php
DEFINE('_ERRSIGNAMEUNK', 'SigName 未知');
DEFINE('_ERRSIGPROIRITYUNK', 'SigPriority 未知');
DEFINE('_UNCLASS', '未分類');
//base_state_citems.inc.php
DEFINE('_DENCODED', '資料解碼成');
DEFINE('_NODENCODED', '(沒有資料被轉換, 表示原本的從資料庫解碼)');
DEFINE('_SHORTJAN', '一月'); //NEW
DEFINE('_SHORTFEB', '二月'); //NEW
DEFINE('_SHORTMAR', '三月'); //NEW
DEFINE('_SHORTAPR', '四月'); //NEW
DEFINE('_SHORTMAY', '五月'); //NEW
DEFINE('_SHORTJUN', '六月'); //NEW
DEFINE('_SHORTJLY', '七月'); //NEW
DEFINE('_SHORTAUG', '八月'); //NEW
DEFINE('_SHORTSEP', '九月'); //NEW
DEFINE('_SHORTOCT', '十月'); //NEW
DEFINE('_SHORTNOV', '十一月'); //NEW
DEFINE('_SHORTDEC', '十二月'); //NEW
DEFINE('_DISPSIG', '{ 特徵 }'); //NEW
DEFINE('_DISPANYCLASS', '{ 任何分類 }'); //NEW
DEFINE('_DISPANYPRIO', '{ 任何優先等級 }'); //NEW
DEFINE('_DISPANYSENSOR', '{ 任何偵測器 }'); //NEW
DEFINE('_DISPADDRESS', '{ 位址 }'); //NEW
DEFINE('_DISPFIELD', '{ 欄位 }'); //NEW
DEFINE('_DISPPORT', '{ 通訊埠 }'); //NEW
DEFINE('_DISPENCODING', '{ 解碼 }'); //NEW
DEFINE('_DISPCONVERT2', '{ 轉換成 }'); //NEW
DEFINE('_DISPANYAG', '{ 任何警告群組 }'); //NEW
DEFINE('_DISPPAYLOAD', '{ 封包內容 }'); //NEW
DEFINE('_DISPFLAGS', '{ 旗標 }'); //NEW
DEFINE('_SIGEXACTLY', '精確的'); //NEW
DEFINE('_SIGROUGHLY', '模糊的'); //NEW
DEFINE('_SIGCLASS', '特徵分類'); //NEW
DEFINE('_SIGPRIO', '特徵優先等級'); //NEW
DEFINE('_SHORTSOURCE', '來源'); //NEW
DEFINE('_SHORTDEST', '目的'); //NEW
DEFINE('_SHORTSOURCEORDEST', '來源或目的'); //NEW
DEFINE('_NOLAYER4', '無 layer4'); //NEW
DEFINE('_INPUTCRTENC', '輸入解碼型態規則'); //NEW
DEFINE('_CONVERT2WS', '轉換成 (當查詢時)'); //NEW
//base_state_common.inc.php
DEFINE('_PHPERRORCSESSION', 'PHP 錯誤: 被檢測出客戶 (使用者) PHP 已經連線. 無論如何, BASE 不設定明確的使用這個客戶處理.  設定值 <CODE>use_user_session=1</CODE> in <CODE>base_conf.php</CODE>');
DEFINE('_PHPERRORCSESSIONCODE', 'PHP 錯誤: 客戶 (使用者) PHP 連線維繫已被設定, 但是供應維繫碼定義成 <CODE>user_session_path</CODE> 是不合法.');
DEFINE('_PHPERRORCSESSIONVAR', 'PHP 錯誤: 客戶 (使用者) PHP 連線維繫已被設定, 但是建置這個維繫沒有在 BASE 定義.  如果客戶連線維繫是需要的, 設定 <CODE>user_session_path</CODE> 變數在設定檔 <CODE>base_conf.php</CODE>.');
DEFINE('_PHPSESSREG', '連線已登錄');
//base_state_criteria.inc.php
DEFINE('_REMOVE', '移除中');
DEFINE('_FROMCRIT', '從標準');
DEFINE('_ERRCRITELEM', '不合法標準元件');
//base_state_query.inc.php
DEFINE('_VALIDCANNED', '合法錄製查詢列表');
DEFINE('_DISPLAYING', '顯示中');
DEFINE('_DISPLAYINGTOTAL', '顯示警告數 %d-%d 中 %s 全數');
DEFINE('_NOALERTS', '找不到警告.');
DEFINE('_QUERYRESULTS', '查詢結果');
DEFINE('_QUERYSTATE', '查詢狀態');
DEFINE('_DISPACTION', '{ 動作 }'); //NEW
//base_ag_common.php
DEFINE('_ERRAGNAMESEARCH', '指定查詢的警告群組名稱不合法.  請重試!');
DEFINE('_ERRAGNAMEEXIST', '指定的警告群組不存在.');
DEFINE('_ERRAGIDSEARCH', '指定查詢的警告群組識別碼不合法.  請重試!');
DEFINE('_ERRAGLOOKUP', '查詢警告群組識別碼時發生錯誤');
DEFINE('_ERRAGINSERT', '插入新警告群組時發生錯誤');
//base_ag_main.php
DEFINE('_AGMAINTTITLE', '警告群組 (AG) 維護');
DEFINE('_ERRAGUPDATE', '更新警告群組時發生錯誤');
DEFINE('_ERRAGPACKETLIST', '從警告群組中刪除封包列表時發生錯誤:');
DEFINE('_ERRAGDELETE', '刪除警告群組時發生錯誤');
DEFINE('_AGDELETE', '已成功刪除');
DEFINE('_AGDELETEINFO', '訊息已刪除');
DEFINE('_ERRAGSEARCHINV', '輸入的查詢標準是不合法.  請重試!');
DEFINE('_ERRAGSEARCHNOTFOUND', '用該標準在警告群組中找不到.');
DEFINE('_NOALERTGOUPS', '這裏沒有警告群組');
DEFINE('_NUMALERTS', '# 警告數');
DEFINE('_ACTIONS', '動作');
DEFINE('_NOTASSIGN', '還未指定');
DEFINE('_SAVECHANGES', ' 儲存變更'); //NEW
DEFINE('_CONFIRMDELETE', ' 確認刪除'); //NEW
DEFINE('_CONFIRMCLEAR', ' 確認清除'); //NEW
//base_common.php
DEFINE('_PORTSCAN', '通訊埠掃描傳輸情形');
//base_db_common.php
DEFINE('_ERRDBINDEXCREATE', '無法建立 INDEX 索引自');
DEFINE('_DBINDEXCREATE', '成功建立資料 INDEX 索引自');
DEFINE('_ERRSNORTVER', '這可是是較舊版本.  只有警告資料庫建立於 Snort 1.7-beta0 或以後才有支援');
DEFINE('_ERRSNORTVER1', '底層資料庫');
DEFINE('_ERRSNORTVER2', '出現不相容/不合法');
DEFINE('_ERRDBSTRUCT1', '資料庫版本與 BASE 資料庫結構不合法');
DEFINE('_ERRDBSTRUCT2', '沒有出現. 使用 <A HREF="base_db_setup.php">Setup page</A> 來調整及設定資料庫.');
DEFINE('_ERRPHPERROR', 'PHP 錯誤');
DEFINE('_ERRPHPERROR1', '不相容版本');
DEFINE('_ERRVERSION', '版本');
DEFINE('_ERRPHPERROR2', ' PHP 版本太老舊.  請昇級至 4.0.4 或以後版本');
DEFINE('_ERRPHPMYSQLSUP', '<B>PHP 建置不相容</B>: <FONT>預查詢的 MySQL 資料庫資援需要
               去讀取警告資料庫沒有經由所建立的 PHP去讀取.
               請重新編譯 PHP 和所需的程式庫 (<CODE>--with-mysql</CODE>)</FONT>');
DEFINE('_ERRPHPPOSTGRESSUP', '<B>PHP 建置不相容</B>: <FONT>預查詢的 PostgreSQL 支援需要
               去讀取警告資料庫沒有經由所建立的 PHP 去讀取.
               請重新編譯 PHP 和所需的程式庫 (<CODE>--with-pgsql</CODE>)</FONT>');
DEFINE('_ERRPHPMSSQLSUP', '<B>PHP 建置不相容</B>: <FONT>預查詢的 MS SQL Server 資料庫支援需要
                   去讀取警告資料庫沒有經由所建立的 PHP 去讀取.
                   P請重新編譯 PHP 和所需的程式庫  (<CODE>--enable-mssql</CODE>)</FONT>');
DEFINE('_ERRPHPORACLESUP', '<B>PHP 未完整建立</B>: <FONT>在預設情況要支援 Oracle 需要重新編譯程式碼 
                   警告資料庫無法被內建 PHP 讀取.  
                   請重新編譯 PHP 需包含 Oracle 程式庫 (<CODE>--with-oci8</CODE>)</FONT>');
//base_graph_form.php
DEFINE('_CHARTTITLE', '圖形標題:');
DEFINE('_CHARTTYPE', '圖形型態:'); //NEW
DEFINE('_CHARTTYPES', '{ 圖表形式 }'); //NEW
DEFINE('_CHARTPERIOD', '圖表週期:'); //NEW
DEFINE('_PERIODNO', '沒有週期'); //NEW
DEFINE('_PERIODWEEK', '7 (一週)'); //NEW
DEFINE('_PERIODDAY', '24 (整天)'); //NEW
DEFINE('_PERIOD168', '168 (24x7)'); //NEW
DEFINE('_CHARTSIZE', '尺寸: (寬 x 高)'); //NEW
DEFINE('_PLOTMARGINS', '繪圖範圍: (左 x 右 x 上 x 下)'); //NEW
DEFINE('_PLOTTYPE', '繪圖型態:'); //NEW
DEFINE('_TYPEBAR', '條狀圖'); //NEW
DEFINE('_TYPELINE', '線形圖'); //NEW
DEFINE('_TYPEPIE', '圓餅圖'); //NEW
DEFINE('_CHARTHOUR', '{時}'); //NEW
DEFINE('_CHARTDAY', '{日}'); //NEW
DEFINE('_CHARTMONTH', '{月}'); //NEW
DEFINE('_GRAPHALERTS', '警告數'); //NEW
DEFINE('_AXISCONTROLS', 'X / Y 控制'); //NEW
DEFINE('_CHRTTYPEHOUR', '時間 (小時) vs. 警告數');
DEFINE('_CHRTTYPEDAY', '時間 (日) vs. 警告數');
DEFINE('_CHRTTYPEWEEK', '時間 (週) vs. 警告數');
DEFINE('_CHRTTYPEMONTH', '時間 (月) vs. 警告數');
DEFINE('_CHRTTYPEYEAR', '時間 (年) vs. 警告數');
DEFINE('_CHRTTYPESRCIP', '來源. IP 位址 vs. 警告數');
DEFINE('_CHRTTYPEDSTIP', '目地. IP 位址 vs. 警告數');
DEFINE('_CHRTTYPEDSTUDP', '目地. UDP 通訊埠 vs. 警告數');
DEFINE('_CHRTTYPESRCUDP', '來源. UDP 通訊埠 vs. 警告數');
DEFINE('_CHRTTYPEDSTPORT', '目地. TCP 通訊埠 vs. 警告數');
DEFINE('_CHRTTYPESRCPORT', '來源. TCP 通訊埠 vs. 警告數');
DEFINE('_CHRTTYPESIG', '特徵. 分類 vs. 警告數');
DEFINE('_CHRTTYPESENSOR', '偵測器 vs. 警告數');
DEFINE('_CHRTBEGIN', '圖形開始:');
DEFINE('_CHRTEND', '圖形結束:');
DEFINE('_CHRTDS', '資料來源:');
DEFINE('_CHRTX', 'X 軸');
DEFINE('_CHRTY', 'Y 軸');
DEFINE('_CHRTMINTRESH', '最小門欄值');
DEFINE('_CHRTROTAXISLABEL', '旋轉軸標記 (90 度)');
DEFINE('_CHRTSHOWX', '顯示 X-軸 格線');
DEFINE('_CHRTDISPLABELX', '顯示 X-軸 標籤每次');
DEFINE('_CHRTDATAPOINTS', '資料點數');
DEFINE('_CHRTYLOG', 'Y-軸 對數顯示');
DEFINE('_CHRTYGRID', '顯示 Y-軸 線格');
//base_graph_main.php
DEFINE('_CHRTTITLE', 'BASE 圖形');
DEFINE('_ERRCHRTNOTYPE', '沒有圖型型態被指定');
DEFINE('_ERRNOAGSPEC', '沒有 AG 警告群組被指定.  使用全部警告.');
DEFINE('_CHRTDATAIMPORT', '開始資料轉入');
DEFINE('_CHRTTIMEVNUMBER', '時間 vs. 警告數');
DEFINE('_CHRTTIME', '時間');
DEFINE('_CHRTALERTOCCUR', '警告事件');
DEFINE('_CHRTSIPNUMBER', '來源 IP vs. N警告數s');
DEFINE('_CHRTSIP', '來源 IP 位址');
DEFINE('_CHRTDIPALERTS', '目地 IP vs. 警告數');
DEFINE('_CHRTDIP', '目地 IP 位址');
DEFINE('_CHRTUDPPORTNUMBER', 'UDP 通訊埠 (目地) vs. 警告數');
DEFINE('_CHRTDUDPPORT', 'Dst. UDP 通訊埠');
DEFINE('_CHRTSUDPPORTNUMBER', 'UDP 通訊埠 (來源) vs. 警告數');
DEFINE('_CHRTSUDPPORT', 'Src. UDP 通訊埠');
DEFINE('_CHRTPORTDESTNUMBER', 'TCP 通訊埠 (目地) vs. 警告數');
DEFINE('_CHRTPORTDEST', 'Dst. TCP 通訊埠');
DEFINE('_CHRTPORTSRCNUMBER', 'TCP 通訊埠 (來源) vs. 警告數');
DEFINE('_CHRTPORTSRC', 'Src. TCP 通訊埠');
DEFINE('_CHRTSIGNUMBER', '特徵分類 vs. 警告數');
DEFINE('_CHRTCLASS', '分類');
DEFINE('_CHRTSENSORNUMBER', '偵測器 vs. 警告數');
DEFINE('_CHRTHANDLEPERIOD', '維持週期 如果需要');
DEFINE('_CHRTDUMP', '頃入資料中 ... (每次只寫入');
DEFINE('_CHRTDRAW', ' 繪製圖表');
DEFINE('_ERRCHRTNODATAPOINTS', '沒有資料點可以繪製');
DEFINE('_GRAPHALERTDATA', ' 繪出警告資料'); //NEW
//base_maintenance.php
DEFINE('_MAINTTITLE', '維護');
DEFINE('_MNTPHP', 'PHP 建制:');
DEFINE('_MNTCLIENT', '客戶端:');
DEFINE('_MNTSERVER', '伺服端:');
DEFINE('_MNTSERVERHW', '伺服端硬體:');
DEFINE('_MNTPHPVER', 'PHP 版本:');
DEFINE('_MNTPHPAPI', 'PHP API:');
DEFINE('_MNTPHPLOGLVL', 'PHP 登入位準:');
DEFINE('_MNTPHPMODS', '載入模組:');
DEFINE('_MNTDBTYPE', '資料庫型態:');
DEFINE('_MNTDBALV', '資料庫抽象版本:');
DEFINE('_MNTDBALERTNAME', '警告資料庫名稱:');
DEFINE('_MNTDBARCHNAME', '封存資料庫名稱:');
DEFINE('_MNTAIC', '警告資訊快取暫存:');
DEFINE('_MNTAICTE', '全部事件數:');
DEFINE('_MNTAICCE', '快取事件數:');
DEFINE('_MNTIPAC', 'IP 位址快取');
DEFINE('_MNTIPACUSIP', '單一來源 IP:');
DEFINE('_MNTIPACDNSC', 'DNS 快取:');
DEFINE('_MNTIPACWC', 'Whois 快取:');
DEFINE('_MNTIPACUDIP', '單一目地 IP:');
//base_qry_alert.php
DEFINE('_QAINVPAIR', '不合法 (sid,cid) 配對');
DEFINE('_QAALERTDELET', '警告已刪除');
DEFINE('_QATRIGGERSIG', '觸發事件特徵');
DEFINE('_QANORMALD', '正常顯示'); //NEW
DEFINE('_QAPLAIND', '簡易顯示'); //NEW
DEFINE('_QANOPAYLOAD', '已使用快速記錄因此封包內容被丟棄'); //NEW
//base_qry_common.php
DEFINE('_QCSIG', '特徵');
DEFINE('_QCIPADDR', 'IP 位址');
DEFINE('_QCIPFIELDS', 'IP 欄位');
DEFINE('_QCTCPPORTS', 'TCP 通訊埠');
DEFINE('_QCTCPFLAGS', 'TCP 旗標');
DEFINE('_QCTCPFIELD', 'TCP 欄位');
DEFINE('_QCUDPPORTS', 'UDP 通訊埠');
DEFINE('_QCUDPFIELDS', 'UDP 欄位');
DEFINE('_QCICMPFIELDS', 'ICMP 欄位');
DEFINE('_QCDATA', '資料');
DEFINE('_QCERRCRITWARN', '標準警告:');
DEFINE('_QCERRVALUE', '一個值為');
DEFINE('_QCERRFIELD', '一個欄位為');
DEFINE('_QCERROPER', '一個運算子為');
DEFINE('_QCERRDATETIME', '一個日期/時間值為');
DEFINE('_QCERRPAYLOAD', '一個封包內容值為');
DEFINE('_QCERRIP', '一個 IP 位址為');
DEFINE('_QCERRIPTYPE', '一個 IP 位址型態');
DEFINE('_QCERRSPECFIELD', ' 已經輸入通訊協定欄位, 但是特定欄位沒有被指定.');
DEFINE('_QCERRSPECVALUE', '已經選擇指出這項為標準, 但是沒有數值被指定相符.');
DEFINE('_QCERRBOOLEAN', '多個通訊協定欄位標準輸入但是沒有邏輯運算子 (例. AND, OR) 在倆者之間.');
DEFINE('_QCERRDATEVALUE', '已經選定和指出但是一些日期/時間必需要被符合, 沒有數值指定.');
DEFINE('_QCERRINVHOUR', '(不合法的小時) 沒有用指定的時間來輸入標準資料.');
DEFINE('_QCERRDATECRIT', '已選擇指出一些日期/時間必需要被符合, 但是沒有數值被指定.');
DEFINE('_QCERROPERSELECT', '已經輸入但是沒有運算元被選擇.');
DEFINE('_QCERRDATEBOOL', '多個日期/時間標準輸入但沒有邏輯運算子 (例. AND, OR) 介於兩者之間.');
DEFINE('_QCERRPAYCRITOPER', '已經輸入作為封包標準欄位, 但運算子 (例. has, has not) 沒有被指定.');
DEFINE('_QCERRPAYCRITVALUE', '已經選擇指定封包可能做為標準, 但是沒有數值介於所指定兩者之間.');
DEFINE('_QCERRPAYBOOL', '多個資料封包標準被輸入但是沒有邏輯運算子 (例. AND, OR) 介於兩者之間.');
DEFINE('_QCMETACRIT', 'Meta 標準');
DEFINE('_QCIPCRIT', 'IP 標準');
DEFINE('_QCPAYCRIT', '封包內容標準');
DEFINE('_QCTCPCRIT', 'TCP 標準');
DEFINE('_QCUDPCRIT', 'UDP 標準');
DEFINE('_QCICMPCRIT', 'ICMP 標準');
DEFINE('_QCLAYER4CRIT', '第四層規則'); //NEW
DEFINE('_QCERRINVIPCRIT', '不合法 IP 位址標準');
DEFINE('_QCERRCRITADDRESSTYPE', '已經被輸入做為標準值, 但是位址型態 (例. 來源, 目地) 沒有被指定.');
DEFINE('_QCERRCRITIPADDRESSNONE', '指出一個 IP 位址必需當做標準, 但是沒有位址可以與指定的相符合.');
DEFINE('_QCERRCRITIPADDRESSNONE1', '已經選擇 (於 #');
DEFINE('_QCERRCRITIPIPBOOL', '多個 IP 位址標準輸入但是沒有一個邏輯運算子 (例. AND, OR) 介於兩個 IP 標準間');
//base_qry_form.php
DEFINE('_QFRMSORTORDER', '排序規則');
DEFINE('_QFRMSORTNONE', '無'); //NEW
DEFINE('_QFRMTIMEA', '時間戳記 (ascend)');
DEFINE('_QFRMTIMED', '時間戳記 (descend)');
DEFINE('_QFRMSIG', '特徵');
DEFINE('_QFRMSIP', '來源 IP');
DEFINE('_QFRMDIP', '目地 IP');
//base_qry_sqlcalls.php
DEFINE('_QSCSUMM', '摘要狀態');
DEFINE('_QSCTIMEPROF', '時間數據');
DEFINE('_QSCOFALERTS', '警告數');
//base_stat_alerts.php
DEFINE('_ALERTTITLE', '警告列表');
//base_stat_common.php
DEFINE('_SCCATEGORIES', '目錄:');
DEFINE('_SCSENSORTOTAL', '偵測器/全部:');
DEFINE('_SCTOTALNUMALERTS', '全部警告數:');
DEFINE('_SCSRCIP', '來源 IP 位址:');
DEFINE('_SCDSTIP', '目地 IP 位址:');
DEFINE('_SCUNILINKS', '單一 IP 連結數');
DEFINE('_SCSRCPORTS', '來源 通訊埠數: ');
DEFINE('_SCDSTPORTS', '目地 通訊埠數: ');
DEFINE('_SCSENSORS', '偵測器');
DEFINE('_SCCLASS', '分類');
DEFINE('_SCUNIADDRESS', '單一位址: ');
DEFINE('_SCSOURCE', '來源');
DEFINE('_SCDEST', '目地');
DEFINE('_SCPORT', '通訊埠');
//base_stat_ipaddr.php
DEFINE('_PSEVENTERR', 'PORTSCAN 事件錯誤: ');
DEFINE('_PSEVENTERRNOFILE', '沒有檔案被指定在 \$portscan_file 變數.');
DEFINE('_PSEVENTERROPENFILE', '無法開啟 通訊埠掃描 事件檔');
DEFINE('_PSDATETIME', '日期/時間');
DEFINE('_PSSRCIP', '來源 IP');
DEFINE('_PSDSTIP', '目地 IP');
DEFINE('_PSSRCPORT', '來源通訊埠');
DEFINE('_PSDSTPORT', '目地通訊埠');
DEFINE('_PSTCPFLAGS', 'TCP 旗標');
DEFINE('_PSTOTALOCC', '全部<BR> 事件');
DEFINE('_PSNUMSENSORS', '偵測器數目');
DEFINE('_PSFIRSTOCC', '最早<BR> 事件');
DEFINE('_PSLASTOCC', '最後<BR> 事件');
DEFINE('_PSUNIALERTS', '單一警告數');
DEFINE('_PSPORTSCANEVE', '通訊埠掃描事件');
DEFINE('_PSREGWHOIS', '登入查詢 (whois) 於');
DEFINE('_PSNODNS', '沒有 DNS 解析企圖');
DEFINE('_PSNUMSENSORSBR', '偵測器 <BR>數目');
DEFINE('_PSOCCASSRC', '發生 <BR>做為來源');
DEFINE('_PSOCCASDST', '發生 <BR>做為目地');
DEFINE('_PSWHOISINFO', 'Whois 資訊');
DEFINE('_PSTOTALHOSTS', '全部掃描到主機'); //NEW
DEFINE('_PSDETECTAMONG', '%d 單項偵測到警告在 %d 之中為 %s'); //NEW
DEFINE('_PSALLALERTSAS', '全部警告數為 %s/%s as'); //NEW
DEFINE('_PSSHOW', ' 顯示'); //NEW
DEFINE('_PSEXTERNAL', '外部'); //NEW
//base_stat_iplink.php
DEFINE('_SIPLTITLE', 'IP 連結數');
DEFINE('_SIPLSOURCEFGDN', '來源 FQDN');
DEFINE('_SIPLDESTFGDN', '目地 FQDN');
DEFINE('_SIPLDIRECTION', '方向');
DEFINE('_SIPLPROTO', '通訊協定');
DEFINE('_SIPLUNIDSTPORTS', '單一目地通訊埠');
DEFINE('_SIPLUNIEVENTS', '單一事件');
DEFINE('_SIPLTOTALEVENTS', '全部事件');
//base_stat_ports.php
DEFINE('_UNIQ', '單一');
DEFINE('_DSTPS', '目地通訊埠數');
DEFINE('_SRCPS', '來源通訊埠數');
DEFINE('_OCCURRENCES', 'Occurrences'); //NEW
//base_stat_sensor.php
DEFINE('SPSENSORLIST', '偵測器列表');
//base_stat_time.php
DEFINE('_BSTTITLE', '警告時間數據表');
DEFINE('_BSTTIMECRIT', '時間標準');
DEFINE('_BSTERRPROFILECRIT', '<FONT><B>沒有數據庫標準被指定!</B>  選擇 "小時", "日", 或 "月" 來選定成為粒狀的狀態.</FONT>');
DEFINE('_BSTERRTIMETYPE', '<FONT><B>通過的時間參數型態沒有被指定!</B>  選擇 "on", ,指定的單獨日期或 "between" 來指定特定期間.</FONT>');
DEFINE('_BSTERRNOYEAR', '<FONT><B>沒有年參數被指定!</B></FONT>');
DEFINE('_BSTERRNOMONTH', '<FONT><B>沒有月參數被指定!</B></FONT>');
DEFINE('_BSTERRNODAY', '<FONT><B>沒有日參數被指定!</B></FONT>');
DEFINE('_BSTPROFILEBY', ' 曲線圖表-自'); //NEW
DEFINE('_TIMEON', 'on'); //NEW
DEFINE('_TIMEBETWEEN', '之間'); //NEW
DEFINE('_PROFILEALERT', ' 曲線圖表 警告資料'); //NEW
//base_stat_uaddr.php
DEFINE('_UNISADD', '單一來源位址數)');
DEFINE('_SUASRCIP', '來源 IP 位址');
DEFINE('_SUAERRCRITADDUNK', '標準錯誤: 未知位址型態 -- 表示目地位址');
DEFINE('_UNIDADD', '單一目地位址數');
DEFINE('_SUADSTIP', '目地 IP 位址');
DEFINE('_SUAUNIALERTS', '單一&nbsp;警告數');
DEFINE('_SUASRCADD', '來源&nbsp;位址');
DEFINE('_SUADSTADD', '目地&nbsp;位址');
//base_user.php
DEFINE('_BASEUSERTITLE', 'BASE 使用者參數');
DEFINE('_BASEUSERERRPWD', '您的密碼不能為空白或兩個密碼沒有吻合!');
DEFINE('_BASEUSEROLDPWD', '舊密碼:');
DEFINE('_BASEUSERNEWPWD', '新密碼:');
DEFINE('_BASEUSERNEWPWDAGAIN', ' 再輸入新密碼一次:');
DEFINE('_LOGOUT', ' 登出');
?>
