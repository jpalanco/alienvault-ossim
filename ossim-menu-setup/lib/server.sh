#!/bin/bash
# 


SERVERSTEP=0
ServerWizard()
{
  SERVERSTEP=0
  while true;do
	case $SERVERSTEP in
  	0)
	  ChooseInterfaces;;
  	1)
	  ChooseNetworks;;
	2)
	  dbhost;;
	3)
	  dbport;;
	4)
	  dbpass;;
  	*)
      return
	esac
  done
  # another variables if $EXPERT is set
}

