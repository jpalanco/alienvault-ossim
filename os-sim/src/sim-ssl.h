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

#ifndef __SIM_SSL_H__
#define __SIM_SSL_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <gnet.h>

typedef struct _SimSsl        SimSsl;
typedef struct _SimSslClass   SimSslClass;
typedef struct _SimSslPrivate SimSslPrivate;

#define SIM_TYPE_SSL                  (sim_ssl_get_type ())
#define SIM_SSL(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_SSL, SimSsl))
#define SIM_SSL_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_SSL, SimSslClass))
#define SIM_IS_SSL(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_SSL))
#define SIM_IS_SSL_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_SSL))
#define SIM_SSL_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_SSL, SimSslClass))

G_BEGIN_DECLS

typedef enum
{
  SSL_CLIENT = 0,
  SSL_SERVER
}
SIM_SSL_SIDE;

#define MAX_SIDES 2

typedef enum
{
  SSL_SSLv3 = 0,
  SSL_TLSv1,
}
SIM_SSL_METHOD;

#define MAX_METHODS 2

struct _SimSsl {
  GObject parent;
  SimSslPrivate *_priv;
};

struct _SimSslClass {
  GObjectClass parent_class;
};

GType               sim_ssl_get_type                (void);
SimSsl *            sim_ssl_new_full                (gint           sd,
                                                     SIM_SSL_SIDE   side,
                                                     SIM_SSL_METHOD method,
                                                     const gchar  * cert_file,
                                                     const gchar  * key_file,
                                                     gboolean       force_client_cert);

SimSsl *            sim_ssl_client_new              (GTcpSocket   * socket);
SimSsl *            sim_ssl_server_new              (GTcpSocket   * socket);

void                sim_ssl_get_remote_certificate  (SimSsl       * ssl);
gint                sim_ssl_write                   (SimSsl       * ssl,
                                                     gchar        * data,
                                                     gsize          data_len);
gint                sim_ssl_read                    (SimSsl       * ssl,
                                                     gchar        * data,
                                                     gsize          data_len);
gint                sim_ssl_accept                  (SimSsl       * ssl);
gint                sim_ssl_connect                 (SimSsl       * ssl);
gint                sim_ssl_shutdown                (SimSsl       * ssl);

G_END_DECLS

#endif
