from feature_utils import remotely_backup_file,remotely_restore_file,remotely_create_sample_yml_file, \
    remotely_remove_file, set_plugin_add_hosts, set_plugin_delete_hosts, touch_file,remotely_copy_file, \
    remotely_create_sample_client_keys_file
from avconfig.ossimsetupconfig import AVOssimSetupConfigHandler
from db.methods.system import get_system_id_from_local

CONFIG_FILE = "/etc/ossim/ossim_setup.conf"
ossim_setup = AVOssimSetupConfigHandler(CONFIG_FILE)

def modified_registry_entries_prepare():
        #Create a virtual agent
        #Copy the sample_windows_registry_log to its own file
        remotely_backup_file(ossim_setup.get_general_admin_ip(), "/var/ossec/etc/client.keys", "/var/ossec/etc/client.keys.bk")
        raw_key_file="""998 windows_behave 172.17.2.99 7be1e0d5108daa6775a50a6a28cc9c20cae2b43f58907e0b3ed82175e4bcc327"""
        f = open("/tmp/ossec_agent_keys","w")
        f.write(raw_key_file)
        f.close()

        if not remotely_copy_file(ossim_setup.get_general_admin_ip(), "/tmp/ossec_agent_keys", "/var/ossec/etc/client.keys"):
            print "Can't create the virtual keys file"
            raise KeyboardInterrupt()

        raw_file = """+++7139:33206:0:0:2ddaf5b8e8e2505d02b43ae6b7977e6a:ea1cf52734d7f41257d9f0b8d223cace4492aa40 !1401114783 ossec.conf
+++2958:33206:0:0:d183af1ab64cefdbda623e84cf17a923:bf86e2c2b674ed890a753492f19d247520891abe !1401114783 internal_options.conf
+++477:33206:0:0:100b7ff0a7389e4fb171921318d661e0:eb22a7ad669750dd5cc42699064034ed9ab18753 !1401114842 C:\WINDOWS/win.ini
+++231:33206:0:0:6bc453c2f9ffb59bb53766fee4cf3424:382ea036d833cc6e40adca63aa2253c10f9ad653 !1401114842 C:\WINDOWS/system.ini
+++0:33279:0:0:d41d8cd98f00b204e9800998ecf8427e:da39a3ee5e6b4b0d3255bfef95601890afd80709 !1401114842 C:\autoexec.bat
+++0:33206:0:0:d41d8cd98f00b204e9800998ecf8427e:da39a3ee5e6b4b0d3255bfef95601890afd80709 !1401114842 C:\config.sys
+++211:33206:0:0:1ae305bd07c7cba47e22430e387fe18f:6660c5bb2d82dafec63534768e08c6a479218ec5 !1401114842 C:\boot.ini
+++1688:33206:0:0:cc1e399436472fed13970f520096559e:429673d3064be1ac4f2b25bff279cbdde711ad00 !1401114842 C:\WINDOWS/System32/AUTOEXEC.NT
+++25088:33279:0:0:b7ce8d04e1426e3ff74ba835826e20b1:2dbfe5ca6c8893c660ce4c97ed70f8065a93df06 !1401114842 C:\WINDOWS/System32/at.exe
+++12288:33279:0:0:b51ffd72c9dc537087065016d7185bb9:6eec50bf6d94ba8e0f0168c8da96bb199ee01f72 !1401114842 C:\WINDOWS/System32/attrib.exe
+++19968:33279:0:0:b2e1cfc665e99c2182bc0090c1c4c75f:94eb86cf15d4ba2cb6998c059f0714fe9da51bbf !1401114842 C:\WINDOWS/System32/cacls.exe
+++20634:33279:0:0:f62e79ee40057f3a85cc143cb5fa67a5:16c051778b7d47dff27cc7298d7be6b6af7726e7 !1401114842 C:\WINDOWS/System32/debug.exe
+++28112:33279:0:0:10876464c41cedd474db98f6c3ab0fa1:0c11a0701360d77315c08fa9c50ba79792644f4e !1401114842 C:\WINDOWS/System32/drwatson.exe
+++45568:33279:0:0:4aeb5e644fde4cd143096b77f955a37b:42cd474e4114f6b1e4abde448eda02758536f2d2 !1401114842 C:\WINDOWS/System32/drwtsn32.exe
+++12642:33279:0:0:7381acbcaa8085b97ec5be6feca6b1e8:5977a028c45394e639fb6bd40c704dee6eb9a1fb !1401114842 C:\WINDOWS/System32/edlin.exe
+++50688:33279:0:0:7e3712b2c6b42ed1a4810b2e85e490b9:566ede9eae0c71e6d0c60b91e151fdcdbddbe056 !1401114842 C:\WINDOWS/System32/eventcreate.exe
+++82944:33279:0:0:9acb96e03402b501ad03ae9d153ce593:dad13844179167907a017b472034502294722c6f !1401114842 C:\WINDOWS/System32/eventtriggers.exe
+++42496:33279:0:0:2086c102f1e00ecc3f353c2d7e03f3b2:02a2413e9b04d6df5681371be85ba238083d1d73 !1401114844 C:\WINDOWS/System32/ftp.exe
+++42496:33279:0:0:be161275170e304694fdf5f9d1eb9e4e:1c91337ebb72a3ccf68335f42f6dea1d462e6d8c !1401114844 C:\WINDOWS/System32/net.exe
+++124928:33279:0:0:85322bb4878f5a6a1082e7f32ffab11c:edeb17513b0d74b9c79210975d3e3e8d1d98d386 !1401114844 C:\WINDOWS/System32/net1.exe
+++86016:33279:0:0:3b7460095cf973d1813fbcbb57c40833:bf47d1ca532cb793005c675b815fd0d9cc3e467b !1401114844 C:\WINDOWS/System32/netsh.exe
+++21504:33279:0:0:a10dda286946449b6d7dad9c04049dfe:66a5809717a580b407d7307379663073249f88b4 !1401114844 C:\WINDOWS/System32/rcp.exe
+++50176:33279:0:0:9fa007922f3e5aef5336550c60c079d9:3561bab6a6f7e296bcb68c9bddf42f2a2fc5605e !1401114844 C:\WINDOWS/System32/reg.exe
+++146432:33279:0:0:c9ce86c6ec3e46375f91d71c126c17d8:cd3147fe008576592b34aca09a206b5b2d47390c !1401114844 C:\WINDOWS/regedit.exe
+++3584:33279:0:0:f1e1d50a114308c18b8ed625c00f84cd:ab144a35c40b10617674cebddd7b1f92800c108e !1401114844 C:\WINDOWS/System32/regedt32.exe
+++11776:33279:0:0:73c6b9cc9d5c86f439fe0cc22139cb06:8f71f689cb0a4d186c95cddc7397d6ad7b7b98f6 !1401114844 C:\WINDOWS/System32/regsvr32.exe
+++13824:33279:0:0:f782c5797936b2a0b4924791d411b5c1:6babd659cd244af7a42d6e3124917125a29e24c2 !1401114844 C:\WINDOWS/System32/rexec.exe
+++14848:33279:0:0:5ceb736e4c4bb924150e767d0e75471d:5bff0f9d8d17fed50c3a09212e0c21a6d5afa00d !1401114844 C:\WINDOWS/System32/rsh.exe
+++16384:33279:0:0:5d296039665d63018259ed0b1a88d43c:bb33b5d2dad2c6f5e5970053f796be7d7544af34 !1401114844 C:\WINDOWS/System32/runas.exe
+++35328:33279:0:0:4725b599f4946134955038907785401a:f6063d436a98225d7d05c668d640b38d01148764 !1401114844 C:\WINDOWS/System32/sc.exe
+++9216:33279:0:0:60c245225e759fb37223cb00c5924a45:05331733d1b93092650d58040e46d9fb8fa77819 !1401114844 C:\WINDOWS/System32/subst.exe
+++76288:33279:0:0:962849304dc0716d4f5e6b532826eac9:7461ce748b95f4b474e22b8c1b304f351f90001a !1401114844 C:\WINDOWS/System32/telnet.exe
+++16896:33279:0:0:f5f00e7585141e59ad27213f14d1ac4a:78730d158e2a222f1a913eae42bb9731fdd0779e !1401114846 C:\WINDOWS/System32/tftp.exe
+++73216:33279:0:0:5f943d69c35acc1cdbe444ddab7bc4d3:44a7d807d89aba06e6c447482c9fd5f7d06c2901 !1401114846 C:\WINDOWS/System32/tlntsvr.exe
+++768:33206:0:0:9d9fffbaa563d47b795ca72c1499bfea:9d3faa028f3558ab15b353c3f0f7233bebef064c !1401114846 C:\WINDOWS/System32/drivers/etc/hosts
+++3683:33206:0:0:a6da6d0247a309e080cafd4d84b68efd:c6c53df2820cf7e796bea68b5f392baeeef5b811 !1401114846 C:\WINDOWS/System32/drivers/etc/lmhosts.sam
+++407:33206:0:0:39a13dd5e24491fbfcf81d9ad45dc3c6:49cfc59c518620abdffa5aff9b8f6c306c81e94a !1401114846 C:\WINDOWS/System32/drivers/etc/networks
+++799:33206:0:0:584358ed2e7778d9df48127722fac8ff:05bf58d2ca87772573c00b0da23970257252592e !1401114846 C:\WINDOWS/System32/drivers/etc/protocol
+++7116:33206:0:0:8a1991455775af63f05e46983f36e275:b7f38efaef425d47c9ede36466fc9cde5f226fb6 !1401114846 C:\WINDOWS/System32/drivers/etc/services
+++84:33206:0:0:9dd844cd10086c5dfa0752c993bee334:da7fb5b36194ec6a82abefddbbc377a28cfeefae !1401114846 C:\Documents and Settings/All Users/Start Menu/Programs/Startup/desktop.ini"""
        f = open("/tmp/registry","w")
        f.write(raw_file)
        f.close()

        if not remotely_copy_file(ossim_setup.get_general_admin_ip(), "/tmp/registry", "/var/ossec/queue/syscheck/\"(windows_behave) 172.17.2.99->syscheck-registry\""):
            print "Can't create the virtual registry log file"
            raise KeyboardInterrupt()


def modified_registry_entries_restore():
    remotely_remove_file(ossim_setup.get_general_admin_ip(), "/var/ossec/queue/syscheck/\"(windows_behave) 172.17.2.99->syscheck-registry\"")
    remotely_restore_file(ossim_setup.get_general_admin_ip(), "/var/ossec/etc/client.keys.bk","/var/ossec/etc/client.keys")


def get_passlist_scenario1_prepare():
    remotely_backup_file(ossim_setup.get_general_admin_ip(), "/var/ossec/agentless/.passlist", "/var/ossec/agentless/.passlist.bk")
    remotely_remove_file(ossim_setup.get_general_admin_ip(), "/var/ossec/agentless/.passlist")

def get_passlist_scenario1_restore():
    print remotely_copy_file(ossim_setup.get_general_admin_ip(), "/var/ossec/agentless/.passlist.bk","/var/ossec/agentless/.passlist")


def put_passfile_scenario1_prepare():
    result, system_id = get_system_id_from_local()
    if not result:
        raise  KeyboardInterrupt()
    base_path = "/var/alienvault/%s/ossec/" % system_id
    pass_file = base_path + "agentless/.passlist"
    pass_file_backup = base_path + "agentless/.passlist.bk"

    remotely_backup_file(ossim_setup.get_general_admin_ip(),pass_file,
                         pass_file_backup)
    remotely_remove_file(ossim_setup.get_general_admin_ip(), pass_file)

def put_passfile_scenario1_restore():
    result, system_id = get_system_id_from_local()
    if not result:
        raise  KeyboardInterrupt()
    base_path = "/var/alienvault/%s/ossec/" % system_id
    pass_file = base_path + "agentless/.passlist"
    pass_file_backup = base_path + "agentless/.passlist.bk"
    remotely_restore_file(ossim_setup.get_general_admin_ip(), pass_file_backup, pass_file)
    remotely_remove_file(ossim_setup.get_general_admin_ip(), pass_file_backup)


def put_passfile_scenario2_prepare():
    raw_file="""root@192.168.1.45|mypasss123|"""
    result, system_id = get_system_id_from_local()
    if not result:
        raise  KeyboardInterrupt()
    base_path = "/var/alienvault/%s/ossec/" % system_id
    pass_file = base_path + "agentless/.passlist"
    pass_file_backup = base_path + "agentless/.passlist.bk"

    ossec_pass_file = "/var/ossec/agentless/.passlist"
    ossec_pass_file_backup = "/var/ossec/agentless/.passlist.bk"

    remotely_backup_file(ossim_setup.get_general_admin_ip(),pass_file,
                         pass_file_backup)
    remotely_remove_file(ossim_setup.get_general_admin_ip(), pass_file)

    remotely_backup_file(ossim_setup.get_general_admin_ip(),ossec_pass_file,
                         ossec_pass_file_backup)
    remotely_remove_file(ossim_setup.get_general_admin_ip(), ossec_pass_file)

    f = open(pass_file,"w")
    f.write(raw_file)
    f.close()


def put_passfile_scenario2_restore():
    result, system_id = get_system_id_from_local()
    if not result:
        raise  KeyboardInterrupt()
    base_path = "/var/alienvault/%s/ossec/" % system_id
    pass_file = base_path + "agentless/.passlist"
    pass_file_backup = base_path + "agentless/.passlist.bk"
    remotely_restore_file(ossim_setup.get_general_admin_ip(), pass_file_backup, pass_file)
    remotely_remove_file(ossim_setup.get_general_admin_ip(), pass_file_backup)

    ossec_pass_file = "/var/ossec/agentless/.passlist"
    ossec_pass_file_backup = "/var/ossec/agentless/.passlist.bk"

    remotely_restore_file(ossim_setup.get_general_admin_ip(), ossec_pass_file_backup, ossec_pass_file)
    remotely_remove_file(ossim_setup.get_general_admin_ip(), ossec_pass_file_backup)


def empty_ossec_keys_file():
    remotely_backup_file("127.0.0.1", "/var/ossec/etc/client.keys", "/var/ossec/etc/client.keys.bk")
    remotely_remove_file("127.0.0.1", "/var/ossec/etc/client.keys")


def restore_ossec_keys_file():
    remotely_restore_file("127.0.0.1", "/var/ossec/etc/client.keys.bk", "/var/ossec/etc/client.keys")
    remotely_remove_file("127.0.0.1", "/var/ossec/etc/client.keys.bk")


def add_fake_ossec_agent():
    # Add fake agent so agent creation through API will fail
    # echo "001 test_agent 10.1.1.1 436f12f28757e6eb67ddfd0a226380d2c04939238eff94f21369495f1cf8e3cc" > /var/ossec/etc/client.keys
    remotely_backup_file("127.0.0.1", "/var/ossec/etc/client.keys", "/var/ossec/etc/client.keys.bk")
    remotely_create_sample_client_keys_file("127.0.0.1")
