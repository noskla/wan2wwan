<?php
require_once("./SSHOps.php");
require_once("./Report.php");
require_once("./colors.php");

$ops     = new SSHOps();
$reports = new Report();

$ops->update_devices_mac();
$ops->refresh_ifstatus();

$net_status = null;
$outage_start = null;
$outage_diagnosis = null;
$main_net_recovery_count = 0;

if ($ops->get_interfaces()["main_net"]["ifstatus"]["up"])
    $net_status = "main";
elseif ($ops->get_interfaces()["backup_net"]["ifstatus"]["up"])
    $net_status = "backup";
else {
    echo COLOR_ERR . "All known interfaces down, enabling main..." . COLOR_RESET . PHP_EOL;
    $ops->enable_interface($ops->get_interfaces()["main_net"]["ifname"]);
    $net_status = "main";
}
echo "Detected active link: " . COLOR_INFO . $net_status . COLOR_RESET . PHP_EOL;

while (true) {
    $ops->refresh_ifstatus();
    $ops->save_interfaces();
    sleep($net_status == "main" ? 6 : 4);

    switch ($net_status) {

        case "main":
            $dev = $ops->get_interfaces()["main_net"];
            $res = $ops->ping_by_mac($dev["device_mac"], $dev["devname"]);
            if ($res["positive"]) {
                echo "Main link intact" . PHP_EOL;
                $ops->set_interface_state("main_net", 1);
            } else { 
                echo COLOR_INFO . "Network disturbance on main link detected, analyzing..." . COLOR_RESET . PHP_EOL;
                $ops->set_interface_state("main_net", 3);
                $res = $ops->ping_by_mac($dev["device_mac"], $dev["devname"]);
                $packets_ok = (intval($res["packets_lost"]) < 2);
                $delay_ok = ($delay != "N/A") ? (intval(substr($delay, 0, -2)) < 400) : false;
                if ($packets_ok && $delay_ok)
                    echo COLOR_INFO . "Main link is either intact or the disturbance is too minor to switch to fallback." . COLOR_RESET . PHP_EOL;
                else {
                    $outage_start = time();
                    if ($packets_ok && !$delay_ok) {
                        echo COLOR_ERR . "Main link's RTT (round-trip delay) is too high and is causing significant performance drops. Switching to backup network..." . COLOR_RESET . PHP_EOL;
                        $outage_diagnosis = "High RTT (delay)";
                    } elseif (!$packets_ok && $delay_ok) {
                        echo COLOR_ERR . "Main link is dropping more packets than acceptable limit and is causing significant issues. Switching to backup network..." . COLOR_RESET . PHP_EOL;
                        $outage_diagnosis = "Dropping packets";
                    } else {
                        echo COLOR_ERR . "Main link is either dead or is experiencing significant connectivity issues on the ISP's side. Switching to backup network..." . COLOR_RESET . PHP_EOL;
                        $outage_diagnosis = "Many issues";
                    }
                    $net_status = "backup";
                    $ops->enable_interface($ops->get_interfaces()["backup_net"]["ifname"]);
                    $ops->set_interface_state("main_net", 4);
                    $ops->set_interface_state("backup_net", 1);
                }
            }
            break;

        case "backup":
            $dev = $ops->get_interfaces()["main_net"];
            $res = $ops->ping_by_mac($dev["device_mac"], $dev["devname"]);
            if ($res["positive"]) {
                $main_net_recovery_count++;
                if ($main_net_recovery_count < 40) {
                    echo "Main link seems to be recovering... (" .  COLOR_OK . strval($main_net_recovery_count) . "/40" . COLOR_RESET . ")" . PHP_EOL;
                    $ops->set_interface_state("main_net", 2);
                } else {
                    echo COLOR_OK . "Main link is back online and looks to be stable. Routing back to main..." . COLOR_RESET . PHP_EOL;
                    $main_net_recovery_count = 0;
                    $net_status = "main";
                    $ops->disable_interface($ops->get_interfaces()["backup_net"]["ifname"]);
                    $ops->enable_interface($ops->get_interfaces()["main_net"]["ifname"]);
                    $ops->set_interface_state("main_net", 1);
                    $ops->set_interface_state("backup_net", 0);
                    $reports->create_report($outage_start, time(), $outage_diagnosis, "unknown");
                    $outage_start = $outage_diagnosis = null;
                }
            } else {
                $main_net_recovery_count = 0;
                echo "Test failed: Main link is still down.";
                $ops->set_interface_state("main_net", 4);
            }
            break;

    }

}



