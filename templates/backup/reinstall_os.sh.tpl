export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
prlctl stop {$vps_vzid};
prlctl delete {$vps_vzid};
