# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2014 AlienVault
#    All rights reserved.
#
#    This package is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; version 2 dated June, 1991.
#    You may not use, modify or distribute this program under any other version
#    of the GNU General Public License.
#
#    This package is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this package; if not, write to the Free Software
#    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#    MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

REMEDY = {
  'Action': ('action', '@CALCULATED@'),
  'Corporate_ID': ('', '@DEFAULT@'),
  'First_Name': ('', '@DEFAULT@'),
  'Last_Name': ('', '@DEFAULT@'),
  'Product_Categorization_Tier_1': ('', '@DEFAULT@'),
  'Product_Categorization_Tier_2': ('', '@DEFAULT@'),
  'Product_Categorization_Tier_3': ('', '@DEFAULT@'),
  'Support_Company': ('', '@DEFAULT@'),
  'Support_Organization': ('', '@DEFAULT@'),
  'Assigned_Group': ('in_charge', ''),
  'Status': ('status', ''),
  'Impact': ('', '@CALCULATED@'),
  'Urgency': ('', '@CALCULATED@'),
  'Summary': ('title', ''),
  'Description': ('description', ''),
  'Reported_Source': ('', '@DEFAULT@'),
  'Service_Type': ('', '@DEFAULT@'),
  'Asignee': ('submitter', ''),
  'Create_Date': ('date', ''),
  'Phone_Number': ('', '@DEFAULT@'),
}

REMEDY_TYPE = {'Alarm': 1,
               'Alert': 2,
               'Event': 3,
               'Metric': 4,
               'Anomaly': 3,
               'Vulnerability': 2,
               'Custom': 3}

REMEDY_ACTION = {'insert': 'CREATE'}

REMEDY_IMPACT = ['1-Extensive/Widespread', '2-Significant/Large', '3-Moderate/Limited', '4-Minor/Localized']
REMEDY_URGENCY = ['1-Critical', '2-High', '3-Medium', '4-Low']

REMEDY_CALCULATED_VALUES = {'Impact': 'REMEDY_IMPACT[REMEDY_TYPE[@type@] - 1]',
                            'Urgency': 'REMEDY_URGENCY[(int(round((11 - int(@priority@))/2.0))) - 1]',
                            'Action': 'REMEDY_ACTION[@action@]'}

# Valid translation dictionaries.
# Note the ugly hack there: locals() already returns a dictionary, but Python evaluates it in a lazy fashion.
# So if you don't add the dict() conversion, Python will complain because the dictionary returned by locals()
# changes its size while performing the list comprehension. In short, dict() forces local() to be evaluated.
TRANSLATION_DICTS = [item for item in dict(locals()) if not (item.startswith('__') or item == 'ALL_VARS' or item == 'TRANSLATION_DICTS')]
