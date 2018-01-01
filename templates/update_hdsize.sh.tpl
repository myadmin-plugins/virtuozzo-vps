								echo "export PATH=\"\$PATH:/usr/sbin:/sbin:/bin:/usr/bin:\";\n";
								echo "prlctl set {$vps['vps_vzid']} --diskspace {$space}G;\n";
