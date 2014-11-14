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
DEFINE('_TITLE', 'Forensics Console ' . $BASE_installID);
DEFINE('_FRMLOGIN', 'Login:');
DEFINE('_FRMPWD', 'Kata Sandi:');
DEFINE('_SOURCE', 'Sumber');
DEFINE('_SOURCENAME', 'Nama Sumber');
DEFINE('_DEST', 'Sasaran');
DEFINE('_DESTNAME', 'Nama Sasaran');
DEFINE('_SORD', 'Sumber atau Sasaran');
DEFINE('_EDIT', 'Edit');
DEFINE('_DELETE', 'Hapus');
DEFINE('_ID', 'ID');
DEFINE('_NAME', 'Nama');
DEFINE('_INTERFACE', 'Interface');
DEFINE('_FILTER', 'Filter');
DEFINE('_DESC', 'Deskripsi');
DEFINE('_LOGIN', 'Login');
DEFINE('_ROLEID', 'ID jenis pengguna');
DEFINE('_ENABLED', 'Aktif');
DEFINE('_SUCCESS', 'Berhasil');
DEFINE('_SENSOR', 'Sensor');
DEFINE('_SENSORS', 'Sensors'); //NEW
DEFINE('_SIGNATURE', 'Nama Alarem');
DEFINE('_TIMESTAMP', 'Waktu');
DEFINE('_NBSOURCEADDR', 'Alamat Sumber');
DEFINE('_NBDESTADDR', 'Alamat Sasaran');
DEFINE('_NBLAYER4', 'Protokol Lapisan&nbsp;4&nbsp;');
DEFINE('_PRIORITY', 'Prioritas');
DEFINE('_EVENTTYPE', 'jenis peristiwa');
DEFINE('_JANUARY', 'Januari');
DEFINE('_FEBRUARY', 'Februari');
DEFINE('_MARCH', 'Maret');
DEFINE('_APRIL', 'April');
DEFINE('_MAY', 'Mei');
DEFINE('_JUNE', 'Juni');
DEFINE('_JULY', 'Juli');
DEFINE('_AUGUST', 'Augustus');
DEFINE('_SEPTEMBER', 'September');
DEFINE('_OCTOBER', 'Oktober');
DEFINE('_NOVEMBER', 'November');
DEFINE('_DECEMBER', 'Desember');
DEFINE('_LAST', 'terakhir');
DEFINE('_FIRST', 'First'); //NEW
DEFINE('_TOTAL', 'Total'); //NEW
DEFINE('_ALERT', 'Alarem');
DEFINE('_ADDRESS', 'Alamat');
DEFINE('_UNKNOWN', 'tak diketahui');
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
DEFINE('_HOME', 'Home');
DEFINE('_SEARCH', 'Cari');
DEFINE('_AGMAINT', 'Pemel. Kelompok Alarem (AG)');
DEFINE('_USERPREF', 'Preferensi Pengguna');
DEFINE('_CACHE', 'Cache & Status');
DEFINE('_ADMIN', 'Administrasi');
DEFINE('_GALERTD', 'Gambarkan Data Alarem');
DEFINE('_GALERTDT', 'Gambarkan Waktu Alarem');
DEFINE('_USERMAN', 'Pengelolaan Pengguna');
DEFINE('_LISTU', 'Tampilkan Pengguna');
DEFINE('_CREATEU', 'Ciptakan Pengguna');
DEFINE('_ROLEMAN', 'Pengelolaan Jenis Pengguna');
DEFINE('_LISTR', 'Tampilkan Jenis Pengguna');
DEFINE('_CREATER', 'Ciptakan Jenis Pengguna');
DEFINE('_LISTALL', 'Tampilkan Semua');
DEFINE('_CREATE', 'Ciptakan');
DEFINE('_VIEW', 'Tampilkan');
DEFINE('_CLEAR', 'Kosongkan');
DEFINE('_LISTGROUPS', 'Pandangan Kelompok');
DEFINE('_CREATEGROUPS', 'Ciptakan Kelompok');
DEFINE('_VIEWGROUPS', 'Tampilan Kelompok');
DEFINE('_EDITGROUPS', 'Edit Kelompok');
DEFINE('_DELETEGROUPS', 'Hapus Kelompok');
DEFINE('_CLEARGROUPS', 'Kosongkan Kelompok');
DEFINE('_CHNGPWD', 'Ganti kata sandi');
DEFINE('_DISPLAYU', 'Tampilkan pengguna');
//base_footer.php
DEFINE('_FOOTER', '( oleh <A class="largemenuitem" href="mailto:base@secureideas.net">Kevin Johnson</A> dan kelompok <A class="largemenuitem" href="http://sourceforge.net/project/memberlist.php?group_id=103348">projek BASE</A><BR>Diciptakan dari sumber ACID oleh Roman Danyliw )');
//index.php --Log in Page
DEFINE('_LOGINERROR', 'Nama pengguna atau kata sandi salah!<br>Silakan dicoba ulang');
// base_main.php
DEFINE('_MOSTRECENT', 'Terbaru, ');
DEFINE('_MOSTFREQUENT', 'Paling sering, ');
DEFINE('_ALERTS', ' alarem:');
DEFINE('_ADDRESSES', ' Alamat');
DEFINE('_ANYPROTO', 'Semua Protokol');
DEFINE('_UNI', 'unik');
DEFINE('_LISTING', 'seluruh');
DEFINE('_TALERTS', 'Alarem hari ini: ');
DEFINE('_SOURCEIP', 'Source IP'); //NEW
DEFINE('_DESTIP', 'Destination IP'); //NEW
DEFINE('_L24ALERTS', 'Alarem dalam 24 jam terakhir: ');
DEFINE('_L72ALERTS', 'Alarem dalam 72 jam terakhir: ');
DEFINE('_UNIALERTS', ' Alarem Unik');
DEFINE('_LSOURCEPORTS', 'Port sumber terakhir: ');
DEFINE('_LDESTPORTS', 'Port sasaran terakhir: ');
DEFINE('_FREGSOURCEP', 'Port sumber seringkali: ');
DEFINE('_FREGDESTP', 'Port sasaran seringkali: ');
DEFINE('_QUERIED', 'Ditampilkan pada');
DEFINE('_DATABASE', 'Database:');
DEFINE('_SCHEMAV', 'Versi skema:');
DEFINE('_TIMEWIN', 'Jarak Waktu:');
DEFINE('_NOALERTSDETECT', 'Alarem tidak ditemukan');
DEFINE('_USEALERTDB', 'Use Alert Database'); //NEW
DEFINE('_USEARCHIDB', 'Use Archive Database'); //NEW
DEFINE('_TRAFFICPROBPRO', 'Traffic Profile by Protocol'); //NEW
//base_auth.inc.php
DEFINE('_ADDEDSF', 'Sukses Ditambahkan');
DEFINE('_NOPWDCHANGE', 'Gagal merubah kata sandi Anda: ');
DEFINE('_NOUSER', 'Pengguna tidak ditemukan!');
DEFINE('_OLDPWD', 'Sebuah kata sandi lama digunakan yang tidak cocok dengan catatan!');
DEFINE('_PWDCANT', 'Gagal merubah kata sandi Anda: ');
DEFINE('_PWDDONE', 'Kata sandi Anda telah diubah!');
DEFINE('_ROLEEXIST', 'Jenis ini sudah ada');
DEFINE('_ROLEIDEXIST', 'Jenis pengguna ini sudah ada');
DEFINE('_ROLEADDED', 'Sukses menambah jenis');
//base_roleadmin.php
DEFINE('_ROLEADMIN', 'Administrasi jenis BASE');
DEFINE('_FRMROLEID', 'Jenis ID pengguna:');
DEFINE('_FRMROLENAME', 'Nama jenis pengguna:');
DEFINE('_FRMROLEDESC', 'Deskripsi:');
DEFINE('_UPDATEROLE', 'Update Role'); //NEW
//base_useradmin.php
DEFINE('_USERADMIN', 'Administrasi pengguna BASE');
DEFINE('_FRMFULLNAME', 'Nama Lengkap:');
DEFINE('_FRMROLE', 'Jenis Pengguna:');
DEFINE('_FRMUID', 'ID Pengguna:');
DEFINE('_SUBMITQUERY', 'Submit Query'); //NEW
DEFINE('_UPDATEUSER', 'Update User'); //NEW
//admin/index.php
DEFINE('_BASEADMIN', 'Administrasi BASE');
DEFINE('_BASEADMINTEXT', 'Silakan gunakan salah satu pilihan di sebelah kiri.');
//base_action.inc.php
DEFINE('_NOACTION', 'Tidak ada aksi yang ditetapkan untuk alarem');
DEFINE('_INVALIDACT', ' adalah aksi yang tidak berlaku');
DEFINE('_ERRNOAG', 'Gagal menambah alarem sehubungan KA belum ditetapkan');
DEFINE('_ERRNOEMAIL', 'Gagal mengirim e-mail sehubungan alamat e-mail belum disebutkan');
DEFINE('_ACTION', 'TINDAKAN');
DEFINE('_CONTEXT', 'konteks');
DEFINE('_ADDAGID', 'Tambahkan pada AG (menurut ID)');
DEFINE('_ADDAG', 'Tambahkan AG baru');
DEFINE('_ADDAGNAME', 'Tambahkan ke AG (menurut nama)');
DEFINE('_CREATEAG', 'Ciptakan AG (menurut nama)');
DEFINE('_CLEARAG', 'Hapus dari AG');
DEFINE('_DELETEALERT', 'Hapus alarem');
DEFINE('_EMAILALERTSFULL', 'Kirim alarem melalui e-mail (semua)');
DEFINE('_EMAILALERTSSUMM', 'Kirim alarem melalui e-mail (ringkasan)');
DEFINE('_EMAILALERTSCSV', 'Kirim alarem melalui e-mail  (csv)');
DEFINE('_ARCHIVEALERTSCOPY', 'Arsipkan alarem (salinkan)');
DEFINE('_ARCHIVEALERTSMOVE', 'Arsipkan alarem (pindahkan)');
DEFINE('_IGNORED', 'Diabaikan ');
DEFINE('_DUPALERTS', ' alarem ganda');
DEFINE('_ALERTSPARA', ' alarem');
DEFINE('_NOALERTSSELECT', 'Alarem tidak dipilihkan atau');
DEFINE('_NOTSUCCESSFUL', 'tidak sukses');
DEFINE('_ERRUNKAGID', 'Penetapan AG tidak diketahui (AG mungkin tidak ada)');
DEFINE('_ERRREMOVEFAIL', 'Gagal menghapus KA baru');
DEFINE('_GENBASE', 'Dihasilkan oleh BASE');
DEFINE('_ERRNOEMAILEXP', 'KESALAHAN PADA EKSPOR: Gagal mengirim alarem yang diekspor kepada');
DEFINE('_ERRNOEMAILPHP', 'Periksalah kembali konfigurasi Mail pada PHP.');
DEFINE('_ERRDELALERT', 'Gagal menghapus alarem');
DEFINE('_ERRARCHIVE', 'Gagal mengarsip:');
DEFINE('_ERRMAILNORECP', 'MAIL ERROR: Penerima tidak ditentukan');
//base_cache.inc.php
DEFINE('_ADDED', '<BR>Ditambahkan ');
DEFINE('_HOSTNAMESDNS', ' nama host pada cache IP DNS');
DEFINE('_HOSTNAMESWHOIS', ' nama host pada cache Whois');
DEFINE('_ERRCACHENULL', 'KESALAHAN pada Caching: Baris daftar peristiwa TIDAK DITEMUKAN?');
DEFINE('_ERRCACHEERROR', 'KESALAHAN pada CACHING PERISTIWA:');
DEFINE('_ERRCACHEUPDATE', 'Gagal meng-update cache peristiwa');
DEFINE('_ALERTSCACHE', ' alarem dalam cache peristiwa<BR>');
//base_db.inc.php
DEFINE('_ERRSQLTRACE', 'Gagal membuka file SQL catatan');
DEFINE('_ERRSQLCONNECT', 'Koneksi ke DB gagal :');
DEFINE('_ERRSQLCONNECTINFO', '<P>Mohon periksa variabel koneksi ke DB pada file <I>base_conf.php</I> 
              <PRE>
               = $alert_dbname   : nama database MySQL untuk menyimpan alarem 
               = $alert_host     : nama host yang menyimpan database
               = $alert_port     : nama port yang digunakan oleh database
               = $alert_user     : nama pengguna pada database
               = $alert_password : kata sandi untuk nama pengguna
              </PRE>
              <P>');
DEFINE('_ERRSQLPCONNECT', 'Gagal (p)menghubungi DB :');
DEFINE('_ERRSQLDB', 'Database ERROR:');
DEFINE('_DBALCHECK', 'Periksa libari niskala DB pada');
DEFINE('_ERRSQLDBALLOAD1', '<P><B>Kesalahan mengangkat libari niskala DB: </B> dari ');
DEFINE('_ERRSQLDBALLOAD2', '<P>Periksalah variabel libari niskala DB <CODE>$DBlib_path</CODE> pada <CODE>base_conf.php</CODE>
            <P>
            Libari database yang digunakan sebagai syarat pada saat ini adalah ADODB, yang dapat di-download
            melalui <A HREF="http://adodb.sourceforge.net/">http://adodb.sourceforge.net/</A>');
DEFINE('_ERRSQLDBTYPE', 'Jenis database yang ditetapkan tidak valid');
DEFINE('_ERRSQLDBTYPEINFO1', 'Variabel <CODE>\$DBtype</CODE> pada <CODE>base_conf.php</CODE> ditetapkan pada jenis database yang tidak dikenal. ');
DEFINE('_ERRSQLDBTYPEINFO2', 'Hanya jenis database yang berikut didukung oleh BASE: <PRE>
                MySQL         : \'mysql\'
                PostgreSQL    : \'postgres\'
                MS SQL Server : \'mssql\'
                Oracle        : \'oci8\'
             </PRE>');
//base_log_error.inc.php
DEFINE('_ERRBASEFATAL', 'BASE KESALAHAN FATAL:');
//base_log_timing.inc.php
DEFINE('_LOADEDIN', 'Ditampilkan dalam waktu');
DEFINE('_SECONDS', 'detik');
//base_net.inc.php
DEFINE('_ERRRESOLVEADDRESS', 'Gagal menguraikan alamat');
//base_output_query.inc.php
DEFINE('_QUERYRESULTSHEADER', 'Hasil pencarian kepala berita');
//base_signature.inc.php
DEFINE('_ERRSIGNAMEUNK', 'Penandaan Paraf tidak dikenal');
DEFINE('_ERRSIGPROIRITYUNK', 'Prioritas Paraf tidak dikenal');
DEFINE('_UNCLASS', 'belum dihubungkan');
//base_state_citems.inc.php
DEFINE('_DENCODED', 'data dikode sebagai');
DEFINE('_NODENCODED', '(tanpa penyalinan data, anggap kriteria sebagai Encoding asal)');
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
DEFINE('_PHPERRORCSESSION', 'KESALAHAN PHP: Ditemukan sebuah definisi sidang pemakaian (pengguna).  BASE tidak di-set untuk mengunakan \'Custom Handler\' ini. Tetapkan <CODE>use_user_session=1</CODE> pada <CODE>base_conf.php</CODE>');
DEFINE('_PHPERRORCSESSIONCODE', 'KESALAHAN PHP: Sebuah definisi sidang pemakaian pengguna telah ditetapkan, tetapi \'Handler Code\' yang ditetapkan pada <CODE>user_session_path</CODE> tidak berlaku.');
DEFINE('_PHPERRORCSESSIONVAR', 'KESALAHAN PHP: Sebuah definisi sidang pemakaian pengguna telah ditetapkan, tetapi implementasi dari Handler ini tidak ditetapkan pada BASE. Bilamana sebuah  Handler sidang pemakaian khusus diinginkan mohon tepatkan variabel <CODE>user_session_path</CODE> pada <CODE>base_conf.php</CODE>.');
DEFINE('_PHPSESSREG', 'Sidang telah didaftarkan');
//base_state_criteria.inc.php
DEFINE('_REMOVE', 'Menghapus');
DEFINE('_FROMCRIT', 'dari kriteria');
DEFINE('_ERRCRITELEM', 'Elemen kriteria tidak berlaku');
//base_state_query.inc.php
DEFINE('_VALIDCANNED', 'Daftar pencarian berlaku');
DEFINE('_DISPLAYING', 'Tampilkan');
DEFINE('_DISPLAYINGTOTAL', 'Tampilan alarem %d s/d. %d dari jumlah %s');
DEFINE('_NOALERTS', 'Tidak ada alarem ditemukan.');
DEFINE('_QUERYRESULTS', 'Halaman Pencarian');
DEFINE('_QUERYSTATE', 'Status Pencarian');
DEFINE('_DISPACTION', '{ action }'); //NEW
//base_ag_common.php
DEFINE('_ERRAGNAMESEARCH', 'Nama kelompok alarem yang dirinci tidak berlaku.  Silakan dicoba ulang!');
DEFINE('_ERRAGNAMEEXIST', 'Kelompok alarem yang dirinci tidak ada.');
DEFINE('_ERRAGIDSEARCH', 'Pencarian ID kelompok alarem yang dirinci tidak valid.  Silakan dicoba ulang!');
DEFINE('_ERRAGLOOKUP', 'Galal pada pencarian ID kelompok alarem');
DEFINE('_ERRAGINSERT', 'Gagal menyisipkan kelompok alarem baru');
//base_ag_main.php
DEFINE('_AGMAINTTITLE', 'Pemeliharaan kelompok alarem (AG)');
DEFINE('_ERRAGUPDATE', 'Gagal meng-update kelompok alarem (AG)');
DEFINE('_ERRAGPACKETLIST', 'Gagal menghapus daftar paket untuk kelompok alarem (AG):');
DEFINE('_ERRAGDELETE', 'Gagal menghapus kelompok alarem (AG)');
DEFINE('_AGDELETE', 'DIHAPUS dengan sukses');
DEFINE('_AGDELETEINFO', 'informasi dihapus');
DEFINE('_ERRAGSEARCHINV', 'Kriteria pencarian tidak berlaku. Mohon dicoba ulang!');
DEFINE('_ERRAGSEARCHNOTFOUND', 'Kelompok alarem (AG) dengan kriteria yang dimaksud tidak ditemukan.');
DEFINE('_NOALERTGOUPS', 'Kelompok Alarem (AG) tidak ditemukan');
DEFINE('_NUMALERTS', '# Alarem');
DEFINE('_ACTIONS', 'Pilihan');
DEFINE('_NOTASSIGN', 'belum digolongkan');
DEFINE('_SAVECHANGES', 'Save Changes'); //NEW
DEFINE('_CONFIRMDELETE', 'Confirm Delete'); //NEW
DEFINE('_CONFIRMCLEAR', 'Confirm Clear'); //NEW
//base_common.php
DEFINE('_PORTSCAN', 'Lalu-Lintas Portscan');
//base_db_common.php
DEFINE('_ERRDBINDEXCREATE', 'Gagal menciptakan indeks untuk');
DEFINE('_DBINDEXCREATE', 'Sukses menciptakan indeks untuk');
DEFINE('_ERRSNORTVER', 'Mungkin Anda menggunakan versi yang lama.  Hanya database alarem yang diciptakan oleh Snort 1.7-beta0 atau lebih baru yang didukung');
DEFINE('_ERRSNORTVER1', 'Database yang digunakan sebagai dasar');
DEFINE('_ERRSNORTVER2', 'rupanya tidak lengkap/tidak berlaku');
DEFINE('_ERRDBSTRUCT1', 'Versi database berlaku tetapi struktur DB BASE');
DEFINE('_ERRDBSTRUCT2', 'tidak ditemukan. Gunakanklah <A HREF="base_db_setup.php">Halaman Setup</A> untuk mengkonfigurasikan dan mengoptimasikan DB.');
DEFINE('_ERRPHPERROR', 'ERROR PHP');
DEFINE('_ERRPHPERROR1', 'Versi tidak kompatibel');
DEFINE('_ERRVERSION', 'Versi');
DEFINE('_ERRPHPERROR2', 'PHP terlalu tua.  Mohon upgrade ke versi 4.0.4 atau lebih baru');
DEFINE('_ERRPHPMYSQLSUP', '<B>Build PHP tidak komplit</B>: <FONT>Dukungan MySQL diperlukan sebagai dasar untuk  
               membaca alarem dari database. Dukungan MySQL tidak di-build dalam PHP. 
               Mohon kompile ulang PHP dengan libari yang diperlukan (<CODE>--with-mysql</CODE>)</FONT>');
DEFINE('_ERRPHPPOSTGRESSUP', '<B>Build PHP tidak komplit</B>: <FONT>Dukungan PostgreSQL diperlukan sebagai dasar untuk 
               membaca alarem dari database. Dukungan PostgreSQL tidak di-build dalam PHP.   
               Mohon kompile ulang PHP dengan libari yang diperlukan (<CODE>--with-pgsql</CODE>)</FONT>');
DEFINE('_ERRPHPMSSQLSUP', '<B>Build PHP tidak komplit</B>: <FONT>Dukungan MS SQL Server diperlukan sebagai dasar untuk 
                   membaca alarem dari database. Dukungan MS SQL Server tidak di-build dalam PHP.  
                   Mohon kompile ulang PHP dengan libari yang diperlukan (<CODE>--enable-mssql</CODE>)</FONT>');
DEFINE('_ERRPHPORACLESUP', '<B>PHP build incomplete</B>: <FONT>the prerequisite Oracle support required to 
                   read the alert database was not built into PHP.  
                   Please recompile PHP with the necessary library (<CODE>--with-oci8</CODE>)</FONT>');
//base_graph_form.php
DEFINE('_CHARTTITLE', 'Judul Grafik:');
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
DEFINE('_CHRTTYPEHOUR', 'Waktu (jam) vs. Jumlah Alarem');
DEFINE('_CHRTTYPEDAY', 'Waktu (hari) vs. Jumlah Alarem');
DEFINE('_CHRTTYPEWEEK', 'Waktu (minggu) vs. Jumlah Alarem');
DEFINE('_CHRTTYPEMONTH', 'Waktu (bulan) vs. Jumlah Alarem');
DEFINE('_CHRTTYPEYEAR', 'Waktu (tahun) vs. Jumlah Alarem');
DEFINE('_CHRTTYPESRCIP', 'Alamat IP sumber vs. Jumlah Alarem');
DEFINE('_CHRTTYPEDSTIP', 'Alamat IP sasaran vs. Jumlah Alarem');
DEFINE('_CHRTTYPEDSTUDP', 'Port UDP sasaran vs. Jumlah Alarem');
DEFINE('_CHRTTYPESRCUDP', 'Port UDP sumber vs. Jumlah Alarem');
DEFINE('_CHRTTYPEDSTPORT', 'Port TCP sasaran vs. Jumlah Alarem');
DEFINE('_CHRTTYPESRCPORT', 'Port TCP sumber vs. Jumlah Alarem');
DEFINE('_CHRTTYPESIG', 'Klasifikasi Sig. vs. Jumlah Alarem');
DEFINE('_CHRTTYPESENSOR', 'Sensor vs. Jumlah Alarem');
DEFINE('_CHRTBEGIN', 'Grafik dimulai:');
DEFINE('_CHRTEND', 'Grafik diakhiri:');
DEFINE('_CHRTDS', 'Sumber Data:');
DEFINE('_CHRTX', 'Koordinat X');
DEFINE('_CHRTY', 'Koordinat Y');
DEFINE('_CHRTMINTRESH', 'Nilai minimal');
DEFINE('_CHRTROTAXISLABEL', 'Memutarkan tanda koordinat (90 derajat)');
DEFINE('_CHRTSHOWX', 'Tampilkan garis pada koordinat X');
DEFINE('_CHRTDISPLABELX', 'Tampilkan nama tingkatan pada koordinat X setiap');
DEFINE('_CHRTDATAPOINTS', 'angka data');
DEFINE('_CHRTYLOG', 'Koordinat Y secara logaritma');
DEFINE('_CHRTYGRID', 'Tampilkan garis pada koordinat Y');
//base_graph_main.php
DEFINE('_CHRTTITLE', 'Grafik BASE');
DEFINE('_ERRCHRTNOTYPE', 'Jenis grafik belum ditepatkan');
DEFINE('_ERRNOAGSPEC', 'Kelompok alarem tidak ditepatkan.  Menggunakan semua alarem.');
DEFINE('_CHRTDATAIMPORT', 'Memulai impor data');
DEFINE('_CHRTTIMEVNUMBER', 'Waktu vs. Jumlah Alarem');
DEFINE('_CHRTTIME', 'Waktu');
DEFINE('_CHRTALERTOCCUR', 'Peristiwa Alarem');
DEFINE('_CHRTSIPNUMBER', 'IP sumber vs. Jumlah Alarem');
DEFINE('_CHRTSIP', 'Alamat IP sumber');
DEFINE('_CHRTDIPALERTS', 'IP sasaran vs. Jumlah Alarem');
DEFINE('_CHRTDIP', 'Alamat IP sasaran');
DEFINE('_CHRTUDPPORTNUMBER', 'Port UDP (Sasaran) vs. Jumlah Alarem');
DEFINE('_CHRTDUDPPORT', 'Port UDP sasaran');
DEFINE('_CHRTSUDPPORTNUMBER', 'Port UDP (Sumber) vs. Jumlah Alarem');
DEFINE('_CHRTSUDPPORT', 'Port UDP sumber');
DEFINE('_CHRTPORTDESTNUMBER', 'Port TCP (Sasaran) vs. Jumlah Alarem');
DEFINE('_CHRTPORTDEST', 'Port TCP sasaran');
DEFINE('_CHRTPORTSRCNUMBER', 'Port TCP (Sumber) vs. Jumlah Alarem');
DEFINE('_CHRTPORTSRC', 'Port TCP sumber');
DEFINE('_CHRTSIGNUMBER', 'Klasifikasi Signature vs. Jumlah Alarem');
DEFINE('_CHRTCLASS', 'Klasifikasi');
DEFINE('_CHRTSENSORNUMBER', 'Sensor vs. Jumlah Alarem');
DEFINE('_CHRTHANDLEPERIOD', 'Jangka waktu penanangan bila perlu');
DEFINE('_CHRTDUMP', 'Menerbitkan data ... (hanya tulis setiap');
DEFINE('_CHRTDRAW', 'Menciptakan grafik');
DEFINE('_ERRCHRTNODATAPOINTS', 'Titik data untuk menciptakan grafik tidak ditemukan');
DEFINE('_GRAPHALERTDATA', 'Graph Alert Data'); //NEW
//base_maintenance.php
DEFINE('_MAINTTITLE', 'Pemeliharaan');
DEFINE('_MNTPHP', 'PHP Build:');
DEFINE('_MNTCLIENT', 'KLIEN:');
DEFINE('_MNTSERVER', 'SERVER:');
DEFINE('_MNTSERVERHW', 'SERVER HW:');
DEFINE('_MNTPHPVER', 'VERSI PHP:');
DEFINE('_MNTPHPAPI', 'API PHP:');
DEFINE('_MNTPHPLOGLVL', 'PHP Level Logging:');
DEFINE('_MNTPHPMODS', 'Modul2 yang berjalan:');
DEFINE('_MNTDBTYPE', 'Jenis DB:');
DEFINE('_MNTDBALV', 'Versi Niskala DB:');
DEFINE('_MNTDBALERTNAME', 'Nama DB Alarem:');
DEFINE('_MNTDBARCHNAME', 'Nama DB Arsip:');
DEFINE('_MNTAIC', 'Peristiwa dalam Cache:');
DEFINE('_MNTAICTE', 'Jumlah Peristiwa:');
DEFINE('_MNTAICCE', 'Peristiwa dalam Cache:');
DEFINE('_MNTIPAC', 'Cache alamat IP');
DEFINE('_MNTIPACUSIP', 'Jml. IP sumber unik:');
DEFINE('_MNTIPACDNSC', 'DNS dalam Cache:');
DEFINE('_MNTIPACWC', 'Whois dalam Cache:');
DEFINE('_MNTIPACUDIP', 'Jml. IP sasaran unik:');
//base_qry_alert.php
DEFINE('_QAINVPAIR', 'Pasangan (sid,cid) yg. tdk. berlaku');
DEFINE('_QAALERTDELET', 'Alarem TERHAPUS');
DEFINE('_QATRIGGERSIG', 'Deskripsi Alarem');
DEFINE('_QANORMALD', 'Normal Display'); //NEW
DEFINE('_QAPLAIND', 'Plain Display'); //NEW
DEFINE('_QANOPAYLOAD', 'Fast logging used so payload was discarded'); //NEW
//base_qry_common.php
DEFINE('_QCSIG', 'Signatur');
DEFINE('_QCIPADDR', 'Alamat IP');
DEFINE('_QCIPFIELDS', 'Field IP');
DEFINE('_QCTCPPORTS', 'Port TCP');
DEFINE('_QCTCPFLAGS', 'Flag TCP');
DEFINE('_QCTCPFIELD', 'Field TCP');
DEFINE('_QCUDPPORTS', 'Port UDP');
DEFINE('_QCUDPFIELDS', 'Field UDP');
DEFINE('_QCICMPFIELDS', 'Field ICMP');
DEFINE('_QCDATA', 'Data');
DEFINE('_QCERRCRITWARN', 'Peringatan kriteria:');
DEFINE('_QCERRVALUE', 'Nilai dari');
DEFINE('_QCERRFIELD', 'Field dari');
DEFINE('_QCERROPER', 'Operator dari');
DEFINE('_QCERRDATETIME', 'Nilai tanggal/waktu dari');
DEFINE('_QCERRPAYLOAD', 'Nilai Payload dari');
DEFINE('_QCERRIP', 'Alamat IP dari');
DEFINE('_QCERRIPTYPE', 'Alamat IP jenis');
DEFINE('_QCERRSPECFIELD', ' dimasukkan pada field protokol tetapi field yang bersangkutan tidak ditetapkan.');
DEFINE('_QCERRSPECVALUE', 'ditetapkan sebagai dasar pencarian tetapi nilai untuk yang dicarikan belum ditentukan.');
DEFINE('_QCERRBOOLEAN', 'Mohon menggunakan operator boolean (seperti AND, OR) bila memakai lebih dari satu protokol.');
DEFINE('_QCERRDATEVALUE', 'merincikan, bahwa sebuah tanggal/waktu diminatkan tetapi belum ada nilai ditetapkan.');
DEFINE('_QCERRINVHOUR', '(Nilai jam tidak berlaku) Kriteria tanggal untuk waktu yang dicari belum ditetapkan.');
DEFINE('_QCERRDATECRIT', 'merincikan, bahwa sebuah tanggal/waktu dicarikan tetapi belum ada nilai yang ditetapkan.');
DEFINE('_QCERROPERSELECT', 'telah ditetapkan tetapi operator belum dipilihkan.');
DEFINE('_QCERRDATEBOOL', 'Lebih dari satu kriteria tanggal/waktu ditetapkan tanpa menggunakan operator boolean (seperti AND, OR).');
DEFINE('_QCERRPAYCRITOPER', 'telah ditetapkan sebagai kriteria Payload tanpa menggunakan operator yang diperlukan (seperti has, has not).');
DEFINE('_QCERRPAYCRITVALUE', 'merincikan, bahwa Payload diartikan sebagai kriteria tetapi nilai yang bersangkutan belum ditetapkan.');
DEFINE('_QCERRPAYBOOL', 'Lebih dari satu kriteria Payload ditetapkan tanpa menggunakan operator boolean (seperti AND, OR).');
DEFINE('_QCMETACRIT', 'Kriteria Meta');
DEFINE('_QCIPCRIT', 'Kriteria IP');
DEFINE('_QCPAYCRIT', 'Kriteria Payload');
DEFINE('_QCTCPCRIT', 'Kriteria TCP');
DEFINE('_QCUDPCRIT', 'Kriteria UDP');
DEFINE('_QCICMPCRIT', 'Kriteria ICMP');
DEFINE('_QCLAYER4CRIT', 'Layer 4 Criteria'); //NEW
DEFINE('_QCERRINVIPCRIT', 'Kriteria alamat IP tidak valid');
DEFINE('_QCERRCRITADDRESSTYPE', 'telah ditetapkan sebagai kriteria tetapi jenis alamat (seperti sumber, sasaran) belum ditentukan.');
DEFINE('_QCERRCRITIPADDRESSNONE', 'menunjukkan, bahwa sebuah alamat IP dimaksud sebagai kriteria pencarian tetapi alamat IP yang bersangkutan belum ditetapkan.');
DEFINE('_QCERRCRITIPADDRESSNONE1', 'dipilih (pada #');
DEFINE('_QCERRCRITIPIPBOOL', 'Lebih dari satu kriteria alamat IP dimasukkan tanpa menggunakan operator boolean (seperti AND, OR)');
//base_qry_form.php
DEFINE('_QFRMSORTORDER', 'Urutan');
DEFINE('_QFRMSORTNONE', 'none'); //NEW
DEFINE('_QFRMTIMEA', 'waktu (menaik)');
DEFINE('_QFRMTIMED', 'waktu (menurun)');
DEFINE('_QFRMSIG', 'nama alarem');
DEFINE('_QFRMSIP', 'IP sumber');
DEFINE('_QFRMDIP', 'IP sasaran');
//base_qry_sqlcalls.php
DEFINE('_QSCSUMM', 'Rekapitulasi Statistik');
DEFINE('_QSCTIMEPROF', 'Riwayat waktu');
DEFINE('_QSCOFALERTS', 'dari alarem');
//base_stat_alerts.php
DEFINE('_ALERTTITLE', 'Daftar Alarem');
//base_stat_common.php
DEFINE('_SCCATEGORIES', 'Kategori:');
DEFINE('_SCSENSORTOTAL', 'Sensor/Jumlah:');
DEFINE('_SCTOTALNUMALERTS', 'Jumlah alarem:');
DEFINE('_SCSRCIP', 'Alamat IP sumber:');
DEFINE('_SCDSTIP', 'Alamat IP sasaran:');
DEFINE('_SCUNILINKS', 'IP unik berhubungan');
DEFINE('_SCSRCPORTS', 'Port sumber: ');
DEFINE('_SCDSTPORTS', 'Port sasaran: ');
DEFINE('_SCSENSORS', 'Sensor');
DEFINE('_SCCLASS', 'Klasifikasi');
DEFINE('_SCUNIADDRESS', 'Alamat unik: ');
DEFINE('_SCSOURCE', 'Sumber');
DEFINE('_SCDEST', 'Sasaran');
DEFINE('_SCPORT', 'Port');
//base_stat_ipaddr.php
DEFINE('_PSEVENTERR', 'ERROR PADA PERISTIWA PORTSCAN: ');
DEFINE('_PSEVENTERRNOFILE', 'File pada variabel \$portscan_file belum ditetapkan.');
DEFINE('_PSEVENTERROPENFILE', 'Gagal membuka file tentang peristiwa Portscan');
DEFINE('_PSDATETIME', 'Tanggal/Waktu');
DEFINE('_PSSRCIP', 'IP Sumber');
DEFINE('_PSDSTIP', 'IP Sasaran');
DEFINE('_PSSRCPORT', 'Port Sumber');
DEFINE('_PSDSTPORT', 'Port Sasaran');
DEFINE('_PSTCPFLAGS', 'Flag TCP');
DEFINE('_PSTOTALOCC', 'Jumlah<BR> Peristiwa');
DEFINE('_PSNUMSENSORS', 'Jml. Sensor');
DEFINE('_PSFIRSTOCC', 'Peristiwa<BR> Pertama');
DEFINE('_PSLASTOCC', 'Peristiwa<BR> Terakhir');
DEFINE('_PSUNIALERTS', 'Alarem Unik');
DEFINE('_PSPORTSCANEVE', 'Peristiwa Portscan');
DEFINE('_PSREGWHOIS', 'Periksa pendaftar (whois) di');
DEFINE('_PSNODNS', 'pemecahan DNS tidak dilakukan');
DEFINE('_PSNUMSENSORSBR', 'Jumlah <BR>Sensor');
DEFINE('_PSOCCASSRC', 'Peristiwa <BR>pada sumber');
DEFINE('_PSOCCASDST', 'Peristiwa <BR>pada sasaran');
DEFINE('_PSWHOISINFO', 'Informasi Whois');
DEFINE('_PSTOTALHOSTS', 'Total Hosts Scanned'); //NEW
DEFINE('_PSDETECTAMONG', '%d unique alerts detected among %d alerts on %s'); //NEW
DEFINE('_PSALLALERTSAS', 'all alerts with %s/%s as'); //NEW
DEFINE('_PSSHOW', 'show'); //NEW
DEFINE('_PSEXTERNAL', 'external'); //NEW
//base_stat_iplink.php
DEFINE('_SIPLTITLE', 'Link IP');
DEFINE('_SIPLSOURCEFGDN', 'FQDN Sumber');
DEFINE('_SIPLDESTFGDN', 'FQDN Sasaran');
DEFINE('_SIPLDIRECTION', 'arah');
DEFINE('_SIPLPROTO', 'Protokol');
DEFINE('_SIPLUNIDSTPORTS', 'Port Sasaran Unik');
DEFINE('_SIPLUNIEVENTS', 'Peristiwa Unik');
DEFINE('_SIPLTOTALEVENTS', 'Jumlah Peristiwa');
//base_stat_ports.php
DEFINE('_UNIQ', 'Unik');
DEFINE('_DSTPS', 'Port Sasaran');
DEFINE('_SRCPS', 'Port Sumber');
DEFINE('_OCCURRENCES', 'Occurrences'); //NEW
//base_stat_sensor.php
DEFINE('SPSENSORLIST', 'Daftar Sensor');
//base_stat_time.php
DEFINE('_BSTTITLE', 'Profil waktu Alarem');
DEFINE('_BSTTIMECRIT', 'Kriteria Waktu');
DEFINE('_BSTERRPROFILECRIT', '<FONT><B>Penggolongan satuan belum ditetapkan!</B>  Silakan pilih "jam", "hari" atau "bulan" terlebih dahulu.</FONT>');
DEFINE('_BSTERRTIMETYPE', '<FONT><B>Jenis parameter tentang waktu belum ditetapkan!</B>  Silakan pilih "pada" untuk suatu hari tertentu atau pilih "antara" untuk tentukan jangka waktu tertentu.</FONT>');
DEFINE('_BSTERRNOYEAR', '<FONT><B>Parameter Tahun belum ditetapkan!</B></FONT>');
DEFINE('_BSTERRNOMONTH', '<FONT><B>Parameter Bulan belum ditetapkan!</B></FONT>');
DEFINE('_BSTERRNODAY', '<FONT><B>Parameter Hari belum ditetapkan!</B></FONT>');
DEFINE('_BSTPROFILEBY', 'Profile by'); //NEW
DEFINE('_TIMEON', 'on'); //NEW
DEFINE('_TIMEBETWEEN', 'between'); //NEW
DEFINE('_PROFILEALERT', 'Profile Alert'); //NEW
//base_stat_uaddr.php
DEFINE('_UNISADD', 'Alamat Sumber unik');
DEFINE('_SUASRCIP', 'Sumber alamat IP');
DEFINE('_SUAERRCRITADDUNK', 'ERROR PADA CRITERIA: jenis alamat tidak dikenal -- menggunakan alamat sasaran');
DEFINE('_UNIDADD', 'Alamat sasaran unik');
DEFINE('_SUADSTIP', 'Alamat IP sasaran');
DEFINE('_SUAUNIALERTS', 'Alarem&nbsp;Unik');
DEFINE('_SUASRCADD', 'Sumber&nbsp;Alamat');
DEFINE('_SUADSTADD', 'Sasaran&nbsp;Alamat');
//base_user.php
DEFINE('_BASEUSERTITLE', 'Preferensi pengguna BASE');
DEFINE('_BASEUSERERRPWD', 'Kata Sandi kosong tidak diperbolehkan atau kedua kata sandi tidak sama!');
DEFINE('_BASEUSEROLDPWD', 'Kata Sandi lama:');
DEFINE('_BASEUSERNEWPWD', 'Kata Sandi baru:');
DEFINE('_BASEUSERNEWPWDAGAIN', 'Ulangi kata sandi baru:');
DEFINE('_LOGOUT', 'Logout');
?>
