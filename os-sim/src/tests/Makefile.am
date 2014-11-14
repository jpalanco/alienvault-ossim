check_PROGRAMS = test-load-directives

test_load_directives_SOURCES = test-load-directives.c  sim-dummy.c \
		../sim-xml-directive.c \
		../sim-rule.c ../sim-inet.c ../sim-radix.c ../sim-network.c ../sim-net.c ../sim-sensor.c \
		../sim-uuid.c ../sim-plugin.c ../sim-context.c ../sim-host.c ../sim-database.c ../sim-policy.c \
		../sim-role.c ../sim-util.c ../sim-event.c ../sim-plugin-sid.c ../sim-mini-object.c ../sim-text-fields.c ../sim-command.c \
		../sim-engine.c ../sim-directive.c ../sim-directive-group.c ../sim-db-command.c ../sim-server.c ../sim-db-insert.c ../sim-idm-entry.c \
		../sim-idm-anomalies.c ../sim-config.c ../sim-session.c ../sim-container.c ../sim-plugin-state.c ../sim-timetable.c ../sim-idm.c ../sim-timezone.c \
		../sim-geoip.c ../sim-alarm-stats.c ../sim-xml-config.c ../sim-groupalarm.c  ../sim-reputation.c ../sim-connect.c \
		../sim-log.c ../sim-organizer.c ../sim-correlation.c
test_load_directives_CFLAGS =  $(LIBGDA_CFLAGS) $(GNET_CFLAGS) \
				$(JSON_GLIB_CFLAGS) \
				$(LIBSOUP_CFLAGS) \
				$(LIBGEOIP_CFLAGS) \
				-I.. -DOS_SIM_GLOBAL_WSD_FILE=\""directives.xsd"\" \
				-DOS_SIM_RUN_FILE=\""."\" \
				-DOS_SIM_VERSION=\""$(OS_SIM_VERSION)"\" \
				-DOS_SIM_GLOBAL_CONFIG_FILE=\""config.xml"\" \
				-DOS_SIM_GLOBAL_DIRECTIVE_FILE=\""directives.xml"\" \
				-DOS_SIM_LOG_DIR=\"".\""
test_load_directives_LDADD =  $(LIBGDA_LIBS) $(GNET_LIBS) $(LIBUUID_LIBS) $(JSON_GLIB_LIBS) $(LIBGEOIP_LIBS) $(LIBSOUP_LIBS)
TESTS = test-load-directives
DISTCLEANFILES = Makefile.in
