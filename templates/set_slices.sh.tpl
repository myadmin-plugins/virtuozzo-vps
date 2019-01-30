{assign var=ram value=$settings['slice_ram'] * $vps_slices}
{assign var=hd value=(($settings['slice_hd'] * $vps_slices) + $settings['additional_hd']) * 1024}
{assign var=cpus value=$vps_slices}
{if in_array($vps_custid, [2773, 8, 2304])}
{assign var=cpuunits value=1500 * 1.5 * $vps_slices}
{assign var=cpulimit value=100 * $vps_slices}
{else}
{assign var=cpuunits value=1500 * $vps_slices}
{assign var=cpulimit value=25 * $vps_slices}
{/if}
prlctl set {$vps_vzid} --cpus {$cpus};
prlctl set {$vps_vzid} --cpuunits {$cpuunits};
prlctl set {$vps_vzid} --cpulimit {$cpulimit};
prlctl set {$vps_vzid} --device-set hdd0 --size {$hd};
