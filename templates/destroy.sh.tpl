									echo "export PATH=\"\$PATH:/usr/sbin:/sbin:/bin:/usr/bin:\";\n";
									echo "prlctl set {$vps['vps_vzid']} --onboot no;\n";
									echo "prlctl set {$vps['vps_vzid']} --disable;\n";
									echo "prlctl stop {$vps['vps_vzid']};\n";
									echo "prlctl delete {$vps['vps_vzid']};\n";
