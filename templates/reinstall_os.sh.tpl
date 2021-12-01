/root/cpaneldirect/provirted.phar stop {if $vps_vzid == "0"}{$vps_id}{else}{$vps_vzid|escapeshellarg}{/if};
/root/cpaneldirect/provirted.phar destroy {if $vps_vzid == "0"}{$vps_id}{else}{$vps_vzid|escapeshellarg}{/if};
