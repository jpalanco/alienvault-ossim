;--------------------------------
;Include Modern UI

!include "MUI.nsh"

;--------------------------------
;General

!define VERSION "2.2.3"
!define NAME "OSSIM agent collection"
!define /date CDATE "%b %d %Y at %H:%M:%S"


Name "${NAME} v${VERSION}"
BrandingText "Copyright (C) 2010 AlienVault LLC"
OutFile "alienvault_agents.exe"


InstallDir "$PROGRAMFILES\OSSIM"


;--------------------------------
;Interface Settings

!define MUI_ABORTWARNING

;--------------------------------
;Pages
  !define MUI_ICON favicon.ico
  !define MUI_UNICON faviconu.ico


  !insertmacro MUI_PAGE_INSTFILES

  !insertmacro MUI_UNPAGE_INSTFILES

;--------------------------------
;Languages

  !insertmacro MUI_LANGUAGE "English"

;--------------------------------

Section "OSSIM Agents" MainSec

;Required section.
SectionIn RO
SetOutPath $INSTDIR

ClearErrors

File ossec_installer.exe ocs_installer.exe
WriteUninstaller "uninstall.exe"


; Install in the services 
Exec '"$INSTDIR\ossec_installer.exe"'
Exec '"$INSTDIR\ocs_installer.exe"'

Quit
SectionEnd

Section "Uninstall"
  
  ; Remove files and uninstaller
  Delete '"$INSTDIR\ossec_installer.exe"'
  Delete '"$INSTDIR\ocs_installer.exe"'

  RMDir "$INSTDIR"

SectionEnd
