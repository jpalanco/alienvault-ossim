#!/usr/bin/env python

import xml.sax.handler as pxml
from xml.sax import make_parser
import ConfigParser
import pdb
import os
import time
import sys
import os
import re
class rule_info():
    def __init__(self):
        self.__type = 'notype'
        self.__value = ''

    def get_type(self):
        return self.__type


    def get_value(self):
        return self.__value


    def set_type(self, value):
        self.__type = value


    def set_value(self, value):
        self.__value = value


    def del_type(self):
        del self.__type


    def del_value(self):
        del self.__value
    def printdata(self):
        print "\t\t\tinfo type:<%s> value:<%s>" % (self.__type, self.__value)
    type = property(get_type, set_type, del_type, "type's docstring")
    value = property(get_value, set_value, del_value, "value's docstring")

    #def get_pluginid_by_sids():


class rule ():
    def __init__(self):
        self.__id = 0
        self.__level = 0
        self.__decode = 'null'
        self.__description = 'null'
        self.__matchList = []
        self.__ifsid = 0
        self.__group = 'null'
        self.__avplugin_sid = 0
        self.__infolist = []#lista de ruleinfo.

    def get_id(self):
        return self.__id


    def get_level(self):
        return self.__level


    def get_decode(self):
        return self.__decode


    def get_description(self):
        return self.__description


    def get_match_list(self):
        return self.__matchList


    def get_ifsid(self):
        return self.__ifsid


    def get_group(self):
        return self.__group


    def get_avplugin_sid(self):
        return self.__avplugin_sid


    def get_infolist(self):
        return self.__infolist


    def set_id(self, value):
        self.__id = value


    def set_level(self, value):
        self.__level = value


    def set_decode(self, value):
        self.__decode = value


    def set_description(self, value):
        self.__description = value


    def set_match_list(self, value):
        self.__matchList = value


    def set_ifsid(self, value):
        self.__ifsid = value


    def set_group(self, value):

        group_list = value.split(',')
        group_len = len(group_list)
        for i in range(group_len - 1, -1, -1):
            if not group_list[i] == '':
                self.__group = group_list[i].strip()
                break
        #print "set_group rule_id:%s group:%s value:%s" % (self.__id, self.__group, value)



    def set_avplugin_sid(self, value):
        self.__avplugin_sid = value


    def set_infolist(self, value):
        self.__infolist = value


    def del_id(self):
        del self.__id


    def del_level(self):
        del self.__level


    def del_decode(self):
        del self.__decode


    def del_description(self):
        del self.__description


    def del_match_list(self):
        del self.__matchList


    def del_ifsid(self):
        del self.__ifsid


    def del_group(self):
        del self.__group


    def del_avplugin_sid(self):
        del self.__avplugin_sid


    def del_infolist(self):
        del self.__infolist

    def addMatch(self, v):
        self.__matchList.append(v)
    def addInfo(self, info):
        self.__infolist.append(info)
    id = property(get_id, set_id, del_id, "id's docstring")
    level = property(get_level, set_level, del_level, "level's docstring")
    decode = property(get_decode, set_decode, del_decode, "decode's docstring")
    description = property(get_description, set_description, del_description, "description's docstring")
    matchList = property(get_match_list, set_match_list, del_match_list, "matchList's docstring")
    ifsid = property(get_ifsid, set_ifsid, del_ifsid, "ifsid's docstring")
    group = property(get_group, set_group, del_group, "group's docstring")
    avplugin_sid = property(get_avplugin_sid, set_avplugin_sid, del_avplugin_sid, "avplugin_sid's docstring")
    infolist = property(get_infolist, set_infolist, del_infolist, "infolist's docstring")
    def  printdata(self):
        print "\t\trule id:<%s> level:<%s> ,description:<%s> - group: <%s>" % (self.__id, self.__level, self.__description, self.__group)
        for r in self.__matchList:
            print "\t\t\tmatch : <%s>" % r
        for info in self.__infolist:
            info.printdata()


class ossec_data():
    def __init__(self):
        self.__name = ''
        self.__ruleList = [] #lista objetos tipo rule
        self.__avplugin_id = 0
        self.__groupDict = {}
        self.__groupdic_loaded = False

    def get_avplugin_id(self):
        return self.__avplugin_id

    def get_name(self):
        return self.__name

    def get_rule_list(self):
        return self.__ruleList

    def set_name(self, value):
        self.__name = value

    def set_rule_list(self, value):
        self.__ruleList = value

    def set_avplugin_id(self, value):
        self.__avplugin_id = value

    def del_avplugin_id(self):
        del self.__name

    def del_name(self):
        del self.__name

    def del_rule_list(self):
        del self.__ruleList

    def addrule(self, rule):
        self.__ruleList.append(rule)
    def get_group_dic(self):
        #print "Dagaj"
        if not self.__groupdic_loaded:
            self.fill_group_dic()
        return self.__groupDict
    def fill_group_dic(self):
        #import pdb
        #pdb.set_trace()
        for r in self.__ruleList:
            if not self.__groupDict.has_key(r.get_group()):
                self.__groupDict[r.get_group()] = ''
        self.__groupdic_loaded = True

    name = property(get_name, set_name, del_name, "name's docstring")
    ruleList = property(get_rule_list, set_rule_list, del_rule_list, "ruleList's docstring")
    avplugin_id = property(get_avplugin_id, set_avplugin_id, del_avplugin_id, "avplugin id docstring")

    def printdata(self):
        print"ossecdata-  name <%s> " % self.__name
        for r in self.__ruleList:
            r.printdata()


class ossec_rule_handler(pxml.ContentHandler):
    GROUP_NODE = 'group'
    GROUP_NODE_ATTR_NAME = 'name'
    GROUP_NODE_RULE_NODE = 'rule'
    GROUP_NODE_RULE_NODE_ATTR_ID = 'id'
    GROUP_NODE_RULE_NODE_ATTR_LEVEL = 'level'
    GROUP_NODE_RULE_NODE_DECODE_NODE = 'decode_as'
    GROUP_NODE_RULE_NODE_DESCRIPTION_NODE = 'description'
    GROUP_NODE_RULE_NODE_IF_SID_NODE = 'if_sid'
    GROUP_NODE_RULE_NODE_MATCH_NODE = 'match'
    GROUP_NODE_RULE_NODE_INFO_NODE = 'info'
    GROUP_NODE_RULE_NODE_INFO_NODE_ATTR_TYPE = 'type'
    GROUP_NODE_RULE_NODE_GROUP_NODE = 'group'

    def __init__(self):
        self._ossecdata = ossec_data()
        self._tmpRule = None
        self._tmpRuleDescription = ''
        self._readingDecodeAsValue = False
        self._readingDescriptionValue = False
        self._readingifsidValue = False
        self._readingmatchValue = False
        self._tmpMatch = ''
        self._tmpInfo = None
        self._readinginfoValue = False
        self._readingrulegroup = False
        self.__inside_rule_node = False
        self.__rulegroup = 'null'
        self._parentGroup = ''
    def startElement(self, name, attributes):
        self.__rulegroup == ''
        if name == ossec_rule_handler.GROUP_NODE and not self.__inside_rule_node:
            if attributes.has_key(ossec_rule_handler.GROUP_NODE_ATTR_NAME):
#                print "Atributo name:%s " % attributes[ossec_rule_handler.GROUP_NODE_ATTR_NAME]
                self._ossecdata.set_name(attributes[ossec_rule_handler.GROUP_NODE_ATTR_NAME])
                self._parentGroup = attributes[ossec_rule_handler.GROUP_NODE_ATTR_NAME]
                #print "Parent group: %s" % self._parentGroup
        if name == ossec_rule_handler.GROUP_NODE_RULE_NODE:
            self.__inside_rule_node = True
            self._tmpRuleDescription = ''
            self._tmpRule = rule()
            #print "Setting parent group: %s " % self._parentGroup
            self._tmpRule.set_group(self._parentGroup)
            if attributes.has_key(ossec_rule_handler.GROUP_NODE_RULE_NODE_ATTR_ID):
                self._tmpRule.set_id(attributes[ossec_rule_handler.GROUP_NODE_RULE_NODE_ATTR_ID])
            if attributes.has_key(ossec_rule_handler.GROUP_NODE_RULE_NODE_ATTR_LEVEL):
                self._tmpRule.set_level(attributes[ossec_rule_handler.GROUP_NODE_RULE_NODE_ATTR_LEVEL])

        if name == ossec_rule_handler.GROUP_NODE_RULE_NODE_DECODE_NODE and self.__inside_rule_node:
            self._readingDecodeAsValue = True

        if name == ossec_rule_handler.GROUP_NODE_RULE_NODE_DESCRIPTION_NODE and self.__inside_rule_node:
            self._readingDescriptionValue = True

        if name == ossec_rule_handler.GROUP_NODE_RULE_NODE_IF_SID_NODE and self.__inside_rule_node:
            self._readingifsidValue = True

        if name == ossec_rule_handler.GROUP_NODE_RULE_NODE_MATCH_NODE and self.__inside_rule_node:
            self._readingmatchValue = True

        if name == ossec_rule_handler.GROUP_NODE_RULE_NODE_INFO_NODE and self.__inside_rule_node:
            self._tmpInfo = rule_info()
            self._readinginfoValue = True
            if attributes.has_key(ossec_rule_handler.GROUP_NODE_RULE_NODE_INFO_NODE_ATTR_TYPE):
                self._tmpInfo.set_type(attributes[ossec_rule_handler.GROUP_NODE_RULE_NODE_INFO_NODE_ATTR_TYPE])

        if name == ossec_rule_handler.GROUP_NODE_RULE_NODE_GROUP_NODE and self.__inside_rule_node:
           # print "Reading rule group --- %s Parent group : %s" % (self._tmpRule.get_id(), self._parentGroup)
            self._readingrulegroup = True


    def characters(self, data):
        if self._readingDecodeAsValue:
            self._tmpRule.set_decode(data)
        if self._readingDescriptionValue:
            self._tmpRuleDescription += data
        if self._readingifsidValue:
            self._tmpRule.set_ifsid (data)
        if self._readingmatchValue:
            self._tmpMatch = data
        if self._readinginfoValue:
           self._tmpInfo.set_value (data)
        if self._readingrulegroup:
            self.__rulegroup = data

    def endElement(self, name):
        if name == ossec_rule_handler.GROUP_NODE:
           pass
        if name == ossec_rule_handler.GROUP_NODE_RULE_NODE:
            print "Adding rule description :%s" % self._tmpRuleDescription
            self._tmpRule.set_description(self._tmpRuleDescription)
            self._ossecdata.addrule(self._tmpRule)
            self.__inside_rule_node = False

        if name == ossec_rule_handler.GROUP_NODE_RULE_NODE_DECODE_NODE:
            self._readingDecodeAsValue = False


        if name == ossec_rule_handler.GROUP_NODE_RULE_NODE_DESCRIPTION_NODE:
            self._readingDescriptionValue = False


        if name == ossec_rule_handler.GROUP_NODE_RULE_NODE_IF_SID_NODE:
            pass
        if name == ossec_rule_handler.GROUP_NODE_RULE_NODE_MATCH_NODE:
            self._readingmatchValue = False
            self._tmpRule.addMatch(self._tmpMatch)

        if name == ossec_rule_handler.GROUP_NODE_RULE_NODE_INFO_NODE:
            self._readinginfoValue = False
            self._tmpRule.addInfo(self._tmpInfo)

        if name == ossec_rule_handler.GROUP_NODE_RULE_NODE_GROUP_NODE and self.__inside_rule_node:
            self._readingrulegroup = False
#            print "He leido group hijo: %s" % self.__rulegroup
            self._tmpRule.set_group(self.__rulegroup)

    def getData(self):
        return self._ossecdata


class avplugins ():
    def __init__(self, default=None):
        dict.__init__(self)
        self.__id = 0
        self.__type = 0
        self.__name = ''
        self.__description = ''
        self.__sids = []#lista de sids

    def __getitem__(self, key):
        try:
            return dict.__getitem__(self, key)
        except KeyError:
            return self.__id

    def get_id(self):
        return self.__id
    def get_type(self):
        return self.__type
    def get_name(self):
        return self.__name
    def get_description(self):
        return self.__description
    def get_sids(self):
        return self.__sids
    def set_id(self, value):
        self.__id = value
    def set_type(self, value):
        self.__type = value
    def set_name(self, value):
        self.__name = value
    def set_description(self, value):
        self.__description = value
    def set_sids(self, value):
        self.__sids = value
    def del_id(self):
        del self.__id
    def del_type(self):
        del self.__type
    def del_name(self):
        del self.__name
    def del_description(self):
        del self.__description
    def del_sids(self):
        del self.__sids
    def addSid(self, sid):
        self.__sids.append(sid)

class avsids ():
    def __init__(self):
        self.__sid = 0
        self.__category = 0
        self.__class = 0
        self.__reliability = 0
        self.__priority = 0
        self.__name = ''

    def get_sid(self):
        return self.__sid
    def set_sid(self, value):
        self.__sid = value
    def del_sid(self):
        del self.__sid

    def get_category(self):
        return self.__category
    def set_category(self, value):
        self.__category = value
    def del_category(self):
        del self.__category

    def get_class(self):
        return self.__class
    def set_class(self, value):
        self.__class = value
    def del_class(self):
        del self.__class

    def get_reliability(self):
        return self.__reliability
    def set_reliability(self, value):
        self.__reliability = value
    def del_reliability(self):
        del self.__reliability

    def get_priority(self):
        return self.__priority
    def set_priority(self, value):
        self.__priority = value
    def del_priority(self):
        del self.__priority

    def get_name(self):
        return self.__name
    def set_name(self, value):
        self.__name = value
    def del_name(self):
        del self.__name


class pair():
    def __init__(self):
        self.__rule_id = 0
        self.__group_name = ''
        self.__notFound = False

    def get_not_found(self):
        return self.__notFound


    def set_not_found(self, value):
        self.__notFound = value


    def del_not_found(self):
        del self.__notFound


    def get_rule_id(self):
        return self.__rule_id


    def get_group_name(self):
        return self.__group_name


    def set_rule_id(self, value):
        self.__rule_id = value


    def set_group_name(self, value):
        self.__group_name = value


    def del_rule_id(self):
        del self.__rule_id


    def del_group_name(self):
        del self.__group_name
    def printPair(self):
        print "rule_id:%s group:%s" % (self.__rule_id, self.__group_name)
    def __str__(self):
        st = "rule_id:%s     group:%s " % (self.__rule_id, self.__group_name)
        return st
    rule_id = property(get_rule_id, set_rule_id, del_rule_id, "rule_id's docstring")
    group_name = property(get_group_name, set_group_name, del_group_name, "group_name's docstring")
    notFound = property(get_not_found, set_not_found, del_not_found, "notFound's docstring")


class pluginfile():
    def __init__(self, dic_ruleid_translation, dic_ruleid_group_name, dic_ruleid_description):

        self.__filename = ''
        #self.__translation = trclass()
        self.__xmlData = None
        self.__xmlDataReaded = False
        self.__tranlationsdic = {}
        self.__tranlaction_rule_id_group = {}
        self.__dic_ruleid_translation = dic_ruleid_translation
        self.__dic_ruleid_group_name = dic_ruleid_group_name
        self.__dic_ruleid_description = dic_ruleid_description

    def get_translation_rule_id_group(self):
        return self.__tranlaction_rule_id_group
    def get_configurationfile(self):
        return self.__configurationfile


    def get_xml_data(self):
        return self.__xmlData


    def get_xml_data_readed(self):
        return self.__xmlDataReaded

    def set_xml_data(self, value):
        self.__xmlData = value


    def set_xml_data_readed(self, value):
        self.__xmlDataReaded = value


    def del_configurationfile(self):
        del self.__configurationfile


    def del_xml_data(self):
        del self.__xmlData


    def del_xml_data_readed(self):
        del self.__xmlDataReaded


    def get_filename(self):
        return self.__filename


    def set_filename(self, value):
        self.__filename = value

    def del_filename(self):
        del self.__filename


#    def del_translation(self):
#        del self.__translation

    def parseFile(self):
        parser_ossec_xml = make_parser()
        handler = ossec_rule_handler()
        parser_ossec_xml.setContentHandler(handler)
        parser_ossec_xml.parse(open(self.__filename))
        self.__xmlData = handler.getData()
        self.__xmlDataReaded = True
        #self.__xmlData.printdata()

    def loadTranslations(self):
        for rule in self.__xmlData.get_rule_list():
            #print "Fichero xml: %s rule_id: %s groupname_:%s" % (self.get_filename(), rule.get_id(), rule.get_group())
            if not self.__dic_ruleid_description.has_key(rule.get_id()):
                self.__dic_ruleid_description[rule.get_id()] = rule.get_description()

            if not self.__dic_ruleid_translation.has_key(rule.get_id()):
                self.__dic_ruleid_translation[rule.get_id()] = 0
            self.__dic_ruleid_group_name[rule.get_id()] = rule.get_group()
    def printYourRules(self):
        print "========================================================"
        print "RULES READED FROM FILE: %s" % self.get_filename()
        for rule in self.__xmlData.get_rule_list():
             print "rule_id: %s groupname_:%s" % (rule.get_id(), rule.get_group())
        print "========================================================"
    def printTranslationDic (self):
        for tr, va  in self.__tranlationsdic.items():
            print "%s --> %s " % (tr, va)
    def printTranslationRuleGroup(self):
        print "====================================================="
        print "Readed file: %s" % self.get_filename()

        for tr_id, list_pair in self.__tranlaction_rule_id_group.items():
            assigned_pairs = []
            not_assigned_pairs = []
            for pair in list_pair:
                if not pair.get_not_found():
                    assigned_pairs.append(pair)
                else:
                    not_assigned_pairs.append(pair)
            if len(assigned_pairs) > 0:
                print "Translation id: %s" % tr_id
                for pair in assigned_pairs:
                    print pair
                print ""
                print ""
                print "_________________ Not assigned pairs:"
                for pair in not_assigned_pairs:
                    print pair

    filename = property(get_filename, set_filename, del_filename, "filename's docstring")
    xmlData = property(get_xml_data, set_xml_data, del_xml_data, "xmlData's docstring")
    xmlDataReaded = property(get_xml_data_readed, set_xml_data_readed, del_xml_data_readed, "xmlDataReaded's docstring")

class error_file():
    def __init__(self):
        self.__file = ''
        self.__exception = None

    def get_file(self):
        return self.__file


    def get_exception(self):
        return self.__exception


    def set_file(self, value):
        self.__file = value


    def set_exception(self, value):
        self.__exception = value


    def del_file(self):
        del self.__file


    def del_exception(self):
        del self.__exception

    file = property(get_file, set_file, del_file, "file's docstring")
    exception = property(get_exception, set_exception, del_exception, "exception's docstring")


class PluginInfo():
    def __init__(self):
        self.__ruleid_sid = '0'
        self.__group_name = 'na'
        self.__translation_pluginid = '0'
        self.__real_plugin_id = '0'
        self.__isnew = True
    def get_isnew(self):
        return self.__isnew
    def set_isnew(self, v):
        self.__isnew = False
    def get_ruleid_sid(self):
        return self.__ruleid_sid


    def get_group_name(self):
        return self.__group_name


    def get_translation_pluginid(self):
        return self.__translation_pluginid


    def get_real_plugin_id(self):
        return self.__real_plugin_id


    def set_ruleid_sid(self, value):
        self.__ruleid_sid = value


    def set_group_name(self, value):
        self.__group_name = value


    def set_translation_pluginid(self, value):
        self.__translation_pluginid = value


    def set_real_plugin_id(self, value):
        self.__real_plugin_id = value


    def del_ruleid_sid(self):
        del self.__ruleid_sid


    def del_group_name(self):
        del self.__group_name


    def del_translation_pluginid(self):
        del self.__translation_pluginid


    def del_real_plugin_id(self):
        del self.__real_plugin_id

    ruleid_sid = property(get_ruleid_sid, set_ruleid_sid, del_ruleid_sid, "ruleid_sid's docstring")
    group_name = property(get_group_name, set_group_name, del_group_name, "group_name's docstring")
    translation_pluginid = property(get_translation_pluginid, set_translation_pluginid, del_translation_pluginid, "translation_pluginid's docstring")
    real_plugin_id = property(get_real_plugin_id, set_real_plugin_id, del_real_plugin_id, "real_plugin_id's docstring")

def getFiles(dir, list):
    #pdb.set_trace()
    #print "Files in dir: %s" % dir
    for fname in os.listdir(dir):
        absolute_path = os.path.join(os.path.abspath(dir), fname)
        if os.path.isdir(absolute_path):
            #print "get files from %s" % absolute_path
            getFiles(absolute_path, list)
        else:
           # print "adding file:%s" % absolute_path
            list.append(absolute_path)

    return True


def usage():
    print "Rule map converter:"
    print "Ex: ./ossec-new-rules.py <ossec plugin sql file> <ossec plugin config file> <rules dir>\n"
    print "IMPORTANT: Ossec rules aren't standard XML so must run ossec-clean-to-tmp.sh and use /tmp/ossec_rules as <rules dir>\n"
def readSQLFile(sql_file, dic_plugin_id_gname, sql_groupnames_list):
    """
    INSERT IGNORE INTO plugin(id, type, name, description) VALUES(7073, 1, "AlienVault HIDS-postfix", "Postfix");
    """
    print "SQL INFORMATION -----------------------------"
    reg_str = "INSERT IGNORE INTO plugin\(id, type, name, description\) VALUES\((?P<plugin_id>\d+), \d+, \"AlienVault HIDS-(?P<gname>.*)\", \"(.*)\"\);"
    regx = re.compile(reg_str)
    for line in open(sql_file, 'r').readlines():
        mg = regx.match(line)
        pid = 0
        gn = ''
        if mg:
            if mg.group('plugin_id'):
                 pid = mg.group('plugin_id')
            if mg.group('gname'):
                gn = mg.group('gname')
            print "Plugin-id: %s gname = %s" % (pid, gn)
            dic_plugin_id_gname[pid] = gn
            if not gn in sql_groupnames_list:
                sql_groupnames_list.append(gn)
    print "SQL INFORMATION -----------------------------END"

def readConfigFile(config_file, dic_ruleid_translation, dic_ruleid_group_name):
    config = ConfigParser.RawConfigParser()
    config.read(config_file)
    for v, k in config.items('translation'):
            #v = rule_id
            #k = translation
        if v != "plugin_id":
            dic_ruleid_translation[v] = k
            dic_ruleid_group_name[v] = ""


def getListPluginInfobygroup_name(dic, gname):
    tmpList = []
    for sid, pluginifo in dic.items():
        if pluginifo.get_group_name() == gname:
            pluginifo.set_isnew(False)
            tmpList.append(pluginifo)
    return tmpList

if __name__ == '__main__':
#
    sql_file = 'ossec.sql'
    rules_directory = "./rules"
    config_file = 'ossec.cfg'
    if len(sys.argv) < 4:
        usage()
        exit(0)
    sql_file = sys.argv[1]
    config_file = sys.argv[2]
    rules_directory = sys.argv[3]
    if not os.path.isfile(sql_file):
        print "%s file not exists!" % sql_file
        usage()
        exit(-1)
    if not os.path.isfile(config_file):
        print "%s file not exists!" % config_file
        usage()
        exit(-1)
    if not os.path.exists(rules_directory):
        print "%s directory not exist!" % rules_directory
        usage()
        exit(-2)
    dic_plugin_id_gname = {}#SQL
    dic_ruleid_translation = {}#CONFIG FILE
    dic_ruleid_group_name = {}#CONFIG FILE
    dic_ruleid_description = {}#from xml files.
    sql_groupnames_list = []
    readSQLFile(sql_file, dic_plugin_id_gname, sql_groupnames_list)
    readConfigFile(config_file, dic_ruleid_translation, dic_ruleid_group_name)

    print ""
    print ""
    print "CONFIGURATION FILE DATA OBTAINED:"
    print "\tRULE_ID, TRANLATION DIC INIT:"
    for rid, trid in dic_ruleid_translation.items():
        print "\t\t ruleid:%s, translationid:%s" % (rid, trid)
    print "\tRULE_ID, TRANLATION DIC END"
#    print "\tRULE_ID, GROUP NAME DIC INIT:"
#    for rid, gname in dic_ruleid_group_name.items():
#        print "\t\t ruleid:%s, gname:%s" % (rid, gname)
#    print "\tRULE_ID, GROUP NAME DIC END"
    print "END CONFIGURATION FILE DATA"
    print ""
    print ""
    file_list = []
    error_files = []

    print "DATA BEFORE READED XML FILES INIT"
    if(getFiles(rules_directory, file_list)):
        #print "Number of files to read: %d" % len(file_list)
        plugins_files = []

        for file in file_list:
            #print "Reading file  ............%s" % file
            try:
                pl_file = pluginfile(dic_ruleid_translation, dic_ruleid_group_name, dic_ruleid_description)
                pl_file.set_filename(file)
                #pl_file.set_configurationfile(config_file)
                pl_file.parseFile()
                pl_file.loadTranslations()
                #pl_file.printTranslationRuleGroup()
                plugins_files.append(pl_file)
                pl_file.printYourRules()

            except Exception, e:
                err = error_file()
                err.set_file(file)
                err.set_exception(e)
                error_files.append(err)
    for file in error_files:
        print "Error reading file: %s \n\tException: %s" % (file.get_file(), file.get_exception())

    for rid, trid in dic_ruleid_translation.items():
        print "\t\t ruleid:%s, translationid:%s" % (rid, trid)
    print "\tRULE_ID, TRANLATION DIC END"
    print "\tRULE_ID, GROUP NAME DIC INIT:"
    for rid, gname in dic_ruleid_group_name.items():
        print "\t\t ruleid:%s, gname:%s" % (rid, gname)
    print "\tRULE_ID, GROUP NAME DIC END"


    dic_group_name_translation = {}
    pluginInfo_dic = {}#key rule_id, PluginInfo

    for rule_id, group_name in dic_ruleid_group_name.items():
        translation = 0
        if dic_ruleid_translation.has_key(rule_id):
            translation = dic_ruleid_translation[rule_id]

        if not dic_group_name_translation.has_key(group_name):
            dic_group_name_translation[group_name] = translation
        elif dic_group_name_translation[group_name] == 0:
            dic_group_name_translation[group_name] = translation

        tmpPlugin = PluginInfo()
        tmpPlugin.set_ruleid_sid(rule_id)
        tmpPlugin.set_translation_pluginid(translation)
        tmpPlugin.set_group_name(group_name)
        pluginInfo_dic[rule_id] = tmpPlugin

    print "\t DICTIONARY GROUP_NAME, TRANSLATION ID"
    readed_gnames = dic_group_name_translation.keys()
    sql_pluginidlist = dic_plugin_id_gname.keys()
    print "Before:"
    print dic_plugin_id_gname
    print sql_pluginidlist 
    sql_pluginidlist.sort()
    print sql_pluginidlist[len(sql_pluginidlist) - 1]
    print sql_pluginidlist
    last_assigned_plugin_id = int(sql_pluginidlist[len(sql_pluginidlist) - 1])
    print "Last assiged plugin_id = %s" % last_assigned_plugin_id
    print sql_pluginidlist
    
    for readed_gname in readed_gnames:
        if not readed_gname in sql_groupnames_list:
            #search for new pluginid
            #
            new_plugin_id = last_assigned_plugin_id + 1
            founded = False
            while not founded:
                if not new_plugin_id in  sql_pluginidlist:
                    founded = True
                    last_assigned_plugin_id = new_plugin_id
                    sql_pluginidlist.append(new_plugin_id)

            print "Detected new gname: %s" % readed_gname
            dic_plugin_id_gname[new_plugin_id] = readed_gname

    for gname, trid in dic_group_name_translation.items():
        print "\t\t GNAME :%s TRANSLATIONID:%s " % (gname, trid)

    print "\t DICTIONARY GROUP_NAME, TRANSLATION ID END"

    print "\t DICTIONARY GROUP_NAME, PLUGIN INFO"
    for rule_id, pinfo in pluginInfo_dic.items():
        print "SID: %s TRANSLATION:%s GROUP-NAME:%s REAL-PID:%s ISNEW:%s" \
        % (pinfo.get_ruleid_sid(), pinfo.get_translation_pluginid(), pinfo.get_group_name(), pinfo.get_real_plugin_id(), pinfo.get_isnew())
    print "\t DICTIONARY GROUP_NAME, PLUGIN INFO END"

    print "DATA BEFORE READED XML FILES END"
    print ""
    print ""


    #Buscamos en dic_ruleid_group_name todos los rules id con groupname = dic_group_name_translation.
    #Una vez obtenidos buscamos en dic_ruleid_translation y ponemos el plugin id leido del sql. Si es 
    #distinto sacamos un warning.

    pidlist = dic_plugin_id_gname.keys()
    pidlist.sort()
    for pid, gname in dic_plugin_id_gname.items():

        tmplist = getListPluginInfobygroup_name(pluginInfo_dic, gname)
        print "Looking for plugins with group name: %s  Plugins found(%d)" % (gname, len(tmplist))
        for p in tmplist:
            if p.get_translation_pluginid() != pid:
                print "Warning rule_id:%s with translation:%s and group-name:%s has diferent pid (%s). Modifying it!" % (p.get_ruleid_sid(), p.get_translation_pluginid(), p.get_group_name(), pid)
                p.set_real_plugin_id(pid)

#    config = ConfigParser.RawConfigParser()
#    config.read(config_file)
#    config.remove_section("translation")
#    config.add_section("translation")

    nconfig = []
    # reading header of config file
    cfile = open(config_file, "r")
    while 1:
        line = cfile.readline()
        if not line:
            break
        if re.search(r'\[translation\]', line):
            nconfig.append(line)
            break
        nconfig.append(line)
    cfile.close()
        

    
    print "Detailed Report ----------------------------------init!"
    for rule_id, pinfo in pluginInfo_dic.items():
        print "SID: <%s> TRANSLATION:<%s> GROUP-NAME:<%s> REAL-PID:<%s> ISNEW:<%s>" \
        % (pinfo.get_ruleid_sid(), pinfo.get_translation_pluginid(), pinfo.get_group_name(), pinfo.get_real_plugin_id(), pinfo.get_isnew())
        pid = pinfo.get_translation_pluginid()
        if pinfo.get_real_plugin_id() != "0" :
            pid = pinfo.get_real_plugin_id()
        a = str(pinfo.get_ruleid_sid()) + "=" + str(pid) + "\n"
        nconfig.append(a)
        #config.set("translation",pinfo.get_ruleid_sid(),pid)
    #config.write(open("ossec.new.cfg","wb"))

    # reading footer of config file
    cfile = open(config_file, "r")
    stranslation = 0
    while 1:
        line = cfile.readline()
        if not line:
            break
        if stranslation==1 and re.search(r'\d+=\d+',line,re.M):
            pass
        if stranslation==1 and re.search(r'^\[',line,re.M):
            stranslation=2
        if stranslation==2:
            nconfig.append(line)
        if re.search(r'\[translation\]',line,re.M) and stranslation==0:
            stranslation=1
    cfile.close()

    print "Generating ossec.new.cfg"
    ncfile = open("ossec.new.cfg","w")
    for line in nconfig:
        ncfile.write("%s" % line)
    ncfile.close()


    print "Detailed Report ----------------------------------END!"
    print "SQLS -------------------------------"
    sql_statements = []
    for pid, gname in dic_plugin_id_gname.items():
        s1 = "DELETE FROM plugin where id = '%s';\n" % pid
        s2 = "DELETE FROM plugin_sid where plugin_id = '%s';\n" % pid
        s3 = 'INSERT IGNORE INTO plugin(id, type, name, description) VALUES(%s, 1, "AlienVault HIDS-%s", "%s");\n' % (pid, gname, gname)
        print s1
        print s2
        print s3
        sql_statements.append(s1)
        sql_statements.append(s2)
        sql_statements.append(s3)

    s1 = 'INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, reliability, priority, name) VALUES\n'
    print s1
    sql_statements.append(s1)
    for rule_id, pinfo in pluginInfo_dic.items():
        pid = 0
        if int(pinfo.get_real_plugin_id()) != 0:
            pid = int(pinfo.get_real_plugin_id())
        else:
            pid = pinfo.get_translation_pluginid()
        sid = pinfo.get_ruleid_sid()
        gname = pinfo.get_group_name()
        description = "Not founded"
        if dic_ruleid_description.has_key(rule_id):
            description = dic_ruleid_description[rule_id]
        s1 = '(%s, %s, NULL, NULL, 1,1, "AlienVault HIDS: %s"),\n' % (pid, sid, description.replace('"','\\"'))
        print s1
        sql_statements.append(s1)
    for sid in range(1, 99):
        s1 = '(7999, %s, NULL, NULL, 1, 2, "AlienVault HIDS: preprocessor"),\n' % (sid)
        print s1
        sql_statements.append(s1)
    s1 = '(7999, 99, NULL, NULL, 1, 2, "AlienVault HIDS: preprocessor");'
    print s1
    sql_statements.append(s1)
    print "SQLS -------------------------------END"
    sql_file = "ossec.new.sql"
    ff = open(sql_file, "w")
    ff.writelines(sql_statements)
    ff.close
    print "\n\n==============================================="
    print "Generated new config files: ossec.new.cfg  and ossec.new.sql"
    print "Recommended to do:"
    print "1) cp ossec.new.cfg /etc/ossim/agent/plugins/ossec.cfg"
    print "2) gzip -c ossec.new.sql > /usr/share/doc/ossim-mysql/contrib/plugins/ossec.sql.gz"
    print "3) zcat /usr/share/doc/ossim-mysql/contrib/plugins/ossec.sql.gz | ossim-db"
    print "4) /etc/init.d/ossim-server restart"
    print "5) /etc/init.d/ossim-agent restart"
