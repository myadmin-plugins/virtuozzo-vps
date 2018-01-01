export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
prlctl set {$vps_vzid} --hostname {$param|escapeshellarg};
