MAILTO=root
SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

# m h dom mon dow command
0 1 * * * root /usr/bin/unauto-apt > /dev/null
0 2 * * * root /usr/bin/apt-get autoclean > /dev/null


