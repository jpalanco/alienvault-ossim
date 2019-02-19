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

#ifndef __SIM_UTIL_H__
#define __SIM_UTIL_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <gio/gio.h>
#include <inttypes.h>
#include <time.h>

typedef struct _Plugin_PluginSid  Plugin_PluginSid;
typedef struct _SimPortProtocol   SimPortProtocol;
typedef struct _SimVersion        SimVersion;

#include "sim-uuid.h"
#include "sim-enums.h"
#include "sim-database.h"
#include "sim-command.h"

G_BEGIN_DECLS

/* Macro for loop on hash table elements */
#define SIM_WHILE_HASH_TABLE(hash_table)                  \
  GHashTableIter _iter;                                   \
  gpointer _key, value;                                   \
  g_hash_table_iter_init (&_iter, hash_table);            \
  while (g_hash_table_iter_next (&_iter, &_key, &value))

//this struct is valid just for the Policy groups.

struct _Plugin_PluginSid
{
  gint   plugin_id;
  GList *plugin_sid; // *gint list
};

struct _SimPortProtocol
{
  gint              port;
  SimProtocolType   protocol;
  gboolean          any;
};

struct _SimVersion
{
  guint8 major;
  guint8 minor;
  guint8 micro;
  guint8 nano;
};


SimPortProtocol*  sim_port_protocol_new                  (gint                 port,
                                                          SimProtocolType      protocol);

gboolean          sim_port_protocol_equal                (SimPortProtocol     *pp1,
                                                          SimPortProtocol     *pp2);

SimProtocolType   sim_protocol_get_type_from_str          (const gchar        *str);
gchar*            sim_protocol_get_str_from_type          (SimProtocolType     type);

SimConditionType  sim_condition_get_type_from_str         (const gchar        *str);
gchar*            sim_condition_get_str_from_type         (SimConditionType    type);

SimRuleVarType    sim_get_rule_var_from_char              (const gchar        *var);

SimAlarmRiskType  sim_get_alarm_risk_from_char            (const gchar        *var);
SimAlarmRiskType  sim_get_alarm_risk_from_risk            (gint                risk);

GList *           sim_get_ias                             (const gchar         *value);
GList *           sim_get_inets                           (const gchar         *value);
GList *           sim_get_inets_from_string               (const gchar         *,
                                                           gboolean            *);
GList *           sim_string_hash_to_list                 (GHashTable          *hash_table);
gchar *           sim_string_to_inet_db_string            (const gchar         *ip);

/* File management utility functions */
gchar *           sim_file_load                           (const gchar         *filename);
gboolean          sim_file_save                           (const gchar         *filename,
                                                           const gchar         *buffer,
                                                           gint                 len);

gulong            sim_inetaddr_aton                       (GInetAddr           *ia);
inline gulong     sim_inetaddr_ntohl                      (GInetAddr           *ia);
inline gulong     sim_ipchar_2_ulong                      (gchar               *ip);
gchar *           sim_mac_to_db_string                    (const gchar         *mac);
guint8 *          sim_mac_to_bin                          (const gchar         *mac);
gchar *           sim_bin_to_mac                          (const guint8        *bin);
gboolean          sim_util_is_hex_string                  (const gchar         *string);
inline gboolean   sim_string_is_number                    (gchar               *string,
                                                           gboolean             may_be_float);
inline gchar *    sim_string_remove_char                  (gchar               *string,
                                                           gchar                c);
inline gchar *    sim_string_substitute_char              (gchar               *string,
                                                           gchar                c_orig,
                                                           gchar                c_dest);

guint             sim_g_strv_length                       (gchar              **str_array);

/* base64 */
gboolean          sim_base64_encode                       (gchar               *_in,
                                                           guint                inlen,
                                                           gchar               *_out,
                                                           guint                outmax,
                                                           guint               *outlen);
gboolean          sim_base64_decode                       (gchar               *in,
                                                           guint                inlen,
                                                           gchar               *out,
                                                           guint               *outlen);

size_t            sim_strnlen                             (const char          *str,
                                                           size_t               maxlen);
gchar*            sim_normalize_host_mac                  (gchar               *old_mac);
guint8 *          sim_hex2bin                             (gchar               *);
gchar*            sim_bin2hex                             (guint8              *,
                                                           guint);
gchar*            sim_buffer_sanitize                     (gchar               *buffer);
gint              sim_is_a_great_than_b                   (gconstpointer        a,
                                                           gconstpointer        b);
gchar*            sim_hidden_password_in_text             (gchar               *text,
                                                           gchar               *match,
                                                           gchar                end);
gboolean          sim_util_parse_snort_db_dns             (SimCommand          *cmd,
                                                           gchar               *db_dns);
gchar *           sim_convert_hex_to_char                 (guint8              *data,
                                                           guint                len);
inline void       sim_log_xml_handler                     (gpointer             ctx_ptr,
                                                           const gchar         *message,
                                                           ...);
inline void       sim_log_xml_handler                     (gpointer             ctx_ptr,
                                                           const gchar         *message,
                                                           ...);
gboolean          sim_check_is_valid_ip                   (const gchar         *st);
gboolean          sim_check_is_ipv6                       (const char          *st);
gboolean          sim_check_is_ipv4                       (const char          *st);
guint32           sim_string_to_hash                      (guchar              *key,
                                                           size_t               key_len);
gboolean          sim_cmp_list_gchar                      (GList               *list,
                                                           gchar               *string);
gchar *           sim_util_utf8_to_html                   (gchar               *);
gchar *           sim_string_substitute_with_string       (gchar               *src,
                                                           const gchar         *s_orig,
                                                           const gchar         *s_dest);
gboolean          sim_util_block_signal                   (int                  sig);
gboolean          sim_util_unblock_signal                 (int                  sig);
gboolean          sim_util_check_ip_array                 (gchar              **array);
void              sim_options                             (int argc, char **argv);
void              sim_pid_init                            (void);
gint              sim_get_current_date                    (void);
void              sim_time_t_to_str                       (gchar outstr[TIMEBUF_SIZE],
                                                           const time_t time);
gchar *           sim_util_substite_problematic_chars     (const gchar         *p,
                                                           gsize                len);
const gchar *     sim_backlog_event_str_from_type         (gint                 type);
gint              sim_backlog_event_type_from_str         (gchar               *str);
gchar *           sim_str_escape                          (const gchar         *source,
                                                           GdaConnection       *connection,
                                                           gsize                can_have_nulls);
gboolean          sim_version_match                       (SimVersion * a, SimVersion * b);
void              sim_version_parse                       (const gchar *string, guint8 *major, guint8 *minor, guint8 *micro, guint8 *nano);

guint             sim_parse_month_day                     (guint day, guint month, guint year);

gboolean          sim_socket_send_simple                  (GSocket* socket, const gchar *buffer);
gboolean          sim_util_is_pulse_id                    (const gchar *string);
//Small Semaphore Implementation
struct _GSemaphore
{
  gint    value;
  GMutex access;
  GCond  sig;
};

typedef struct _GSemaphore GSemaphore;
#define g_semaphore_new() g_semaphore_new_with_value(1)

GSemaphore*       g_semaphore_new_with_value              (gint                 value);
void              g_semaphore_free                        (GSemaphore          *);
void              g_semaphore_up                          (GSemaphore          *);
void              g_semaphore_down                        (GSemaphore          *);

G_END_DECLS

#endif
// vim: set tabstop=2:
