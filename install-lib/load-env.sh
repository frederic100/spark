
BUILD_WHEN_INSTALL=false
DOCKER_COMPOSE_FILES="-f docker-compose.yml"
DOCKER_DEV=false

function load_env() {
    # Définir le chemin des fichiers .env.local et .env
  ENV_LOCAL_FILE="./.env.local"
  ENV_FILE="./.env"
  _load_env_if_not_exist_from_file "$ENV_FILE"
  _load_env_if_not_exist_from_file "$ENV_LOCAL_FILE"
  if [ "$DOCKER_DEV" = true ]; then
      DOCKER_COMPOSE_FILES="-f docker-compose.yml -f docker-compose.dev.yml"
  fi;
  export "DOCKER_PHP_BUILT_IMAGE=$DOCKER_PHP_BUILT_IMAGE"
}

function _load_env_if_not_exist_from_file() {
  if [ -f "$1" ]; then
    while IFS= read -r line || [[ -n "$line" ]]; do
      if [[ ! -z "$line" && ! "$line" =~ ^# ]]; then
        varname=$(echo "$line" | cut -d= -f1)
        varvalue=$(echo "$line" | cut -d= -f2-)
        export "$varname=$varvalue"
      fi
    done < "$1"
  fi
}
