								echo "export PATH=\"\$PATH:/usr/sbin:/sbin:/bin:/usr/bin:\";\n";
								echo "prlctl exec {$vps['vps_vzid']} 'if [ ! -e /usr/bin/screen ]; then yum -y install screen; fi; if [ ! -e /admin/cpanelinstall ]; then rsync -a rsync://mirror.trouble-free.net/admin /admin; fi; /admin/cpanelinstall " . $tf->accounts->cross_reference($vps['vps_custid']) . ";'\n";

