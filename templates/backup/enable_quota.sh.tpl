export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
prlctl set {$vps_vzid} --quotaugidlimit 200 --save --setmode restart;
