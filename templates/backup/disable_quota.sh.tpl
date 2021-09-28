export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
prlctl set {$vps_vzid} --quotaugidlimit 0 --save --setmode restart;

