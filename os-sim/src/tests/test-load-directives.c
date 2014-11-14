#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <glib.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <errno.h>
#include <fcntl.h>

#include "sim-xml-directive.h"
#include "sim-rule.h"
#include "sim-dummy.h"

static int copy_file (const char *orig, const char *dst)
{
  char buf[4096];
  ssize_t r;
  int fin = -1;
  int fout = -1;
  int result = -1;
  if ((fin = open (orig,O_RDONLY)) != -1)
  {
    if ((fout = open (dst,O_CREAT|O_WRONLY,0600))!= -1)
    {
      while ((r=read(fin,buf,4096))!=-1 && r == 4096)
      {
        while ((r = write (fout,buf,4096)) == -1 && errno == EINTR);
      }
      if (r != -1)
      {
        int err;
        while ( (err = write (fout,buf,r)) && errno == EINTR);
        if (err != -1)
         result = 0;
      }
    }
    
  }
  if (fin != -1)
    close (fin);
  if (fout != -1)
    close (fout);
  if (result == -1)
    remove (dst);
  return result;
}


static void test_load1 (void)
{
  /* First ,try to load the free version of the directives */
  /* Change to the DATA directory */
  SimXmlDirective *dxml = NULL;
  g_assert (chdir ("dtest") == 0);
  remove ("directives.xsd");
  g_assert ((dxml =  sim_xml_directive_new_from_file ("dt1.xml")) != NULL);
  g_object_unref (dxml);
  g_assert (chdir ("..") == 0);
    

}
static void test_load2 (void)
{
  /* Test with the xsd file */
  SimXmlDirective *dxml = NULL;
  g_assert (chdir ("dtest") == 0);
  remove ("directives.xsd");
  g_assert (copy_file ("dt.xsd","directives.xsd") == 0);
  g_assert ((dxml =  sim_xml_directive_new_from_file ("dt1.xml")) != NULL);
  g_object_unref (dxml);
  remove ("directives.xsd");
  g_assert (chdir ("..") == 0);
}

static void test_load3 (void)
{
  SimXmlDirective *dxml = NULL;
  g_assert (chdir ("dtest") == 0);
  g_assert ((dxml =sim_xml_directive_new ()) !=  NULL);
  remove ("directives.xsd");
  g_assert (sim_xml_directive_load_file (dxml,"dt1.xml") == 0); 
  g_object_unref (dxml);
  g_assert (chdir ("..") == 0);

}
static void test_load4 (void)
{
  SimXmlDirective *dxml = NULL;
  g_assert (chdir ("dtest") == 0);
  g_assert ((dxml =sim_xml_directive_new ()) !=  NULL);
  remove ("directives.xsd");
  g_assert (copy_file ("dt.xsd","directives.xsd") == 0);
  g_assert (sim_xml_directive_load_file (dxml,"dt1.xml") == 0); 
  g_object_unref (dxml);
  remove ("directives.xsd");
  g_assert (chdir ("..") == 0);

}
/* Load and bad XML directive file */
static void test_load5 (void)
{
 SimXmlDirective *dxml = NULL;
  g_assert (chdir ("dtest") == 0);
  g_assert ((dxml =sim_xml_directive_new ()) !=  NULL);
  remove ("directives.xsd");
  g_assert (sim_xml_directive_load_file (dxml,"dt2.xml") == -1); 
  g_object_unref (dxml);
  g_assert (chdir ("..") == 0);

}
static void test_load6 (void)
{
 SimXmlDirective *dxml = NULL;
  g_assert (chdir ("dtest") == 0);
  g_assert ((dxml =sim_xml_directive_new ()) !=  NULL);
  remove ("directives.xsd");
  g_assert (sim_xml_directive_load_file (dxml,"dt3.xml") == 0); 
  g_object_unref (dxml);
  g_assert (chdir ("..") == 0);

}
/* Test the XSD file, directive id != integer */
static void test_load7 (void)
{
 SimXmlDirective *dxml = NULL;
  g_assert (chdir ("dtest") == 0);
  g_assert ((dxml =sim_xml_directive_new ()) !=  NULL);
  remove ("directives.xsd");
  g_assert (copy_file ("dt.xsd","directives.xsd") == 0);
  g_assert (sim_xml_directive_load_file (dxml,"dt3.xml") == -1); 
  g_object_unref (dxml);
  remove ("directives.xsd");
  g_assert (chdir ("..") == 0);

}

static void discard_output ( const gchar *log_domain,GLogLevelFlags log_level,const gchar *message, gpointer user_data)
{
  (void)log_domain;
  (void)log_level;
  (void) message;
  (void)user_data;
}
int main (int argc,char **argv)
{
  g_test_init (&argc,&argv,NULL);
  g_type_init();
  sim_xml_directive_register_type ();
  sim_network_register_type ();
  sim_rule_get_type (); /* Force register of SimRule */
   g_log_set_always_fatal (G_LOG_LEVEL_ERROR);
  /* See  https://bugzilla.gnome.org/show_bug.cgi?id=679556 */
  init_dummy(); 
  g_log_set_default_handler (discard_output,NULL);
  g_test_add_func ("/directives-xml/1",test_load1);
  g_test_add_func ("/directives-xml/2",test_load2);
  g_test_add_func ("/directives-xml/3",test_load3);
  g_test_add_func ("/directives-xml/4",test_load4);
  g_test_add_func ("/directives-xml/5",test_load5);
  g_test_add_func ("/directives-xml/6",test_load6);
  g_test_add_func ("/directives-xml/7",test_load7);

  return g_test_run ();
}
