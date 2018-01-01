								echo "export PATH=\"\$PATH:/usr/sbin:/sbin:/bin:/usr/bin:\";\n";
								echo "prlctl stop {$vps['vps_vzid']};\n";
								echo "prlctl delete {$vps['vps_vzid']};\n";
{vps_create}
