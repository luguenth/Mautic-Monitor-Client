# Mautic-Monitor-Client
A monitoring tool for Mautic instances.
You need to install the [MauticMonitorApiBundle](https://github.com/luguenth/Mautic-Monitor-API-Plugin) in order to use the Mautic-Monitor-Client.

![img.png](img.png)

## Installation:
configure the [.env](https://github.com/luguenth/Mautic-Monitor-Client/blob/master/.env) file in the root directory of the project.
```
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/mautic_monitor?serverVersion=5.7"
```
replace `db_user` with your database user, `db_password` with your database password

then execute the following commands:
```
bin/console doctrine:database:create
bin/console doctrine:schema:update --force
```

to update the instances with the latest monitor data, add the following command to your crontab:
```
*/5 * * * * /usr/bin/php /path/to/mautic-monitor-client/bin/console instances:sync
```