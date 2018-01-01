export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
prlctl set {$vps_vzid} --onboot no;
prlctl set {$vps_vzid} --disable;
prlctl stop {$vps_vzid};
