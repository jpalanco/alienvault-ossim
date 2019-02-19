#
#  License:
#
#  Copyright (c) 2013 AlienVault
#  All rights reserved.
#
#  This package is free software; you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation; version 2 dated June, 1991.
#  You may not use, modify or distribute this program under any other version
#  of the GNU General Public License.
#
#  This package is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with this package; if not, write to the Free Software
#  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#  MA  02110-1301  USA
#
#
#  On Debian GNU/Linux systems, the complete text of the GNU General
#  Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
#  Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

import MySQLdb
from xml.etree import ElementTree

class PassiveVulnScan:
  # Database objects.
  __db_conn = None
  __db_cursor = None

  # XML documents.
  __cve_doc = None
  __namespaces = {}
  __vulns_nessus = {}
  __hosts_software = {}
  __vulns = []
  __nbe_entries = []

  # Output file.
  __nbe_file = None

  def __init__ (self, db_host, db_user, db_password, db_name, cve_filename, nbe_filename='/var/ossim/passvuln.nbe'):
    # Initialize MySQL connection.
    try:
      self.__db_conn = MySQLdb.connect (host=db_host, user=db_user, passwd=db_password, db=db_name)
      self.__db_cursor = self.__db_conn.cursor()
    except Exception:
      print 'Cannot connect to database'
      return None

    try:
      self.__cve_doc, self.__namespaces = self.__parse_and_get_ns__(cve_filename)
    except Exception, msg:
      print 'Cannot open CVE file: %s' % msg
      return None

    try:
      self.__nbe_file = open (nbe_filename, 'w+')
    except Exception:
      print 'Cannot open NBE file'
      return None

    self.__load_vuln_nessus__ ()
    self.__load_hosts_software__ ()
    self.__load_cve__ ()

  # Acknowledgments to: http://effbot.org/zone/element-namespaces.htm
  def __parse_and_get_ns__ (self, file):
    events = "start", "start-ns"
    root = None
    ns = {}
    for event, elem in ElementTree.iterparse(file, events):
      if event == "start-ns":
        if elem[0] in ns and ns[elem[0]] != elem[1]:
          raise KeyError("Duplicate prefix with different URI found.")
        ns[elem[0]] = "{%s}" % elem[1]
      elif event == "start":
        if root is None:
          root = elem
    return ElementTree.ElementTree(root), ns

  # Load vulnerabilities in the Nessus table.
  def __load_vuln_nessus__ (self):
    self.__db_cursor.execute ('SELECT oid, cve_id FROM vuln_nessus_plugins WHERE cve_id != ""')
    rows = self.__db_cursor.fetchall ()

    for row in rows:
      self.__vulns_nessus[row[1]] = row[0]

  # Load hosts installed software.
  def __load_hosts_software__ (self):
    try:
      self.__db_cursor.execute ('SELECT inet6_ntoa(host_ip.ip), host_software.cpe FROM host_software INNER JOIN host_ip ON host_software.host_id = host_ip.host_id')
      rows = self.__db_cursor.fetchall ()
    except:
      raise

    for row in rows:
      try:
        value = self.__hosts_software[row[0]]
      except KeyError:
        self.__hosts_software[row[0]] = []
      finally:
        self.__hosts_software[row[0]].append(row[1])

  # Load CVE listing.
  def __load_cve__ (self):
    items = self.__cve_doc.findall(self.__namespaces[''] + 'entry')

    for item in items:
      vuln = Vulnerability (item, self.__namespaces)
      if vuln != None:
        self.__vulns.append (vuln)

  # Obtain the generated nbe entries..
  def get_nbe_entries (self):
    return self.__nbe_entries

  # Execute the actual process.
  def run (self):
    tried = 0
    found = 0

    for host in self.__hosts_software:
      for vuln in self.__vulns:
        tried += 1
        if vuln.check (self.__hosts_software[host]):
          try:
            vuln_nessus = self.__vulns_nessus[vuln.get_cve_id()]
          except KeyError:
            vuln_nessus = ''
          self.__nbe_entries.append (NessusEntry (host, '', vuln_nessus, vuln.get_description()))
          found += 1
    print '[Info] Tried: %d Found: %d' % (tried, found)

  # Write results to a file.
  def write (self):
    for entry in self.__nbe_entries:
      self.__nbe_file.write (str(entry))

'''
Vulnerability class.
Contains the CVE definition (metadata and criteria) of a
vulnerability.
'''
class Vulnerability (object):
  __namespaces = None
  __cve_id = ''
  __description = ''
  __criteria = ('', '', [])

  # Uses a XML element or node to get some of its attributes.
  def __init__ (self, node, namespaces):
    self.__namespaces = namespaces

    try:
      # CVE Id.
      self.__cve_id = node.get('id')

      # Add description summary.
      self.__description += '\n\nOverview:\n' + node.find(self.__namespaces['vuln']+'summary').text
      cvss_node = node.find(self.__namespaces['vuln']+'cvss').find(self.__namespaces['cvss']+'base_metrics')

      # Reject CVE entries without CVSS information.
      if cvss_node == None:
        print '[Warn] %s does not have CVSS information and so is rejected' % self.__cve_id
        return None

      cvss_attribs = dict([(elem.tag[elem.tag.rfind('}')+1:], elem.text) for elem in list(cvss_node)])

      # Add CVSS base score.
      self.__description += '\nCVSS Base Score: ' + cvss_attribs['score']

      # Add access vector, access complexity, etc.
      self.__description += ' (AV:' + cvss_attribs['access-vector'][0]
      self.__description += '/AC:' + cvss_attribs['access-complexity'][0]
      self.__description += '/Au:' + cvss_attribs['authentication'][0:1]
      self.__description += '/C:' + cvss_attribs['confidentiality-impact'][0]
      self.__description += '/I:' + cvss_attribs['integrity-impact'][0]
      self.__description += '/A:' + cvss_attribs['availability-impact'][0] + ')'

      # Add references.
      self.__description += '\n\nReferences:\n'
      for references in node.findall(self.__namespaces['vuln']+'references'):
        for reference in references.findall(self.__namespaces['vuln']+'reference'):
          self.__description += reference.get('href') + '\n'

      vuln_config = node.find(self.__namespaces['vuln']+'vulnerable-configuration')
      logical_tests = vuln_config.find(self.__namespaces['cpe-lang']+'logical-test')
    except AttributeError:
      if self.__cve_id:
        print '[Warn] %s does not have enough information and so is rejected' % self.__cve_id
      else:
        print '[Warn] An entry does not have enough information and so is rejected'
      return None

    # Create criteria list.
    if logical_tests != None:
      self.__criteria = self.__init_criteria__ (logical_tests)

  # Initialize criteria set for this vulnerability.
  def __init_criteria__ (self, node):
    # There is at least one test element.
    criteria = (operator, negate, check) = (node.get('operator'), \
                                              node.get('negate'), [])

    for item in list(node):
      if item.tag == self.__namespaces['cpe-lang']+'logical-test':
        check.append (self.__init_criteria__ (item))
      elif item.tag == self.__namespaces['cpe-lang']+'fact-ref':
        check.append (item.get('name'))
      else:
        pass

    return criteria

  # Get CVE Id.
  def get_cve_id (self):
    return self.__cve_id

  # Get full description.
  def get_description (self):
    return self.__description

  # Check the software against this vulnerability.
  def check (self, cpes, criteria_set = None):
    if criteria_set == None:
      criteria_set = self.__criteria

    ret = None
    partial = True
    (operation, negate, criteria) = criteria_set

    if len(criteria) < 1:
      return False

    for criterion in criteria:
      if type(criterion) == tuple:
        partial = self.check (cpes, criterion)
      else:
        partial_cpe = False
        for cpe in cpes:
          partial_cpe |= (cpe in criterion)
        partial &= partial_cpe

      if ret == None:
        ret = partial

      # Could use functools.reduce() and eval() here.
      if operation == 'AND':
        ret = ret and partial
      elif operation == 'OR':
        ret = ret or partial
      else:
        continue

    return ret != (True if negate == 'true' else False)

  def __str__ (self):
    return 'CVE Id: %s; Description: %s; Criteria: %s\n' % (self.__cve_id, self.__description, str(self.__criteria))


'''
NessusEntry class.
'''
class NessusEntry:
  # Fields
  __net = ''
  __ip = ''
  __service = ''
  __oid = ''
  __risk = ''
  __description = ''

  def __init__ (self, ip, service, oid, description, risk = 'Security warning'):
    self.__ip = ip
    self.__net = self.__ip.rsplit('.', 1)[0]
    self.__service = service
    self.__oid = oid
    self.__risk = risk
    self.__description = description.replace('\n', '\\n')

  def __str__ (self):
    return 'results|%s|%s|%s|%s|%s|%s\n' % (self.__net, self.__ip, self.__service, self.__oid, self.__risk, self.__description)
