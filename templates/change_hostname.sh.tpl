/root/cpaneldirect/provirted.phar update --hostname={$param|escapeshellarg} {if $vps_vzid == "0"}{$vps_id}{else}{$vps_vzid|escapeshellarg}{/if};
