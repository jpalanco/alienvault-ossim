#!/usr/bin/perl -X
##################
# (c) Copyright 2004-2006 Open Security Foundation (OSF) / Open Source Vulnerability Database (OSVDB), All Rights Reserved.
#
# Revision History
# 2006-06-13 -	Version 1.1
# 		Christohper Wilson  - Updates to handle data fields which may have unescaped data.
# 		Sullo - Added DEBUG mode variable for very verbose output. Fixed a few more strict warnings.
##################

##################
# Copy the stable parts of the database to another database.
##################

use DBI;
use POSIX;
use XML::Parser;
use Encode;
use HTML::Entities;

#open(STDERR, ">>error.log");
#chomp(my $date= qx/date/);
#print STDERR "\n\nERRORS on $date\n";

##################
# Declare User defined variables
##################
 
my $newDB="";
my $newUsr="";
my $newPwd="";
my $newDBLoc="localhost";
my $newDBType=1;
my $xmlFile="xmlDumpByID.xml";
my $DEBUG=0;
my $dbh;

##################
# Process args
##################

if (scalar(@ARGV)<1) {
	usage();
} else {
	processArgs();
}

my $date= qx/date/;
print "\n\n *** Importing OSVDB database ***\n";
print "Starting at $date\n";

##################
# Setup db connection.
##################
if ($newDBType==1) {
	print "Connecting to PostgreSQL database";
	$dbh = DBI->connect("DBI:Pg:dbname=$newDB", $newUsr, $newPwd)
		or die "Couldn't connect to database: " . DBI->errstr;
}
if ($newDBType==2) {
	print "Connecting to MySQL database";
	$dbh = DBI->connect("DBI:mysql:$newDB:$newDBLoc", $newUsr, $newPwd)
		or die "Couldn't connect to database: " . DBI->errstr;
}

##################
# Declare variables
##################

 ##################
 # General variables
 ##################
 my $db="osvdb_mangle";
 my $currentID=0;
 my $currentType=0;
 my $currentTag="";
 my $VULN=1;
 my $OBJECT=2;
 my $SCORE=3;
 my $EXTREF=4;
 my $AUTHOR=5;
 my $CREDIT=6;
 my $EXTTXT=7;
 my $OTHER=0;
 my $progress=0;
 my $refFirst=1;
 my $scale=100;
 my $maxVulnId="maxVulnId";
 my $affStatus="affected";
 my $max=0;
 my $finishString="";
 my $compiledString="";

 ##################
 # Tables elements and names
 ##################

 my $obj="object";
 my $obj_id="object_id";
 my $objAutoNum=1;

 my $obj_corr="object_correlation";
 my $obj_corr_id="corr_id";
 my $obj_corrAutoNum=1;
 my $obj_corr_idCur=1;

 my $obj_aff="object_affect_type";
 my $obj_aff_type_id="type_id";
 my $obj_aff_type_name="type_name";
 my $obj_aff_typeAutoNum=1;
 my $obj_aff_type_idCur=1;

 my $obj_b="object_base";
 my $obj_b_n="base_name";
 my $obj_b_id="base_id";
 my $obj_bAutoNum=1;
 my $obj_b_idCur=1;

 my $obj_vnd="object_vendor";
 my $obj_vnd_n="vendor_name";
 my $obj_vnd_id="vendor_id";
 my $obj_vndAutoNum=1;
 my $obj_vnd_idCur=1;

 my $obj_ver="object_version";
 my $obj_ver_n="version_name";
 my $obj_ver_id="version_id";
 my $obj_verAutoNum=1;
 my $obj_ver_idCur=1;

 my $vln="vuln";
 my $vid="osvdb_id";
 my $title="osvdb_title";
 my $dscl="disclosure_date";
 my $dsc="discovery_date";
 my $ocreate="osvdb_create_date";
 my $lastmod="last_modified_date";
 my $explt_publish_date="exploit_publish_date";
 my $lcnp="location_physical";
 my $lcnl="location_local";
 my $lcnr="location_remote";
 my $lcnd="location_dialup";
 my $lcnu="location_unknown";
 my $atype_auth_manage="attack_type_auth_manage";
 my $atype_crypt="attack_type_crypt";
 my $atype_dos="attack_type_dos";
 my $atype_hijack="attack_type_hijack";
 my $atype_info_disclose="attack_type_info_disclose";
 my $atype_infrastruct="attack_type_infrastruct";
 my $atype_input_manip="attack_type_input_manip";
 my $atype_miss_config="attack_type_miss_config";
 my $atype_race="attack_type_race";
 my $atype_other="attack_type_other";
 my $atype_unknown="attack_type_unknown";
 my $iconfidential="impact_confidential";
 my $iintegrity="impact_integrity";
 my $iavailable="impact_available";
 my $iunknown="impact_unknown";
 my $eavailable="exploit_available";
 my $eunavailable="exploit_unavailable";
 my $erumored="exploit_rumored";
 my $eunknown="exploit_unknown";
 my $vverified="vuln_verified";
 my $vmyth_fake="vuln_myth_fake";
 my $vbest_prac="vuln_best_prac";
 my $vconcern="vuln_concern";
 my $vweb_check="vuln_web_check";

 my $extt="ext_txt";
 my $extts="ext_txts";
 my $extt_rev="revision";
 my $extt_txt="text";
 my $extt_id="ext_id";
 my $extt_idCur=1;
 my $revisionCur="";
 my $append=0;

 my $extt_t="ext_txt_type";
 my $extt_t_n="type_name";
 my $extt_t_id="type_id";
 my $extt_t_idCur=1; 
 my $extt_tAutoNum=1;

 my $extt_lang="language";
 my $extt_lang_n="lang_name";
 my $extt_lang_id="lang_id";
 my $extt_lang_idCur=1;
 my $extt_langAutoNum=1;

 my $auth="author";
 my $auth_n="author_name";
 my $auth_c="author_company";
 my $auth_e="author_email";
 my $auth_u="company_url";
 my $auth_id="author_id";
 my $authAutoNum=1;
 my $auth_idCur=1;

 my $crd="credit";
 my $crds="credits";
 my $crd_id="credit_id";
 my $crd_idCur=1;
 my $crdAutoNum=1;

 my $er="ext_ref";
 my $ers="ext_refs";
 my $indirect="indirect";
 my $er_id="ref_id";
 my $erAutoNum=1;
 my $indirectCur=1;

 my $er_value="ext_ref_value";
 my $ref_value="ref_value";
 my $er_value_id="value_id";
 my $er_valueAutoNum=1;
 my $er_value_idCur=1;

 my $er_type="ext_ref_type";
 my $type_name="type_name";
 my $er_type_id="type_id";
 my $er_typeAutoNum=1;
 my $er_type_idCur=1;

 my $scr="score";
 my $scrs="scores";
 my $scr_id="score_id";
 my $scrAutoNum=1;

 my $scrw="score_weight";
 my $scrw_w="weight";
 my $scrw_w_n="weight_name";
 my $scrw_id="weight_id";
 my $scrwAutoNum=1;
 my $scrw_idCur=1;

 ##################
 # Dynamic table structures
 ##################

  ##################
  # Simple tables
  ##################

  my %object_base = ( $obj_b_id => $obj_b_n );

  my %object_vendor = ( $obj_vnd_id => $obj_vnd_n );

  my %object_version = ( $obj_ver_id => $obj_ver_n );

  my %object_affect_type = ( $obj_aff_type_id => $obj_aff_type_name );

  my %language = (  $extt_lang_id => $extt_lang_n );

  my %ext_txt_types = ( $extt_t_id => $extt_t_n );

  my %ext_ref_types = ( $er_type_id => $type_name );


 ##################
 # Complex tables
 ##################


  my @object = ( [  $vid,
		    $obj_id,
		    $obj_corr_id,
		    $obj_aff_type_id ] );

  my @object_corr = ( [  $obj_corr_id,
		    	  $obj_b_id,
		    	  $obj_vnd_id,
		    	  $obj_ver_id ] );

  my @ext_txt = ( [  $vid,
		     $extt_rev,
		     $extt_txt,
		     $extt_id,
		     $extt_t_id,
		     $extt_lang_id,
		     $auth_id ] );

  my @credit = ( [ $vid,
		   $auth_id,
		   $crd_id ] );

  my @score = ( [ $vid,
		  $scr_id,
		  $scrw_id ] );

  my @ext_ref_value = ( [ $vid,
			  $ref_value,
			  $er_value_id,
			  $er_type_id ] );

  my @ext_ref = ( [ $vid,
		    $er_value_id,
		    $indirect,
		    $er_id ] );

  my %author = (  $auth_id => [ $auth_n,
				$auth_c,
				$auth_e,
				$auth_u ] );
			   
  my %vulnerability = ( $vid => [ $title,
				  $dscl,
				  $dsc,
				  $ocreate,
				  $lastmod,
				  $explt_publish_date,
				  $lcnp,
				  $lcnl,
				  $lcnr,
				  $lcnd,
				  $lcnu,
				  $atype_auth_manage,
				  $atype_crypt,
				  $atype_dos,
				  $atype_hijack,
				  $atype_info_disclose,
				  $atype_infrastruct,
				  $atype_input_manip,
				  $atype_miss_config,
				  $atype_race,
				  $atype_other,
				  $atype_unknown,
				  $iconfidential,
				  $iintegrity,
				  $iavailable,
				  $iunknown,
				  $eavailable,
				  $eunavailable,
				  $erumored,
				  $eunknown,
				  $vverified,
				  $vmyth_fake,
				  $vbest_prac,
				  $vconcern,
				  $vweb_check ] );

  my %score_weight = ( $scrw_id => [ $scrw_w, 
				     $scrw_w_n ] );

##################
# Begin Main Function
##################

my $p= new XML::Parser( Handlers =>
                         { Start   	=> \&startTag,
                           End     	=> \&endTag,
			   Char		=> \&foundChar,
                           Default 	=> \&default
                         },
                      );

print "..............................done\n\nParsing XML Dump\nThis may take a while\n";
$p->parsefile($xmlFile);

#debugVulnerability();
#debugObject();
#debugObjectBase();
#debugObjectVendor();
#debugObjectVersion();
#debugScore();
#debugScoreWeight();
#debugAuthor();
#debugExtRef();
#debugExtRefTypes();
#debugExtRefValue();
#debugExtTxtTypes();
#debugExtTxt();
#debugLanguage();
#debugCredit();

insertVuln();
insertObjectBase();
insertObjectVendor();
insertObjectVersion();
insertObjectAffectType();
insertObjectCorr();
insertObject();
insertScoreWeight();
insertScore();
insertExtRefTypes();
insertExtRefValue();
insertExtRef();
insertAuthor();
insertExtTxtTypes();
insertLanguage();
insertExtTxt();
insertCredit();


$date= qx/date/;
print "\nFinished at $date\n";

exit;

###################
# End Main Function
###################

###################
# Subroutines
###################

###################
# finishAssocTables
###################

sub finishAssocTables($) {
	my ($val) = @_;
    	if ($currentType == $VULN) {
		addVulnElement($currentTag, $compiledString);
	}
	if ($currentType == $SCORE && !($val =~ /<\/$scrs>/)) {
		addScoreElement($compiledString);
	}
	if ($currentType == $AUTHOR && !($val =~ /<\/$auth>|<\/$extt>|<\/$extts>/)) {
		addAuthorElement($currentTag, $compiledString);
	}
	if ($currentType == $EXTTXT && $val =~ /<\/text>/) {
		addExtTxtElement($compiledString);
	}
	if ($currentType == $OBJECT && !($val =~ /\/product>|<\/products>/)) {
		addObjectElement($currentTag, $compiledString);
	}
	if ($currentType == $EXTREF && !($val =~ /<\/$ers>/)) {
		if ($refFirst) {
			$refFirst=0;
			$append=0;
		}
		addExtRefElement($compiledString);
	}
	if ($currentType == $CREDIT && !($val =~ /<\/$crds>|<\/$crd>/)) {
		addAuthorElement($currentTag, $compiledString);
		assocCredit();
	}
    	if ($val =~ /<\/vuln>/) {
		if ($DEBUG) { print "Leaving Vuln.\n"; }
	}
    	if ($val =~ /<\/product>/) {
		if ($DEBUG) { print "Leaving Product.\n"; }
		assocObjects();
	}
    	if ($val =~ /<\/$er>/) {
		if ($DEBUG) { print "Leaving External Reference.\n"; }
		$refFirst=1;
	}
    	if ($val =~ /<\/$crd>/) {
		if ($DEBUG) { print "Leaving Credit.\n"; }
		authorExists();
		$credit[$crd_idCur][1]=$auth_idCur;
		$currentType=$CREDIT;
	}
    	if ($val =~ /<\/$auth>/) {
		if ($DEBUG) { print "Leaving Author.\n"; }
		authorExists();
		$currentType=$EXTTXT;
	}
    	if ($val =~ /<\/$extt>/) {
		if ($DEBUG) { print "Setting extTxtId $extt_idCur author id for ext_txt to $auth_idCur.\n"; }
		$ext_txt[$extt_idCur][6]=$auth_idCur;
	}
    	if ($val =~ /<\/$scr>/) {
		if ($DEBUG) { print "Leaving Score.\n"; }
		assocScores();
	}
	if ($val =~ /<\/$db>/) {
		syswrite STDOUT, $finishString;
	}
	$append=0;
	$compiledString="";

}

###################
# SetTypeArray
###################

sub setTypeArray($) {
	my ($val) = @_;
	my %attrs;
	setCurrentTag($val);
	$append=0;
    	if ($val =~ /<vuln /) {
		if ($DEBUG) { print "Changing status for Vulnerability $val.\n"; }
		%attrs=extractAttrs($val);
		if (defined $attrs{$vid}) {
			status();
			$attrs{$vid}=decode_entities(decode_entities(decode("utf8", $attrs{$vid})));
			if ($DEBUG) { print "I am on osvdbId $attrs{$vid}\n"; }
			initVuln(\%attrs);
		}
		$currentType=$VULN;
	}
    	if ($val =~ /<product /) {
		if ($DEBUG) { print "Changing status for Product with val $val.\n"; }
		%attrs=extractAttrs($val);
		if (defined  $attrs{$affStatus}) {
			if ($DEBUG) { print "I am adding product with attrib ".$attrs{$affStatus}."\n"; }
			initObjAffType(\%attrs);
		}
		$currentType=$OBJECT;
	}
    	if ($val =~ /<$er /) {
		if ($DEBUG) { print "Changing status for External Reference with $erAutoNum.\n"; }
		%attrs=extractAttrs($val);
		if (defined $attrs{$indirect}) {
			initExtRef(\%attrs);
		}
		$currentType=$EXTREF;
	}
    	if ($val =~ /<$crd>/) {
		if ($DEBUG) { print "Changing status for Credit.\n"; }
		$currentType=$CREDIT;
	}
    	if ($val =~ /<$auth>/) {
		if ($DEBUG) { print "Changing status for Author with $authAutoNum.\n"; }
		$currentType=$AUTHOR;
	}
    	if ($val =~ /<$extt /) {
		if ($DEBUG) { print "Changing status for External Text with.\n"; }
		%attrs=extractAttrs($val);
		if (defined $attrs{$extt_t_n}) {
			initExtTxt(\%attrs);
		}
		$currentType=$EXTTXT;
	}
    	if ($val =~ /<$scr /) {
		if ($DEBUG) { print "Changing status for Score with $scrAutoNum.\n"; }
		%attrs=extractAttrs($val);
		if (defined $attrs{$scrw_w_n}) {
			initScore(\%attrs);
		}
		$currentType=$SCORE;
	}
	if ($val =~ /<$db /) {
		%attrs=extractAttrs($val);
		if (defined $attrs{$maxVulnId}) {
			calculateScale($attrs{$maxVulnId});
			$progress=0;
			$max=$attrs{$maxVulnId};
		}
	}
}

###################
# Status
###################

sub status() {
	if ($progress == 0) {
		syswrite STDOUT, "Building Associations";

	}
	$progress++;
	if ($progress % $scale ==0) {
		syswrite STDOUT, ".";

	}
}

###################
# Extract Attributes
###################

sub extractAttrs($) {
	my ($tag)= @_;
	my @attrKeys;
	my %results;
    	my @attrs=split(/\"/, $tag);
	my @attrList;
	my $first=1;
	my $keyCount=0;
	$append=0;
	for( my $i=1; $i<scalar(@attrs); $i++) {
		@attrList[scalar(@attrList)]=$attrs[$i++];
	}
	for( my $j=0; $j<(scalar(@attrs)-1); $j++) {
		if($first==1) {
			@attrKeys=split(/\s+|>/, $attrs[$j++]);
			$attrKeys[1]=~s/\s+|=//g;
			$results{$attrKeys[1]}=$attrList[$keyCount++];
			$first=0;
		}
		else {
			$attrs[$j]=~s/\s+|=//g;
			$results{$attrs[$j++]}=$attrList[$keyCount++];
		}
	}
	return %results;
}

###################
# initScore
###################

sub initScore($) { 
	my ($initScore) = @_;
	if ($append) {
		$$initScore{$scrw_w_n}=~ s/'/''/g;
		$$initScore{$scrw_w_n}=~ s/\\/\\\\/g;
		$score_weight{$scrwAutoNum-1}[1].=$$initScore{$scrw_w_n};
		$$initScore{$scrw_w_n}=$score_weight{$scrwAutoNum-1}[1];
		if (scoreExists($$initScore{$scrw_w_n}, \$scrw_idCur) eq "false") {
			$scrwAutoNum--;
			$score_weight{$scrwAutoNum}[1]=$$initScore{$scrw_w_n};
			$scrw_idCur=$scrwAutoNum++;
		}
	} else {
		$append =1;
		$$initScore{$scrw_w_n}=~ s/'/''/g;
		$$initScore{$scrw_w_n}=~ s/\\/\\\\/g;
		if (scoreExists($$initScore{$scrw_w_n}, \$scrw_idCur) eq "false") {
			$score_weight{$scrwAutoNum}[1]=$$initScore{$scrw_w_n};
			$scrw_idCur=$scrwAutoNum++;
		}
	}
}

###################
# initObjAffType
###################

sub initObjAffType($) {
	my ($initObjAffType) = @_;
	if ($DEBUG) { print "got passed in $$initObjAffType{$affStatus}\n"; }
	if ($DEBUG) { print "adding aff type $$initObjAffType{$affStatus}\n"; }
	if ($append) {
		$$initObjAffType{$affStatus}=~ s/'/''/g;
		$$initObjAffType{$affStatus}=~ s/\\/\\\\/g;
		$object_affect_type{$obj_aff_typeAutoNum-1}.=$$initObjAffType{$affStatus};
		$$initObjAffType{$affStatus}=$object_affect_type{$obj_aff_typeAutoNum-1};
		if (objAffTypeExists($$initObjAffType{$affStatus}, \$obj_aff_type_idCur) eq "false") {
			$obj_aff_typeAutoNum--;
			$object_affect_type{$obj_aff_typeAutoNum}=$$initObjAffType{$affStatus};
			$obj_aff_type_idCur=$obj_aff_typeAutoNum++;
		}
	} else {
	        $append =1;
	        $$initObjAffType{$affStatus}=~ s/'/''/g;
		$$initObjAffType{$affStatus}=~ s/\\/\\\\/g;
		if (objAffTypeExists($$initObjAffType{$affStatus}, \$obj_aff_type_idCur) eq "false") {
			$object_affect_type{$obj_aff_typeAutoNum}=$$initObjAffType{$affStatus};
			$obj_aff_type_idCur=$obj_aff_typeAutoNum++;
		}
	}
}

###################
# initExtTxt
###################

sub initExtTxt($) {
	my ($initExtTxt) = @_;
		if ($DEBUG) { print "Adding lang with append $append : ".$$initExtTxt{$extt_lang}."\n"; }
	if ($append) {
		$$initExtTxt{$extt_lang}=~ s/'/''/g;
		$$initExtTxt{$extt_lang}=~ s/\\/\\\\/g;
		$language{$extt_langAutoNum-1}.=$$initExtTxt{$extt_lang};
		$$initExtTxt{$extt_lang}=$language{$extt_langAutoNum-1};
		if (langExists($$initExtTxt{$extt_lang}, \$extt_lang_idCur) eq "false") {
			$extt_langAutoNum--;
			$language{$extt_langAutoNum}=$$initExtTxt{$extt_lang};
			$extt_lang_idCur=$extt_langAutoNum++;
		}
	} else {
		$$initExtTxt{$extt_lang}=~ s/'/''/g;
		$$initExtTxt{$extt_lang}=~ s/\\/\\\\/g;
		if (langExists($$initExtTxt{$extt_lang}, \$extt_lang_idCur) eq "false") {
			$language{$extt_langAutoNum}=$$initExtTxt{$extt_lang};
			$extt_lang_idCur=$extt_langAutoNum++;
		}
	}
		if ($DEBUG) { print "Adding text type with append $append : ".$$initExtTxt{$extt_t_n}."\n"; }
	if ($append) {
		print "Decrement typeID $extt_tAutoNum\n";
		$$initExtTxt{$extt_t_n}=~ s/'/''/g;
		$$initExtTxt{$extt_t_n}=~ s/\\/\\\\/g;
		$ext_txt_types{$extt_tAutoNum-1}.=$$initExtTxt{$extt_t_n};
		$$initExtTxt{$extt_t_n}=$ext_txt_types{$extt_tAutoNum-1};
		if (extTxtTypeExists($$initExtTxt{$extt_t_n}, \$extt_t_idCur) eq "false") {
			$extt_tAutoNum--;
			$ext_txt_types{$extt_tAutoNum}=$$initExtTxt{$extt_t_n};
			$extt_t_idCur=$extt_tAutoNum++;
		}
	} else {
		$append=1;
		$$initExtTxt{$extt_t_n}=~ s/'/''/g;
		$$initExtTxt{$extt_t_n}=~ s/\\/\\\\/g;
		if (extTxtTypeExists($$initExtTxt{$extt_t_n}, \$extt_t_idCur) eq "false") {
			$ext_txt_types{$extt_tAutoNum}=$$initExtTxt{$extt_t_n};
			$extt_t_idCur=$extt_tAutoNum++;
		}
	}
		if ($DEBUG) { print "Increment typeID $extt_tAutoNum\n"; }
	$revisionCur=$$initExtTxt{$extt_rev};
}


###################
# initExtRef
###################

sub initExtRef($) {
	my ($initExtRef) = @_;
		if ($DEBUG) { print "Adding ext ref type with append $append : $$initExtRef{$type_name}\n"; }
	if ($append) {
		$$initExtRef{$type_name}=~ s/'/''/g;
		$$initExtRef{$type_name}=~ s/\\/\\\\/g;
		$ext_ref_types{$er_typeAutoNum-1}.=$$initExtRef{$type_name};
		$$initExtRef{$type_name}=$ext_ref_types{$er_typeAutoNum-1};
		if (refTypeExists($$initExtRef{$type_name}, \$er_type_idCur) eq "false") {
			$er_typeAutoNum--;
			$ext_ref_types{$er_typeAutoNum}=$$initExtRef{$type_name};
			$er_type_idCur=$er_typeAutoNum++;
		}
	} else {
		$$initExtRef{$type_name}=~ s/'/''/g;
		$$initExtRef{$type_name}=~ s/\\/\\\\/g;
		$append=1;
		if (refTypeExists($$initExtRef{$type_name}, \$er_type_idCur) eq "false") {
			$ext_ref_types{$er_typeAutoNum}=$$initExtRef{$type_name};
			$er_type_idCur=$er_typeAutoNum++;
		}
	}
	$indirectCur=$$initExtRef{$indirect};
}

###################
# initVuln
###################

sub initVuln($) { 
	my ($initVuln) = @_;
	# The epoch 1970-01-01 00:00:00
	$currentID=$$initVuln{$vid};
	$vulnerability{$currentID}[0]="";
	$vulnerability{$currentID}[1]="";
	$vulnerability{$currentID}[2]="";
	$vulnerability{$currentID}[3]="";
	$vulnerability{$currentID}[4]="";
	$vulnerability{$currentID}[5]="";
	for (my $i=6; $i<scalar(@{$vulnerability{$vid}}); $i++) {
		$vulnerability{$currentID}[$i]=0;
	}
	addVulnElement($ocreate, $$initVuln{$ocreate});
	addVulnElement($lastmod, $$initVuln{$lastmod});
}

###################
# setCurrentTag
###################

sub setCurrentTag($) {
	my ($tag) = @_;
	if ($tag=~/\"/) {
		my @tagParts=split(/\s+/, $tag);
		$tagParts[0]=~s/<//g;
		$currentTag=$tagParts[0];
	}
	else {
		$tag=~s/<|>//g;
		$currentTag=$tag;
	}
}

###################
# addObjectElement
###################

sub addObjectElement($$) {
	my ($type, $key) = @_;
	if ($type eq $obj_b_n) {
	if ($DEBUG) { print "$currentID: adding base with id $obj_bAutoNum $key\n"; }
		$append=1;
		$key=~ s/'/''/g;
		$key=~ s/\\/\\\\/g;
		if (objectExists(\%object_base, $key, \$obj_b_idCur) eq "false") {
			$object_base{$obj_bAutoNum}=$key;
			$obj_b_idCur=$obj_bAutoNum++;
		}
	}
	if ($type eq $obj_vnd_n) {
        if ($DEBUG) { print "$currentID: adding vnd with id $obj_vndAutoNum $key\n"; }
		$append=1;
		$key=~ s/'/''/g;
		$key=~ s/\\/\\\\/g;
		if (objectExists(\%object_vendor, $key, \$obj_vnd_idCur) eq "false") {
			$object_vendor{$obj_vndAutoNum}=$key;
			$obj_vnd_idCur=$obj_vndAutoNum++;
		}
	}
	if ($type eq $obj_ver_n) {
	if ($DEBUG) { print "$currentID: adding ver with id $obj_verAutoNum $key\n"; }
		$append =1;
		$key=~ s/'/''/g;
		$key=~ s/\\/\\\\/g;
		if (objectExists(\%object_version, $key, \$obj_ver_idCur) eq "false") {
			$object_version{$obj_verAutoNum}=$key;
			$obj_ver_idCur=$obj_verAutoNum++;
		}
	}
}

###################
# addExtRefElement
###################

sub addExtRefElement($) {
	my ($key) = @_;
	my $nextIndex=0;
	if ($DEBUG) { print "In add ext ref element with append of $append : $key\n"; }
	$nextIndex=scalar(@ext_ref_value);
	$key=~ s/'/''/g;
	$key=~ s/\\/\\\\/g;
	if (refValueExists(\@ext_ref_value, $key, \$er_value_idCur) eq "false") {
		if ($DEBUG) { print "$er_valueAutoNum :\n"; }
		$ext_ref_value[$nextIndex][0]=$currentID;
		$ext_ref_value[$nextIndex][1]=$key;
		$ext_ref_value[$nextIndex][2]=$er_valueAutoNum;
		$ext_ref_value[$nextIndex][3]=$er_type_idCur;
		$er_value_idCur=$er_valueAutoNum++;
	}
	if (refExists(\@ext_ref, \$er_value_idCur) eq "false") {
		if ($DEBUG) { print "INSIDE add ext ref with append $append : $key\n"; }
		$nextIndex=scalar(@ext_ref);
		$ext_ref[$nextIndex][0]=$currentID;
		$ext_ref[$nextIndex][1]=$er_value_idCur;
		$ext_ref[$nextIndex][2]=$indirectCur;
		$ext_ref[$nextIndex][3]=$erAutoNum++;
	}
	$append=1;
}

###################
# addExtTxtElement
###################

sub addExtTxtElement($) {
	my ($key) = @_;
	my $alreadyAssoc=0;
	if ($DEBUG) { print "In add ext txt with append $append : ".$key."\n"; }
	for(my $i=0; $i<scalar(@ext_txt); $i++) {
		if ($DEBUG) { print "Comparing $ext_txt[$i][0]==$currentID\n           
		    $ext_txt[$i][1] == $revisionCur\n           
		    ".$ext_txt[$i][2]." eq ".$key."\n
		    $ext_txt[$i][4] == $extt_t_idCur\n           
		    $ext_txt[$i][5] == $extt_lang_idCur\n"; }

		if ($ext_txt[$i][0]==$currentID &&
		    $ext_txt[$i][1]==$revisionCur &&
		    $ext_txt[$i][2] eq $key &&
		    $ext_txt[$i][4]==$extt_t_idCur &&
		    $ext_txt[$i][5]==$extt_lang_idCur) {
			if ($DEBUG) { print "Already exists\n"; }
			$alreadyAssoc=1;
			break;
		}
	}
	if ($alreadyAssoc==0) {
		my $nextIndex=scalar(@ext_txt);
		$key=~ s/'/''/g;
		$key=~ s/\\/\\\\/g;
		$ext_txt[$nextIndex][0]=$currentID;
		$ext_txt[$nextIndex][1]=$revisionCur;
		$ext_txt[$nextIndex][2]=$key;
		$ext_txt[$nextIndex][3]=$nextIndex;
		$ext_txt[$nextIndex][4]=$extt_t_idCur;
		$ext_txt[$nextIndex][5]=$extt_lang_idCur;
		$append=1;
		$extt_idCur=$nextIndex;
		if ($DEBUG) { print "adding \n"; }
	}
}

###################
# assocScores
###################

sub assocScores() {
	my $alreadyAssoc=0;
	for(my $i=0; $i<scalar(@score); $i++) {
		if ($score[$i][0]==$currentID &&
		    $score[$i][2]==$scrw_idCur) {
			if ($DEBUG) { print "Already exists $score[$i][0] $score[$i][2]\n"; }
			$alreadyAssoc=1;
			break;
		}
	}
	if ($alreadyAssoc==0) {
		my $nextIndex=scalar(@score);
		$score[$nextIndex][0]=$currentID;
		$score[$nextIndex][1]=$scrAutoNum++;
		$score[$nextIndex][2]=$scrw_idCur;
		if ($DEBUG) { print "adding $score[$nextIndex][0] $score[$nextIndex][1] $score[$nextIndex][2]\n"; }

	}
}

###################
# assocObjects
###################

sub assocObjects() {
	my $alreadyAssoc=0;
	for(my $i=0; $i<scalar(@object_corr); $i++) {
		if ($object_corr[$i][1] == $obj_b_idCur &&
		    $object_corr[$i][2] == $obj_vnd_idCur &&
		    $object_corr[$i][3] == $obj_ver_idCur) {
		    if ($DEBUG) { print "$i: Already exists baseId $object_corr[$i][1] vndId $object_corr[$i][2] verId $object_corr[$i][3] \n"; }
			$alreadyAssoc=1;
			$obj_corr_idCur=$i;
			break;
		}   
	}
	if ($alreadyAssoc==0) {
		my $nextIndex=scalar(@object_corr);
		$object_corr[$nextIndex][0]=$obj_corrAutoNum;
		$object_corr[$nextIndex][1]=$obj_b_idCur;
		$object_corr[$nextIndex][2]=$obj_vnd_idCur;
		$object_corr[$nextIndex][3]=$obj_ver_idCur;
	 	if ($DEBUG) { print "adding corrId $object_corr[$nextIndex][0] baseId $object_corr[$nextIndex][1] vndId $object_corr[$nextIndex][2] verId $object_corr[$nextIndex][3]\n"; }
		$obj_corr_idCur=$obj_corrAutoNum++;
	}
	$alreadyAssoc=0;
	for(my $i=0; $i<scalar(@object); $i++) {
		if ($object[$i][0] == $currentID &&
		    $object[$i][2] == $obj_corr_idCur) {
		    	if ($DEBUG) { print "osvdbId $currentID already assoc with $obj_corr_idCur\n"; }
			$alreadyAssoc=1;
			break;
		}
	}
	if ($alreadyAssoc==0) {
		my $nextIndex=scalar(@object);
		$object[$nextIndex][0]=$currentID;
		$object[$nextIndex][1]=$objAutoNum++;
		$object[$nextIndex][2]=$obj_corr_idCur;
		$object[$nextIndex][3]=$obj_aff_type_idCur;
		if ($DEBUG) { print "adding vid $object[$nextIndex][0] objId $object[$nextIndex][1] corrId $object[$nextIndex][2] affId $object[$nextIndex][3]\n"; }
	}	
}

###################
# objectExists
###################

sub objectExists($$$) {
	my ($table, $key, $curKey) = @_;
	if ($DEBUG) { print "In objectExists with $key\n"; }
	foreach my $tKey (keys %$table){
		if ($DEBUG) { print "Comparing $$table{$tKey} eq $key\n"; }
		if (lc($$table{$tKey}) eq lc($key)) {
			if ($DEBUG) { print "Already exists, changing cur key to $tKey\n"; }
			$$curKey=$tKey;
			return "true";
		}
	}
	return "false";
}

###################
# refExists
###################

sub refExists($$) {
	my ($table, $key) = @_;
	if ($DEBUG) { print "In refExists with $table and $$key\n"; }
	for(my $tKey=0; $tKey<scalar(@$table); $tKey++){
		if ($DEBUG) { print "Comparing $$table[$tKey][0] eq $currentID\n\t$$table[$tKey][1] eq $$key\n\t$$table[$tKey][2] eq $indirectCur\n"; }
		if ($$table[$tKey][0] == $currentID &&
		    $$table[$tKey][1] eq $$key &&
		    $$table[$tKey][2] eq $indirectCur) {
			if ($DEBUG) { print "FOUND MATCH\n"; }
			return "true";
		}
	}
	return "false";
}

###################
# refValueExists
###################

sub refValueExists($$$) {
	my ($table, $key, $curKey) = @_;
#	print "In refValueExists with $table and $key\n";
	for(my $tKey=0; $tKey<scalar(@$table); $tKey++){
		if ($$table[$tKey][1] eq $key &&
		    $$table[$tKey][3] == $er_type_idCur) {
			$$curKey=$tKey;
			return "true";
		}
	}
	return "false";
}

###################
# addVulnElement
###################

sub addVulnElement($$) {
	my ($type, $key) = @_;
#	print "In add vuln element with $type and $key\n";
	if ($append) {
		$key=~ s/'/''/g;
		$key=~ s/\\/\\\\/g;
		$vulnerability{$currentID}[findVulnIndex($type)].=$key;
	} else {
		if ($type eq $title || $type eq $dscl || $type eq $dsc || $type eq $ocreate || $type eq $lastmod || $type eq $explt_publish_date) {
			$append=1;
		}
		$key=~ s/'/''/g;
		$key=~ s/\\/\\\\/g;
		$vulnerability{$currentID}[findVulnIndex($type)]=$key;

	}

}

###################
# assocCredit
###################

sub assocCredit() {
	my $alreadyAssoc=0;
	for(my $i=0; $i<scalar(@credit); $i++) {
		if ($credit[$i][0]==$currentID &&
		    $credit[$i][1]==$auth_idCur) {
			$crd_idCur=$i;
			if ($DEBUG) { print "Already exists $credit[$i][0] $credit[$i][1] $credit[$i][2]\n"; }
			$alreadyAssoc=1;
			break;
		}   
	}
	if ($alreadyAssoc==0) {
		my $nextIndex=scalar(@credit);
		$crd_idCur=$nextIndex;
		$credit[$nextIndex][0]=$currentID;
		$credit[$nextIndex][1]=$auth_idCur;
		$credit[$nextIndex][2]=$crdAutoNum++;
		if ($DEBUG) { print "$currentID Credits adding $credit[$nextIndex][0] $credit[$nextIndex][2]\n"; }

	}
}

###################
# addAuthorElement
###################

sub addAuthorElement($$) {
	my ($type, $key) = @_;
	$key=~ s/'/''/g;
	$key=~ s/\\/\\\\/g;
	if ($DEBUG) { print "In add author element with $type and $key\n"; }
	if ($type eq $auth_n) {
		if ($DEBUG) { print "adding $key\n"; }
		$append =1;
		$author{$authAutoNum}[0]=$key;
		$auth_idCur=$authAutoNum;
	}
	if ($type eq $auth_c) {
		if ($DEBUG) { print "adding $key\n"; }
		$append=1;
		$author{$authAutoNum}[1]=$key;
		$auth_idCur=$authAutoNum;
	}
	if ($type eq $auth_e) {
		if ($DEBUG) { print "adding $key\n"; }
		$append=1;
		$author{$authAutoNum}[2]=$key;
		$auth_idCur=$authAutoNum;
	}
	if ($type eq $auth_u) {
		if ($DEBUG) { print "adding $key\n"; }
		$append=1;
		$author{$authAutoNum}[3]=$key;
		$auth_idCur=$authAutoNum;
	}
}

###################
# authorExists
###################

sub authorExists() {
	if ($DEBUG) { print "In authorExists\n"; }
	$authAutoNum++;
	foreach my $aKey (keys %author ){
		if ($DEBUG) { print "comparing $aKey and $auth_idCur\n"; }
		if ($aKey ne $auth_idCur) {
			if ($DEBUG) { print "Comparing $author{$aKey}[0] eq $author{$auth_idCur}[0]\n 
			   $author{$aKey}[1] eq $author{$auth_idCur}[1]\n         
			   $author{$aKey}[2] eq $author{$auth_idCur}[2]\n         
			   $author{$aKey}[3] eq $author{$auth_idCur}[3]\n"; }
			
			if (lc($author{$aKey}[0]) eq lc($author{$auth_idCur}[0]) &&
		    	    lc($author{$aKey}[2]) eq lc($author{$auth_idCur}[2])) {
			    if ($DEBUG) { print "\nFOUND MATCH\n"; }
				delete $author{$auth_idCur};
				$authAutoNum--;
				$auth_idCur=$aKey;
				return 1;
			}
		}
	}
	return 0;
}

###################
# addScoreElement
###################

sub addScoreElement($) {
	my ($key) = @_;
	$key=~ s/'/''/g;
	$key=~ s/\\/\\\\/g;
	if ($DEBUG) { print "In add score element with $key\n"; }
	$append=1;
	$score_weight{$scrw_idCur}[0]=$key;

}

###################
# FindVulnIndex
###################

sub findVulnIndex($) { 
	my ($key) = @_;
	if ($DEBUG) { print "in find vuln index with $key and currentKey $currentID\n"; }
	for (my $i=0; $i<scalar(@{$vulnerability{$vid}}); $i++) {
		if ($DEBUG) { print "comparing $vulnerability{$vid}[$i] and $key\n"; }
		if (lc($vulnerability{$vid}[$i]) eq lc($key)) {
			if ($DEBUG) { print "$key found at index $i\n"; }
			return $i;
		}
	}
}


###################
# scoreExists
###################

sub scoreExists($$) {
	my ($key, $curKey) = @_;
	if ($DEBUG) { print "in find score index with $key and currentKey $currentID\n"; }
	foreach my $sKey (keys %score_weight) {
		if ($DEBUG) { print "comparing $score_weight{$sKey}[1] and $key\n"; }
		if ($score_weight{$sKey}[0]==$currentID &&
		    $score_weight{$sKey}[1] eq $key) {
		    if ($DEBUG) { print "$key found at index $sKey\n"; }
			$$curKey=$sKey;
			return "true";
		}
	}
	return "false";
}


###################
# objAffTypeExists
###################

sub objAffTypeExists($$) {
	my ($key, $curKey) = @_;
	 if ($DEBUG) { print "in find objAffType index with $key and currentKey $currentID\n"; }
	foreach my $sKey (keys %object_affect_type) {
		if ($DEBUG) { print "comparing $object_affect_type{$sKey} and $key\n"; }
		if (lc($object_affect_type{$sKey}) eq lc($key)) {
			if ($DEBUG) { print "$key found at index $sKey with object aff type of $key\n"; }
			$$curKey=$sKey;
			return "true";
		}
	}
	return "false";
}


###################
# extTxtTypeExists
###################

sub extTxtTypeExists($$) {
	my ($key, $curKey) = @_;
	if ($DEBUG) { print "in find txt type index with $key and currentKey $currentID\n"; }
	foreach my $tKey (keys %ext_txt_types) {
		if ($DEBUG) { print "comparing $ext_txt_types{$tKey} and $key\n"; }
		if (lc($ext_txt_types{$tKey}) eq lc($key)) {
			if ($DEBUG) { print "$key found at index $tKey with val of $key\n"; }
			$$curKey=$tKey;
			return "true";
		}
	}
	return "false";
}


###################
# langExists
###################

sub langExists($$) {
	my ($key, $curKey) = @_;
	if ($DEBUG) { print "in find lang index with $key and currentKey $currentID\n"; }
	foreach my $lKey (keys %language) {
		if ($DEBUG) { print "comparing $language{$lKey} and $key\n"; }
		if (lc($language{$lKey}) eq lc($key)) {
			if ($DEBUG) { print "$key found at index $lKey\n"; }
			$$curKey=$lKey;
			return "true";
		}
	}
	return "false";
}

###################
# refTypeExists
###################

sub refTypeExists($$) {
	my ($key, $curKey) = @_;
	if ($DEBUG) { print "in find refType index with $key and currentKey $currentID\n"; }
	foreach my $rKey (keys %ext_ref_types) {
		if ($DEBUG) { print "comparing $ext_ref_types{$rKey} and $key\n"; }
		if (lc($ext_ref_types{$rKey}) eq lc($key)) {
			if ($DEBUG) { print "$key found at index $rKey\n"; }
			$$curKey=$rKey;
			return "true";
		}
	}
	return "false";
}

###################
# StartTag
###################

sub startTag() {
	my $p= shift;
    	my $string= $p->recognized_string();
    	setTypeArray($string);
}

###################
# EndTag
###################

sub endTag() {
	my $p= shift;
    	my $string= $p->recognized_string();
	finishAssocTables($string);
}

###################
# foundChar
###################

sub foundChar() {
	my $p= shift;
    	my $string= $p->recognized_string();
	if ($string=~/\w+/) {
		$compiledString.=$string;
	}
}

###################
# default
###################

sub default() {
	my $p= shift;
    	my $string= $p->recognized_string();
#    	print "I found this default ".$string."\n";
}

###################
# insertExtRefTypes
###################

sub insertExtRefTypes() {
	syswrite STDOUT, "\nInsert Ext Ref Types";
	my $key;
	my $sql;
	my $res;
	calculateScale($er_typeAutoNum);
  	for(my $key=1; $key<$er_typeAutoNum; $key++) {
		status();
		$sql = "INSERT INTO $er_type ($type_name) values (?)";
		$res = $dbh->prepare($sql);
		$res->execute(
					decode_entities(decode_entities(decode("utf8", $ext_ref_types{$key})))
								 ) or die ("Error: $DBI::errstr\n$sql\n");
		$res->finish();
	}
	syswrite STDOUT, $finishString;
}

###################
# insertScoreWeight
###################

sub insertScoreWeight() {
	syswrite STDOUT, "\nInsert Score Weight";
	my $key;
	my $sql;
	my $res;
	calculateScale($scrw_wAutoNum);
  	for(my $key=1; $key<$scrw_wAutoNum; $key++) {
		status();
		$sql = "INSERT INTO $scrw ($scrw_w_n,$scrw_w) values (?, ?)";
		$res = $dbh->prepare($sql);
		$res->execute(
			decode_entities(decode_entities(decode("utf8", $score_weight{$key}[1]))),
			decode_entities(decode_entities(decode("utf8", $score_weight{$key}[0])))
								 ) or die ("Error: $DBI::errstr\n$sql\n");
		$res->finish();
	}
	syswrite STDOUT, $finishString;
}

###################
# insertAuthor
###################

sub insertAuthor() {
	syswrite STDOUT, "\nInsert Author";
	my $key;
	my $sql;
	my $res;
	calculateScale($authAutoNum);
  	for(my $key=1; $key<$authAutoNum; $key++) {
		status();
		$sql = "INSERT INTO $auth ($auth_n,$auth_c,$auth_e,$auth_u)  
            values (?, ?, ?, ?)";
		if ($DEBUG) { print "$sql\n"; }
		$res = $dbh->prepare($sql);
		$res->execute(
						decode_entities(decode_entities(decode("utf8", $author{$key}[0]))),
						decode_entities(decode_entities(decode("utf8", $author{$key}[1]))),
						decode_entities(decode_entities(decode("utf8", $author{$key}[2]))),
						decode_entities(decode_entities(decode("utf8", $author{$key}[3])))
		             ) or die ("Error: $DBI::errstr\n$sql\n");
		$res->finish();
	}
	syswrite STDOUT, $finishString;
}

###################
# insertExtTxtTypes
###################

sub insertExtTxtTypes() {
	syswrite STDOUT, "\nInsert Ext Txt Types";
	my $key;
	my $sql;
	my $res;
	calculateScale($extt_tAutoNum);
  	for(my $key=1; $key<$extt_tAutoNum; $key++) {
		status();
		$sql = "INSERT INTO $extt_t ($extt_t_n) values (?)";
		$res = $dbh->prepare($sql);
		$res->execute(
					decode_entities(decode_entities(decode("utf8", $ext_txt_types{$key})))
					       ) or die ("Error: $DBI::errstr\n$sql\n");
		$res->finish();
	}
	syswrite STDOUT, $finishString;
}

###################
# insertLanguage
###################

sub insertLanguage() {
	syswrite STDOUT, "\nInsert Language";
	my $key;
	my $sql;
	my $res;
	calculateScale($extt_langAutoNum);
  	for(my $key=1; $key<$extt_langAutoNum; $key++) {
		status();
		$sql = "INSERT INTO $extt_lang ($extt_lang_n) values (?)";
		$res = $dbh->prepare($sql);
		$res->execute(
							decode_entities(decode_entities(decode("utf8", $language{$key})))
								 ) or die ("Error: $DBI::errstr\n$sql\n");
		$res->finish();
	}
	syswrite STDOUT, $finishString;
}

###################
# insertVuln
###################

sub insertVuln() {
	syswrite STDOUT, "\nInsert Vuln";
	my $key;
	my $sql;
	my $res;
	calculateScale($max);
  	for(my $key=1; $key<=$currentID; $key++) {
	    if ($DEBUG) { print "Checking $key\n"; }
	    if (defined($vulnerability{$key}[0]) && $vulnerability{$key}[0] ne $vln) {
            	status();
		if ($DEBUG) { print "Inserting $key $vulnerability{$key}[0]\n"; }
		$sql = "INSERT INTO $vln ($vid,
				  $title,
				  $dscl,
				  $dsc,
				  $ocreate,
				  $lastmod,
				  $explt_publish_date,
				  $lcnp,
				  $lcnl,
				  $lcnr,
				  $lcnd,
				  $lcnu,
				  $atype_auth_manage,
				  $atype_crypt,
				  $atype_dos,
				  $atype_hijack,
				  $atype_info_disclose,
				  $atype_infrastruct,
				  $atype_input_manip,
				  $atype_miss_config,
				  $atype_race,
				  $atype_other,
				  $atype_unknown,
				  $iconfidential,
				  $iintegrity,
				  $iavailable,
				  $iunknown,
				  $eavailable,
				  $eunavailable,
				  $erumored,
				  $eunknown,
				  $vverified,
				  $vmyth_fake,
				  $vbest_prac,
				  $vconcern,
				  $vweb_check) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
															 ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
															 ?, ?, ?, ?)";
		$res = $dbh->prepare($sql);
		$res->execute($key, 
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[0]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[1]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[2]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[3]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[4]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[5]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[6]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[7]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[8]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[9]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[10]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[11]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[12]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[13]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[14]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[15]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[16]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[17]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[18]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[19]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[20]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[21]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[22]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[23]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[24]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[25]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[26]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[27]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[28]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[29]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[30]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[31]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[32]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[33]))),
		 decode_entities(decode_entities(decode("utf8", $vulnerability{$key}[34])))
		) or die ("Error: $DBI::errstr\n$sql\n");
		$res->finish();
	    }
	}
	syswrite STDOUT, $finishString;
}

###################
# insertExtTxt
###################

sub insertExtTxt() {
	syswrite STDOUT, "\nInsert Ext Txt";
	my $key;
	my $sql;
	my $res;
	my $text;
	calculateScale(scalar(@ext_txt));
  	for(my $key=1; $key<scalar(@ext_txt); $key++) {
		if ($DEBUG) { print "$ext_txt[$key][2]\n"; }
		status();
		$text=decode_entities(decode_entities(decode("utf8", $ext_txt[$key][2])));
		$text=~s/\&#xA;/\\n/g;
		$sql = "INSERT INTO $extt ($vid,
		     			$extt_rev,
		     			$extt_txt,
		     			$extt_t_id,
		     			$extt_lang_id,
		     			$auth_id ) values (?, ?, ?, ?, ?, ?)";
		$res = $dbh->prepare($sql);
		$res->execute(
           decode_entities(decode_entities(decode("utf8", $ext_txt[$key][0]))),
					 decode_entities(decode_entities(decode("utf8", $ext_txt[$key][1]))),
					 $text,
					 decode_entities(decode_entities(decode("utf8", $ext_txt[$key][4]))),
					 decode_entities(decode_entities(decode("utf8", $ext_txt[$key][5]))),
					 decode_entities(decode_entities(decode("utf8", $ext_txt[$key][6])))
								) or die ("Error: $DBI::errstr\n$sql\n");
		$res->finish();
	}
	syswrite STDOUT, $finishString;
}

###################
# insertExtRef
###################

sub insertExtRef() {
	syswrite STDOUT, "\nInsert Ext Ref";
	my $key;
	my $sql;
	my $res;
	calculateScale(scalar(@ext_ref));
	if ($DEBUG) { print "Inserting in ext ref for key $key to ".scalar(@ext_ref)."\n"; }
  	for(my $key=1; $key<scalar(@ext_ref); $key++) {
		status();
		$sql = "INSERT INTO $er ($vid,
		    			$er_value_id,
		    			$indirect) values (?, ?, ?)";
		$res = $dbh->prepare($sql);
		$res->execute(
						decode_entities(decode_entities(decode("utf8", $ext_ref[$key][0]))),
						decode_entities(decode_entities(decode("utf8", $ext_ref[$key][1]))),
						decode_entities(decode_entities(decode("utf8", $ext_ref[$key][2])))
								 ) or die ("Error: $DBI::errstr\n$sql\n");
		$res->finish();
	}
	syswrite STDOUT, $finishString;
}

###################
# insertObjectBase
###################

sub insertObjectBase() {
	syswrite STDOUT, "\nInsert Object Base";
	my $key;
	my $sql;
	my $res;
	calculateScale($obj_bAutoNum);
  	for(my $key=1; $key<$obj_bAutoNum; $key++) {
		status();
		$sql = "INSERT INTO $obj_b ($obj_b_n, $obj_b_id) values (?, ?)";
		$res = $dbh->prepare($sql);
		$res->execute(
					decode_entities(decode_entities(decode("utf8", $object_base{$key}))), 
					$key) or die ("Error: $DBI::errstr\n$sql\n");
		$res->finish();
	}
	syswrite STDOUT, $finishString;
}

###################
# insertObjectVendor
###################

sub insertObjectVendor() {
	syswrite STDOUT, "\nInsert Object Vendor";
	my $key;
	my $sql;
	my $res;
	calculateScale($obj_vndAutoNum);
  	for(my $key=1; $key<$obj_vndAutoNum; $key++) {
		status();
		$sql = "INSERT INTO $obj_vnd ($obj_vnd_n) values (?)";
		$res = $dbh->prepare($sql);
		$res->execute(
					decode_entities(decode_entities(decode("utf8", $object_vendor{$key})))
								 ) or die ("Error: $DBI::errstr\n$sql\n");
		$res->finish();
	}
	syswrite STDOUT, $finishString;
}

###################
# insertObjectVersion
###################

sub insertObjectVersion() {
	syswrite STDOUT, "\nInsert Object Version";
	my $key;
	my $sql;
	my $res;
	calculateScale($obj_verAutoNum);
  	for(my $key=1; $key<$obj_verAutoNum; $key++) {
		status();
		$sql = "INSERT INTO $obj_ver ($obj_ver_n) values (?)";
		$res = $dbh->prepare($sql);
		$res->execute(
				 decode_entities(decode_entities(decode("utf8", $object_version{$key})))
								 ) or die ("Error: $DBI::errstr\n$sql\n");
		$res->finish();
	}
	syswrite STDOUT, $finishString;
}

###################
# insertObjectAffectType
###################

sub insertObjectAffectType() {
	syswrite STDOUT, "\nInsert Object Affect Type";
	my $key;
	my $sql;
	my $res;
	calculateScale($obj_affAutoNum);
	if ($DEBUG) { print "The autonum size is $obj_aff_typeAutoNum\n"; }
	for(my $key=1; $key<$obj_aff_typeAutoNum; $key++) {
		status();
		 $sql = "INSERT INTO $obj_aff ($obj_aff_type_name) values (?)";
		 if ($DEBUG) { print "$sql\n"; }
		 $res = $dbh->prepare($sql);
		 $res->execute(
		 decode_entities(decode_entities(decode("utf8", $object_affect_type{$key})))
								  ) or die ("Error: $DBI::errstr\n$sql\n");
		 $res->finish();
	}
	syswrite STDOUT, $finishString;	
}

###################
# insertScore
###################

sub insertScore() {
	syswrite STDOUT, "\nInsert Score";
	my $key;
	my $sql;
	my $res;
	calculateScale(scalar(@score));
  	for(my $key=1; $key<scalar(@score); $key++) {
		status();
		$sql = "INSERT INTO $scr ($vid, $scrw_id) values (?, ?)";
		$res = $dbh->prepare($sql);
		$res->execute(
							decode_entities(decode_entities(decode("utf8", $score[$key][0]))),
							decode_entities(decode_entities(decode("utf8", $score[$key][2])))
								 ) or die ("Error: $DBI::errstr\n$sql\n");
		$res->finish();
	}
	syswrite STDOUT, $finishString;
}

###################
# insertCredit
###################

sub insertCredit() {
	syswrite STDOUT, "\nInsert Credit";
	my $key;
	my $sql;
	my $res;
	calculateScale(scalar(@credit));
  	for(my $key=1; $key<scalar(@credit); $key++) {
		status();
		$sql = "INSERT INTO $crd ($vid, $auth_id) values (?, ?)";
		$res = $dbh->prepare($sql);
		$res->execute(
		   			decode_entities(decode_entities(decode("utf8", $credit[$key][0]))),
						decode_entities(decode_entities(decode("utf8", $credit[$key][1])))
								 ) or die ("Error: $DBI::errstr\n$sql\n");
		$res->finish();
	}
	syswrite STDOUT, $finishString;
}

###################
# insertExtRefValue
###################

sub insertExtRefValue() {
	syswrite STDOUT, "\nInsert Ext Ref Value";
	my $key;
	my $sql;
	my $res;
	my $text;
	calculateScale(scalar(@ext_ref_value));
  	for(my $key=1; $key<scalar(@ext_ref_value); $key++) {
		status();
		$text=decode_entities(decode_entities(decode("utf8", $ext_ref_value[$key][1])));		
		$text=~s/\&amp;amp;\#xA;/\&\#xA;/g;
		$sql = "INSERT INTO $er_value ($ref_value, $er_value_id,
			  		$er_type_id) values (?, ?, ?)";
		if ($DEBUG) { print "$sql\n"; }
		$res = $dbh->prepare($sql);
		$res->execute($text,
			decode_entities(decode_entities(decode("utf8", $ext_ref_value[$key][2]))),
			decode_entities(decode_entities(decode("utf8", $ext_ref_value[$key][3])))
								 ) or die ("Error: $DBI::errstr\n$sql\n");
		$res->finish();
	}
	syswrite STDOUT, $finishString;
}

###################
# insertObjectCorr
###################

sub insertObjectCorr() {
	syswrite STDOUT, "\nInsert Object Correlation";
	my $key;
	my $sql;
	my $res;
	calculateScale(scalar(@object_corr));
  	for(my $key=1; $key<scalar(@object_corr); $key++) {
		status();
		$sql = "INSERT INTO $obj_corr ($obj_b_id,
		    			$obj_vnd_id, $obj_ver_id) values (?, ?, ?)";
		$res = $dbh->prepare($sql);
		$res->execute(
				decode_entities(decode_entities(decode("utf8", $object_corr[$key][1]))),
				decode_entities(decode_entities(decode("utf8", $object_corr[$key][2]))),
				decode_entities(decode_entities(decode("utf8", $object_corr[$key][3])))
								 ) or die ("Error: $DBI::errstr\n$sql\n");
		$res->finish();
	}
	syswrite STDOUT, $finishString;
}

###################
# insertObject
###################

sub insertObject() {
	syswrite STDOUT, "\nInsert Object";
	my $key;
	my $sql;
	my $res;
	calculateScale(scalar(@object));
  	for(my $key=1; $key<scalar(@object); $key++) {
		status();
		$sql = "INSERT INTO $obj ($vid,
		    			$obj_corr_id, $obj_aff_type_id) values (?, ?, ?)";
		$res = $dbh->prepare($sql);
		$res->execute(
						decode_entities(decode_entities(decode("utf8", $object[$key][0]))),
						decode_entities(decode_entities(decode("utf8", $object[$key][2]))),
						decode_entities(decode_entities(decode("utf8", $object[$key][3])))
								 ) or die ("Error: $DBI::errstr\n$sql\n");
		$res->finish();
	}
	syswrite STDOUT, $finishString;
}

##################
# Usage
##################

sub usage() {
	print "Usage: perl xmldbInput.pl [-d database_name] [-u user_name] [-p password]\n                          [-l location] [-t database_type] XML_File\n";
	print "\t-d [osvdb] Specify the database to instert xml dump into.\n";
	print "\t-u [osvdb] Specify the user to connect to database with.\n";
	print "\t-p [] Specify the password for supplied user.\n";
	print "\t-l [localhost] Specify the location of the database.\n";
	print "\t-t [1] Specify the type of database.  Currently only postgresql and mysql are supported.  \n\t\t1 => postgresql database\n\t\t2 => mysql\n";
	print "\tXML_FILE Specify the xml dump file to import.\n\n";
	exit(0);
}

##################
# process Args
##################

sub processArgs() {
	my $valid;
	for(my $i=0; $i<(scalar(@ARGV)-1); $i+=2) {
		$valid=0;
		if ($ARGV[$i] eq "-d") {
			$newDB=$ARGV[$i+1];
			$valid=1;
		} 
		if ($ARGV[$i] eq "-u") {
			$newUsr=$ARGV[$i+1];
			$valid=1;
		}
		if ($ARGV[$i] eq "-p") {
			$newPwd=$ARGV[$i+1];
			$valid=1;
		}
		if ($ARGV[$i] eq "-l") {
			$newDBLoc=$ARGV[$i+1];
			$valid=1;
		}
		if ($ARGV[$i] eq "-t") {
			if (!($newDBType=~/\d/)) {
				usage();
			}
			$newDBType=$ARGV[$i+1];
			$valid=1;
		}
		if ($ARGV[$i] eq "-h" || $ARGV[$i] eq "--help" || $valid ==0) {
			usage();
		}
		
	}
	if ($ARGV[scalar(@ARGV)-1]eq "-h" || $ARGV[scalar(@ARGV)-1] eq "--help") {
		usage();
	}
	if ($DEBUG) { print $ARGV[scalar(@ARGV)-1]; }
	$xmlFile=$ARGV[scalar(@ARGV)-1];
}

##################
# calculateScale
##################

sub calculateScale($) {
	my ($val)=@_;
	$progress=1;
	$scale=int($val/30);
	$finishString="";
	if ($scale<1) {
		$scale=1;
		for (my $i=0; $i<(30-$val); $i++) {
			$finishString.=".";
		}
	}
	$finishString.="done";
}

##################
# Debug Tables
##################
 
 ##################
 # Debug Object Base
 ##################
 
 sub debugObjectBase() {
	print "Debug Object Base\n";
	my $key;
  	foreach $key (keys %object_base) {
		print "\tOBseId $key => $object_base{$key}\n";
	}
 }

 ##################
 # Debug Object Vendor
 ##################

 sub debugObjectVendor() {
	print "Debug Object Vendor\n";
	my $key;
  	foreach $key (keys %object_vendor) {
		print "\tOVndId $key => $object_vendor{$key}\n";
	}
 }

 ##################
 # Debug Object Version
 ##################

 sub debugObjectVersion() {
	print "Debug Object Version\n";
	my $key;
  	foreach $key (keys %object_version) {
		print "\tOVsnId $key => $object_version{$key}\n";
	}
 }

 ##################
 # Debug Language
 ##################

 sub debugLanguage() {
	print "Debug Language\n";
	my $key;
  	foreach $key (keys %language) {
		print "\tLangId $key => $language{$key}\n";
	}
 }

 ##################
 # Debug External Text Types
 ##################

 sub debugExtTxtTypes() {
	print "Debug Ext Txt Types\n";
	my $key;
  	foreach $key (keys %ext_txt_types) {
		print "\tExtTxtTId $key => $ext_txt_types{$key}\n";
	}
 }

 ##################
 # Debug External Ref Types
 ##################

 sub debugExtRefTypes() {
	print "Debug Ext Ref Types\n";
	my $key;
  	foreach $key (keys %ext_ref_types) {
		print "\tExtRTId $key => $ext_ref_types{$key}\n";
	}
 }

 ##################
 # Debug Object
 ##################

 sub debugObject() {
	print "Debug Object\n";
	my $key;
	for($key=0; $key < scalar(@object); $key++) { 
		print "VId $object[$key][0] ObjId $object[$key][1] ObseId $object[$key][2] OVndId $object[$key][3] OVsnId $object[$key][4]\n";
	}
 }


 ##################
 # Debug External Text
 ##################

 sub debugExtTxt() {
	print "Debug External Text\n";
	my $key;
	for($key=0; $key < scalar(@ext_txt); $key++) { 
		print "VId $ext_txt[$key][0] ExttId $ext_txt[$key][3] Rev $ext_txt[$key][1] TypeId $ext_txt[$key][4] LangId $ext_txt[$key][5] AuthId $ext_txt[$key][6]\n\t$ext_txt[$key][2]\n";
	}
 }

 ##################
 # Debug Credit
 ##################

 sub debugCredit() {
	print "Debug Credit\n";
	my $key;
	for($key=0; $key < scalar(@credit); $key++) { 
		print "VId $credit[$key][0] AuthId $credit[$key][1] CrdId $credit[$key][2]\n";
	}
 }

 ##################
 # Debug Score
 ##################

 sub debugScore() {
	print "Debug Score\n";
	my $key;
	for($key=0; $key < scalar(@score); $key++) { 
		print "VId $score[$key][0] ScrId $score[$key][1] ScrWId $score[$key][2]\n";
	}
 }

 ##################
 # Debug Ext Ref Value
 ##################

 sub debugExtRefValue() {
	print "Debug Ext Ref Value\n";
	my $key;
	for($key=0; $key < scalar(@ext_ref_value); $key++) { 
		print "VId $ext_ref_value[$key][0]\nValId $ext_ref_value[$key][2]\nTypId $ext_ref_value[$key][3]\n\t$ext_ref_value[$key][1]\n";
	}
 }

 ##################
 # Debug Ext Ref
 ##################

 sub debugExtRef() {
	print "Debug Ext Ref\n";
	my $key;
	for($key=0; $key < scalar(@ext_ref); $key++) { 
		print "VId $ext_ref[$key][0] ErId $ext_ref[$key][3] ValId $ext_ref[$key][1] Indirect $ext_ref[$key][2]\n";
	}
 }

 ##################
 # Debug Author
 ##################
		    
 sub debugAuthor() {
	print "Debug Author\n";
	my $key;
	foreach $key (keys %author) {
		print "$key => $author{$key}[0], $author{$key}[1], $author{$key}[2], $author{$key}[3]\n";
	}
 }

 ##################
 # Debug Vulnerability
 ##################

 sub debugVulnerability() {
	print "Debug Vulnerability\n";
	my $key;
  	foreach $key (keys %vulnerability) {			   
  		print "Vid $key =>\n\t\t";
		print "$title $vulnerability{$key}[0]\n\t\t";
		print "$dscl $vulnerability{$key}[1]\n\t\t";
		print "$dsc $vulnerability{$key}[2]\n\t\t";
		print "$ocreate $vulnerability{$key}[3]\n\t\t";
		print "$lastmod $vulnerability{$key}[4]\n\t\t";
		print "$explt_publish_date $vulnerability{$key}[5]\n\t\t";
		print "$lcnp $vulnerability{$key}[6]\n\t\t";
		print "$lcnl $vulnerability{$key}[7]\n\t\t";
		print "$lcnr $vulnerability{$key}[8]\n\t\t";
		print "$lcnd $vulnerability{$key}[9]\n\t\t";
		print "$lcnu $vulnerability{$key}[10]\n\t\t";
		print "$atype_auth_manage $vulnerability{$key}[11]\n\t\t";
		print "$atype_crypt $vulnerability{$key}[12]\n\t\t";
		print "$atype_dos $vulnerability{$key}[13]\n\t\t";
		print "$atype_hijack $vulnerability{$key}[14]\n\t\t";
		print "$atype_info_disclose $vulnerability{$key}[15]\n\t\t";
		print "$atype_infrastruct $vulnerability{$key}[16]\n\t\t";
		print "$atype_input_manip $vulnerability{$key}[17]\n\t\t";
		print "$atype_miss_config $vulnerability{$key}[18]\n\t\t";
		print "$atype_race $vulnerability{$key}[19]\n\t\t";
		print "$atype_other $vulnerability{$key}[20]\n\t\t";
		print "$atype_unknown $vulnerability{$key}[21]\n\t\t";
		print "$iconfidential $vulnerability{$key}[22]\n\t\t";
		print "$iintegrity $vulnerability{$key}[23]\n\t\t";
		print "$iavailable $vulnerability{$key}[24]\n\t\t";
		print "$iunknown $vulnerability{$key}[25]\n\t\t";
		print "$eavailable $vulnerability{$key}[26]\n\t\t";
		print "$eunavailable $vulnerability{$key}[27]\n\t\t";
		print "$erumored $vulnerability{$key}[28]\n\t\t";
		print "$eunknown $vulnerability{$key}[29]\n\t\t";
		print "$vverified $vulnerability{$key}[30]\n\t\t";
		print "$vmyth_fake $vulnerability{$key}[31]\n\t\t";
		print "$vbest_prac $vulnerability{$key}[32]\n\t\t";
		print "$vconcern $vulnerability{$key}[33]\n\t\t";
		print "$vweb_check $vulnerability{$key}[34]\n";
	}
 }

 ##################
 # Debug Score Weight
 ##################

 sub debugScoreWeight() {
	print "Debug Score Weight\n";
	my $key;
  	foreach $key (keys %score_weight) {
		print "\tScrWId $key => SrcWW $score_weight{$key}[0] SrcWN $score_weight{$key}[1]\n";
	}
 }

 ###################
