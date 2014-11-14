/* (c) AlienVault Inc. 2012-2013
 * All rights reserved
 *
 * This code is protected by copyright and distributed under licenses
 * restricting its use, copying, distribution, and decompilation. It may
 * only be reproduced or used under license from Alienvault Inc. or its
 * authorised licensees.
 */

#ifndef __SIM_USER_AUTH_H__
#define __SIM_USER_AUTH_H__ 1

#include <glib.h>
#include <glib-object.h>

#define SIM_TYPE_USER_AUTH                  (sim_user_auth_get_type ())
#define SIM_USER_AUTH(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_USER_AUTH, SimUserAuth))
#define SIM_USER_AUTH_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_USER_AUTH, SimUserAuthClass))
#define SIM_IS_USER_AUTH(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_USER_AUTH))
#define SIM_IS_USER_AUTH_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_USER_AUTH))
#define SIM_USER_AUTH_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_USER_AUTH, SimUserAuthClass))

G_BEGIN_DECLS

typedef struct _SimUserAuth        SimUserAuth;
typedef struct _SimUserAuthClass   SimUserAuthClass;
typedef struct _SimUserAuthPrivate SimUserAuthPrivate;


struct _SimUserAuth {
  GObject parent;
  SimUserAuthPrivate *_priv;
};

struct _SimUserAuthClass {
  GObjectClass parent_class;
};

typedef enum  {SimUserAuthDatabase = 1} SimUserAuthType;


GType               sim_user_auth_get_type                (void);
SimUserAuth *       sim_user_auth_new                     (SimUserAuthType auth_type); 

gboolean            sim_user_auth_check_login             (SimUserAuth *,const gchar *login, const gchar *password);

G_END_DECLS

#endif
