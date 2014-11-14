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
DEFINE('_LOCALESTR1', 'tur_TUR.ISO8859-9');
DEFINE('_LOCALESTR2', 'tur_TUR.utf-8');
DEFINE('_LOCALESTR3', 'turkish');
DEFINE('_STRFTIMEFORMAT', '%a %B %d, %Y %H:%M:%S'); //see strftime() sintax
//common phrases
DEFINE('_CHARSET', 'iso-8859-9');
DEFINE('_TITLE', 'Forensics Console ' . $BASE_installID);
DEFINE('_FRMLOGIN', 'Oturum Aç:');
DEFINE('_FRMPWD', 'Parola:');
DEFINE('_SOURCE', 'Kaynak');
DEFINE('_SOURCENAME', 'Kaynak Adý');
DEFINE('_DEST', 'Varýþ');
DEFINE('_DESTNAME', 'Varýþ Adý');
DEFINE('_SORD', 'Kaynak veya Varýþ');
DEFINE('_EDIT', 'Düzenle');
DEFINE('_DELETE', 'Sil');
DEFINE('_ID', 'ID');
DEFINE('_NAME', 'Ad');
DEFINE('_INTERFACE', 'Arabirim');
DEFINE('_FILTER', 'Süzgeç');
DEFINE('_DESC', 'Betimleme');
DEFINE('_LOGIN', 'Oturum Aç');
DEFINE('_ROLEID', 'Rol ID');
DEFINE('_ENABLED', 'Seçilir Kýlýnmýþ');
DEFINE('_SUCCESS', 'Baþarýlý');
DEFINE('_SENSOR', 'Algýlayýcý');
DEFINE('_SENSORS', 'Algýlayýcýlar');
DEFINE('_SIGNATURE', 'Ýmza');
DEFINE('_TIMESTAMP', 'Zaman Damgasý');
DEFINE('_NBSOURCEADDR', 'Kaynak Adresi');
DEFINE('_NBDESTADDR', 'Varýþ Adresi');
DEFINE('_NBLAYER4', '4. Katman Protokolü');
DEFINE('_PRIORITY', 'Öncelik');
DEFINE('_EVENTTYPE', 'olay türü');
DEFINE('_JANUARY', 'Ocak');
DEFINE('_FEBRUARY', 'Þubat');
DEFINE('_MARCH', 'Mart');
DEFINE('_APRIL', 'Nisan');
DEFINE('_MAY', 'Mayýs');
DEFINE('_JUNE', 'Haziran');
DEFINE('_JULY', 'Temmuz');
DEFINE('_AUGUST', 'Aðustos');
DEFINE('_SEPTEMBER', 'Eylül');
DEFINE('_OCTOBER', 'Ekim');
DEFINE('_NOVEMBER', 'Kasým');
DEFINE('_DECEMBER', 'Aralýk');
DEFINE('_LAST', 'Son');
DEFINE('_FIRST', 'Ýlk');
DEFINE('_TOTAL', 'Toplam');
DEFINE('_ALERT', 'Uyarý');
DEFINE('_ADDRESS', 'Adres');
DEFINE('_UNKNOWN', 'bilinmeyen');
DEFINE('_AND', 'VE');
DEFINE('_OR', 'YA DA');
DEFINE('_IS', 'is');
DEFINE('_ON', 'üzerinde');
DEFINE('_IN', 'içinde');
DEFINE('_ANY', 'herhangibir');
DEFINE('_NONE', 'hiçbiri');
DEFINE('_HOUR', 'Saat');
DEFINE('_DAY', 'Gün');
DEFINE('_MONTH', 'Ay');
DEFINE('_YEAR', 'Yýl');
DEFINE('_ALERTGROUP', 'Uyarý Grubu');
DEFINE('_ALERTTIME', 'Uyarý Zamaný');
DEFINE('_CONTAINS', 'kapsar');
DEFINE('_DOESNTCONTAIN', 'kapsamaz');
DEFINE('_SOURCEPORT', 'kaynak portu');
DEFINE('_DESTPORT', 'varýþ portu');
DEFINE('_HAS', 'sahip');
DEFINE('_HASNOT', 'sahip deðil');
DEFINE('_PORT', 'Port');
DEFINE('_FLAGS', 'Bayraklar');
DEFINE('_MISC', 'Misc');
DEFINE('_BACK', 'Geri');
DEFINE('_DISPYEAR', '{ yýl }');
DEFINE('_DISPMONTH', '{ ay }');
DEFINE('_DISPHOUR', '{ saat }');
DEFINE('_DISPDAY', '{ gün }');
DEFINE('_DISPTIME', '{ zaman }');
DEFINE('_ADDADDRESS', 'Adres EKLE');
DEFINE('_ADDIPFIELD', 'IP Alaný EKLE');
DEFINE('_ADDTIME', 'ZAMAN EKLE');
DEFINE('_ADDTCPPORT', 'TCP Portu EKLE');
DEFINE('_ADDTCPFIELD', 'TCP Alaný EKLE');
DEFINE('_ADDUDPPORT', 'UDP Portu EKLE');
DEFINE('_ADDUDPFIELD', 'UDP Alaný EKLE');
DEFINE('_ADDICMPFIELD', 'ICMP Alaný EKLE');
DEFINE('_ADDPAYLOAD', 'Payload EKLE');
DEFINE('_MOSTFREQALERTS', 'En Sýk Uyarýlar');
DEFINE('_MOSTFREQPORTS', 'En Sýk Portlar');
DEFINE('_MOSTFREQADDRS', 'En Sýk IP adresleri');
DEFINE('_LASTALERTS', 'Son Uyarýlar');
DEFINE('_LASTPORTS', 'Son Portlar');
DEFINE('_LASTTCP', 'Son TCP Uyarýlarý');
DEFINE('_LASTUDP', 'Son UDP Uyarýlarý');
DEFINE('_LASTICMP', 'Son ICMP Uyarýlarý');
DEFINE('_QUERYDB', 'Sorgu DB');
DEFINE('_QUERYDBP', 'Sorgu+DB'); //_QUERYDB 'ye eþit, boþluklar '+' lardýr.
//Bunun gibi bir þey olmasý gerekli: DEFINE('_QUERYDBP',str_replace(" ", "+", _QUERYDB));
DEFINE('_SELECTED', 'Seçilmiþ');
DEFINE('_ALLONSCREEN', 'HEPSÝ Ekranda');
DEFINE('_ENTIREQUERY', 'Bütün Sorgu');
DEFINE('_OPTIONS', 'Seçenekler');
DEFINE('_LENGTH', 'uzunluk');
DEFINE('_CODE', 'kod');
DEFINE('_DATA', 'veri');
DEFINE('_TYPE', 'tür');
DEFINE('_NEXT', 'Sonraki');
DEFINE('_PREVIOUS', 'Önceki');
//Menu items
DEFINE('_HOME', 'Ev');
DEFINE('_SEARCH', 'Ara');
DEFINE('_AGMAINT', 'Uyarý Grubu Bakýmý');
DEFINE('_USERPREF', 'Kullanýcý Yeðlenenleri');
DEFINE('_CACHE', 'Önbellek & Durum');
DEFINE('_ADMIN', 'Yönetim');
DEFINE('_GALERTD', 'Çizge Uyarý Verisi');
DEFINE('_GALERTDT', 'Çizge Uyarýsý Algýlama Zamaný');
DEFINE('_USERMAN', 'Kullanýcý Yönetimi');
DEFINE('_LISTU', 'Kullanýcýlarý Listele');
DEFINE('_CREATEU', 'Bir Kullanýcý Yarat');
DEFINE('_ROLEMAN', 'Rol Yönetimi');
DEFINE('_LISTR', 'Rolleri Listele');
DEFINE('_CREATER', 'Bir Rol Yarat');
DEFINE('_LISTALL', 'Hepsini Listele');
DEFINE('_CREATE', 'Yarat');
DEFINE('_VIEW', 'Görünüm');
DEFINE('_CLEAR', 'Temizle');
DEFINE('_LISTGROUPS', 'Gruplarý Listele');
DEFINE('_CREATEGROUPS', 'Grup Yarat');
DEFINE('_VIEWGROUPS', 'Grup Görüntüle');
DEFINE('_EDITGROUPS', 'Grup Düzenle');
DEFINE('_DELETEGROUPS', 'Grup Sil');
DEFINE('_CLEARGROUPS', 'Grup Temizle');
DEFINE('_CHNGPWD', 'Parola Deðiþtir');
DEFINE('_DISPLAYU', 'Kullanýcý Görüntüle');
//base_footer.php
DEFINE('_FOOTER', ' (by <A class="largemenuitem" href="mailto:base@secureideas.net">Kevin Johnson</A> and the <A class="largemenuitem" href="http://sourceforge.net/project/memberlist.php?group_id=103348">BASE Project Team</A><BR>Built on ACID by Roman Danyliw )');
//index.php --Log in Page
DEFINE('_LOGINERROR', 'Kullanýcý geçerli deðil ya da parolanýz yanlýþ!<br>Lütfen tekrar deneyin');
// base_main.php
DEFINE('_MOSTRECENT', 'En sondaki ');
DEFINE('_MOSTFREQUENT', 'En sýk ');
DEFINE('_ALERTS', ' Uyarýlar:');
DEFINE('_ADDRESSES', ' Adresler');
DEFINE('_ANYPROTO', 'herhangibir protokol');
DEFINE('_UNI', 'benzersiz');
DEFINE('_LISTING', 'listeleme');
DEFINE('_TALERTS', 'Bugün\'ün uyarýlarý: ');
DEFINE('_SOURCEIP', 'Kaynak IP');
DEFINE('_DESTIP', 'Varýþ IP');
DEFINE('_L24ALERTS', 'Son 24 Saatin uyarýlarý: ');
DEFINE('_L72ALERTS', 'Son 72 Saatin uyarýlarý: ');
DEFINE('_UNIALERTS', ' Benzersiz Uyarýlar');
DEFINE('_LSOURCEPORTS', 'Son Kaynak Portlarý: ');
DEFINE('_LDESTPORTS', 'Son Varýþ Portlarý: ');
DEFINE('_FREGSOURCEP', 'En Sýk Kaynak Portlarý: ');
DEFINE('_FREGDESTP', 'En Sýk Varýþ Portlarý: ');
DEFINE('_QUERIED', 'Sorgulandý');
DEFINE('_DATABASE', 'Veritabaný:');
DEFINE('_SCHEMAV', 'Þema Sürümü:');
DEFINE('_TIMEWIN', 'Zaman Penceresi:');
DEFINE('_NOALERTSDETECT', 'hiçbir uyarý algýlanmadý');
DEFINE('_USEALERTDB', 'Uyarý Veritabanýný Kullan');
DEFINE('_USEARCHIDB', 'Arþiv Veritabanýný Kullan');
DEFINE('_TRAFFICPROBPRO', 'Protokole Göre Trafik Profili');
//base_auth.inc.php
DEFINE('_ADDEDSF', 'Baþarýlý Biçimde Eklendi');
DEFINE('_NOPWDCHANGE', 'Parolanýzý deðiþtirmek olanaksýz: ');
DEFINE('_NOUSER', 'Kullanýcý geçerli deðil!');
DEFINE('_OLDPWD', 'Girilen Eski parola kayýtlarýmýzla eþleþmiyor!');
DEFINE('_PWDCANT', 'Parolanýzý deðiþtirmek olanaksýz: ');
DEFINE('_PWDDONE', 'Parolanýz deðiþtirildi!');
DEFINE('_ROLEEXIST', 'Rol Zaten Var');
DEFINE('_ROLEIDEXIST', 'Rol ID Zaten Var');
DEFINE('_ROLEADDED', 'Rol Baþarýlý Biçimde Eklendi');
//base_roleadmin.php
DEFINE('_ROLEADMIN', 'BASE Rol Yönetimi');
DEFINE('_FRMROLEID', 'Rol ID:');
DEFINE('_FRMROLENAME', 'Rol Adý:');
DEFINE('_FRMROLEDESC', 'Betimleme:');
DEFINE('_UPDATEROLE', 'Rolü Güncelle');
//base_useradmin.php
DEFINE('_USERADMIN', 'BASE Kullanýcý Yönetimi');
DEFINE('_FRMFULLNAME', 'Tüm Ad:');
DEFINE('_FRMROLE', 'Rol:');
DEFINE('_FRMUID', 'Kullanýcý ID:');
DEFINE('_SUBMITQUERY', 'Sorguyu Sun');
DEFINE('_UPDATEUSER', 'Kullanýcýyý Güncelle');
//admin/index.php
DEFINE('_BASEADMIN', 'BASE Yönetimi');
DEFINE('_BASEADMINTEXT', 'Lütfen soldan bir seçenek seçiniz.');
//base_action.inc.php
DEFINE('_NOACTION', 'Uyarýlarda hiçbir eylem belirlenmemiþ');
DEFINE('_INVALIDACT', ' geçersiz bir eylemdir');
DEFINE('_ERRNOAG', 'Hiçbir UG belirlenmediði için uyarýlarý ekleyemedi');
DEFINE('_ERRNOEMAIL', 'Email adresi belirlenmediði için uyarýlarý gönderemedi');
DEFINE('_ACTION', 'EYLEM');
DEFINE('_CONTEXT', 'baðlam');
DEFINE('_ADDAGID', 'UG\'na EKLE (ID yoluyla)');
DEFINE('_ADDAG', 'Yeni-UG-EKLE');
DEFINE('_ADDAGNAME', 'UG\'na EKLE (Ad yoluyla)');
DEFINE('_CREATEAG', 'UG Yarat (Ad yoluyla)');
DEFINE('_CLEARAG', 'UG\'dan Temizle');
DEFINE('_DELETEALERT', 'Uyarý(larý) sil');
DEFINE('_EMAILALERTSFULL', 'Uyarý(larý) Email\'e gönder (tüm)');
DEFINE('_EMAILALERTSSUMM', 'Uyarý(larý) Email\'e gönder (özet)');
DEFINE('_EMAILALERTSCSV', 'Uyarý(larý) Email\'e gönder (csv)');
DEFINE('_ARCHIVEALERTSCOPY', 'Uyarý(larý) arþivle (kopyala)');
DEFINE('_ARCHIVEALERTSMOVE', 'Uyarý(larý) arþivle (taþý)');
DEFINE('_IGNORED', 'Yoksayýldý ');
DEFINE('_DUPALERTS', ' uyarý(larý) çoðalt');
DEFINE('_ALERTSPARA', ' uyarý(lar)');
DEFINE('_NOALERTSSELECT', 'Hiçbir uyarý seçilmemiþ ya da');
DEFINE('_NOTSUCCESSFUL', 'baþarýlý deðildi');
DEFINE('_ERRUNKAGID', 'Bilinmeyen UG ID belirlenmiþ (UG muhtemelen geçerli deðil)');
DEFINE('_ERRREMOVEFAIL', 'Yeni UG\'nu çýkarmak baþarýsýz oldu');
DEFINE('_GENBASE', 'BASE tarafýndan Üretildi');
DEFINE('_ERRNOEMAILEXP', 'DIÞARI AKTARIM HATASI: Dýþarý aktarýlmýþ uyarýlarý gönderemedi');
DEFINE('_ERRNOEMAILPHP', 'PHP\'deki mail yapýlandýrmasýný kontrol et.');
DEFINE('_ERRDELALERT', 'Uyarý Silme Hatasý');
DEFINE('_ERRARCHIVE', 'Arþiv hatasý:');
DEFINE('_ERRMAILNORECP', 'MAIL HATASI: Alýcý Belirlenmemiþ');
//base_cache.inc.php
DEFINE('_ADDED', 'Ekledi ');
DEFINE('_HOSTNAMESDNS', ' host isimlerini IP DNS önbelleðine');
DEFINE('_HOSTNAMESWHOIS', ' host isimlerini Whois önbelleðine');
DEFINE('_ERRCACHENULL', 'Önbelleðe Alma HATASI: NULL olay sýrasý bulundu?');
DEFINE('_ERRCACHEERROR', 'OLAYI ÖNBELLEÐE ALMA HATASI:');
DEFINE('_ERRCACHEUPDATE', 'Olay önbelleðini güncelleyemedi');
DEFINE('_ALERTSCACHE', ' uyarý(larý) Uyarý önbelleðine');
//base_db.inc.php
DEFINE('_ERRSQLTRACE', 'SQL iz dosyasýný açmak olanaksýz');
DEFINE('_ERRSQLCONNECT', 'DB baðlantý hatasý :');
DEFINE('_ERRSQLCONNECTINFO', '<P><I>base_conf.php</I> dosyasýndaki DB baðlantý deðiþkenlerini kontrol edin.  
              <PRE>
               = $alert_dbname   : uyarýlarýn depolandýðý MySQL veritabaný adý 
               = $alert_host     : veritabanýnýn depolandýðý host
               = $alert_port     : veritabanýnýn depolandýðý port
               = $alert_user     : veritabaný içindeki kullanýcýadý
               = $alert_password : kullanýcýadý için parola
              </PRE>
              <P>');
DEFINE('_ERRSQLPCONNECT', 'DB (p)baðlantý hatasý :');
DEFINE('_ERRSQLDB', 'Veritabaný HATASI:');
DEFINE('_DBALCHECK', 'DB soyutlama kitaplýðý kontrol ediliyor');
DEFINE('_ERRSQLDBALLOAD1', '<P><B>DB soyutlama kitaplýðý yükleme hatasý: </B> from ');
DEFINE('_ERRSQLDBALLOAD2', '<P><CODE>base_conf.php</CODE> dosyasýndaki <CODE>$DBlib_path</CODE> DB soyutlama kitaplýðý deðiþkenini kontrol edin 
            <P>
            Yürürlükte kullanýlan temel veritabaný kitaplýðý ADODB\'dir,
            <A HREF="http://adodb.sourceforge.net/">http://adodb.sourceforge.net/</A> ten indirilebilir');
DEFINE('_ERRSQLDBTYPE', 'Geçersiz Veritabaný Tipi Belirlenmiþ');
DEFINE('_ERRSQLDBTYPEINFO1', '<CODE>base_conf.php</CODE> dosyasýndaki <CODE>\$DBtype</CODE> deðiþkeni tanýmlanmamýþ veritabaný tipinde ayarlanmýþ ');
DEFINE('_ERRSQLDBTYPEINFO2', 'Yalnýzca aþaðýdaki veritabanlarý desteklenmektedir: <PRE>
                MySQL         : \'mysql\'
                PostgreSQL    : \'postgres\'
                MS SQL Server : \'mssql\'
                Oracle        : \'oci8\'
             </PRE>');
//base_log_error.inc.php
DEFINE('_ERRBASEFATAL', 'BASE ONARILAMAZ HATA:');
//base_log_timing.inc.php
DEFINE('_LOADEDIN', 'Yüklendi');
DEFINE('_SECONDS', 'saniyede');
//base_net.inc.php
DEFINE('_ERRRESOLVEADDRESS', 'Adresi çözmek olanaksýz');
//base_output_query.inc.php
DEFINE('_QUERYRESULTSHEADER', 'Sorgu Sonuçlarý Sayfa Baþlýðý Çýkýþý');
//base_signature.inc.php
DEFINE('_ERRSIGNAMEUNK', 'Bilinmeyen ÝmzaÝsmi');
DEFINE('_ERRSIGPROIRITYUNK', 'Bilinmeyen ÝmzaÖnceliði');
DEFINE('_UNCLASS', 'sýnýflandýrýlmamýþ');
//base_state_citems.inc.php
DEFINE('_DENCODED', 'veri þifrelenmiþ');
DEFINE('_NODENCODED', '(veri dönüþtürme yok, DB yerel þifrelemedeki ölçüt sanýlýyor)');
DEFINE('_SHORTJAN', 'Oca');
DEFINE('_SHORTFEB', 'Þub');
DEFINE('_SHORTMAR', 'Mar');
DEFINE('_SHORTAPR', 'Nis');
DEFINE('_SHORTMAY', 'May');
DEFINE('_SHORTJUN', 'Haz');
DEFINE('_SHORTJLY', 'Tem');
DEFINE('_SHORTAUG', 'Aðu');
DEFINE('_SHORTSEP', 'Eyl');
DEFINE('_SHORTOCT', 'Eki');
DEFINE('_SHORTNOV', 'Kas');
DEFINE('_SHORTDEC', 'Ara');
DEFINE('_DISPSIG', '{ imza }');
DEFINE('_DISPANYCLASS', '{ herhangibir Sýnýflandýrma }');
DEFINE('_DISPANYPRIO', '{ herhangibir Öncelik }');
DEFINE('_DISPANYSENSOR', '{ herhangibir Sensor }');
DEFINE('_DISPADDRESS', '{ adres }');
DEFINE('_DISPFIELD', '{ alan }');
DEFINE('_DISPPORT', '{ port }');
DEFINE('_DISPENCODING', '{ þifreleme }');
DEFINE('_DISPCONVERT2', '{ Dönüþtür }');
DEFINE('_DISPANYAG', '{ herhangibir Uyarý Grubu }');
DEFINE('_DISPPAYLOAD', '{ payload }');
DEFINE('_DISPFLAGS', '{ bayraklar }');
DEFINE('_SIGEXACTLY', 'tam olarak');
DEFINE('_SIGROUGHLY', 'yaklaþýk olarak');
DEFINE('_SIGCLASS', 'Ýmza Sýnýflandýrma');
DEFINE('_SIGPRIO', 'Ýmza Önceliði');
DEFINE('_SHORTSOURCE', 'Kaynak');
DEFINE('_SHORTDEST', 'Varýþ');
DEFINE('_SHORTSOURCEORDEST', 'Kaynak ya da Varýþ');
DEFINE('_NOLAYER4', '4.katman yok');
DEFINE('_INPUTCRTENC', 'Girdi Ölçütü Þifreleme Tipi');
DEFINE('_CONVERT2WS', 'Dönüþtür (ararken)');
//base_state_common.inc.php
DEFINE('_PHPERRORCSESSION', 'PHP HATASI: Özel (kullanýcý) bir PHP oturumu saptandý. Ancak, BASE açýkça bu özel iþleyiciyi kullanmak üzere ayarlanmamýþ. <CODE>base_conf.php</CODE> dosyasýnda <CODE>use_user_session=1</CODE> olarak ayarlayýn');
DEFINE('_PHPERRORCSESSIONCODE', 'PHP HATASI: Özel (kullanýcý) bir PHP oturum iþleyicisi yapýlandýrýlmýþ, fakat <CODE>user_session_path</CODE> \'teki belirlenmiþ iþleyici kodu geçersiz.');
DEFINE('_PHPERRORCSESSIONVAR', 'PHP HATASI: Özel (kullanýcý) bir PHP oturum iþleyicisi yapýlandýrýlmýþ, fakat bu iþleyicinin gerçekleþtirilmesi BASE\'de belirlenmemiþ. Eðer özel bir oturum iþleyici isteniyorsa, <CODE>base_conf.php</CODE> dosyasýndaki <CODE>user_session_path</CODE> deðiþkenini ayarlayýn.');
DEFINE('_PHPSESSREG', 'Oturum Kaydedildi');
//base_state_criteria.inc.php
DEFINE('_REMOVE', 'Kaldýrýlýyor');
DEFINE('_FROMCRIT', 'ölçütten');
DEFINE('_ERRCRITELEM', 'Geçersiz ölçüt öðesi');
//base_state_query.inc.php
DEFINE('_VALIDCANNED', 'Geçerli Konservelenmiþ Sorgu Listesi');
DEFINE('_DISPLAYING', 'Görüntüleniyor');
DEFINE('_DISPLAYINGTOTAL', '%d-%d uyarýlarý görüntüleniyor, %s toplamda');
DEFINE('_NOALERTS', 'Hiçbir Uyarý bulunamadý.');
DEFINE('_QUERYRESULTS', 'Sorgu Sonuçlarý');
DEFINE('_QUERYSTATE', 'Sorgu Durumu');
DEFINE('_DISPACTION', '{ eylem }');
//base_ag_common.php
DEFINE('_ERRAGNAMESEARCH', 'Belirtilen UG ad aramasý geçersiz.  Tekrar deneyin!');
DEFINE('_ERRAGNAMEEXIST', 'Belirtilen UG yok.');
DEFINE('_ERRAGIDSEARCH', 'Belirtilen UG ID aramasý geçersiz.  Tekrar deneyin!');
DEFINE('_ERRAGLOOKUP', 'UG ID arama Hatasý');
DEFINE('_ERRAGINSERT', 'Yeni UG Ekleme Hatasý');
//base_ag_main.php
DEFINE('_AGMAINTTITLE', 'Uyarý Grubu (UG) Bakýmý');
DEFINE('_ERRAGUPDATE', 'UG güncelleme Hatasý');
DEFINE('_ERRAGPACKETLIST', 'UG için paket listesi silme Hatasý:');
DEFINE('_ERRAGDELETE', 'UG silme Hatasý');
DEFINE('_AGDELETE', 'Baþarýlý biçimde SÝLÝNDÝ');
DEFINE('_AGDELETEINFO', 'bilgi silindi');
DEFINE('_ERRAGSEARCHINV', 'Girilen arama ölçütü geçersiz.  Tekrar deneyin!');
DEFINE('_ERRAGSEARCHNOTFOUND', 'Bu ölçüte göre UG bulunamadý.');
DEFINE('_NOALERTGOUPS', 'Hiç Uyarý Grubu yok');
DEFINE('_NUMALERTS', '# Uyarýlar');
DEFINE('_ACTIONS', 'Eylemler');
DEFINE('_NOTASSIGN', 'henüz atanmamýþ');
DEFINE('_SAVECHANGES', 'Deðiþiklikleri Kaydet');
DEFINE('_CONFIRMDELETE', 'Silmeyi Onayla');
DEFINE('_CONFIRMCLEAR', 'Temizlemeyi Onayla');
//base_common.php
DEFINE('_PORTSCAN', 'Portscan Trafiði');
//base_db_common.php
DEFINE('_ERRDBINDEXCREATE', 'INDEX YARATMAK Olanaksýz');
DEFINE('_DBINDEXCREATE', 'Baþarýlý biçimde INDEX yaratýldý');
DEFINE('_ERRSNORTVER', 'Eski bir sürüm olabilir.  Sadece Snort 1.7-beta0 ve sonraki sürümler tarafýndan yaratýlan uyarý veritabanlarý desteklenmektedir');
DEFINE('_ERRSNORTVER1', 'temel veritabaný');
DEFINE('_ERRSNORTVER2', 'eksik/geçersiz görünmektedir');
DEFINE('_ERRDBSTRUCT1', 'veritabaný sürümü geçerli, fakat BASE DB yapýsý');
DEFINE('_ERRDBSTRUCT2', 'sunulu deðil. <A HREF="base_db_setup.php">Setup sayfasýný</A> kullanarak DB\'i yapýlandýrýn ve optimize edin.');
DEFINE('_ERRPHPERROR', 'PHP HATASI');
DEFINE('_ERRPHPERROR1', 'Uyumsuz sürüm');
DEFINE('_ERRVERSION', 'Sürümü');
DEFINE('_ERRPHPERROR2', ' PHP\'nin çok eski.  Lütfen 4.0.4 veya sonraki bir sürüme yükseltin');
DEFINE('_ERRPHPMYSQLSUP', '<B>PHP inþasý eksik</B>: <FONT>uyarý veritabanýný okumak için gerekli 
               önkoþul Mysql desteði PHP içine inþa edilmemiþ.  
               Lütfen gerekli kitaplýk ile birlikte PHP\'yi yeniden derleyin (<CODE>--with-mysql</CODE>)</FONT>');
DEFINE('_ERRPHPPOSTGRESSUP', '<B>PHP inþasý eksik</B>: <FONT>uyarý veritabanýný okumak için gerekli 
               önkoþul PostgreSQL desteði PHP içine inþa edilmemiþ.  
               Lütfen gerekli kitaplýk ile birlikte PHP\'yi yeniden derleyin (<CODE>--with-pgsql</CODE>)</FONT>');
DEFINE('_ERRPHPMSSQLSUP', '<B>PHP inþasý eksik</B>: <FONT>uyarý veritabanýný okumak için gerekli 
                   önkoþul MS SQL Server desteði PHP içine inþa edilmemiþ.  
                   Lütfen gerekli kitaplýk ile birlikte PHP\'yi yeniden derleyin (<CODE>--enable-mssql</CODE>)</FONT>');
DEFINE('_ERRPHPORACLESUP', '<B>PHP inþasý eksik</B>: <FONT>uyarý veritabanýný okumak için gerekli 
                   önkoþul Oracle desteði PHP içine inþa edilmemiþ.  
                   Lütfen gerekli kitaplýk ile birlikte PHP\'yi yeniden derleyin (<CODE>--with-oci8</CODE>)</FONT>');
//base_graph_form.php
DEFINE('_CHARTTITLE', 'Grafik Baþlýðý:');
DEFINE('_CHARTTYPE', 'Grafik Tipi:');
DEFINE('_CHARTTYPES', '{ grafik tipi }');
DEFINE('_CHARTPERIOD', 'Grafik Dönemi:');
DEFINE('_PERIODNO', 'dönem yok');
DEFINE('_PERIODWEEK', '7 (bir hafta)');
DEFINE('_PERIODDAY', '24 (bütün gün)');
DEFINE('_PERIOD168', '168 (24x7)');
DEFINE('_CHARTSIZE', 'Boyut: (en x yükseklik)');
DEFINE('_PLOTMARGINS', 'Çizim Boþluklarý: (sol x sað x üst x alt)');
DEFINE('_PLOTTYPE', 'Çizim tipi:');
DEFINE('_TYPEBAR', 'çubuk');
DEFINE('_TYPELINE', 'çizgi');
DEFINE('_TYPEPIE', 'pasta');
DEFINE('_CHARTHOUR', '{sat}');
DEFINE('_CHARTDAY', '{gün}');
DEFINE('_CHARTMONTH', '{ay}');
DEFINE('_GRAPHALERTS', 'Çizge Uyarýlarý');
DEFINE('_AXISCONTROLS', 'X / Y EKSEN KONTROLLERÝ');
DEFINE('_CHRTTYPEHOUR', 'Zaman (saat) vs. Uyarý Sayýsý');
DEFINE('_CHRTTYPEDAY', 'Zaman (gün) vs. Uyarý Sayýsý');
DEFINE('_CHRTTYPEWEEK', 'Zaman (hafta) vs. Uyarý Sayýsý');
DEFINE('_CHRTTYPEMONTH', 'Zaman (ay) vs. Uyarý Sayýsý');
DEFINE('_CHRTTYPEYEAR', 'Zaman (yýl) vs. Uyarý Sayýsý');
DEFINE('_CHRTTYPESRCIP', 'Kaynak IP adresi vs. Uyarý Sayýsý');
DEFINE('_CHRTTYPEDSTIP', 'Varýþ IP adresi vs. Uyarý Sayýsý');
DEFINE('_CHRTTYPEDSTUDP', 'Varýþ UDP Portu vs. Uyarý Sayýsý');
DEFINE('_CHRTTYPESRCUDP', 'Kynak UDP Portu vs. Uyarý Sayýsý');
DEFINE('_CHRTTYPEDSTPORT', 'Varýþ TCP Portu vs. Uyarý Sayýsý');
DEFINE('_CHRTTYPESRCPORT', 'Kaynak TCP Portu vs. Uyarý Sayýsý');
DEFINE('_CHRTTYPESIG', 'Ýmza Sýnýflamasý vs. Uyarý Sayýsý');
DEFINE('_CHRTTYPESENSOR', 'Sensor vs. Uyarý Sayýsý');
DEFINE('_CHRTBEGIN', 'Grafik Baþlangýcý:');
DEFINE('_CHRTEND', 'Grafik Sonu:');
DEFINE('_CHRTDS', 'Veri Kaynaðý:');
DEFINE('_CHRTX', 'X Ekseni');
DEFINE('_CHRTY', 'Y Ekseni');
DEFINE('_CHRTMINTRESH', 'En Düþük Eþik Deðeri');
DEFINE('_CHRTROTAXISLABEL', 'Eksen Etiketlerini Döndür (90 derece)');
DEFINE('_CHRTSHOWX', 'X-ekseni ýzgara-çizgilerini göster');
DEFINE('_CHRTDISPLABELX', 'Her bir X-ekseni etiketini görüntüle');
DEFINE('_CHRTDATAPOINTS', 'veri göstergeleri');
DEFINE('_CHRTYLOG', 'Logaritmik Y-ekseni');
DEFINE('_CHRTYGRID', 'Y-ekseni ýzgara-çizgilerini göster');
//base_graph_main.php
DEFINE('_CHRTTITLE', 'BASE Grafik');
DEFINE('_ERRCHRTNOTYPE', 'Hiçbir grafik tipi belirtilmemiþ');
DEFINE('_ERRNOAGSPEC', 'Hiçbir UG belirtilmemiþ.  Tüm uyarýlarý kullanýyor.');
DEFINE('_CHRTDATAIMPORT', 'Veri aktarýmýný baþlatýyor');
DEFINE('_CHRTTIMEVNUMBER', 'Zaman vs. Uyarý Sayýsý');
DEFINE('_CHRTTIME', 'Zaman');
DEFINE('_CHRTALERTOCCUR', 'Uyarý Meydana Geliyor');
DEFINE('_CHRTSIPNUMBER', 'Kaynak IP vs. Uyarý Sayýsý');
DEFINE('_CHRTSIP', 'Kaynak IP Adresi');
DEFINE('_CHRTDIPALERTS', 'Varýþ IP vs. Uyarý Sayýsý');
DEFINE('_CHRTDIP', 'Varýþ IP Adresi');
DEFINE('_CHRTUDPPORTNUMBER', 'UDP Portu (Varýþ) vs. Uyarý Sayýsý');
DEFINE('_CHRTDUDPPORT', 'Varýþ UDP Portu');
DEFINE('_CHRTSUDPPORTNUMBER', 'UDP Portu (Kaynak) vs. Uyarý Sayýsý');
DEFINE('_CHRTSUDPPORT', 'Kaynak UDP Portu');
DEFINE('_CHRTPORTDESTNUMBER', 'TCP Portu (Varýþ) vs. Uyarý Sayýsý');
DEFINE('_CHRTPORTDEST', 'Varýþ TCP Portu');
DEFINE('_CHRTPORTSRCNUMBER', 'TCP Portu (Kaynak) vs. Uyarý Sayýsý');
DEFINE('_CHRTPORTSRC', 'Kaynak TCP Portu');
DEFINE('_CHRTSIGNUMBER', 'Ýmza Sýnýflamasý vs. Uyarý Sayýsý');
DEFINE('_CHRTCLASS', 'Sýnýflama');
DEFINE('_CHRTSENSORNUMBER', 'Sensor vs. Uyarý Sayýsý');
DEFINE('_CHRTHANDLEPERIOD', 'Ýþleme Dönemi, eðer gerekliyse');
DEFINE('_CHRTDUMP', 'Veriyi boþaltýyor ... (her birini yazýyor');
DEFINE('_CHRTDRAW', 'Grafiði çiziyor');
DEFINE('_ERRCHRTNODATAPOINTS', 'Çizecek hiç veri göstergesi yok');
DEFINE('_GRAPHALERTDATA', 'Grafik Uyarý Verisi');
//base_maintenance.php
DEFINE('_MAINTTITLE', 'Bakým');
DEFINE('_MNTPHP', 'PHP Ýnþasý:');
DEFINE('_MNTCLIENT', 'ÝSTEMCÝ:');
DEFINE('_MNTSERVER', 'SUNUCU:');
DEFINE('_MNTSERVERHW', 'SUNUCU HW:');
DEFINE('_MNTPHPVER', 'PHP SÜRÜMÜ:');
DEFINE('_MNTPHPAPI', 'PHP API:');
DEFINE('_MNTPHPLOGLVL', 'PHP Günlükleme düzeyi:');
DEFINE('_MNTPHPMODS', 'Yüklü Modüller:');
DEFINE('_MNTDBTYPE', 'DB Tipi:');
DEFINE('_MNTDBALV', 'DB Soyutlama Sürümü:');
DEFINE('_MNTDBALERTNAME', 'UYARI DB Adý:');
DEFINE('_MNTDBARCHNAME', 'ARÞÝV DB Adý:');
DEFINE('_MNTAIC', 'Uyarý Bilgi Önbelleði:');
DEFINE('_MNTAICTE', 'Toplam Olaylar:');
DEFINE('_MNTAICCE', 'Önbellekteki Olaylar:');
DEFINE('_MNTIPAC', 'IP Adres Önbelleði');
DEFINE('_MNTIPACUSIP', 'Benzersiz Kaynak IP:');
DEFINE('_MNTIPACDNSC', 'DNS Önbelleðe alýndý:');
DEFINE('_MNTIPACWC', 'Whois Önbelleðe alýndý:');
DEFINE('_MNTIPACUDIP', 'Benzersiz Varýþ IP:');
//base_qry_alert.php
DEFINE('_QAINVPAIR', 'Geçersiz (sid,cid) çift');
DEFINE('_QAALERTDELET', 'Uyarý SÝLÝNDÝ');
DEFINE('_QATRIGGERSIG', 'Tetiklenmiþ Ýmza');
DEFINE('_QANORMALD', 'Normal Görüntü');
DEFINE('_QAPLAIND', 'Düz Görüntü');
DEFINE('_QANOPAYLOAD', 'Hýzlý günlükleme kullanýldý bu yüzden payload atýldý');
//base_qry_common.php
DEFINE('_QCSIG', 'imza');
DEFINE('_QCIPADDR', 'IP adresleri');
DEFINE('_QCIPFIELDS', 'IP alanlarý');
DEFINE('_QCTCPPORTS', 'TCP portlarý');
DEFINE('_QCTCPFLAGS', 'TCP bayraklarý');
DEFINE('_QCTCPFIELD', 'TCP alanlarý');
DEFINE('_QCUDPPORTS', 'UDP portlarý');
DEFINE('_QCUDPFIELDS', 'UDP alanlarý');
DEFINE('_QCICMPFIELDS', 'ICMP alanlarý');
DEFINE('_QCDATA', 'Veri');
DEFINE('_QCERRCRITWARN', 'Ölçüt uyarýsý:');
DEFINE('_QCERRVALUE', 'deðeri');
DEFINE('_QCERRFIELD', 'alaný');
DEFINE('_QCERROPER', 'iþletmeni');
DEFINE('_QCERRDATETIME', 'tarih/zaman deðeri');
DEFINE('_QCERRPAYLOAD', 'payload deðeri');
DEFINE('_QCERRIP', 'IP adresi');
DEFINE('_QCERRIPTYPE', 'Tipin IP adresi');
DEFINE('_QCERRSPECFIELD', ' bir protokol alaný için girildi, fakat özel alan belirlenmemiþ.');
DEFINE('_QCERRSPECVALUE', 'onun bir ölçüt olmasý gerektiðini göstermek üzere seçilmiþ, fakat hangisiyle eþleþeceðini gösteren hiçbir deðer belirlenmemiþ.');
DEFINE('_QCERRBOOLEAN', 'Aralarýnda bir boolen iþleci olmadan (örneðin; VE, YA DA) Çoklu Protokol Alan ölçütü girildi.');
DEFINE('_QCERRDATEVALUE', 'bazý tarih/zaman ölçütünün eþleþmesi gerektiðini göstermek üzere seçilmiþ, fakat hiçbir deðer belirlenmemiþ.');
DEFINE('_QCERRINVHOUR', '(Geçersiz Saat) Belirtilen zamana uygun hiçbir tarih girilmemiþ.');
DEFINE('_QCERRDATECRIT', 'bazý tarih/zaman ölçütünün eþleþmesi gerektiðini göstermek üzere seçilmiþ, fakat hiçbir deðer belirlenmemiþ.');
DEFINE('_QCERROPERSELECT', 'girilmiþ fakat hiçbir iþletici seçilmemiþ.');
DEFINE('_QCERRDATEBOOL', 'Aralarýnda bir boolen iþleci olmadan (örneðin; VE, YA DA) Çoklu Tarih/Zaman ölçütü girildi.');
DEFINE('_QCERRPAYCRITOPER', 'bir payload ölçüt alaný için girilmiþ, fakat bir iþletici (örneðin; sahip, sahip deðil) belirtilmemiþ.');
DEFINE('_QCERRPAYCRITVALUE', 'payload\'ýn bir ölçüt olmasý gerektiðini göstermek üzere seçilmiþ, fakat hangisiyle eþleþeceðini gösteren hiçbir deðer belirlenmemiþ.');
DEFINE('_QCERRPAYBOOL', 'Aralarýnda bir boolen iþleci olmadan (örneðin; VE, YA DA) Çoklu Veri payload ölçütü girildi.');
DEFINE('_QCMETACRIT', 'Meta Ölçütü');
DEFINE('_QCIPCRIT', 'IP Ölçütü');
DEFINE('_QCPAYCRIT', 'Payload Ölçütü');
DEFINE('_QCTCPCRIT', 'TCP Ölçütü');
DEFINE('_QCUDPCRIT', 'UDP Ölçütü');
DEFINE('_QCICMPCRIT', 'ICMP Ölçütü');
DEFINE('_QCLAYER4CRIT', '4. Katman Ölçütü');
DEFINE('_QCERRINVIPCRIT', 'Geçersiz IP adres ölçütü');
DEFINE('_QCERRCRITADDRESSTYPE', 'bir ölçüt deðeri olmasý için girilmiþ, fakat adresin tipi (örneðin; kaynak, varýþ) belirlenmemiþ.');
DEFINE('_QCERRCRITIPADDRESSNONE', 'bir IP adresinin bir ölçüt olmasý gerektiðini gösteriyor, fakat hangisiyle eþleþeceðini gösteren hiçbir adres belirlenmemiþ.');
DEFINE('_QCERRCRITIPADDRESSNONE1', 'seçilmiþ (#');
DEFINE('_QCERRCRITIPIPBOOL', 'IP Ölçütü arasýnda bir boolen iþleci olmadan (örneðin; VE, YA DA) Çoklu IP adres ölçütü girildi');
//base_qry_form.php
DEFINE('_QFRMSORTORDER', 'Sýralama düzeni');
DEFINE('_QFRMSORTNONE', 'hiçbiri');
DEFINE('_QFRMTIMEA', 'zaman damgasý (artan)');
DEFINE('_QFRMTIMED', 'zaman damgasý (azalan)');
DEFINE('_QFRMSIG', 'imza');
DEFINE('_QFRMSIP', 'kaynak IP');
DEFINE('_QFRMDIP', 'varýþ IP');
//base_qry_sqlcalls.php
DEFINE('_QSCSUMM', 'Ýstatistik Özeti');
DEFINE('_QSCTIMEPROF', 'Zaman profili');
DEFINE('_QSCOFALERTS', 'uyarýlarýn');
//base_stat_alerts.php
DEFINE('_ALERTTITLE', 'Uyarý Listeleme');
//base_stat_common.php
DEFINE('_SCCATEGORIES', 'Kategoriler:');
DEFINE('_SCSENSORTOTAL', 'Sensorler/Toplam:');
DEFINE('_SCTOTALNUMALERTS', 'Toplam Uyarý Sayýsý:');
DEFINE('_SCSRCIP', 'Kaynak IP adresi:');
DEFINE('_SCDSTIP', 'Varýþ IP adresi:');
DEFINE('_SCUNILINKS', 'Benzersiz IP baðlantýlarý');
DEFINE('_SCSRCPORTS', 'Kaynak Portlarý: ');
DEFINE('_SCDSTPORTS', 'Varýþ Portlarý: ');
DEFINE('_SCSENSORS', 'Sensorler');
DEFINE('_SCCLASS', 'sýnýflamalar');
DEFINE('_SCUNIADDRESS', 'Benzersiz adresler: ');
DEFINE('_SCSOURCE', 'Kaynak');
DEFINE('_SCDEST', 'Varýþ');
DEFINE('_SCPORT', 'Port');
//base_stat_ipaddr.php
DEFINE('_PSEVENTERR', 'PORTSCAN OLAY HATASI: ');
DEFINE('_PSEVENTERRNOFILE', '\$portscan_file deðiþkeninde hiçbir dosya belirtilmemiþ');
DEFINE('_PSEVENTERROPENFILE', 'Portscan olay dosyasýný açmak olanaksýz');
DEFINE('_PSDATETIME', 'Tarih/Zaman');
DEFINE('_PSSRCIP', 'Kaynak IP');
DEFINE('_PSDSTIP', 'Varýþ IP');
DEFINE('_PSSRCPORT', 'Kaynak Portu');
DEFINE('_PSDSTPORT', 'Varýþ Portu');
DEFINE('_PSTCPFLAGS', 'TCP Bayraklarý');
DEFINE('_PSTOTALOCC', 'Toplam<BR> Olaylar');
DEFINE('_PSNUMSENSORS', 'Sensor Sayýsý');
DEFINE('_PSFIRSTOCC', 'Ýlk<BR> Gerçekleþen Olay');
DEFINE('_PSLASTOCC', 'Son<BR> Gerçekleþen Olay');
DEFINE('_PSUNIALERTS', 'Benzersiz Uyarýlar');
DEFINE('_PSPORTSCANEVE', 'Portscan Olaylarý');
DEFINE('_PSREGWHOIS', 'Kayýt bakýþý (whois)');
DEFINE('_PSNODNS', 'hiç DNS çözünürlüðü denenmedi');
DEFINE('_PSNUMSENSORSBR', 'Sensor <BR>Sayýsý');
DEFINE('_PSOCCASSRC', 'Kaynak olarak <BR>Ortaya Çýkanlar');
DEFINE('_PSOCCASDST', 'Varýþ olarak <BR>Ortaya Çýkanlar');
DEFINE('_PSWHOISINFO', 'Whois Bilgisi');
DEFINE('_PSTOTALHOSTS', 'Toplam Taranan Hostlar');
DEFINE('_PSDETECTAMONG', '%d benzersiz uyarý saptandý, %d uyarý arasýnda, %s \'de');
DEFINE('_PSALLALERTSAS', 'tüm uyarýlarla birlikte %s/%s olarak');
DEFINE('_PSSHOW', 'göster');
DEFINE('_PSEXTERNAL', 'dýþ');
//base_stat_iplink.php
DEFINE('_SIPLTITLE', 'IP Baðlantýlarý');
DEFINE('_SIPLSOURCEFGDN', 'Kaynak FQDN');
DEFINE('_SIPLDESTFGDN', 'Varýþ FQDN');
DEFINE('_SIPLDIRECTION', 'Yön');
DEFINE('_SIPLPROTO', 'Protokol');
DEFINE('_SIPLUNIDSTPORTS', 'Benzersiz Varýþ Portlarý');
DEFINE('_SIPLUNIEVENTS', 'Benzersiz Olaylar');
DEFINE('_SIPLTOTALEVENTS', 'Toplam Olaylar');
//base_stat_ports.php
DEFINE('_UNIQ', 'Benzersiz');
DEFINE('_DSTPS', 'Varýþ Port(larý)');
DEFINE('_SRCPS', 'Kaynak Port(larý)');
DEFINE('_OCCURRENCES', 'Meydana Geliyor');
//base_stat_sensor.php
DEFINE('SPSENSORLIST', 'Sensor Listeleme');
//base_stat_time.php
DEFINE('_BSTTITLE', 'Uyarýlarýn Zaman Profili');
DEFINE('_BSTTIMECRIT', 'Zaman Ölçütü');
DEFINE('_BSTERRPROFILECRIT', '<FONT><B>Hiçbir profilleme ölçütü belirlenmemeiþ!</B>  "saat", "gün", ya da "ay" üzerine týklayarak kümelenmiþ istatistiklerden taneli olaný seçin.</FONT>');
DEFINE('_BSTERRTIMETYPE', '<FONT><B>Geçecek olan zaman parametresi tipi belirlenmemeiþ!</B>  Tek bir zaman belirtmek için "üzerinde", ya da bir aralýk belirtmek için "arasýnda" \'dan herhangi birini seçin.</FONT>');
DEFINE('_BSTERRNOYEAR', '<FONT><B>Hiçbir Yýl parametresi belirtilmemiþ!</B></FONT>');
DEFINE('_BSTERRNOMONTH', '<FONT><B>Hiçbir Ay parametresi belirtilmemiþ!</B></FONT>');
DEFINE('_BSTERRNODAY', '<FONT><B>Hiçbir Gün parametresi belirtilmemiþ!</B></FONT>');
DEFINE('_BSTPROFILEBY', 'Profil tarafýndan');
DEFINE('_TIMEON', 'üzerinde');
DEFINE('_TIMEBETWEEN', 'arasýnda');
DEFINE('_PROFILEALERT', 'Profil Uyarýsý');
//base_stat_uaddr.php
DEFINE('_UNISADD', 'Benzersiz Kaynak Adres(leri)');
DEFINE('_SUASRCIP', 'Kaynak IP adresi');
DEFINE('_SUAERRCRITADDUNK', 'ÖLÇÜT HATASI: bilinmeyen adres tipi -- Varýþ adresi olduðu sanýlýyor');
DEFINE('_UNIDADD', 'Benzersiz Varýþ Adres(leri)');
DEFINE('_SUADSTIP', 'Varýþ IP adresi');
DEFINE('_SUAUNIALERTS', 'Benzersiz Uyarýlar');
DEFINE('_SUASRCADD', 'Kaynak Adresi');
DEFINE('_SUADSTADD', 'Varýþ Adresi');
//base_user.php
DEFINE('_BASEUSERTITLE', 'BASE Kullanýcý Yeðlenenleri');
DEFINE('_BASEUSERERRPWD', 'Parolanýz boþ olamaz ya da iki parola eþleþmedi!');
DEFINE('_BASEUSEROLDPWD', 'Eski Parola:');
DEFINE('_BASEUSERNEWPWD', 'Yeni Parola:');
DEFINE('_BASEUSERNEWPWDAGAIN', 'Yeni Parola Tekrar:');
DEFINE('_LOGOUT', 'Oturumu Kapat');
?>
