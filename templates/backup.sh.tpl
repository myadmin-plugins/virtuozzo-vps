/root/cpaneldirect/vps_swift_backup.sh {$vps_id} {$vps_vzid} {$param1} 2>&1 | tee /root/cpaneldirect/backup{$vps_id}.log && \
curl --connect-timeout 60 --max-time 600 -k -d action=backup_status -d vps_id={$vps_id} https://{$domain}/vps_queue.php || \
curl --connect-timeout 60 --max-time 600 -k -d action=backup_status -d vps_id={$vps_id} https://{$domain}/vps_queue.php;
cat /root/cpaneldirect/backup{$vps_id}.log | mail -v '{$email}';
rm -f /root/cpaneldirect/backup{$vps_id}.log;
