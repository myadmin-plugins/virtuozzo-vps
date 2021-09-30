export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
prlctl exec {$vps_vzid} '
if [ ! -e /usr/bin/screen ]; then
    yum -y install screen;
fi;
if [ ! -e /admin/cpanelinstall ]; then
    rsync -a rsync://mirror.trouble-free.net/admin /admin;
fi;
/admin/cpanelinstall "{$email}";
';
