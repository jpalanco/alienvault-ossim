module OssimSetup =
  autoload xfm

let comment  = IniFile.comment IniFile.comment_re IniFile.comment_default
let sep      = IniFile.sep IniFile.sep_re IniFile.sep_default
let empty    = IniFile.empty

let entry    = IniFile.entry IniFile.entry_re sep comment

let title       = IniFile.title ( IniFile.record_re - "general" )
let record      = IniFile.record title entry

let record_anon = [ label "general" . ( entry | empty )+ ]

let lns    = record_anon? . record*

let filter = (incl "/etc/ossim/ossim_setup.conf")

let xfm = transform lns filter
