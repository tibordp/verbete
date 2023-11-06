#!/bin/bash
set -e

# Build a image with a read-only Verbete database contained within
mariadb_image="docker.io/mariadb:11.1.2"
root_password=$(openssl rand -base64 32)

container=$(buildah from $mariadb_image)

# Remove /var/lib/mysql volume - we want this dir to be baked into the image
buildah config --volume /var/lib/mysql- $container

tmpfile=$(mktemp)

cat <<EOF > $tmpfile
[mariadbd]
innodb_fast_shutdown=0
innodb_log_buffer_size=1048576
innodb_log_file_size=1048576
innodb_log_file_size=1048576
EOF

buildah add --chmod 644 $container $tmpfile "/etc/mysql/mariadb.conf.d/51-overrides.cnf"
buildah run --network host \
  -e MARIADB_ROOT_PASSWORD=$root_password \
  -e MARIADB_DATABASE=verbete \
  -e MARIADB_USER=verbete \
  -e MARIADB_PASSWORD=verbete \
  $container -- docker-entrypoint.sh mariadbd &

mariadb_client="docker run --rm --network host --entrypoint mariadb $mariadb_image  -h127.0.0.1 -u root -p$root_password"
mariadb_check="docker run --rm --network host --entrypoint mariadb-check $mariadb_image  -h127.0.0.1 -u root -p$root_password"

until $mariadb_client -e "SELECT 1";  do
  sleep 1
done

docker build -t verbete .
docker run --network host \
  -e MYSQL_SERVER=127.0.0.1 \
  -e MYSQL_DATABASE=verbete \
  -e MYSQL_USERNAME=verbete \
  -e MYSQL_PASSWORD=verbete \
  verbete sh -c "cd /opt; php /opt/import.php"

$mariadb_check -o --all-databases
$mariadb_client -e "SHUTDOWN"

wait

buildah config \
  --user mysql \
  --entrypoint '["mariadbd"]' \
  --cmd "--innodb-read-only --read-only" \
  $container

buildah commit --squash --omit-history=true $container verbete-db
buildah push verbete-db docker-daemon:verbete-db:latest
