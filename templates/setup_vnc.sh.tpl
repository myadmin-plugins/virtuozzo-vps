/root/cpaneldirect/provirted.phar vnc setup {if $vps_vzid == "0"}{$vps_id}{else}{$vps_vzid|escapeshellarg}{/if} {$param|escapeshellarg};
