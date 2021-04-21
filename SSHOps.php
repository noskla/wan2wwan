<?php
require_once("./config.php");
require_once("./colors.php");
use \phpseclib3\Net\SSH2;

class SSHOps
{

    private $ssh_session;
    private $interfaces;

    public function __construct()
    {
        $ssh = new SSH2(Config::$ssh["address"], Config::$ssh["port"]);
        $res = $ssh->login(Config::$ssh["username"], Config::$ssh["password"]);

        if (!$res)
            throw new Exception(COLOR_ERR . "SSH login failed" . COLOR_RESET);

        $this->ssh_session = $ssh;
        echo "SSH " . Config::$ssh["username"] . "@" . Config::$ssh["address"]
            . ":" . strval(Config::$ssh["port"]) . COLOR_OK . " OK" . COLOR_RESET . PHP_EOL;

        $this->interfaces = Config::$interfaces;
    }

    public function get_interfaces()
    {
        return $this->interfaces;
    }

    public function set_interface_state($net, $state)
    {
        $this->interfaces[$net]["state"] = $state;
    }

    public function save_interfaces()
    {
        file_put_contents("./status.json", json_encode($this->interfaces));
    }

    public function update_devices_mac()
    {
        $out = $this->ssh_session->exec("cat /proc/net/arp");
        $lines = explode("\n", $out);
        array_shift($lines); // remove first
        array_pop($lines);   // and last
        $devices = array();

        foreach ($lines as $line) {
            $values = explode(" ", $line);
            $values = array_values(array_filter($values)); // remove empty elements
            $devices[$values[0]] = array(
                "hw_type"    => $values[1],
                "flags"      => $values[2],
                "hw_address" => $values[3],
                "device"     => $values[5]);
            
            $dev = $devices[$values[0]];
            if (!intval(str_replace(":", "", $dev["hw_address"])))
                continue;

            foreach ($this->interfaces as $ifaceID => $iface)
                if ($iface["devname"] == $dev["device"]) {
                    $this->interfaces[$ifaceID]["device_mac"] = $dev["hw_address"];
                    echo $ifaceID . " => " . $dev["hw_address"] . " (" . $values[0] . ")" . PHP_EOL;
                }

           //echo $values[0] . " => " . $devices[$values[0]]["hw_address"] . " (" . $devices[$values[0]]["device"] . ")" . PHP_EOL;
        }

        return $devices;
    }

    public function refresh_ifstatus()
    {
        foreach ($this->interfaces as $ifaceID => $iface) {
            $out = $this->ssh_session->exec("/sbin/ifstatus " . $iface["ifname"]);
            $this->interfaces[$ifaceID]["ifstatus"] = json_decode($out, true);
            if ($this->interfaces[$ifaceID]["ifstatus"]["up"])
                echo $ifaceID . " updated IFSTATUS (uptime " . COLOR_OK . strval($this->interfaces[$ifaceID]["ifstatus"]["uptime"]) . "s" . COLOR_RESET . ")" . PHP_EOL; 
            else
                echo $ifaceID . " updated IFSTATUS (" . COLOR_ERR . "disabled" . COLOR_RESET . ")" . PHP_EOL;
        }
    }

    public function enable_interface($ifname)
    {
        $this->ssh_session->exec("/sbin/ifup " . $ifname);
        $this->refresh_ifstatus();
    }

    public function disable_interface($ifname)
    {
        $this->ssh_session->exec("/sbin/ifdown " . $ifname);
        $this->refresh_ifstatus();
    }

    public function ping_by_mac($mac, $devname)
    {
        $target = Config::$internet_test_targets[array_rand(Config::$internet_test_targets)];
        echo COLOR_INFO . $devname . COLOR_RESET . " => PING target " . COLOR_INFO . $target . COLOR_RESET . ": ";

        $out = $this->ssh_session->exec(
            "/usr/bin/nping --dest-mac \"" . $mac . "\" -q -e " . $devname . " " . $target);
        $lines = explode("\n", $out);

        $delay = explode("Avg rtt: ", $lines[2])[1];
        $packets_lost = explode(" (", explode("Lost: ", $lines[3])[1])[0];

        echo $delay . " (" . $packets_lost . " packets lost) => ";
        $positive = (2 > intval($packets_lost) && ($delay != "N/A") ? (intval(substr($delay, 0, -2)) < 220) : false);
        echo ($positive ? (COLOR_OK . "Positive") : (COLOR_ERR . "Negative")) . COLOR_RESET . PHP_EOL;

        return array("positive" => $positive, "packets_lost" => $packets_lost, "delay" => $delay);
    }

}

