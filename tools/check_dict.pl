#!/usr/bin/perl
#!/usr/bin/perl -w 

if (@ARGV==0){
print "USAGE check_dict.pl dico_file [1=count_all_entries]\n Must to be launch in GLPI root dir\n\n";

exit();
}

$count_all=0;
if (length($ARGV[1])>0){
$count_all=1;
print "EXACT ENTRY COUNT\n";
} else {
print "NOT EXACT ENTRY COUNT - JUST FIRST FIND FILE\n";
}	

open(INFO,$ARGV[0]) or die("Fichier ".$ARGV[0]." absent");
@lines=<INFO>;
close(INFO);

foreach (@lines)
{
	
	if ($_=~m/\$(LANG)\[['"]([a-zA-Z_]+)['"]\]\[([0-9]+)\]/){
		print "SEARCH $1\['$2'\]\[$3\] : ";
		$count=0;
		do_dir(".",$1,$2,$3);
		print $count;
		if ($2=~m/calendarDay/||$2=~m/calendarM/) {print " <- not found but OK - used with key computation";}
 		elsif ($count==0) {print " <<<<--------------- NOT FOUND";}
		print "\n";
	}

	if ($_=~m/\$(LANG)\[['"]([a-zA-Z_]+)['"]\]\[['"]([a-zA-Z_]+)['"]\]\[([0-9]+)\]/){
		print "SEARCH2 $1\['$2'\]['$3'\]\[$4\] : ";
		$count=0;
		do_dir2(".",$1,$2,$3,$4);
		print $count;
		print "\n";
	}

}


sub do_dir{
	local ($dir,$varname,$module,$i)=@_;	
	#print "Entering $dir\n";
	my $found_php=0;
	opendir(DIRHANDLE,$dir)||die "ERROR: can not read current directory\n"; 

	foreach (readdir(DIRHANDLE)){ 
		if ($_ ne '..' && $_ ne '.'){
				
			if (-d "$dir/$_" && $_!~m/locales/ && $_!~m/files/ && $_!~m/\.svn/ ){
				if ($count_all==1 || $count==0){
					do_dir("$dir/$_",$varname,$module,$i);
				}
			} else {
				if ($count_all==1 || $count==0){
					if (!-d "$dir/$_" && (index($_,".php",0)==length($_)-4)){
					$found_php=1;
					}
				}
			}
		}
	}
	if ($found_php==1 && ($count_all==1 || $count==0) ){
		open COUNT, "cat $dir/*.php | grep \"$varname\\\[\['\\\"\]$module\['\\\"\]\\\]\\\[$i\\\]\" | wc -l |";
		while(<COUNT>) {
			$count+=$_;
			#print $_."\n";
		}
	}
	
	closedir DIRHANDLE; 
	
}

sub do_dir2{
	local ($dir,$varname,$module,$module2,$i)=@_;	
	
	#print "Entering $dir\n";
	my $found_php=0;
	opendir(DIRHANDLE,$dir)||die "ERROR: can not read current directory\n"; 
	foreach (readdir(DIRHANDLE)){ 
		if ($_ ne '..' && $_ ne '.'){
				
			if (-d "$dir/$_" && $_!~m/locales/ && $_!~m/files/ && $_!~m/\.svn/ ){
				if ($count_all==1 || $count==0){
					do_dir2("$dir/$_",$varname,$module,$module2,$i);
				}
			} else {
				if ($count_all==1 || $count==0){
					if (!-d "$dir/$_" && (index($_,".php",0)==length($_)-4)){
					$found_php=1;
					}
				}
			}
		}
	}
	if ($found_php==1 && ($count_all==1 || $count==0) ){
		open COUNT, "cat $dir/*.php | grep \"$varname\\\[\['\\\"\]$module\['\\\"\]\\\]\\\[\['\\\"\]$module2\['\\\"\]\\\]\\\[$i\\\]\" | wc -l |";
		while(<COUNT>) {
			$count+=$_;
			#print $_."\n";
		}
	}
	
	closedir DIRHANDLE; 
	
}
