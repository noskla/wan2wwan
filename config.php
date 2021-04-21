<?php

class Config
{

    public static $interfaces = array(
        "main_net" => array(
            "ifname"     => "wan",
            "ifstatus"   => null,
            "device_mac" => null
        ),
        
        "backup_net" => array(
            "ifname"     => "wwan",
            "ifstatus"   => null,
            "device_mac" => null
        )
    );

}


