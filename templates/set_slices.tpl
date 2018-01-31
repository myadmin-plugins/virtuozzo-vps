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
vmd5="$(md5sum /etc/vz/conf/{$vps_vzid}.conf | cut -d" " -f1)";
/usr/sbin/vzctl set {$vps_vzid} \
 --save \
 --force \
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
 --shmpages {$shmpages}:{$shmpages_b};
if [ -e /usr/sbin/vzcfgvalidate ]; then
  /usr/sbin/vzcfgvalidate -r /etc/vz/conf/{$vps_vzid}.conf >/dev/null 2>&1;
fi;
vmd52="$(md5sum /etc/vz/conf/{$vps_vzid}.conf | cut -d" " -f1)";
echo "Original MD5      ${vmd5}";
echo "New MD5           ${vmd52}";
if [ ! "${vmd5}" = "${vmd52}" ]; then
  echo "          Config File Changed, Restarting VPS";
else
  echo "          No Config File Changes";
fi;
