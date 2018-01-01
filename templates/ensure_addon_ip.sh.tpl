export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
if [ "$(grep -E "^IP_ADDRESS.*[ \\"]+{$ipreg}[ \\"]+" /etc/vz/conf/{$vps_vzid}.conf)" = "" ]; then
	prlctl set {$vps_vzid} --ipadd {$ipesc};
fi;
