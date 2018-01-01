								echo "export PATH=\"\$PATH:/usr/sbin:/sbin:/bin:/usr/bin:\";\n";
								echo "prlctl set {$vps['vps_vzid']} --userpasswd root:" . escapeshellarg($vps['history_old_value']) . ";\n";
