export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
prlctl set {$vps_vzid} --onboot yes;
prlctl set {$vps_vzid} --enable;
