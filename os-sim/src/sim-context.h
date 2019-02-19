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

#ifndef _SIM_CONTEXT_H
#define _SIM_CONTEXT_H 1

#include <glib.h>

G_BEGIN_DECLS

typedef struct _SimContext        SimContext;
typedef struct _SimContextClass   SimContextClass;
typedef struct _SimContextPrivate SimContextPrivate;

#include "sim-host.h"
#include "sim-policy.h"
#include "sim-event.h"
#include "sim-server.h"
#include "sim-net.h"
#include "sim-directive.h"
#include "sim-plugin-sid.h"
#include "sim-groupalarm.h"
#include "sim-database.h"
#include "sim-uuid.h"
#include "sim-engine.h"
#include "sim-policy.h"

#define SIM_TYPE_CONTEXT                     (sim_context_get_type ())
#define SIM_CONTEXT(obj)                     (G_TYPE_CHECK_INSTANCE_CAST ((obj), SIM_TYPE_CONTEXT,SimContext))
#define SIM_IS_CONTEXT(obj)                  (G_TYPE_CHECK_INSTANCE_TYPE ((obj),SIM_TYPE_CONTEXT))
#define SIM_CONTEXT_CLASS(kclass)            (G_TYPE_CHECK_CLASS_CAST ((kclass),SIM_TYPE_CONTEXT,SimContextClass))
#define SIM_CONTEXT_IS_CONTEXT_CLASS(kclass) (G_TYPE_CHECK_CLASS_TYPE ((kclass),SIM_TYPE_CONTEXT))
#define SIM_CONTEXT_GET_CLASS(obj)           (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_CONTEXT,SimContextClass))

#define SIM_CONTEXT_COMMON "00000000-0000-0000-0000-000000000000"

#define SIM_CONTEXT_DEFAULT  NULL

struct _SimContext
{
  GObject parent;
  SimContextPrivate *priv;
};

struct _SimContextClass
{
  GObjectClass  parent_class;
};

typedef struct _SimHostPluginSid SimHostPluginSid;
struct _SimHostPluginSid
{
  SimInet    * host_ip;
  SimContext * context;

  gint         plugin_id;
  gint         plugin_sid;

  gint         reference_id;
  gint         reference_sid;

  gboolean     in_database;
};

typedef struct _SimPluginReference SimPluginReference;
struct _SimPluginReference
{
  // Plugin id and sid of the first incoming event.
  gint         plugin_id;
  gint         plugin_sid;


  // Plugin id and sid of the subsequent events that may match.
  gint         reference_id;
  gint         reference_sid;
};

/* Prototypes */
GType           sim_context_get_type                (void);
void            sim_context_register_type           (void);

SimContext *    sim_context_new                     (SimUuid        *id);
SimContext *    sim_context_new_full                (SimUuid        *id,
                                                     const gchar    *name,
                                                     SimDatabase    *database);

gchar *         sim_context_get_name                (SimContext     *self);
SimUuid *       sim_context_get_id                  (SimContext     *context);

void            sim_context_set_database            (SimContext     *context,
                                                     SimDatabase    *database);

// Plugins
SimPlugin *     sim_context_get_plugin              (SimContext     *context,
                                                     gint            id);
void            sim_context_add_new_plugin          (SimContext    * context,
                                                     gint            id);

// Plugin Sids
void            sim_context_add_plugin_sid          (SimContext     *context,
                                                     SimPluginSid   *plugin_sid);
void            sim_context_load_directive_plugin_sids (SimContext   *context,
                                                        SimUuid      *plugin_ctx);
SimPluginSid *  sim_context_get_plugin_sid          (SimContext     *context,
                                                     gint            id,
                                                     gint            sid);

// Netwotk & Hosts functions
gboolean        sim_context_has_host_with_inet      (SimContext     *context,
                                                     SimInet        *inet);
SimHost *       sim_context_get_host_by_inet        (SimContext     *context,
                                                     SimInet        *inet);
SimHost *       sim_context_get_host_by_name        (SimContext    * context,
                                                     gchar         * name);
SimHost *       sim_context_get_host_by_id          (SimContext    * context,
                                                     SimUuid       * id);
SimNet *        sim_context_get_net_by_name         (SimContext     *context,
                                                     const gchar    *name);
SimNet *        sim_context_get_net_by_id           (SimContext    * context,
                                                     SimUuid       * id);
gint            sim_context_get_inet_asset          (SimContext     *context,
                                                     SimInet        *inet);
gint            sim_context_get_host_asset          (SimContext    * context,
                                                     SimUuid       * id);
gboolean        sim_context_is_inet_in_homenet      (SimContext     *context,
                                                     SimInet        *inet);
SimNetwork     *sim_context_get_home_net            (SimContext     *context);

SimInet *       sim_context_get_homenet_inet        (SimContext     *context,
                                                     SimInet        *inet);
gboolean        sim_context_has_inet                (SimContext     *context,
                                                     SimInet        *inet);

// Net & Host Risk level

void            sim_context_append_host             (SimContext     *context,
                                                     SimHost        *host);

// Loaders
void            sim_context_load_all                (SimContext     *context);
void            sim_context_external_load_all       (SimContext     *context);
void            sim_context_reload_all              (SimContext     *context);
void            sim_context_reload_hosts            (SimContext     *context);
void            sim_context_reload_nets             (SimContext     *context);
void            sim_context_reload_host_plugin_sids (SimContext     *context);
void            sim_context_reload_hierarchy        (SimContext    * context);
void            sim_context_reload_policies         (SimContext     *context);


// Host Plugin sids
void            sim_context_lock_host_plugin_sids_r   (SimContext     *context);
void            sim_context_unlock_host_plugin_sids_r (SimContext     *context);
void            sim_context_lock_host_plugin_sids_w   (SimContext     *context);
void            sim_context_unlock_host_plugin_sids_w (SimContext     *context);
GList *         sim_context_get_host_plugin_sid_list  (SimContext    * context);

SimPluginSid *  sim_context_get_event_host_plugin_sid (SimContext     *context,
                                                       SimEvent       *event);
gboolean        sim_context_try_set_host_plugin_sid   (SimContext    * context,
                                                       SimEvent      * event);
void            sim_context_check_host_plugin_sids  (SimContext     * context);

// Expand Directives
gboolean        sim_context_expand_directive_rule_ips (SimContext *context,
                                                       gchar      *asset_name,
                                                       SimRule    *rule,
                                                       gboolean    is_negated,
                                                       gboolean    is_src);
gboolean       sim_context_expand_directive_rule_product (SimContext *context,
                                                          gint        product,
                                                          SimRule    *rule,
                                                          gboolean    is_negated);

// Policies
SimPolicy *     sim_context_get_event_policy        (SimContext     *context,
                                                     SimEvent      *event);
GList *         sim_context_get_policies            (SimContext     *context);

// Taxonomy Products
GList *         sim_context_get_taxonomy_product    (SimContext   *context,
                                                     gint          product_id);

// Stats
guint           sim_context_get_stats               (SimContext     *context,
                                                     glong           elapsed_time);
gfloat          sim_context_get_stats_5_minutes     (SimContext     *context,
                                                     glong           elapsed_time);
void            sim_context_inc_total_events        (SimContext     *context);

// Debug
#if DEBUG_ENABLED
void            sim_context_debug_print_all         (SimContext     *context);
void            sim_context_debug_print_hosts       (SimContext     *context);
void            sim_context_debug_print_nets        (SimContext     *context);
void            sim_context_debug_print_policies    (SimContext     *context);
#endif /* DEBUG_ENABLED */

#ifdef USE_UNITTESTS
void            sim_context_register_tests          (SimUnittesting *engine);
#endif

G_END_DECLS

#endif /* _SIM_CONTEXT_H */
