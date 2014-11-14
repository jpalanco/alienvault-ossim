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
DEFINE('_CHARSET', 'UTF-8');
DEFINE('_TITLE', 'Forensics Console 日本 ' . $BASE_installID);
DEFINE('_FRMLOGIN', 'ログイン:');
DEFINE('_FRMPWD', 'パスワード:');
DEFINE('_SOURCE', '発信元');
DEFINE('_SOURCENAME', '発信元　名前');
DEFINE('_DEST', '発信先');
DEFINE('_DESTNAME', '送信先　名前');
DEFINE('_SORD', '発信元 or 送信先');
DEFINE('_EDIT', '編集');
DEFINE('_DELETE', '削除');
DEFINE('_ID', 'ID');
DEFINE('_NAME', '名前');
DEFINE('_INTERFACE', 'インターフェース');
DEFINE('_FILTER', 'フィルター');
DEFINE('_DESC', '詳細');
DEFINE('_LOGIN', 'ログイン');
DEFINE('_ROLEID', 'Role ID');
DEFINE('_ENABLED', '有効');
DEFINE('_SUCCESS', '成功');
DEFINE('_SENSOR', 'センサー');
DEFINE('_SENSORS', 'Sensors'); //NEW
DEFINE('_SIGNATURE', 'シグネチャ');
DEFINE('_TIMESTAMP', 'タイムスタンプ');
DEFINE('_NBSOURCEADDR', '発信元&nbsp;アドレス');
DEFINE('_NBDESTADDR', '発信先&nbsp;アドレス');
DEFINE('_NBLAYER4', 'レイヤー&nbsp;4&nbsp;Proto');
DEFINE('_PRIORITY', '優先度');
DEFINE('_EVENTTYPE', 'イベントタイプ');
DEFINE('_JANUARY', '１月');
DEFINE('_FEBRUARY', '２月');
DEFINE('_MARCH', '３月');
DEFINE('_APRIL', '４月');
DEFINE('_MAY', '５月');
DEFINE('_JUNE', '６月');
DEFINE('_JULY', '７月');
DEFINE('_AUGUST', '８月');
DEFINE('_SEPTEMBER', '９月');
DEFINE('_OCTOBER', '１０月');
DEFINE('_NOVEMBER', '１１月');
DEFINE('_DECEMBER', '１２月');
DEFINE('_LAST', '最新の');
DEFINE('_FIRST', 'First'); //NEW
DEFINE('_TOTAL', 'Total'); //NEW
DEFINE('_ALERT', 'アラート');
DEFINE('_ADDRESS', 'アドレス');
DEFINE('_UNKNOWN', '不明');
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
DEFINE('_HOME', 'ホーム');
DEFINE('_SEARCH', '検索');
DEFINE('_AGMAINT', 'アラートグループ設定');
DEFINE('_USERPREF', 'ユーザー設定');
DEFINE('_CACHE', 'キャッシュ & ステータス');
DEFINE('_ADMIN', '管理');
DEFINE('_GALERTD', 'グラフ作成');
DEFINE('_GALERTDT', '時間に基づくグラフ作成');
DEFINE('_USERMAN', 'ユーザーマネージメント');
DEFINE('_LISTU', 'ユーザー表示');
DEFINE('_CREATEU', 'ユーザー作成');
DEFINE('_ROLEMAN', 'Role Management');
DEFINE('_LISTR', 'List Roles');
DEFINE('_CREATER', 'Create a Role');
DEFINE('_LISTALL', 'すべて表示');
DEFINE('_CREATE', '作成');
DEFINE('_VIEW', 'View');
DEFINE('_CLEAR', 'クリア');
DEFINE('_LISTGROUPS', 'グループ表示');
DEFINE('_CREATEGROUPS', 'グループ作成');
DEFINE('_VIEWGROUPS', 'グループを見る');
DEFINE('_EDITGROUPS', 'グループを編集');
DEFINE('_DELETEGROUPS', 'グループを削除');
DEFINE('_CLEARGROUPS', 'グループをクリア');
DEFINE('_CHNGPWD', 'パスワード変更');
DEFINE('_DISPLAYU', 'ユーザー表示');
//base_footer.php
DEFINE('_FOOTER', '(<A class="largemenuitem" href="mailto:base@secureideas.net">Kevin Johnson</A> BASEプロジェクトチーム) <A class="largemenuitem" href="mailto:kenji@pingu.zive.net">日本語訳に関してはこちら。</A>BASEはRoman Danyliw氏のACIDを引き継いで開発されています。 )');
//index.php --Log in Page
DEFINE('_LOGINERROR', 'ユーザーが存在しないか、パスワードが間違っています。!<br>Please try again');
// base_main.php
DEFINE('_MOSTRECENT', '最近');
DEFINE('_MOSTFREQUENT', '上位');
DEFINE('_ALERTS', ' アラート:');
DEFINE('_ADDRESSES', ' アドレス:');
DEFINE('_ANYPROTO', 'すべて');
DEFINE('_UNI', '種類別');
DEFINE('_LISTING', 'リスト');
DEFINE('_TALERTS', '今日のアラート: ');
DEFINE('_SOURCEIP', 'Source IP'); //NEW
DEFINE('_DESTIP', 'Destination IP'); //NEW
DEFINE('_L24ALERTS', '過去24時間のアラート: ');
DEFINE('_L72ALERTS', '過去72時間のアラート: ');
DEFINE('_UNIALERTS', '件のユニークアラート');
DEFINE('_LSOURCEPORTS', '最近の発信元ポート: ');
DEFINE('_LDESTPORTS', '最近の送信先ポート: ');
DEFINE('_FREGSOURCEP', '発信元ポートランキング: ');
DEFINE('_FREGDESTP', '送信先ポートランキング: ');
DEFINE('_QUERIED', 'クエリ発行');
DEFINE('_DATABASE', 'データベース:');
DEFINE('_SCHEMAV', 'スキマバージョン:');
DEFINE('_TIMEWIN', 'Time Window:');
DEFINE('_NOALERTSDETECT', 'アラートは検知されませんでした');
DEFINE('_USEALERTDB', 'Use Alert Database'); //NEW
DEFINE('_USEARCHIDB', 'Use Archive Database'); //NEW
DEFINE('_TRAFFICPROBPRO', 'Traffic Profile by Protocol'); //NEW
//base_auth.inc.php
DEFINE('_ADDEDSF', '追加されました。');
DEFINE('_NOPWDCHANGE', 'パスワードの変更が出来ませんでした。: ');
DEFINE('_NOUSER', 'ユーザーが存在しません。');
DEFINE('_OLDPWD', '古いパスワードが間違っています。');
DEFINE('_PWDCANT', 'パスワードの変更が出来ませんでした。: ');
DEFINE('_PWDDONE', 'パスワードは正常に変更されました。');
DEFINE('_ROLEEXIST', 'Role はすでに存在します。');
DEFINE('_ROLEIDEXIST', 'Role ID がすでに存在します。');
DEFINE('_ROLEADDED', 'Role は正常に追加されました。');
//base_roleadmin.php
DEFINE('_ROLEADMIN', 'BASE Role管理');
DEFINE('_FRMROLEID', 'Role ID:');
DEFINE('_FRMROLENAME', 'Role名:');
DEFINE('_FRMROLEDESC', '詳細:');
DEFINE('_UPDATEROLE', 'Update Role'); //NEW
//base_useradmin.php
DEFINE('_USERADMIN', 'BASE ユーザー管理');
DEFINE('_FRMFULLNAME', '氏名:');
DEFINE('_FRMROLE', 'Role:');
DEFINE('_FRMUID', 'ユーザーID:');
DEFINE('_SUBMITQUERY', 'Submit Query'); //NEW
DEFINE('_UPDATEUSER', 'Update User'); //NEW
//admin/index.php
DEFINE('_BASEADMIN', 'BASE管理');
DEFINE('_BASEADMINTEXT', '左のオプションから選択して下さい。');
//base_action.inc.php
DEFINE('_NOACTION', 'このアラートで設定されているアクションはありません。');
DEFINE('_INVALIDACT', ' 無効なアクションです。');
DEFINE('_ERRNOAG', 'AG を指定してからアラートを追加してください。');
DEFINE('_ERRNOEMAIL', 'メールアドレスが指定されていないため、メールは送信されませんでした。');
DEFINE('_ACTION', 'アクション');
DEFINE('_CONTEXT', 'コンテキスト');
DEFINE('_ADDAGID', 'アラートグループに追加 (IDで指定)');
DEFINE('_ADDAG', '新しいアラートグループを追加');
DEFINE('_ADDAGNAME', 'アラートグループに追加 (名前で指定)');
DEFINE('_CREATEAG', 'アラートグループ作成 (名前で指定)');
DEFINE('_CLEARAG', 'アラートグループを削除');
DEFINE('_DELETEALERT', 'アラートを削除');
DEFINE('_EMAILALERTSFULL', 'Email アラート (すべて)');
DEFINE('_EMAILALERTSSUMM', 'Email アラート (完結)');
DEFINE('_EMAILALERTSCSV', 'Email アラート (csv)');
DEFINE('_ARCHIVEALERTSCOPY', 'アーカイブアラート(コピー)');
DEFINE('_ARCHIVEALERTSMOVE', 'アーカイブアラート(移動)');
DEFINE('_IGNORED', 'Ignored ');
DEFINE('_DUPALERTS', 'アラートを複製');
DEFINE('_ALERTSPARA', ' アラート');
DEFINE('_NOALERTSSELECT', 'アラートが選択されていません、または');
DEFINE('_NOTSUCCESSFUL', '失敗しました。');
DEFINE('_ERRUNKAGID', '不明なアラートグループIDです (おそらく存在しないAGです。)');
DEFINE('_ERRREMOVEFAIL', 'アラートグループの削除に失敗しました。');
DEFINE('_GENBASE', 'BASEが生成');
DEFINE('_ERRNOEMAILEXP', 'EXPORT ERROR:エクスポート出来ませんでした：');
DEFINE('_ERRNOEMAILPHP', 'PHPのメール設定を確認してください。.');
DEFINE('_ERRDELALERT', 'アラート削除エラー');
DEFINE('_ERRARCHIVE', 'アーカイブエラー:');
DEFINE('_ERRMAILNORECP', 'MAILエラー: あて先が設定されていません');
//base_cache.inc.php
DEFINE('_ADDED', '新しいアラート');
DEFINE('_HOSTNAMESDNS', ' hostnames to the IP DNS cache');
DEFINE('_HOSTNAMESWHOIS', ' hostnames to the Whois cache');
DEFINE('_ERRCACHENULL', 'Caching ERROR: NULL event row found?');
DEFINE('_ERRCACHEERROR', 'イベントキャッシュエラー:');
DEFINE('_ERRCACHEUPDATE', 'イベントキャッシュをアップデート出来ませんでした。');
DEFINE('_ALERTSCACHE', '件をアラートキャッシュへ追加');
//base_db.inc.php
DEFINE('_ERRSQLTRACE', 'SQLのtraceが開けません');
DEFINE('_ERRSQLCONNECT', 'データベースに接続できません :');
DEFINE('_ERRSQLCONNECTINFO', '<P>データベース接続設定を確認して下さい<I>(base_conf.php)</I> 
              <PRE>
               = $alert_dbname   : Snortのデータベース名 
               = $alert_host     : データベースのホスト名
               = $alert_port     : データベースのポート番号
               = $alert_user     : データベースのユーザー名
               = $alert_password : データベースのパスワード
              </PRE>
              <P>');
DEFINE('_ERRSQLPCONNECT', 'データーベース接続エラー :');
DEFINE('_ERRSQLDB', 'データベースエラー:');
DEFINE('_DBALCHECK', 'データベースライブラリをチェック中 ');
DEFINE('_ERRSQLDBALLOAD1', '<P><B>データベースライブラリがロードできません: </B> from ');
DEFINE('_ERRSQLDBALLOAD2', '<P>データベース抽象ライブラリのパスを確認してください <CODE>($DBlib_path)</CODE> in <CODE>base_conf.php</CODE>
            <P>
            データベースライブラリはADODBを使用しています。ダウンロードはこちら→
            <A HREF="http://adodb.sourceforge.net/">http://adodb.sourceforge.net/</A>');
DEFINE('_ERRSQLDBTYPE', '不正なデータベースタイプです');
DEFINE('_ERRSQLDBTYPEINFO1', '<CODE>\$DBtype</CODE> in <CODE>base_conf.php</CODE> が不正です');
DEFINE('_ERRSQLDBTYPEINFO2', 'Only the following databases are supported: <PRE>
                MySQL         : \'mysql\'
                PostgreSQL    : \'postgres\'
                MS SQL Server : \'mssql\'
                Oracle        : \'oci8\'
             </PRE>');
//base_log_error.inc.php
DEFINE('_ERRBASEFATAL', 'BASEに致命的なエラーが発生しました:');
//base_log_timing.inc.php
DEFINE('_LOADEDIN', '読み込み時間');
DEFINE('_SECONDS', '秒');
//base_net.inc.php
DEFINE('_ERRRESOLVEADDRESS', '名前解決できませんでした');
//base_output_query.inc.php
DEFINE('_QUERYRESULTSHEADER', 'クエリ出力結果のヘッダ');
//base_signature.inc.php
DEFINE('_ERRSIGNAMEUNK', 'シグネチャ名が不明です');
DEFINE('_ERRSIGPROIRITYUNK', '優先度が不明です');
DEFINE('_UNCLASS', 'unclassified');
//base_state_citems.inc.php
DEFINE('_DENCODED', 'データのエンコード：');
DEFINE('_NODENCODED', '(no data conversion, assuming criteria in DB native encoding)');
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
//base_state_common.inc.php
DEFINE('_PHPERRORCSESSION', 'PHP ERROR: A custom (user) PHP session have been detected. However, BASE has not been set to explicitly use this custom handler.  Set <CODE>use_user_session=1</CODE> in <CODE>base_conf.php</CODE>');
DEFINE('_PHPERRORCSESSIONCODE', 'PHP ERROR: A custom (user) PHP session hander has been configured, but the supplied hander code specified in <CODE>user_session_path</CODE> is invalid.');
DEFINE('_PHPERRORCSESSIONVAR', 'PHP ERROR: A custom (user) PHP session handler has been configured, but the implementation of this handler has not been specified in BASE.  If a custom session handler is desired, set the <CODE>user_session_path</CODE> variable in <CODE>base_conf.php</CODE>.');
DEFINE('_PHPSESSREG', 'Session Registered');
//base_state_criteria.inc.php
DEFINE('_REMOVE', 'Removing');
DEFINE('_FROMCRIT', 'from criteria');
DEFINE('_ERRCRITELEM', 'Invalid criteria element');
//base_state_query.inc.php
DEFINE('_VALIDCANNED', 'Valid Canned Query List');
DEFINE('_DISPLAYING', 'Displaying');
DEFINE('_DISPLAYINGTOTAL', 'アラート%d-%d件目 アラート総数 %s');
DEFINE('_NOALERTS', 'アラートは見つかりませんでした');
DEFINE('_QUERYRESULTS', 'クエリ結果');
DEFINE('_QUERYSTATE', 'Query State');
DEFINE('_DISPACTION', '{ action }'); //NEW
//base_ag_common.php
DEFINE('_ERRAGNAMESEARCH', 'アラートグループ名が不正です。もう一度どうぞ！');
DEFINE('_ERRAGNAMEEXIST', 'そのようなアラートグループはありません');
DEFINE('_ERRAGIDSEARCH', 'アラートグループＩＤが不正です。もう一度どうぞ！');
DEFINE('_ERRAGLOOKUP', 'アラートグループＩＤ検索エラー');
DEFINE('_ERRAGINSERT', 'アラートグループ挿入エラー');
//base_ag_main.php
DEFINE('_AGMAINTTITLE', 'アラートグループメンテナンス');
DEFINE('_ERRAGUPDATE', 'アラートグループの更新に失敗しました');
DEFINE('_ERRAGPACKETLIST', 'パケットリストの削除に失敗しました:');
DEFINE('_ERRAGDELETE', 'アラートグループの削除に失敗しました');
DEFINE('_AGDELETE', '削除しました！');
DEFINE('_AGDELETEINFO', '詳細情報は削除されました');
DEFINE('_ERRAGSEARCHINV', '検索キーワードが不正です。もう一度どうぞ！');
DEFINE('_ERRAGSEARCHNOTFOUND', 'アラートグループが見つかりませんでした');
DEFINE('_NOALERTGOUPS', 'アラートグループが一つもありません');
DEFINE('_NUMALERTS', '# アラート');
DEFINE('_ACTIONS', 'アクション');
DEFINE('_NOTASSIGN', 'not assigned ');
DEFINE('_SAVECHANGES', 'Save Changes'); //NEW
DEFINE('_CONFIRMDELETE', 'Confirm Delete'); //NEW
DEFINE('_CONFIRMCLEAR', 'Confirm Clear'); //NEW
//base_common.php
DEFINE('_PORTSCAN', 'ポートスキャン量');
//base_db_common.php
DEFINE('_ERRDBINDEXCREATE', 'インデックスを作成できませんでした：');
DEFINE('_DBINDEXCREATE', 'インデックスが正常に作成されました：');
DEFINE('_ERRSNORTVER', 'バージョンが古すぎるおそれがあります。対応しているSnortのバージョン1.7以降です');
DEFINE('_ERRSNORTVER1', 'The underlying database');
DEFINE('_ERRSNORTVER2', '不完全または不正です');
DEFINE('_ERRDBSTRUCT1', 'データベースバージョンは正常ですが、BASEのデータベースバージョンが違います');
DEFINE('_ERRDBSTRUCT2', 'は存在しません。<A HREF="base_db_setup.php">セットアップページ</A>をクリックしてデーターベースを作成してください');
DEFINE('_ERRPHPERROR', 'PHPエラー');
DEFINE('_ERRPHPERROR1', '対応していないバージョンです');
DEFINE('_ERRVERSION', 'バージョン');
DEFINE('_ERRPHPERROR2', 'PHPバージョンが古すぎます4.04以降にアップデートしてください');
DEFINE('_ERRPHPMYSQLSUP', '<B>PHP build incomplete</B>: <FONT>the prerequisite MySQL support required to 
               read the alert database was not built into PHP.  
               Please recompile PHP with the necessary library (<CODE>--with-mysql</CODE>)</FONT>');
DEFINE('_ERRPHPPOSTGRESSUP', '<B>PHP build incomplete</B>: <FONT>the prerequisite PostgreSQL support required to 
               read the alert database was not built into PHP.  
               Please recompile PHP with the necessary library (<CODE>--with-pgsql</CODE>)</FONT>');
DEFINE('_ERRPHPMSSQLSUP', '<B>PHP build incomplete</B>: <FONT>the prerequisite MS SQL Server support required to 
                   read the alert database was not built into PHP.  
                   Please recompile PHP with the necessary library (<CODE>--enable-mssql</CODE>)</FONT>');
DEFINE('_ERRPHPORACLESUP', '<B>PHP build incomplete</B>: <FONT>the prerequisite Oracle support required to 
                   read the alert database was not built into PHP.  
                   Please recompile PHP with the necessary library (<CODE>--with-oci8</CODE>)</FONT>');
//base_graph_form.php
DEFINE('_CHARTTITLE', 'Chart Title:');
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
DEFINE('_CHRTTYPEHOUR', ' 時間 vs. アラート数');
DEFINE('_CHRTTYPEDAY', '日 vs. アラート数');
DEFINE('_CHRTTYPEWEEK', '週 vs. アラート数');
DEFINE('_CHRTTYPEMONTH', '月 vs. アラート数');
DEFINE('_CHRTTYPEYEAR', '年 vs. アラート数');
DEFINE('_CHRTTYPESRCIP', '発信元 IP vs. アラート数');
DEFINE('_CHRTTYPEDSTIP', '送信先 IP address vs. アラート数');
DEFINE('_CHRTTYPEDSTUDP', '送信先 UDP Port vs. アラート数');
DEFINE('_CHRTTYPESRCUDP', '発信元 UDP Port vs. アラート数');
DEFINE('_CHRTTYPEDSTPORT', '送信先 TCP Port vs. アラート数');
DEFINE('_CHRTTYPESRCPORT', '発信元 TCP Port vs. アラート数');
DEFINE('_CHRTTYPESIG', 'Sig. Classification vs. アラート数');
DEFINE('_CHRTTYPESENSOR', 'センサー vs. アラート数');
DEFINE('_CHRTBEGIN', 'Chart Begin:');
DEFINE('_CHRTEND', 'Chart End:');
DEFINE('_CHRTDS', 'Data Source:');
DEFINE('_CHRTX', 'X軸');
DEFINE('_CHRTY', 'Y軸');
DEFINE('_CHRTMINTRESH', 'Minimum Threshold Value');
DEFINE('_CHRTROTAXISLABEL', '軸のラベルを90度回転');
DEFINE('_CHRTSHOWX', 'X軸のグリッドラインを表示');
DEFINE('_CHRTDISPLABELX', 'Display X-axis label every');
DEFINE('_CHRTDATAPOINTS', 'data points');
DEFINE('_CHRTYLOG', 'Y-axis logarithmic');
DEFINE('_CHRTYGRID', 'Y軸のグリッドラインを表示');
//base_graph_main.php
DEFINE('_CHRTTITLE', 'BASEグラフ');
DEFINE('_ERRCHRTNOTYPE', 'グラフタイプを選択してください');
DEFINE('_ERRNOAGSPEC', 'アラートグループが選択されていないため、すべてのアラートグループが使用されます');
DEFINE('_CHRTDATAIMPORT', 'データインポート開始');
DEFINE('_CHRTTIMEVNUMBER', '時間　ｘ　アラート数');
DEFINE('_CHRTTIME', '時間');
DEFINE('_CHRTALERTOCCUR', 'アラート発生数');
DEFINE('_CHRTSIPNUMBER', '発信元IP　ｘ　アラート数');
DEFINE('_CHRTSIP', '発信元IPアドレス');
DEFINE('_CHRTDIPALERTS', '送信先IPアドレス　ｘ　アラート数');
DEFINE('_CHRTDIP', '送信先IPアドレス');
DEFINE('_CHRTUDPPORTNUMBER', 'UDPポート (送信先)　ｘ　アラート数');
DEFINE('_CHRTDUDPPORT', 'Dst. UDP Port');
DEFINE('_CHRTSUDPPORTNUMBER', 'UDP Port (発信元)　ｘ　アラート数');
DEFINE('_CHRTSUDPPORT', 'Src. UDP Port');
DEFINE('_CHRTPORTDESTNUMBER', 'TCP Port (送信先)　ｘ　アラート数');
DEFINE('_CHRTPORTDEST', 'Dst. TCP Port');
DEFINE('_CHRTPORTSRCNUMBER', 'TCP Port (発信元)　ｘ　アラート数');
DEFINE('_CHRTPORTSRC', 'Src. TCP Port');
DEFINE('_CHRTSIGNUMBER', 'シグネチャクラス　ｘ　アラート数');
DEFINE('_CHRTCLASS', 'クラス');
DEFINE('_CHRTSENSORNUMBER', 'センサー　ｘ　アラート数');
DEFINE('_CHRTHANDLEPERIOD', 'Handling Period if necessary');
DEFINE('_CHRTDUMP', 'Dumping data ... (writing only every');
DEFINE('_CHRTDRAW', 'グラフを描画中');
DEFINE('_ERRCHRTNODATAPOINTS', 'データがありません');
DEFINE('_GRAPHALERTDATA', 'Graph Alert Data'); //NEW
//base_maintenance.php
DEFINE('_MAINTTITLE', 'メンテナンス');
DEFINE('_MNTPHP', 'PHPビルド:');
DEFINE('_MNTCLIENT', 'クライアント:');
DEFINE('_MNTSERVER', 'サーバー:');
DEFINE('_MNTSERVERHW', 'サーバー HW:');
DEFINE('_MNTPHPVER', 'PHPバージョン:');
DEFINE('_MNTPHPAPI', 'PHP API:');
DEFINE('_MNTPHPLOGLVL', 'PHP Logging level:');
DEFINE('_MNTPHPMODS', 'ロードされたモジュール:');
DEFINE('_MNTDBTYPE', 'DBタイプ:');
DEFINE('_MNTDBALV', 'DBライブラリバージョン:');
DEFINE('_MNTDBALERTNAME', 'アラートデータベース名:');
DEFINE('_MNTDBARCHNAME', 'アーカイブデータベース名:');
DEFINE('_MNTAIC', 'アラート情報キャッシュ:');
DEFINE('_MNTAICTE', 'イベント総数:');
DEFINE('_MNTAICCE', 'キャッシュされたイベント:');
DEFINE('_MNTIPAC', 'キャッシュされたIPアドレス');
DEFINE('_MNTIPACUSIP', 'ユニーク発信元IP:');
DEFINE('_MNTIPACDNSC', 'キャッシュされたDNS:');
DEFINE('_MNTIPACWC', 'キャッシュされたWhois:');
DEFINE('_MNTIPACUDIP', 'ユニーク送信先IP:');
//base_qry_alert.php
DEFINE('_QAINVPAIR', 'Invalid (sid,cid) pair');
DEFINE('_QAALERTDELET', '削除されたアラート');
DEFINE('_QATRIGGERSIG', 'Triggeredシグネチャ');
DEFINE('_QANORMALD', 'Normal Display'); //NEW
DEFINE('_QAPLAIND', 'Plain Display'); //NEW
DEFINE('_QANOPAYLOAD', 'Fast logging used so payload was discarded'); //NEW
//base_qry_common.php
DEFINE('_QCSIG', 'シグネチャ');
DEFINE('_QCIPADDR', 'IPアドレス');
DEFINE('_QCIPFIELDS', 'IPフィールド');
DEFINE('_QCTCPPORTS', 'TCPポート');
DEFINE('_QCTCPFLAGS', 'TCPフラグ');
DEFINE('_QCTCPFIELD', 'TCPフィールド');
DEFINE('_QCUDPPORTS', 'UDPポート');
DEFINE('_QCUDPFIELDS', 'UDPフィールド');
DEFINE('_QCICMPFIELDS', 'ICMPフィールド');
DEFINE('_QCDATA', 'Data');
DEFINE('_QCERRCRITWARN', 'Criteria警告:');
DEFINE('_QCERRVALUE', 'A value of');
DEFINE('_QCERRFIELD', 'A field of');
DEFINE('_QCERROPER', 'An operator of');
DEFINE('_QCERRDATETIME', 'A date/time value of');
DEFINE('_QCERRPAYLOAD', 'A payload value of');
DEFINE('_QCERRIP', 'A IP address of');
DEFINE('_QCERRIPTYPE', 'An IP address of type');
DEFINE('_QCERRSPECFIELD', ' was entered for a protocol field, but the particular field was not specified.');
DEFINE('_QCERRSPECVALUE', 'was selected indicating that it should be a criteria, but no value was specified on which to match.');
DEFINE('_QCERRBOOLEAN', 'Multiple protocol field criteria entered without a boolean operator (e.g. AND, OR) between them.');
DEFINE('_QCERRDATEVALUE', 'was selected indicating that some date/time criteria should be matched, but no value was specified.');
DEFINE('_QCERRINVHOUR', '(Invalid Hour) No date criteria were entered with the specified time.');
DEFINE('_QCERRDATECRIT', 'was selected indicating that some date/time criteria should be matched, but no value was specified.');
DEFINE('_QCERROPERSELECT', 'was entered but no operator was selected.');
DEFINE('_QCERRDATEBOOL', 'Multiple Date/Time criteria entered without a boolean operator (e.g. AND, OR) between them.');
DEFINE('_QCERRPAYCRITOPER', 'was entered for a payload criteria field, but an operator (e.g. has, has not) was not specified.');
DEFINE('_QCERRPAYCRITVALUE', 'was selected indicating that payload should be a criteria, but no value on which to match was specified.');
DEFINE('_QCERRPAYBOOL', 'Multiple Data payload criteria entered without a boolean operator (e.g. AND, OR) between them.');
DEFINE('_QCMETACRIT', 'Meta Criteria');
DEFINE('_QCIPCRIT', 'IP Criteria');
DEFINE('_QCPAYCRIT', 'Payload Criteria');
DEFINE('_QCTCPCRIT', 'TCP Criteria');
DEFINE('_QCUDPCRIT', 'UDP Criteria');
DEFINE('_QCICMPCRIT', 'ICMP Criteria');
DEFINE('_QCLAYER4CRIT', 'Layer 4 Criteria'); //NEW
DEFINE('_QCERRINVIPCRIT', 'Invalid IP address criteria');
DEFINE('_QCERRCRITADDRESSTYPE', 'was entered for as a criteria value, but the type of address (e.g. source, destination) was not specified.');
DEFINE('_QCERRCRITIPADDRESSNONE', 'indicating that an IP address should be a criteria, but no address on which to match was specified.');
DEFINE('_QCERRCRITIPADDRESSNONE1', 'was selected (at #');
DEFINE('_QCERRCRITIPIPBOOL', 'Multiple IP address criteria entered without a boolean operator (e.g. AND, OR) between IP Criteria');
//base_qry_form.php
DEFINE('_QFRMSORTORDER', 'ソートオーダー');
DEFINE('_QFRMSORTNONE', 'none'); //NEW
DEFINE('_QFRMTIMEA', 'タイムスタンプ(降順)');
DEFINE('_QFRMTIMED', 'タイムスタンプ(昇順)');
DEFINE('_QFRMSIG', 'シグネチャ');
DEFINE('_QFRMSIP', '発信元IP');
DEFINE('_QFRMDIP', '送信先IP');
//base_qry_sqlcalls.php
DEFINE('_QSCSUMM', '統計サマリ');
DEFINE('_QSCTIMEPROF', '時間プロファイル');
DEFINE('_QSCOFALERTS', 'のアラート');
//base_stat_alerts.php
DEFINE('_ALERTTITLE', 'アラートのリスト');
//base_stat_common.php
DEFINE('_SCCATEGORIES', 'カテゴリー:');
DEFINE('_SCSENSORTOTAL', 'センサー/トータル:');
DEFINE('_SCTOTALNUMALERTS', 'アラート総数:');
DEFINE('_SCSRCIP', '発信元IPの数:');
DEFINE('_SCDSTIP', '送信先IPの数:');
DEFINE('_SCUNILINKS', '発信元ー送信先の組み合わせ一覧');
DEFINE('_SCSRCPORTS', '発信元ポート: ');
DEFINE('_SCDSTPORTS', '送信先ポート: ');
DEFINE('_SCSENSORS', 'センサー');
DEFINE('_SCCLASS', 'classifications');
DEFINE('_SCUNIADDRESS', 'アドレス別: ');
DEFINE('_SCSOURCE', '発信元');
DEFINE('_SCDEST', '送信先');
DEFINE('_SCPORT', 'ポート');
//base_stat_ipaddr.php
DEFINE('_PSEVENTERR', 'ポートスキャンイベントエラー: ');
DEFINE('_PSEVENTERRNOFILE', 'No file was specified in the \$portscan_file variable.');
DEFINE('_PSEVENTERROPENFILE', 'ポートスキャンイベントファイルが開けません');
DEFINE('_PSDATETIME', '日付/時刻');
DEFINE('_PSSRCIP', '発信元 IP');
DEFINE('_PSDSTIP', '送信先 IP');
DEFINE('_PSSRCPORT', '発信元 Port');
DEFINE('_PSDSTPORT', '送信先 Port');
DEFINE('_PSTCPFLAGS', 'TCPフラグ');
DEFINE('_PSTOTALOCC', '発生<BR>総数');
DEFINE('_PSNUMSENSORS', 'センサー数');
DEFINE('_PSFIRSTOCC', '最初の<BR>発生時刻');
DEFINE('_PSLASTOCC', '最後の<BR>発生時刻');
DEFINE('_PSUNIALERTS', 'アラートの種類の数');
DEFINE('_PSPORTSCANEVE', 'ポートスキャン数');
DEFINE('_PSREGWHOIS', 'Registry lookup (whois) in');
DEFINE('_PSNODNS', 'no DNS resolution attempted');
DEFINE('_PSNUMSENSORSBR', 'センサー<BR>総数');
DEFINE('_PSOCCASSRC', '発信元<BR>発生回数');
DEFINE('_PSOCCASDST', '送信先<BR>発生回数');
DEFINE('_PSWHOISINFO', 'Whois情報');
DEFINE('_PSTOTALHOSTS', 'Total Hosts Scanned'); //NEW
DEFINE('_PSDETECTAMONG', '%d unique alerts detected among %d alerts on %s'); //NEW
DEFINE('_PSALLALERTSAS', 'all alerts with %s/%s as'); //NEW
DEFINE('_PSSHOW', 'show'); //NEW
DEFINE('_PSEXTERNAL', 'external'); //NEW
//base_stat_iplink.php
DEFINE('_SIPLTITLE', '発信元ー送信先の組み合わせ');
DEFINE('_SIPLSOURCEFGDN', '発信元 FQDN');
DEFINE('_SIPLDESTFGDN', '送信先 FQDN');
DEFINE('_SIPLDIRECTION', '方向');
DEFINE('_SIPLPROTO', 'プロトコル');
DEFINE('_SIPLUNIDSTPORTS', 'ユニーク送信先ポート');
DEFINE('_SIPLUNIEVENTS', 'ユニークイベント');
DEFINE('_SIPLTOTALEVENTS', 'イベント総数');
//base_stat_ports.php
DEFINE('_UNIQ', 'ユニーク');
DEFINE('_DSTPS', '送信先 ポート');
DEFINE('_SRCPS', '発信元 ポート');
DEFINE('_OCCURRENCES', 'Occurrences'); //NEW
//base_stat_sensor.php
DEFINE('SPSENSORLIST', 'センサーリスト');
//base_stat_time.php
DEFINE('_BSTTITLE', 'アラートの時間プロファイル');
DEFINE('_BSTTIMECRIT', 'Time Criteria');
DEFINE('_BSTERRPROFILECRIT', '<FONT><B>No profiling criteria was specified!</B>  Click on "hour", "day", or "month" to choose the granularity of the aggregate statistics.</FONT>');
DEFINE('_BSTERRTIMETYPE', '<FONT><B>The type of time parameter which will be passed was not specified!</B>  Choose either "on", to specify a single date, or "between" to specify an interval.</FONT>');
DEFINE('_BSTERRNOYEAR', '<FONT><B>No Year parameter was specified!</B></FONT>');
DEFINE('_BSTERRNOMONTH', '<FONT><B>No Month parameter was specified!</B></FONT>');
DEFINE('_BSTERRNODAY', '<FONT><B>No Day parameter was specified!</B></FONT>');
DEFINE('_BSTPROFILEBY', 'Profile by'); //NEW
DEFINE('_TIMEON', 'on'); //NEW
DEFINE('_TIMEBETWEEN', 'between'); //NEW
DEFINE('_PROFILEALERT', 'Profile Alert'); //NEW
//base_stat_uaddr.php
DEFINE('_UNISADD', 'ユニーク発信元アドレス');
DEFINE('_SUASRCIP', '発信元IPアドレス');
DEFINE('_SUAERRCRITADDUNK', 'CRITERIA ERROR: unknown address type -- assuming Dst address');
DEFINE('_UNIDADD', '送信先IPアドレス（ユニーク）');
DEFINE('_SUADSTIP', '送信先IPアドレス');
DEFINE('_SUAUNIALERTS', 'ユニーク&nbsp;アラート');
DEFINE('_SUASRCADD', '発信元&nbsp;アドレス');
DEFINE('_SUADSTADD', '送信先&nbsp;アドレス');
//base_user.php
DEFINE('_BASEUSERTITLE', 'BASEユーザー設定');
DEFINE('_BASEUSERERRPWD', 'パスワードが空かまたは確認用パスワードが間違っています。');
DEFINE('_BASEUSEROLDPWD', '古いパスワード:');
DEFINE('_BASEUSERNEWPWD', '新しいパスワード:');
DEFINE('_BASEUSERNEWPWDAGAIN', '新しいパスワード（確認）:');
DEFINE('_LOGOUT', 'ログアウト');
?>
