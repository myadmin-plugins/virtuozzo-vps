{assign var=space value=($settings['slice_hd'] * $vps_slices) + $settings['additional_hd']}
export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
prlctl set {$vps_vzid} --diskspace {$space}G;
