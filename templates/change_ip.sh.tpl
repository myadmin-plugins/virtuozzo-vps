export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
eval $(grep '^IP_ADDRESS=' /etc/vz/conf/{$vps_vzid}.conf)
if [ "$(echo "$IP_ADDRESS" | cut -d' ' -f1)" = "{$newip}" ] && [ $(echo "$IP_ADDRESS" |wc -w) -gt 1 ]; then
	prlctl set {$vps_vzid} --ipdel all --ipadd {$newip};
	for IP in $(echo "$IP_ADDRESS" | cut -d' ' -f2-); do
		prlctl set {$vps_vzid}  --ipadd {$oldip};
	done;
	prlctl restart {$vps_vzid};
else
	prlctl set {$vps_vzid} --ipdel {$oldip} --ipadd {$newip};
fi;
