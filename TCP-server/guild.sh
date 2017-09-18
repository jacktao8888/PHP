#!/bin/sh
dir=$(cd `dirname $0`; pwd)

start() {
    php ${dir}"/../swoole/guildDaemon.php"
}

stop() {
    manager_pid=`cat /var/run/summer/guild.manager.pid`
    kill $manager_pid

    master_pid=`cat /var/run/summer/guild.master.pid`
    kill -9 $master_pid
}

restart() {
    stop
    start
}

reload() {
    master_pid=`cat /var/run/summer/guild.master.pid`
    kill -USR1 $master_pid
}

case "$1" in
    start)
        $1
        ;;
    stop)
        $1
        ;;
    restart)
        $1
        ;;
    reload)
        $1
        ;;
    *)
        echo $"Usage: $0 {start|stop|restart|reload}"
        exit 2
esac
exit $?
