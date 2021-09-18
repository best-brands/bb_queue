# Queue

An advanced add-on that allows you to write jobs that get processed by a worker-pool.

## Creating a new job.

See the add-on 'queue_example'.

## How do I configure workers?

You will need to run supervisor. We use a config that looks as follows.

```conf
[supervisord]
nodaemon = true
logfile = /var/log/supervisord.log
pidfile = /run/supervisord.pid

[program:cscart-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php7.4 -f %%STORE_PATH%%/cli.php -- --dispatch=queue.launch_worker
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=%%STORE_PATH%%/log/worker.log
stopwaitsecs=3600
```

Replace %%STORE_PATH%% with your root folder. It is recommended to put cli.php
outside the root folder, so you end up with a structure like this:

```
%%STORE_PATH%%:
├─ cscart    # Contains the cscart installation folder
│  ├─ app    # Application folder
│  ├─ design # Theming folder
│  ├─ ...
├─ cli.php      # A simple script that sets the workers accordingly.
```