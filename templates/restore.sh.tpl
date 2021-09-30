{assign var='param' value=':'|explode:$param}
/root/cpaneldirect/vps_swift_restore.sh {$param[0]} {$param[1]} {$vps_vzid} && \
curl --connect-timeout 60 --max-time 600 -k -d action=restore_status -d vps_id={$vps_id} https://{$domain}/vps_queue.php || \
curl --connect-timeout 60 --max-time 600 -k -d action=restore_status -d vps_id={$vps_id} https://{$domain}/vps_queue.php;

