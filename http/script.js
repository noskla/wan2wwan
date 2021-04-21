let states = {
    0: 'Down',
    1: 'Stable',
    2: 'Recovering',
    3: 'Failing',
    4: 'Testing'}

window.onload = () => {

    setInterval(() => {
        net_main.style.opacity = '.3';
        setTimeout(() => net_main.style.opacity = '1', 400);
        net_backup.style.opacity = '.3';
        setTimeout(() => net_backup.style.opacity = '1', 500);

        fetch('/status.json').then(res => res.json()).then(data => {
            net_main_name.innerHTML = `Main (${data.main_net.ifname})`;
            net_main_state.innerHTML = states[data.main_net.state];
            net_main_uptime.innerHTML = (data.main_net.ifstatus.uptime | "0") + " s";
            net_main_ip.innerHTML = data.main_net.ifstatus['ipv4-address'][0].address || "No IP";
            switch (data.main_net.state) {
                case 0:
                case 4:
                    net_main.style.background = 'rgba(255, 0, 0, .1)';
                    break;
                case 1:
                    net_main.style.background = 'rgba(0, 255, 0, .1)';
                    break;
                case 2:
                    net_main.style.background = 'rgba(0, 190, 0, .1)';
                    break;
                case 3:
                    net_main.style.background = 'rgba(255, 255, 0, .1)';
            }

            net_backup_name.innerHTML = `Backup (${data.backup_net.ifname})`;
            net_backup_state.innerHTML = states[data.backup_net.state];
            net_backup_uptime.innerHTML = (data.backup_net.ifstatus.uptime | "0") + " s";
            net_backup_ip.innerHTML = data.backup_net.ifstatus['ipv4-address'][0].address || "No IP";
            switch (data.backup_net.state) {
                case 0:
                case 4:
                    net_backup.style.background = 'rgba(255, 0, 0, .1)';
                    break;
                case 1:
                    net_backup.style.background = 'rgba(0, 255, 0, .1)';
                    break;
                case 2:
                    net_backup.style.background = 'rgba(0, 190, 0, .1)';
                    break;
                case 3:
                    net_backup.style.background = 'rgba(255, 255, 0, .1)';
            }
        });
    }, 2000);

};
