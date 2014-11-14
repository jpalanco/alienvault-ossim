#!/usr/bin/perl

$out_sql = "bit9_generated.sql";
$out_translate = "bit9_generated_translate.txt";

open(OUT, ">$out_sql");
open(OUT2, ">$out_translate");


print OUT "-- bit9\n";
print OUT "-- plugin_id: 1630\n";
print OUT "DELETE FROM plugin WHERE id = '1630';\n";
print OUT "DELETE FROM plugin_sid where plugin_id = '1630';\n";
print OUT "INSERT INTO plugin (id, type, name, description) VALUES (1630, 1, 'bit9', 'Bit9, Advanced Threat Protection');\n";

print OUT2 "[translation]\n";

%priority = ();
$priority{"Notice"} = 2;
$priority{"Alert"} = 5;
$priority{"Warning"} = 3;
$priority{"Info"} = 1;
$priority{"Error"} = 4;

$plugin_sid = 1;

while(<STDIN>){
if(/^\s*(.*),\s*(.*),\s*(.*)$/){
print OUT "INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1630, $plugin_sid, NULL, NULL, 'bit9: $2', $priority{$3}, 2);\n";
print OUT2 "$2=$plugin_sid\n";
$plugin_sid++;
}
}

print OUT "INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES (1630, 9999, NULL, NULL, 'bit9: Unknown event', 5, 2);\n";
print OUT2 "_DEFAULT_=9999\n"; 

close OUT;
close OUT2;
