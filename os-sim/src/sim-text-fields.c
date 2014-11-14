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

#include "config.h"

#include "sim-text-fields.h"

#include <glib.h>

static struct
{
	gchar *name;
	int 	index;

} simtextfields[] =
{
  {"username",SimTextFieldUsername},
  {"password",SimTextFieldPassword},
  {"filename",SimTextFieldFilename},
  {"userdata1",SimTextFieldUserdata1},
  {"userdata2",SimTextFieldUserdata2},
  {"userdata3",SimTextFieldUserdata3},
  {"userdata4",SimTextFieldUserdata4},
  {"userdata5",SimTextFieldUserdata5},
  {"userdata6",SimTextFieldUserdata6},
  {"userdata7",SimTextFieldUserdata7},
  {"userdata8",SimTextFieldUserdata8},
  {"userdata9",SimTextFieldUserdata9},
  {"rulename", SimTextFieldRulename},
  {"value", SimTextFieldValue}
};

int sim_text_field_get_index (const char *s){
  int res = -1;
  guint i;
  for (i = 0;i < (sizeof(simtextfields)/sizeof (simtextfields[0])) && res == -1;i++){
    if (g_strcmp0 (s,simtextfields[i].name) == 0) res = simtextfields[i].index;
  }
  return res;
}
const gchar *sim_text_field_get_name (guint inx){
  g_return_val_if_fail (inx < N_TEXT_FIELDS, NULL);
  return simtextfields[inx].name;
}
