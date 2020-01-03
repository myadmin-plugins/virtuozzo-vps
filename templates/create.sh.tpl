{assign var=vps_os value=$vps_os|replace:'.tar.gz':''}
{assign var=ram value=$settings['slice_ram'] * $vps_slices}
{assign var=hd value=(($settings['slice_hd'] * $vps_slices) + $settings['additional_hd']) * 1024}
{assign var=cpus value=$vps_slices}
{if in_array($vps_custid, [2773, 8, 2304])}
{assign var=cpuunits value=1500 * 1.5 * $vps_slices}
{else}
{assign var=cpuunits value=1500 * $vps_slices}
{/if}
function iprogress() {
  curl --connect-timeout 60 --max-time 240 -k -d action=install_progress -d progress=$1 -d server={$vps_id} 'https://myvps2.interserver.net/vps_queue.php' < /dev/null > /dev/null 2>&1;
}
IFS="
"
webuzo=0
cpanel=0
vps_os="{$vps_os}"
if [ "$vps_os" = "centos-7-x86_64-breadbasket" ]; then
  vps_os=centos-7-x86_64
  webuzo=1
elif [ "$vps_os" = "centos-7-x86_64-cpanel" ]; then
  vps_os=centos-7-x86_64
  cpanel=1
fi
iprogress 10
prlctl create {$vzid} --vmtype ct --ostemplate "$vps_os";
iprogress 60
prlctl set {$vzid} --userpasswd root:{$rootpass};
prlctl set {$vzid} --swappages 1G --memsize {$ram}M;
prlctl set {$vzid} --hostname {$hostname};
prlctl set {$vzid} --device-add net --type routed --ipadd {$ip} --nameserver 8.8.8.8;
iprogress 70
prlctl set {$vzid} --cpus {$cpus};
prlctl set {$vzid} --cpuunits {$cpuunits};
prlctl set {$vzid} --device-set hdd0 --size {$hd};
iprogress 80
prlctl set {$vzid} --onboot yes ;
ports=" $(prlctl list -a -i |grep "Remote display:.*port=" |sed s#"^.*port=\([0-9]*\) .*$"#"\1"#g) ";
start=5901;
found=0;
while [ $found -eq 0 ]; do
  if [ "$(echo "$ports" | grep "$start")" = "" ]; then
	found=$start;
  else
	start=$(($start + 1));
  fi;
done;
prlctl set {$vzid} --vnc-mode manual --vnc-port $start --vnc-nopasswd --vnc-address 127.0.0.1;
iprogress 90
prlctl start {$vzid};
iprogress 91
if [ $webuzo -eq 1 ]; then
  if [ "$vps_os" = "centos-7-x86_64" ]; then
	prlctl exec {$vzid} 'yum -y remove httpd sendmail xinetd firewalld samba samba-libs samba-common-tools samba-client samba-common samba-client-libs samba-common-libs rpcbind; userdel apache'
	iprogress 92
	prlctl exec {$vzid} 'yum -y install nano net-tools'
	iprogress 93
  fi
  prlctl exec {$vzid} 'rsync -a rsync://rsync.is.cc/admin /admin;/admin/yumcron;echo "/usr/local/emps/bin/php /usr/local/webuzo/cron.php" > /etc/cron.daily/wu.sh && chmod +x /etc/cron.daily/wu.sh'
  iprogress 94
  prlctl exec {$vzid} 'wget -N http://files.webuzo.com/install.sh -O /install.sh'
  iprogress 95
  prlctl exec {$vzid} 'chmod +x /install.sh;bash -l /install.sh;rm -f /install.sh'
  iprogress 99
  echo "Sleeping for a minute to workaround an ish"
  sleep 10s;
  echo "That was a pleasant nap.. back to the grind..."
fi;
if [ $cpanel -eq 1 ]; then
  echo "Sleeping for a minute to workaround an ish"
  sleep 10s;
  echo "That was a pleasant nap.. back to the grind..."
	prlctl exec {$vzid} 'yum -y install perl nano screen wget psmisc net-tools;'
	prlctl exec {$vzid} 'wget http://layer1.cpanel.net/latest;'
	iprogress 92
	prlctl exec {$vzid} 'systemctl disable firewalld.service; systemctl mask firewalld.service; rpm -e firewalld xinetd httpd'
	prlctl exec {$vzid} 'bash -l latest'
	iprogress 94
	prlctl exec {$vzid} 'yum -y remove ea-apache24-mod_ruid2'
	prlctl exec {$vzid} 'killall httpd; if [ -e /bin/systemctl ]; then systemctl stop httpd.service; else service httpd stop; fi'
	iprogress 95
	prlctl exec {$vzid} 'yum -y install ea-apache24-mod_headers ea-apache24-mod_lsapi ea-liblsapi ea-apache24-mod_env ea-apache24-mod_deflate ea-apache24-mod_expires ea-apache24-mod_suexec'
	iprogress 97
	prlctl exec {$vzid} 'yum -y install ea-php72-php-litespeed ea-php72-php-opcache ea-php72-php-mysqlnd ea-php72-php-mcrypt ea-php72-php-gd ea-php72-php-mbstring'
	iprogress 99
	prlctl exec {$vzid} '/usr/local/cpanel/bin/rebuild_phpconf  --default=ea-php72 --ea-php72=lsapi'
	prlctl exec {$vzid} '/usr/sbin/whmapi1 php_ini_set_directives directive-1=post_max_size%3A32M directive-2=upload_max_filesize%3A128M directive-3=memory_limit%3A256M version=ea-php72'
	prlctl exec {$vzid} 'cd /opt/cpanel; for i in $(find * -maxdepth 0 -name "ea-php*"); do /usr/local/cpanel/bin/rebuild_phpconf --default=ea-php72 --$i=lsapi; done' 
	prlctl exec {$vzid} '/scripts/restartsrv_httpd'
	prlctl exec {$vzid} 'rsync -a rsync://rsync.is.cc/admin /admin && cd /etc/cron.daily && ln -s /admin/wp/webuzo_wp_cli_auto.sh /etc/cron.daily/webuzo_wp_cli_auto.sh'
fi;
iprogress 100
