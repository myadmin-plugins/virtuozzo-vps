export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
prlctl set {$vps_vzid} --onboot no --autostart off;
prlctl set {$vps_vzid} --disable;
prlctl stop {$vps_vzid};
