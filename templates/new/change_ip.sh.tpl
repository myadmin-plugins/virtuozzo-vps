{assign var='param' value=','|explode:$param}
/root/cpaneldirect/cli/provirted.phar change-ip {$vps_vzid|escapeshellarg} {$param[0]|escapeshellarg} {$param[1]|escapeshellarg};