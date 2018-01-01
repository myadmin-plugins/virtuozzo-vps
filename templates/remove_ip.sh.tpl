export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
prlctl set {$vps_vzid} --setmode restart --ipdel {$param1|escapeshellarg};
