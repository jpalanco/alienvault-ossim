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

#include "sim-network.h"

#include "sim-log.h"

typedef union
{
  guint8   addr8[16];
  guint16  addr16[8];
  guint32  addr32[4];
  guint64  addr64[2];

} SimIpBytes;

struct _SimNetworkPrivate
{
  GList *net_ipv4;
  GList *net_ipv6;
};

#define SIM_NETWORK_GET_PRIVATE(self) (G_TYPE_INSTANCE_GET_PRIVATE ((self), SIM_TYPE_NETWORK, SimNetworkPrivate))

SIM_DEFINE_TYPE (SimNetwork, sim_network, G_TYPE_OBJECT, NULL)

/*
 * Prototypes
 */
SimInet *       _sim_network_search_ipv4                  (GList            *tree,
                                                           SimInet          *inet);
SimInet *       _sim_network_search_ipv6                  (GList            *tree,
                                                           SimInet          *inet);
gboolean        _sim_network_search_exact                 (GList            *tree,
                                                           SimInet          *inet);


/*
 * Type methods
 */

static void
sim_network_class_init (SimNetworkClass *klass)
{
  GObjectClass *object_class = G_OBJECT_CLASS (klass);

  object_class->finalize = sim_network_finalize;

  parent_class = g_type_class_ref (G_TYPE_OBJECT);

  g_type_class_add_private (klass, sizeof (SimNetworkPrivate));
}

static void
sim_network_instance_init (SimNetwork *self)
{
  self->priv = SIM_NETWORK_GET_PRIVATE (self);

  self->priv->net_ipv4 = NULL;
  self->priv->net_ipv6 = NULL;
}

static void
sim_network_finalize (GObject *object)
{
  SimNetwork *self = SIM_NETWORK (object);

  if (self->priv->net_ipv4)
  {
    g_list_foreach (self->priv->net_ipv4, (GFunc)g_object_unref, NULL);
    g_list_free (self->priv->net_ipv4);
    self->priv->net_ipv4 = NULL;
  }

  if (self->priv->net_ipv6)
  {
    g_list_foreach (self->priv->net_ipv6, (GFunc)g_object_unref, NULL);
    g_list_free (self->priv->net_ipv6);
    self->priv->net_ipv6 = NULL;
  }

  G_OBJECT_CLASS (parent_class)->finalize (object);
}

/*
 * Public API
 */

SimNetwork *
sim_network_new (void)
{
  return SIM_NETWORK (g_object_new (SIM_TYPE_NETWORK, NULL));
}

SimNetwork *
sim_network_clone (SimNetwork *network)
{
  SimNetwork *new_network;

  g_return_val_if_fail (SIM_IS_NETWORK (network), NULL);

  new_network = SIM_NETWORK (g_object_new (SIM_TYPE_NETWORK, NULL));

  if (network->priv->net_ipv4)
  {
    GList *list = network->priv->net_ipv4;
    while (list)
    {
      new_network->priv->net_ipv4 = g_list_append (new_network->priv->net_ipv4,
                                                   g_object_ref (G_OBJECT (list->data)));
      list = g_list_next (list);
    }
  }

  if (network->priv->net_ipv6)
  {
    GList *list = network->priv->net_ipv6;
    while (list)
    {
      new_network->priv->net_ipv6 = g_list_append (new_network->priv->net_ipv6,
                                                   g_object_ref (G_OBJECT (list->data)));
      list = g_list_next (list);
    }
  }

  return new_network;
}

void
sim_network_add_inet (SimNetwork *network,
                      SimInet    *inet)
{
  g_return_if_fail (SIM_IS_NETWORK (network));
  g_return_if_fail (SIM_IS_INET (inet));

  if (sim_inet_is_ipv4 (inet))
  {
    if (!_sim_network_search_exact (network->priv->net_ipv4, inet))
      network->priv->net_ipv4 = g_list_append (network->priv->net_ipv4, g_object_ref (inet));
  }
  else if (sim_inet_is_ipv6 (inet))
  {
    if (!_sim_network_search_exact (network->priv->net_ipv6, inet))
      network->priv->net_ipv6 = g_list_append (network->priv->net_ipv6, g_object_ref (inet));
  }
}

gint
sim_network_match_inet (SimNetwork *network,
                        SimInet    *inet)
{
  SimInet *matched = NULL;
  gint     matched_mask;

  g_return_val_if_fail (SIM_IS_NETWORK (network), FALSE);
  g_return_val_if_fail (SIM_IS_INET (inet), FALSE);

  if (sim_inet_is_ipv4 (inet))
    matched = _sim_network_search_ipv4 (network->priv->net_ipv4, inet);
  else if (sim_inet_is_ipv6 (inet))
    matched = _sim_network_search_ipv6 (network->priv->net_ipv6, inet);

  if (matched == NULL)
    return NO_MATCH;

  matched_mask = sim_inet_get_mask (matched);
  if (sim_inet_get_mask (inet) == matched_mask)
    return EXACT_MATCH;
  else
    return matched_mask;
}

gboolean
sim_network_has_inet (SimNetwork *network,
                      SimInet    *inet)
{
  gboolean result = FALSE;

  g_return_val_if_fail (SIM_IS_NETWORK (network), FALSE);
  g_return_val_if_fail (SIM_IS_INET (inet), FALSE);

  if (sim_inet_is_ipv4 (inet))
  {
    if (_sim_network_search_ipv4 (network->priv->net_ipv4, inet) != NULL)
      result = TRUE;
  }
  else if (sim_inet_is_ipv6 (inet))
  {
    if (_sim_network_search_ipv6 (network->priv->net_ipv6, inet) != NULL)
      result = TRUE;
  }

  return result;
}

SimInet *
sim_network_search_inet (SimNetwork *network,
                         SimInet    *inet)
{
  SimInet *inet_find = NULL;

  g_return_val_if_fail (SIM_IS_NETWORK (network), FALSE);
  g_return_val_if_fail (SIM_IS_INET (inet), FALSE);

  if (sim_inet_is_none (inet))
    return NULL;

  if (sim_inet_is_ipv4 (inet))
  {
    inet_find = _sim_network_search_ipv4 (network->priv->net_ipv4, inet);
  }
  else if (sim_inet_is_ipv6 (inet))
  {
    inet_find = _sim_network_search_ipv6 (network->priv->net_ipv6, inet);
  }

  return inet_find;
}

gboolean
sim_network_has_exact_inet (SimNetwork *network,
                            SimInet    *inet)
{
  g_return_val_if_fail (SIM_IS_NETWORK (network), FALSE);
  g_return_val_if_fail (SIM_IS_INET (inet), FALSE);

  if (sim_inet_is_ipv4 (inet))
    return _sim_network_search_exact (network->priv->net_ipv4, inet);
  else if (sim_inet_is_ipv6 (inet))
    return _sim_network_search_exact (network->priv->net_ipv6, inet);

  return FALSE;
}

gchar *
sim_network_to_string (SimNetwork *network)
{
  GList   *list;
  GString *net_str;
  gchar   *separator = NULL;
  gchar   *single_ip;

  g_return_val_if_fail (SIM_IS_NETWORK (network), NULL);

  net_str = g_string_new ("");

  list = network->priv->net_ipv4;
  while (list)
  {
    SimInet *inet = SIM_INET (list->data);

    if (sim_inet_is_host (inet))
      single_ip = sim_inet_get_canonical_name (inet);
    else
      single_ip = sim_inet_get_cidr (inet);

    if (separator)
      net_str = g_string_append (net_str, separator);
    else
      separator = g_strdup (",");

    net_str = g_string_append (net_str, single_ip);
    g_free (single_ip);

    list = g_list_next (list);
  }

  list = network->priv->net_ipv6;
  while (list)
  {
    SimInet *inet = SIM_INET (list->data);

    if (sim_inet_is_host (inet))
      single_ip = sim_inet_get_canonical_name (inet);
    else
      single_ip = sim_inet_get_cidr (inet);

    if (separator)
      net_str = g_string_append (net_str, separator);
    else
      separator = g_strdup (",");

    net_str = g_string_append (net_str, single_ip);
    g_free (single_ip);

    list = g_list_next (list);
  }

  if (separator)
    g_free (separator);

  return g_string_free (net_str, FALSE);
}

gboolean
sim_network_is_empty (SimNetwork *network)
{
  gboolean is_empty;

  g_return_val_if_fail (SIM_IS_NETWORK (network), FALSE);

  is_empty = ((network->priv->net_ipv4 == NULL) &&
              (network->priv->net_ipv6 == NULL));

  return is_empty;
}

void
sim_network_print (SimNetwork *network)
{
  gchar *aux;

  g_return_if_fail (SIM_IS_NETWORK (network));

  aux = sim_network_to_string (network);
  ossim_debug ("%s", aux);
  g_free (aux);
}

/*
 * Private API
 */

SimInet *
_sim_network_search_ipv4 (GList   *tree,
                          SimInet *inet)
{
  GList      *list;
  SimInet    *inet_found = NULL;
  gint        best_mask = 0;
  gint        mask = sim_inet_get_mask (inet);
  SimIpBytes *bytes;

  bytes = (SimIpBytes *)sim_inet_get_in_addr (inet);

  list = tree;
  while (list)
  {
    SimInet *list_inet = (SimInet *) list->data;
    gint list_inet_mask = sim_inet_get_mask (list_inet);

    /* Exact match */
    if (mask == list_inet_mask)
    {
      if (sim_inet_noport_equal (inet, list_inet))
      {
        g_free (bytes);
        return list_inet;
      }
    }
    /* inet into net */
    else if ((list_inet_mask > best_mask) && (mask > list_inet_mask))
    {
      SimIpBytes *list_bytes;
      guint32 masked_bytes;
      guint32 list_masked_bytes;

      list_bytes = (SimIpBytes *)sim_inet_get_in_addr (list_inet);

      /* Apply mask */
      masked_bytes = g_ntohl (bytes->addr32[0]) >> (32 - list_inet_mask);
      list_masked_bytes = g_ntohl (list_bytes->addr32[0]) >> (32 - list_inet_mask);

      if (masked_bytes == list_masked_bytes)
      {
        /* Best match at the moment */
        best_mask = list_inet_mask;
        inet_found = list_inet;
      }

      g_free (list_bytes);
    }

    list = g_list_next (list);
  }

  g_free (bytes);

  return inet_found;
}

SimInet *
_sim_network_search_ipv6 (GList   *tree,
                          SimInet *inet)
{
  SimInet    *inet_found = NULL;
  GList      *list;
  gint        best_mask = 0;
  gint        mask = sim_inet_get_mask (inet);
  SimIpBytes *bytes;

  bytes = (SimIpBytes *)sim_inet_get_in_addr (inet);

  list = tree;
  while (list)
  {
    SimInet *list_inet = (SimInet *) list->data;
    gint list_inet_mask = sim_inet_get_mask (list_inet);

    /* Exact match */
    if (mask == list_inet_mask)
    {
      if (sim_inet_noport_equal (inet, list_inet))
      {
        g_free (bytes);
        return list_inet;
      }
    }
    /* inet into net */
    else if ((list_inet_mask > best_mask) && (mask > list_inet_mask))
    {
      SimIpBytes *list_bytes;
      guint64 masked_bytes_h;
      guint64 masked_bytes_l;
      guint64 list_masked_bytes_h;
      guint64 list_masked_bytes_l;

      list_bytes = (SimIpBytes *)sim_inet_get_in_addr (list_inet);

      /* Apply mask */
      if (list_inet_mask > 64)
      {
        masked_bytes_h = GUINT64_FROM_BE (bytes->addr64[0]);
        masked_bytes_l = GUINT64_FROM_BE (bytes->addr64[1]) >> (128 - list_inet_mask);

        list_masked_bytes_h = GUINT64_FROM_BE (list_bytes->addr64[0]);
        list_masked_bytes_l = GUINT64_FROM_BE (list_bytes->addr64[1]) >> (128 - list_inet_mask);
      }
      else
      {
        masked_bytes_h = GUINT64_FROM_BE (bytes->addr64[0]) >> (64 - list_inet_mask);
        masked_bytes_l = 0;

        list_masked_bytes_h = GUINT64_FROM_BE (list_bytes->addr64[0]) >> (64 - list_inet_mask);
        list_masked_bytes_l = 0;
      }

      if (masked_bytes_h == list_masked_bytes_h &&
          masked_bytes_l == list_masked_bytes_l)
      {
        /* Best match at the moment */
        best_mask = list_inet_mask;
        inet_found = list_inet;
      }

      g_free (list_bytes);
    }

    list = g_list_next (list);
  }

  g_free (bytes);

  return inet_found;
}

gboolean
_sim_network_search_exact (GList   *tree,
                           SimInet *inet)
{
  GList *list;
  gint mask;

  mask = sim_inet_get_mask (inet);

  list = tree;
  while (list)
  {
    SimInet *list_inet = SIM_INET (list->data);

    if (mask == sim_inet_get_mask (list_inet) && sim_inet_noport_equal (inet, list_inet))
      return TRUE;

    list = g_list_next (list);
  }

  return FALSE;
}


#ifdef USE_UNITTESTS

/*************************************************************
 *******************      Unit tests      ********************
 *************************************************************/

void     sim_network_test_add_inets (SimNetwork *tree);
gboolean sim_network_test1          (void);
gboolean sim_network_test2          (void);
gboolean sim_network_test3          (void);
gboolean sim_network_test4          (void);
gboolean sim_network_test5          (void);

static const char *ipv4_in[] = {"192.168.2.100",
                                "192.168.2.1",
                                "192.168.5.100/24",
                                "10.0.0.1",
                                "62.16.25.0/16",
                                "192.168.5.2/28",
                                NULL};

static const char *ipv4_out[] = {"19.16.2.100",
                                 "193.0.0.0/24",
                                 "10.0.0.2",
                                 "62.16.25.0/8",
                                 NULL};

static const struct
{
  gchar *ip;
  guint  mask;

}ipv4_match[] = {{"192.168.5.2", 28},
                 {"62.16.25.1", 16},
                 {NULL, 0}};


static const char *ipv6_in[] = {"::192.168.2.100",
                                "FFFF:FFFF:FFFF:FFFF::/64",
                                "FFFF:FFFF:FFFF:FFFF::/32"
                                "1111:0000:0000:0000::/65",
                                "0123:4567:89AB:CDEF:0123:4567:89AB:CDEF",
                                "::10.0.0.1/120",
                                NULL};

static const char *ipv6_out[] = {"::192.168.2.101",
                                 "FFFF::/96",
                                 "4567:89AB:CDEF:0123:4567:89AB:CDEF:0123",
                                 NULL};

static const struct
{
  gchar *ip;
  guint  mask;

}ipv6_match[] = {{"FFFF:FFFF:FFFF:FFFF:FFFF::192.168.5.2", 64},
                 {"::10.0.0.2", 120},
                 {NULL, 0}};

static const char *ip[] = {"192.168.2.100",
                           "192.168.5.100/24",
                           "10.0.0.1",
                           "62.16.25.0/16",
                           "123::123",
                           "321::123/64",
                           NULL};

// Test aux functions
void
sim_network_test_add_inets (SimNetwork *tree)
{
  SimInet *inet;
  guint    i;

  i = 0;
  while (ip[i] != NULL)
  {
    inet = sim_inet_new_from_string (ip[i]);
    sim_network_add_inet (tree, inet);
    g_object_unref (inet);
    i++;
  }
}

gboolean
sim_network_test1 ()
{
  gboolean    success = TRUE;
  SimNetwork *tree;
  SimInet    *inet;
  SimInet    *inet_result;
  guint       match_mask;
  gint        i;

  /* new */
  g_print ("sim_network_new\n");
  tree = sim_network_new ();

  /* is_empty */
  if (!sim_network_is_empty (tree))
  {
    g_print ("Error !empty\n");
    success = FALSE;
  }

  /* add inet */
  i = 0;
  while (ipv4_in[i])
  {
    inet = sim_inet_new_from_string (ipv4_in[i]);
    sim_network_add_inet (tree, inet);

    g_object_unref (inet);
    i++;
  }

  sim_network_print (tree);


  /* has inet */
  g_print ("sim_network_has_inet\n");

  i = 0;
  while (ipv4_in[i])
  {
    inet = sim_inet_new_from_string (ipv4_in[i]);
    if (!sim_network_has_inet (tree, inet))
    {
      g_print ("Error !has_inet %s\n", ipv4_in[i]);
      success = FALSE;
    }

    g_object_unref (inet);
    i++;
  }

  i = 0;
  while (ipv4_out[i])
  {
    inet = sim_inet_new_from_string (ipv4_out[i]);
    if (sim_network_has_inet (tree, inet))
    {
      g_print ("Error has_inet %s\n", ipv4_out[i]);
      success = FALSE;
    }

    g_object_unref (inet);
    i++;
  }

  i = 0;
  while (ipv4_match[i].ip)
  {
    inet = sim_inet_new_from_string (ipv4_match[i].ip);

    if (!sim_network_has_inet (tree, inet))
    {
      g_print ("Error !has_inet %s\n", ipv4_match[i].ip);
      success = FALSE;
    }

    g_object_unref (inet);
    i++;
  }

  /* has inet */
  g_print ("sim_network_has_exact_inet\n");

  i = 0;
  while (ipv4_in[i])
  {
    inet = sim_inet_new_from_string (ipv4_in[i]);
    if (!sim_network_has_exact_inet (tree, inet))
    {
      g_print ("Error !has_exact_inet %s\n", ipv4_in[i]);
      success = FALSE;
    }

    g_object_unref (inet);
    i++;
  }

  i = 0;
  while (ipv4_out[i])
  {
    inet = sim_inet_new_from_string (ipv4_out[i]);
    if (sim_network_has_exact_inet (tree, inet))
    {
      g_print ("Error has_exact_inet %s\n", ipv4_out[i]);
      success = FALSE;
    }

    g_object_unref (inet);
    i++;
  }

  i = 0;
  while (ipv4_match[i].ip)
  {
    inet = sim_inet_new_from_string (ipv4_match[i].ip);

    if (sim_network_has_exact_inet (tree, inet))
    {
      g_print ("Error has_exact_inet %s\n", ipv4_match[i].ip);
      success = FALSE;
    }

    g_object_unref (inet);
    i++;
  }


  /* search inet */
  g_print ("sim_network_search_inet\n");

  i = 0;
  while (ipv4_in[i])
  {
    inet = sim_inet_new_from_string (ipv4_in[i]);
    inet_result = sim_network_search_inet (tree, inet);
    if (!inet_result || !sim_inet_noport_equal (inet, inet_result))
    {
      g_print ("Error search_inet %s\n", ipv4_in[i]);
      success = FALSE;
    }

    g_object_unref (inet);
    i++;
  }

  i = 0;
  while (ipv4_out[i])
  {
    inet = sim_inet_new_from_string (ipv4_out[i]);
    inet_result = sim_network_search_inet (tree, inet);
    if (inet_result)
    {
      g_print ("Error search_inet %s\n", ipv4_out[i]);
      success = FALSE;
    }

    g_object_unref (inet);
    i++;
  }

  /* match inet */
  g_print ("sim_network_match_inet\n");

  i = 0;
  while (ipv4_in[i])
  {
    inet = sim_inet_new_from_string (ipv4_in[i]);
    match_mask = sim_network_match_inet (tree, inet);
    if (match_mask != EXACT_MATCH)
    {
      g_print ("Error match_inet %s %d\n", ipv4_in[i], match_mask);
      success = FALSE;
    }

    g_object_unref (inet);
    i++;
  }

  i = 0;
  while (ipv4_out[i])
  {
    inet = sim_inet_new_from_string (ipv4_out[i]);
    match_mask = sim_network_match_inet (tree, inet);
    if (match_mask != NO_MATCH)
    {
      g_print ("Error match_inet %s %d\n", ipv4_out[i], match_mask);
      success = FALSE;
    }

    g_object_unref (inet);
    i++;
  }

  i = 0;
  while (ipv4_match[i].ip)
  {
    inet = sim_inet_new_from_string (ipv4_match[i].ip);

    match_mask = sim_network_match_inet (tree, inet);
    if (match_mask != ipv4_match[i].mask)
    {
      g_print ("Error match_inet %s : %d : %d\n", ipv4_match[i].ip, match_mask, ipv4_match[i].mask);
      success = FALSE;
    }

    g_object_unref (inet);
    i++;
  }


  /* destroy */
  g_object_unref (tree);

  return success;
}

gboolean
sim_network_test2 ()
{
  gboolean    success = TRUE;
  SimNetwork *tree;
  SimInet    *inet;
  SimInet    *inet_result;
  guint       match_mask;
  gint        i;

  /* new */
  g_print ("sim_network_new\n");
  tree = sim_network_new ();

  /* is_empty */
  if (!sim_network_is_empty (tree))
  {
    g_print ("Error !empty\n");
    success = FALSE;
  }

  /* add inet */
  i = 0;
  while (ipv6_in[i])
  {
    inet = sim_inet_new_from_string (ipv6_in[i]);
    sim_network_add_inet (tree, inet);

    g_object_unref (inet);
    i++;
  }

  sim_network_print (tree);


  /* has inet */
  g_print ("sim_network_has_inet\n");

  i = 0;
  while (ipv6_in[i])
  {
    inet = sim_inet_new_from_string (ipv6_in[i]);
    if (!sim_network_has_inet (tree, inet))
    {
      g_print ("Error !has_inet %s\n", ipv6_in[i]);
      success = FALSE;
    }

    g_object_unref (inet);
    i++;
  }

  i = 0;
  while (ipv6_out[i])
  {
    inet = sim_inet_new_from_string (ipv6_out[i]);
    if (sim_network_has_inet (tree, inet))
    {
      g_print ("Error has_inet %s\n", ipv6_out[i]);
      success = FALSE;
    }

    g_object_unref (inet);
    i++;
  }

  i = 0;
  while (ipv6_match[i].ip)
  {
    inet = sim_inet_new_from_string (ipv6_match[i].ip);

    if (!sim_network_has_inet (tree, inet))
    {
      g_print ("Error !has_inet %s\n", ipv6_match[i].ip);
      success = FALSE;
    }

    g_object_unref (inet);
    i++;
  }

  /* has inet */
  g_print ("sim_network_has_exact_inet\n");

  i = 0;
  while (ipv6_in[i])
  {
    inet = sim_inet_new_from_string (ipv6_in[i]);
    if (!sim_network_has_exact_inet (tree, inet))
    {
      g_print ("Error !has_exact_inet %s\n", ipv6_in[i]);
      success = FALSE;
    }

    g_object_unref (inet);
    i++;
  }

  i = 0;
  while (ipv6_out[i])
  {
    inet = sim_inet_new_from_string (ipv6_out[i]);
    if (sim_network_has_exact_inet (tree, inet))
    {
      g_print ("Error has_exact_inet %s\n", ipv6_out[i]);
      success = FALSE;
    }

    g_object_unref (inet);
    i++;
  }

  i = 0;
  while (ipv6_match[i].ip)
  {
    inet = sim_inet_new_from_string (ipv6_match[i].ip);

    if (sim_network_has_exact_inet (tree, inet))
    {
      g_print ("Error has_exact_inet %s\n", ipv6_match[i].ip);
      success = FALSE;
    }

    g_object_unref (inet);
    i++;
  }


  /* search inet */
  g_print ("sim_network_search_inet\n");

  i = 0;
  while (ipv6_in[i])
  {
    inet = sim_inet_new_from_string (ipv6_in[i]);
    inet_result = sim_network_search_inet (tree, inet);
    if (!inet_result || !sim_inet_noport_equal (inet, inet_result))
    {
      g_print ("Error search_inet %s\n", ipv6_in[i]);
      success = FALSE;
    }

    g_object_unref (inet);
    i++;
  }

  i = 0;
  while (ipv6_out[i])
  {
    inet = sim_inet_new_from_string (ipv6_out[i]);
    inet_result = sim_network_search_inet (tree, inet);
    if (inet_result)
    {
      g_print ("Error search_inet %s -> %s\n", ipv6_out[i], sim_inet_get_cidr (inet_result));
      success = FALSE;
    }

    g_object_unref (inet);
    i++;
  }

  /* match inet */
  g_print ("sim_network_match_inet\n");

  i = 0;
  while (ipv6_in[i])
  {
    inet = sim_inet_new_from_string (ipv6_in[i]);
    match_mask = sim_network_match_inet (tree, inet);
    if (match_mask != EXACT_MATCH)
    {
      g_print ("Error match_inet %s %d\n", ipv6_in[i], match_mask);
      success = FALSE;
    }

    g_object_unref (inet);
    i++;
  }

  i = 0;
  while (ipv6_out[i])
  {
    inet = sim_inet_new_from_string (ipv6_out[i]);
    match_mask = sim_network_match_inet (tree, inet);
    if (match_mask != NO_MATCH)
    {
      g_print ("Error match_inet %s %d\n", ipv6_out[i], match_mask);
      success = FALSE;
    }

    g_object_unref (inet);
    i++;
  }

  i = 0;
  while (ipv6_match[i].ip)
  {
    inet = sim_inet_new_from_string (ipv6_match[i].ip);

    match_mask = sim_network_match_inet (tree, inet);
    if (match_mask != ipv6_match[i].mask)
    {
      g_print ("Error match_inet %s : %d : %d\n", ipv6_match[i].ip, match_mask, ipv6_match[i].mask);
      success = FALSE;
    }

    g_object_unref (inet);
    i++;
  }

  /* destroy */
  g_object_unref (tree);

  return success;
}

gint
sim_network_test3 ()
{
  gint        result = TRUE;
  SimNetwork *tree;
  SimNetwork *tree_clone;

  /* tree empty clone */
  tree = sim_network_new ();
  tree_clone = sim_network_clone (tree);
  if (!sim_network_is_empty (tree_clone))
    result = FALSE;

  g_object_unref (tree_clone);

  if (!result)
    return FALSE;

  /* tree with inets clone */
  sim_network_test_add_inets (tree);

  tree_clone = sim_network_clone (tree);
  if (sim_network_is_empty (tree_clone))
    result = FALSE;

  sim_network_print (tree_clone);
  g_object_unref (tree_clone);

  /* Update nodes */
  sim_network_test_add_inets (tree);

  g_object_unref (tree);

  return result;
}

gboolean
sim_network_test4 ()
{
  gboolean      result = TRUE;
  SimNetwork *tree;
  gchar        *aux;

  tree = sim_network_new ();
  sim_network_test_add_inets (tree);

  aux = sim_network_to_string (tree);
  if (g_strcmp0 (aux, "192.168.2.100,192.168.5.100/24,10.0.0.1,62.16.25.0/16,123::123,321::123/64") != 0)
    result = FALSE;
  g_free (aux);

  /* Insert duplicated ips */
  sim_network_test_add_inets (tree);

  aux = sim_network_to_string (tree);
  if (g_strcmp0 (aux, "192.168.2.100,192.168.5.100/24,10.0.0.1,62.16.25.0/16,123::123,321::123/64") != 0)
    result = FALSE;
  g_free (aux);

  g_object_unref (tree);

  return result;
}

gboolean
sim_network_test5 ()
{
  gint          result = TRUE;
  SimInet      *inet;
  SimInet      *inet_res;
  SimNetwork *tree;
  guint         i;
  gchar        *tree_str;
  gchar        *ip_nest[] = {"192.168.1.5/32",
                             "192.168.1.0/24",
                             "192.168.0.0/16",
                             "FFFF::123:456/128",
                             "FFFF::123:0/64",
                             NULL};

  tree = sim_network_new ();

  i = 0;
  while (ip_nest[i] != NULL)
  {
    inet = sim_inet_new_from_string (ip_nest[i]);
    sim_network_add_inet (tree, inet);
    g_object_unref (inet);
    i++;
  }

  tree_str = sim_network_to_string (tree);
  g_print ("%s\n", tree_str);
  g_free (tree_str);

  i = 0;
  while (ip_nest[i] != NULL)
  {
    inet = sim_inet_new_from_string (ip_nest[i]);
    if (!sim_network_has_inet (tree, inet))
      return FALSE;

    inet_res = sim_network_search_inet (tree, inet);
    if (!sim_inet_equal (inet, inet_res))
      return FALSE;

    if (!sim_network_has_exact_inet (tree, inet))
      return FALSE;

    g_object_unref (inet);
    i++;
  }

  g_object_unref (tree);

  return result;
}

void
sim_network_register_tests (SimUnittesting *engine)
{
  sim_unittesting_append (engine, "sim_network_test1 - ipv4 basics", sim_network_test1, TRUE);
  sim_unittesting_append (engine, "sim_network_test2 - ipv6 basics", sim_network_test2, TRUE);
  sim_unittesting_append (engine, "sim_network_test3 - clone", sim_network_test3, TRUE);
  sim_unittesting_append (engine, "sim_network_test4 - to_string", sim_network_test4, TRUE);
  sim_unittesting_append (engine, "sim_network_test5 - nested networks", sim_network_test5, TRUE);
}
#endif //USE_UNITTESTS

