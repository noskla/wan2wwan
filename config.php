<?php
include_once __DIR__ . "/vendor/autoload.php";

class Config
{

    /**
     * 
     * States:
     * 0 - Off
     * 1 - On (stable)
     * 2 - On (testing - recovering)
     * 3 - On (failing)
     * 4 - On (testing)
     * 
     */
    public static $interfaces = array(
        "main_net" => array(
            "ifname"     => "wan",
            "devname"    => "br-wan",
            "state"      => 0,
            "ifstatus"   => null,
            "device_mac" => null
        ),
        
        "backup_net" => array(
            "ifname"     => "wwan",
            "devname"    => "br-wwan",
            "state"      => 0,
            "ifstatus"   => null,
            "device_mac" => null
        )
    );

    public static $ssh = array(
        "address"  => "horseportal.intranet",
        "port"     => 22,
        "username" => "root",
        "password" => ""
    );

    public static $internet_test_targets = array(
        "1.1.1.1", "1.0.0.1", "8.8.8.8", "8.8.4.4"
    );

}


