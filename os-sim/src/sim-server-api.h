#ifndef _SIM_SERVER_API_H
#define _SIM_SERVER_API_H

#include <glib.h>
#include <libsoup/soup.h>


gboolean sim_server_api_init (SoupServer *server);
gboolean sim_server_api_stop (SoupServer *server);

#endif
