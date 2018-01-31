if [ "$(uname -i)" = "x86_64" ]; then
  limit=9223372036854775807
else
  limit=2147483647
fi;
{assign var=wiggle value=1000}
{assign var=dcache_wiggle value=400000}
{if in_array($vps_custid, [2773, 8, 2304])}
{assign var=cpuunits value=1500 * 1.5 * $vps_slices}
{assign var=cpulimit value=100 * $vps_slices}
{assign var=cpus value=ceil($vps_slices / 4 * 2)}
{else}
{assign var=cpuunits value=1500 * $vps_slices}
{assign var=cpulimit value=25 * $vps_slices}
{assign var=cpus value=ceil($vps_slices / 4)}
{/if}
{assign var=diskspace value=1040000 * (($settings['slice_hd'] * $vps_slices) + $settings['additional_hd'])}
{assign var=diskspace_b value=1040000 * (($settings['slice_hd'] * $vps_slices) + $settings['additional_hd'])}
{assign var=diskinodes value=800000 * $vps_slices}
{assign var=diskinodes_b value=1000000 * $vps_slices}
# numproc, numtcpsock, and numothersock    barrier = limit
{assign var=avnumproc value=200 * $vps_slices}
{assign var=avnumproc_b value=$avnumproc}
{assign var=numproc value=150 * $vps_slices}
{assign var=numproc_b value=$numproc}
{assign var=numtcpsock value=300 * $vps_slices}
{assign var=numtcpsock_b value=$numtcpsock}
{assign var=numothersock value=500 * $vps_slices}
{assign var=numothersock_b value=$numothersock}
# $numfile >= $avnumproc * 32
{assign var=numfile value=32 * $avnumproc}
# $numfile(bar) = $numfile(limit)
{assign var=numfile_b value=$numfile}
# dcachesize(bar) >= $numfile * 384
{assign var=dcachesize value=384 * $numfile + $dcache_wiggle}
{assign var=dcachesize_b value=384 * $numfile_b + $dcache_wiggle}
# GARUNTED SLA MEMORY
{assign var=vmguarpages value=((256 * $settings['slice_ram']) * $vps_slices) - $wiggle}
{assign var=ram value=$settings['slice_ram'] * $vps_slices}
# $privvmpages >= $vmguarpages
{assign var=privvmpages value=((256 * $settings['slice_ram']) * $vps_slices)}
{assign var=privvmpages_b value=$privvmpages + $wiggle}
{assign var=oomguarpages value=$vmguarpages}
# kmemsize(bar) >= 40kb * avnumproc + dcachesize(lim)
{assign var=kmemsize value=(45 * 1024 * $avnumproc + $dcachesize)}
{assign var=kmemsize_b value=(45 * 1024 * $avnumproc_b + $dcachesize_b)}
# dgramrcvbuf(bar) >= 129kb
{assign var=dgramrcvbuf value=262 * 1024 * $vps_slices}
{assign var=dgramrcvbuf_b value=$dgramrcvbuf}
# tcprcvbuf(bar) >= 64k
{assign var=tcprcvbuf value=958464 * $vps_slices}
# tcprcvbuf(lim) - tcprcvbuf(bar) >= 2.5KB * numtcpsock
{assign var=tcprcvbuf_b value=(2561 * $numtcpsock) + $tcprcvbuf}
# tcpsndbuf(bar) >= 64k
{assign var=tcpsndbuf value=958464 * $vps_slices}
# tcpsndbuf(lim) - tcpsndbuf(bar) >= 2.5KB * numtcpsock
{assign var=tcpsndbuf_b value=(2561 * $numtcpsock) + $tcpsndbuf}
# othersockbuf(bar) >= 129kb
{assign var=othersockbuf value=$dgramrcvbuf}
# othersockbuf(lim) - othersockbuf(bar) >= 2.5KB * numtcpsock
{assign var=othersockbuf_b value=(2561 * $numothersock) + $othersockbuf}
{assign var=shmpages value=100000 * $vps_slices}
{assign var=shmpages_b value=$shmpages}
{assign var=numpty value=35 + (24 * $vps_slices)}
{assign var=numpty_b value=$numpty}
{assign var=numflock value=300 * $vps_slices}
{assign var=numflock_b value=500 * $vps_slices}
# gives a like 200-300 range
{assign var=numiptent value=250 * $vps_slices}
{assign var=numiptent_b value=$numiptent}  
function iprogress() {literal}{{/literal}
  curl --connect-timeout 60 --max-time 240 -k -d action=install_progress -d progress=$1 -d server={$vps_id} 'https://myvps2.interserver.net/vps_queue.php' 2>/dev/null;
{literal}}{/literal}
iprogress 10 &
if [ ! -e /vz/template/cache/{$template} ]; then 
  wget -O /vz/template/cache/{$template} {$template_url}; 
fi;
iprogress 15 &
if [ "$(echo "{$template}" | grep "xz$")" != "" ]; then
  newtemplate="$(echo "{$template}" | sed s#"\.xz$"#".gz"#g)";
  if [ -e "/vz/template/cache/$newtemplate" ]; then
    echo "Already Exists in .gz, not changing anything";
  else
    echo "Recompressing {$template} to .gz";
    xz -d --keep "/vz/template/cache/{$template}";
    gzip -9 "$(echo "/vz/template/cache/{$template}" | sed s#"\.xz$"#""#g)";
  fi;
  template="$newtemplate";
fi;
iprogress 20 &
if [ "$(vzctl 2>&1 |grep "vzctl set.*--force")" = "" ]; then
  layout=""
  force=""
else
  if [ "$(mount | grep "^$(df /vz |grep -v ^File | cut -d" " -f1)" | cut -d" " -f5)" = "ext3" ]; then 
    layout=simfs; 
  else
    if [ $(echo "$(uname -r | cut -d\. -f1-2) * 10" | bc -l | cut -d\. -f1) -eq 26 ] && [ $(uname -r | cut -d\. -f3 | cut -d- -f1) -lt 32 ]; then 
      layout=simfs; 
    else 
      layout=ploop; 
    fi; 
  fi;
  layout="--layout $layout";
  force="--force"
fi;
if [ ! -e /etc/vz/conf/ve-vps.small.conf ] && [ -e /etc/vz/conf/ve-basic.conf-sample ]; then
  config="--config basic"
  config=""
else
  config="--config {$config}";
fi;
/usr/sbin/vzctl create {$vps_vzid} --ostemplate {$ostemplate} $layout $config --ipadd {$ip} --hostname {$hostname} 2>&1 || \
{literal} { {/literal}
    /usr/sbin/vzctl destroy {$vps_vzid} 2>&1;
    if [ "$layout" == "--layout ploop" ]; then
      layout="--layout simfs";
    fi;
    /usr/sbin/vzctl create {$vps_vzid} --ostemplate {$ostemplate} $layout $config --ipadd {$ip} --hostname {$hostname} 2>&1;
{literal} }; {/literal}
iprogress 40 &
mkdir -p /vz/root/{$vps_vzid};
/usr/sbin/vzctl set {$vps_vzid} \
 --save $force \
 --cpuunits {$cpuunits} \
 --cpulimit {$cpulimit} \
 --cpus {$cpus} \
 --diskspace {$diskspace}:{$diskspace_b} \
 --diskinodes {$diskinodes}:{$diskinodes_b} \
 --numproc {$numproc}:{$numproc_b} \
 --numtcpsock {$numtcpsock}:{$numtcpsock_b} \
 --numothersock {$numothersock}:{$numothersock_b} \
 --vmguarpages {$vmguarpages}:${limit} \
 --kmemsize {$kmemsize}:{$kmemsize_b} \
 --tcpsndbuf {$tcpsndbuf}:{$tcpsndbuf_b} \
 --tcprcvbuf {$tcprcvbuf}:{$tcprcvbuf_b} \
 --othersockbuf {$othersockbuf}:{$othersockbuf_b} \
 --dgramrcvbuf {$dgramrcvbuf}:{$dgramrcvbuf_b} \
 --oomguarpages {$oomguarpages}:${limit} \
 --privvmpages {$privvmpages}:{$privvmpages_b} \
 --numfile {$numfile}:{$numfile_b} \
 --numflock {$numflock}:{$numflock_b} \
 --physpages 0:${limit} \
 --dcachesize {$dcachesize}:{$dcachesize_b} \
 --numiptent {$numiptent}:{$numiptent_b} \
 --avnumproc {$avnumproc}:{$avnumproc_b} \
 --numpty {$numpty}:{$numpty_b}  \
 --shmpages {$shmpages}:{$shmpages_b} 2>&1;
if [ -e /proc/vz/vswap ]; then
  /bin/mv -f /etc/vz/conf/{$vps_vzid}.conf /etc/vz/conf/{$vps_vzid}.conf.backup;
#  grep -Ev '^(KMEMSIZE|LOCKEDPAGES|PRIVVMPAGES|SHMPAGES|NUMPROC|PHYSPAGES|VMGUARPAGES|OOMGUARPAGES|NUMTCPSOCK|NUMFLOCK|NUMPTY|NUMSIGINFO|TCPSNDBUF|TCPRCVBUF|OTHERSOCKBUF|DGRAMRCVBUF|NUMOTHERSOCK|DCACHESIZE|NUMFILE|AVNUMPROC|NUMIPTENT|ORIGIN_SAMPLE|SWAPPAGES)=' > /etc/vz/conf/{$vps_vzid}.conf <  /etc/vz/conf/{$vps_vzid}.conf.backup;
  grep -Ev '^(KMEMSIZE|PRIVVMPAGES)=' > /etc/vz/conf/{$vps_vzid}.conf <  /etc/vz/conf/{$vps_vzid}.conf.backup;
  /bin/rm -f /etc/vz/conf/{$vps_vzid}.conf.backup;  
  /usr/sbin/vzctl set {$vps_vzid} --ram {$ram}M --swap {$ram}M --save;
  /usr/sbin/vzctl set {$vps_vzid} --reset_ub;
fi;
iprogress 50 &
if [ -e /usr/sbin/vzcfgvalidate ]; then
 /usr/sbin/vzcfgvalidate -r /etc/vz/conf/{$vps_vzid}.conf;
fi;
/usr/sbin/vzctl set {$vps_vzid} --save --devices c:1:3:rw --devices c:10:200:rw --capability net_admin:on;
/usr/sbin/vzctl set {$vps_vzid} --save --nameserver '8.8.8.8 64.20.34.50' --searchdomain interserver.net --onboot yes;
/usr/sbin/vzctl set {$vps_vzid} --save --noatime yes 2>/dev/null;
iprogress 60 &
{foreach item=extraip from=$extraips}
/usr/sbin/vzctl set {$vps_vzid} --save --ipadd {$extraip} 2>&1;
{/foreach}
/usr/sbin/vzctl start {$vps_vzid} 2>&1;
/usr/sbin/vzctl set {$vps_vzid} --save --userpasswd root:{$rootpass} 2>&1;
iprogress 80 &
/usr/sbin/vzctl exec {$vps_vzid} mkdir -p /dev/net;
/usr/sbin/vzctl exec {$vps_vzid} mknod /dev/net/tun c 10 200;
/usr/sbin/vzctl exec {$vps_vzid} chmod 600 /dev/net/tun;
iprogress 90 &
/root/cpaneldirect/vzopenvztc.sh > /root/vzopenvztc.sh && sh /root/vzopenvztc.sh;
/usr/sbin/vzctl set {$vps_vzid} --save --userpasswd root:{$rootpass} 2>&1;
sshcnf="$(find /vz/root/{$vps_vzid}/etc/*ssh/sshd_config 2>/dev/null)";
if [ -e "$sshcnf" ]; then 
{if isset($ssh_key)}
 vzctl exec {$vps_vzid} "mkdir -p /root/.ssh;"
 vzctl exec {$vps_vzid} "echo {$ssh_key} >> /root/.ssh/authorized_keys2;"
 vzctl exec {$vps_vzid} "chmod go-w /root; chmod 700 /root/.ssh; chmod 600 /root/.ssh/authorized_keys2;"
{/if}
 if [ "$(grep "^PermitRootLogin" $sshcnf)" = "" ]; then 
  echo "PermitRootLogin yes" >> $sshcnf; 
  echo "Added PermitRootLogin line in $sshcnf";
  kill -HUP $(vzpid $(pidof sshd) |grep "[[:space:]]{$vps_vzid}[[:space:]]" | sed s#"{$vps_vzid}.*ssh.*$"#""#g);
 elif [ "$(grep "^PermitRootLogin" $sshcnf)" != "PermitRootLogin yes" ]; then
  sed s#"^PermitRootLogin.*$"#"PermitRootLogin yes"#g -i $sshcnf;
  echo "Updated PermitRootLogin line in $sshcnf";
  kill -HUP $(vzpid $(pidof sshd) |grep "[[:space:]]{$vps_vzid}[[:space:]]" | sed s#"{$vps_vzid}.*ssh.*$"#""#g);
 fi;
fi;
if [ "{$ostemplate}" = "centos-7-x86_64-nginxwordpress" ]; then
	vzctl exec {$vps_vzid} /root/change.sh {$rootpass} 2>&1;
fi;

if [ "{$ostemplate}" = "ubuntu-15.04-x86_64-xrdp" ]; then
	/usr/sbin/vzctl set {$vps_vzid} --save --userpasswd kvm:{$rootpass} 2>&1;
fi;
iprogress 100 &
