/*
  License:

  Copyright (c) 2003-2006 ossim.net
  Copyright (c) 2007-2013 AlienVault
  All rights reserved.

  This package is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; version 2 dated June, 1991.
  You may not use, modify or distribute this program under any other version
  of the GNU General Public License.

  This package is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this package; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
  MA  02110-1301  USA


  On Debian GNU/Linux systems, the complete text of the GNU General
  Public License can be found in `/usr/share/common-licenses/GPL-2'.

  Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
*/

#ifndef __SIM_TEXT_FIELDS_H__
#define __SIM_TEXT_FIELDS_H__ 1

#include <glib.h>

enum SimTextIndexFields
{
  SimTextFieldNone  = -1,
  SimTextFieldUsername = 0,
  SimTextFieldPassword = 1,
  SimTextFieldFilename = 2,
  SimTextFieldUserdata1 = 3,
  SimTextFieldUserdata2 = 4,
  SimTextFieldUserdata3 = 5,
  SimTextFieldUserdata4 = 6,
  SimTextFieldUserdata5 = 7,
  SimTextFieldUserdata6 = 8,
  SimTextFieldUserdata7 = 9,
  SimTextFieldUserdata8 = 10,
  SimTextFieldUserdata9 = 11,
  SimTextFieldRulename = 12,
  SimTextFieldValue = 13,
  N_TEXT_FIELDS = 14
};

int sim_text_field_get_index (const char *);
const gchar * sim_text_field_get_name (guint inx);

#endif
