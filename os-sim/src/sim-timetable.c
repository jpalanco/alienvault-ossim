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

#define _XOPEN_SOURCE
#include "config.h"

#include "sim-timetable.h"

#include <time.h>
#include <strings.h>

#include "sim-log.h"

enum
{
  DESTROY,
  LAST_SIGNAL
};
typedef struct _timerange{
	gboolean any;
	int tstart;
	int tend;
}timerange_t;
typedef struct _timenode{
	GList *timeranges[8]; /* the last element = ANY*/
	gboolean any;
}timenode_t;

struct _SimTimetablePrivate {
	GList *timenodes;
	gchar *name;
};


static gchar *daysOfWeek[]={"Sun","Mon","Tue","Wed","Thu","Fri","Sat","ANY"};
static gpointer parent_class = NULL;

/* Prototipes of private functions*/
static void
sim_timetable_delete_timenode(timenode_t *node);
static GList *
sim_timetable_parse_timespec(gchar *timespec);
static void
sim_timetable_delete_timerange_list(GList *list);
static gboolean		sim_timetable_check_range(GList *l,struct tm *t, SimEvent *event);

/* GType Functions */

static void 
sim_timetable_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void 
sim_timetable_impl_finalize (GObject  *gobject)
{
  SimTimetable *timetable = SIM_TIMETABLE (gobject);
	GList *list;
	/* implement here the clean stuff*/
	list = timetable->_priv->timenodes;
	while (list){
		timenode_t * node = list->data;
		list = g_list_remove(list,list->data);
		sim_timetable_delete_timenode(node);
	}
	if (timetable->_priv->name)
		g_free(timetable->_priv->name);
	g_free(timetable->_priv);
  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_timetable_class_init (SimTimetableClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_ref (G_TYPE_OBJECT);

  object_class->dispose = sim_timetable_impl_dispose;
  object_class->finalize = sim_timetable_impl_finalize;
}

static void
sim_timetable_instance_init (SimTimetable *timetable)
{
  timetable->_priv = g_new0 (SimTimetablePrivate, 1);
	timetable->_priv->name = NULL;
}

/* Public Methods */
GType
sim_timetable_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimTimetableClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_timetable_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimTimetable),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_timetable_instance_init,
              NULL                        /* value table */
    };
   
    g_type_init ();
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimTimetable", &type_info, 0);
  }

  return object_type;
}

/*
 *
 */
SimTimetable*
sim_timetable_new (void)
{
  SimTimetable *timetable;

  timetable = SIM_TIMETABLE (g_object_new (SIM_TYPE_TIMETABLE, NULL));

  return timetable;
}
/*
 *
 *
 */
gboolean 
sim_timetable_append_timerule(SimTimetable *timetable,gchar *datespec,gchar *timespec){
	timenode_t *node=NULL;
	int i;
	gboolean error = FALSE;
	gchar **splitdate;
	gchar **ptr;
	splitdate = g_strsplit(datespec,",",0);
	if ((node = g_new0(timenode_t,1))!=NULL){
		for (ptr = splitdate;*ptr!=NULL && !error ;ptr++){
			error = TRUE;
			for(i=0;i<8 ;i++){
				if (strcasecmp(*ptr,daysOfWeek[i])==0){
					error = FALSE;
					if (strcasecmp(*ptr,"ANY")==0 && *(ptr+1)!=NULL){
						error = TRUE;
						ossim_debug("%s Error: ANY token must be alone in timerule %s",__FUNCTION__,datespec);
						break; /* Exit for loop*/
					}else
					{
						GList *list = sim_timetable_parse_timespec(timespec);
						if (list!=NULL){
							if (node->timeranges[i]==NULL){
									node->timeranges[i] = list;
									break; /* Exit for loop*/
							}
							else{
										ossim_debug("%s:duplicate entry in timespec attribute: %s",__FUNCTION__,timespec);
										sim_timetable_delete_timerange_list(list);
										error = TRUE;
							}	
						}else{
							ossim_debug("%s: Error parsing timespec attribute: %s",__FUNCTION__,timespec);
							error = TRUE;
						}
					}
					}
				} // End for loop
				if (error){
					ossim_debug("%s: Error: Bad entry in timerule %s",__FUNCTION__,datespec);
				}
			} // End for loop
			if (!error)
				timetable->_priv->timenodes = g_list_append(timetable->_priv->timenodes,node);
	}else{
		ossim_debug("%s: Error allocating memory",__FUNCTION__);
		error = TRUE;
	}		
	g_strfreev(splitdate);
	if (error && node!=NULL)
		g_free(node);
	return error;
}
/*
 * Private function
 * Parse a string looking for a time range like 8:00-10:00,19:00-20:00
 */
static GList *
sim_timetable_parse_timespec(gchar *timespec){
	gchar **ts;
	ts = g_strsplit(timespec,",",0);
	gchar **ptr;
	gchar *tstart;
	gchar *tend;
	GList *list = NULL;
	timerange_t *timerange;
	gboolean error = FALSE;
	struct tm t;
	for (ptr = ts;*ptr!=NULL && !error; ptr++ ){
		timerange = NULL;
		/* Check for "ANY" timerange*/
		if (strcasecmp("ANY",*ptr) == 0){
			if (*(ptr+1)==NULL){
				timerange = g_new0(timerange_t,1);
				timerange->any = TRUE;
				list = g_list_append(list,timerange);
			}else{
				error =  TRUE;
			}
			break; /* Exit for loop*/	
		}
		tstart = *ptr;
		tend = strchr(*ptr,'-');
		t.tm_hour = t.tm_min = 0;
		if (tend!=NULL){
			(*ptr)[tend-tstart] = '\0';
			tend++; /* Skip \0*/
			timerange = g_new0(timerange_t,1);
			if (timerange){		
				if (strptime(tstart,"%H:%M",&t)!=NULL){
						timerange->tstart = t.tm_hour*3600 + t.tm_min*60;			
					if (strptime(tend,"%H:%M",&t)!=NULL){
						timerange->tend = t.tm_hour*3600 + t.tm_min*60;
						list = g_list_append(list,timerange);
					}else
						error = TRUE;
				}else
					error = TRUE;
			}else
				error = TRUE;
		}
	}
	if (error){
		if (timerange)
			g_free(timerange);
		while(list){
			timerange_t *p = list->data;
			list = g_list_remove(list,p);
			g_free(p);
		}
		list = NULL;	
	}
	g_strfreev(ts);
	return list;
}
/*
 * Private function
 */
static void
sim_timetable_delete_timenode(timenode_t *node){
  //FIXME: BAd implementation!!!
	GList *list = NULL;
	sim_timetable_delete_timerange_list(list);
	g_free(node);
}
static void
sim_timetable_delete_timerange_list(GList *list){
	while(list){
		timerange_t *t = list->data;
		list = g_list_remove(list,t);
		g_free(t);
	}
}
/*
 * Public API
 */
void
sim_timetable_set_name(SimTimetable *timetable,const char *name){
	timetable->_priv->name = g_strdup(name);
}
/*
 * Public API
 */
void 
sim_timetable_dump(SimTimetable *timetable){
	GList *list,*timelist;
	int i;
	ossim_debug("Dumping timetable:%s",timetable->_priv->name);
	list = timetable->_priv->timenodes;
	while (list){
		timenode_t *ptr = (timenode_t*)list->data;
		for (i=0;i<8;i++){
			if ((timelist = ptr->timeranges[i])!=NULL){
				gchar *st=NULL,*sttemp=NULL;
				while (timelist){
					timerange_t *range = (timerange_t*)timelist->data;
					if (range->any){
						st = g_strdup_printf("ANY");
						break;
					}
					else{
						sttemp = st;
						st = g_strdup_printf("%s %02u:%02u-%02u:%02u ",
							 (st!=NULL) ? st : "",
							range->tstart/3600,(range->tstart%3600)/60,
							range->tend/3600,(range->tend%3600)/60);
						if (sttemp)
							g_free(sttemp);
					}
					timelist = timelist->next;
				}
				ossim_debug("\t%s %s",daysOfWeek[i],st);
				g_free(st);
			}
		}
	list = list->next;
	}	
}
/*
 * Public API
 */
gchar *
sim_timetable_get_name(SimTimetable *timetable){
	return timetable->_priv->name;
}
/*
 * Public API
 */
gboolean
sim_timetable_check_timetable(SimTimetable *timetable, struct tm *t, SimEvent *event){
	GList *lnodes;
	gboolean r = FALSE;
	lnodes = timetable->_priv->timenodes;
	while (lnodes && !r){
			timenode_t *node = (timenode_t*)lnodes->data;
			if (node->any && sim_timetable_check_range(node->timeranges[7],t, event)){
				r = TRUE;
			}else{
				if (node->timeranges[t->tm_wday]!=NULL){
						r = sim_timetable_check_range(node->timeranges[t->tm_wday],t, event);
				}
			}

	lnodes = lnodes->next;
	}
	return r;
}
/*
 *
 */
static gboolean
sim_timetable_check_range(GList *l,struct tm *t, SimEvent *event){
	gboolean r = FALSE;
	int seconds;

  // TODO: this unused parameter seems strange
  // unused parameter
  (void) event;

	while(l && !r){
		timerange_t *range = (timerange_t*)l->data;
		if (range->any){
			r = TRUE;
		}else{
			seconds = 3600*t->tm_hour+60*t->tm_min;
			if (seconds>=range->tstart && seconds<=range->tend){
				r = TRUE;
			}
		}
				
		l = l->next;	
	}
	return r;
}


