#!/bin/bash

create() {
    for port in {1..6}
    do 
        cat > redis-637${port}.conf << EOF
port 637${port}
cluster-enabled yes
cluster-config-file nodes-637${port}.conf
cluster-node-timeout 5000
appendonly yes
daemonize no
protected-mode no
pidfile  /data/redis-637${port}.pid
EOF


    docker run -p 637${port}:637${port} -d -v ~/docker/redis-cluster/:/usr/local/etc/redis/ --name redis-node${port} redis redis-server /usr/local/etc/redis/redis-637${port}.conf
    done
}

run() {
    for port in {1..6}
    do 
       docker run -d -p 637${port}:6379 --name redis-node${port} -p 637${port} redis --cluster-enabled yes --cluster-config-file nodes-node-${port}.conf  
    done
}

rm() {
    for port in {1..6}
    do 
       docker rm -f redis-node${port}
    done
}

stop() {
    for port in {1..6}
    do 
       docker stop redis-node${port}
    done
}

restart() {
    for port in {1..6}
    do 
       docker restart redis-node${port}
    done
}

ip() {
    arr=()
    for port in {1..6}
    do 
       a=$(docker inspect redis-node${port} | grep '"IPAddress": "' | head -1 | awk '{print $2}')
       a=${a//\"/}
       a=${a//\,}:637${port}
       arr[$port]=$a
    done

    echo ${arr[*]}

    # redis-cli --cluster create --cluster-replicas 1 ${arr[*]}
}

$@