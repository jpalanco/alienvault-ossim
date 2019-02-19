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

#include "sim-groupalarm.h"

#include <glib.h>
#include <glib-object.h>

struct _SimGroupAlarmPrivate {
	time_t tstart;
	time_t tend;
	unsigned int count;
	gchar *alarmsha1; /* sha1 hash key + tstat*/
};

static gpointer parent_class = NULL;

#define SIM_GROUP_ALARM_GET_PRIVATE(self) (G_TYPE_INSTANCE_GET_PRIVATE ((self), SIM_TYPE_GROUP_ALARM, SimGroupAlarmPrivate))

static void 
sim_group_alarm_instance_init (SimGroupAlarm *self){
		self->_priv =  SIM_GROUP_ALARM_GET_PRIVATE (self);
		self->_priv->tstart = self->_priv->tend = 0;
		self->_priv->count = 0;
}
static void
sim_group_alarm_dispose (GObject *self){
	G_OBJECT_CLASS (parent_class)->dispose (self);
}

static void 
sim_group_alarm_finalize (GObject *self){
	SimGroupAlarm *p = SIM_GROUP_ALARM (self);
	if (p->_priv->alarmsha1)
		g_free (p->_priv->alarmsha1);
	G_OBJECT_CLASS (parent_class)->finalize (self);
}

static void
sim_group_alarm_class_init (SimGroupAlarmClass *klass){
	GObjectClass *selfclass = G_OBJECT_CLASS (klass);
	g_type_class_add_private (klass, sizeof (SimGroupAlarmPrivate));
	parent_class = g_type_class_ref (G_TYPE_OBJECT);
	selfclass->dispose = sim_group_alarm_dispose;
	selfclass->finalize = sim_group_alarm_finalize;	
}


GType
sim_group_alarm_get_type (void)
{
  static GType object_type = 0;
	G_LOCK_DEFINE_STATIC (SimGroupAlarm);
	G_LOCK (SimGroupAlarm); 	
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimGroupAlarmClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_group_alarm_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimGroupAlarm),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_group_alarm_instance_init,
              NULL                        /* value table */
    };
   
                                                                                        
                                     
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimGroupAlarm", &type_info, 0);
		
  }
	G_UNLOCK (SimGroupAlarm);
	return object_type;
}

/** 
	@brief Constructor of SimGroupAlarm object
	
	@param timeout seconds to timeout of the alarm
	
	@return pointer to the new created object or NULL in case of error
*/

SimGroupAlarm * sim_group_alarm_new (unsigned int timeout, const gchar *key){
	SimGroupAlarm *p = NULL;
	GString	*gkey;
	struct tm tvalue;
	char timebuf[2048];

	g_return_val_if_fail (key != NULL, NULL);
	if ( (gkey = g_string_new (key)) != NULL){ 
		if (timeout < 3601 ){
			do{
				p = g_object_new (SIM_TYPE_GROUP_ALARM, NULL);
				p->_priv->tstart = time(NULL);
				p->_priv->tend = time(NULL) + timeout;
				p->_priv->alarmsha1 = NULL;
				if (gmtime_r (&p->_priv->tstart,&tvalue) == NULL){
					g_object_unref (G_OBJECT (p));
					p = NULL;
					break; /* Out */
				}
				/* Now obtain the string for the alarm hash  in SimEvent*/
				if (asctime_r (&tvalue,timebuf) == NULL){
					g_object_unref (G_OBJECT (p));
					p = NULL;
					break;
				}
				g_string_append_printf (gkey,"%s|%s",key,timebuf);
				if  ( (p->_priv->alarmsha1 = g_compute_checksum_for_string (G_CHECKSUM_SHA1, gkey->str,-1)) == NULL){
					g_object_unref (G_OBJECT (p));
					p = NULL;
					break;
				}
				
				
			}while (0);

		}else{
			g_warning ("Max timeout is 3600 seconds");
		}
	}
	if (gkey)
		g_string_free (gkey, TRUE);
	if (p == NULL)
		g_warning ("Can't creatae SimGroupAlarm object\n");
	return p;
}

/**
	@brief Increment the count of alarms in the object
	
	@param self Pointer to the object
*/

void sim_group_alarm_inc_count (SimGroupAlarm *self){
	g_return_if_fail (SIM_IS_GROUP_ALARM (self));
	self->_priv->count++;
}

/**
	@brief Check the Alarm Group is timeout

	@param self Pointer to the object
	
	@return true is the group alarm is timeout

*/

gboolean sim_group_alarm_is_timeout (SimGroupAlarm *self){
	gboolean result = FALSE;
	g_return_val_if_fail (SIM_IS_GROUP_ALARM (self), FALSE);
	if (difftime (time(NULL),self->_priv->tend) > 0.0){
		result = TRUE;	
	}
	return result;
	
}

/**
	
	@brief Return the alarm count of this group.

	@param seld Pointer to the object
	
	@return The count of alarms
*/
inline
unsigned int sim_group_alarm_get_count (SimGroupAlarm *self){
	g_return_val_if_fail (SIM_IS_GROUP_ALARM (self), 0);
	return self->_priv->count;
	
}
/**
	@brief Return de time when the object was created, (time start of the alarm
	
	@param self Pointer to the object

	@return The time_t value of the start of the alarm

*/
inline
time_t sim_group_alarm_get_tstart (SimGroupAlarm *self){
	g_return_val_if_fail (SIM_IS_GROUP_ALARM (self), 0);
	return self->_priv->tstart;
	
}
/**
	@brief This function return a copy of the alarm id. Must be free with g_free by the caller
	
	@param self Pointer to the object
	
	@return A copy of the text id of the alarm. NULL in case of error
*/

inline
gchar * sim_group_alarm_get_id (SimGroupAlarm *self){
	g_return_val_if_fail (SIM_IS_GROUP_ALARM (self), NULL);
	return g_strdup (self->_priv->alarmsha1);
}


	
	


