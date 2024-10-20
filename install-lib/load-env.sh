#!/bin/bash

function load_env() {
    # Définir le chemin des fichiers .env.local et .env
  ENV_LOCAL_FILE="./.env.local"
  ENV_FILE="./.env"
  _load_env_if_not_exist_from_file "$ENV_LOCAL_FILE"
  _load_env_if_not_exist_from_file "$ENV_FILE"
}

function _load_env_if_not_exist_from_file() {
  if [ -f "$1" ]; then
    while IFS= read -r line || [[ -n "$line" ]]; do
      if [[ ! -z "$line" && ! "$line" =~ ^# ]]; then
        varname=$(echo "$line" | cut -d= -f1)
        varvalue=$(echo "$line" | cut -d= -f2-)
        # Vérifier si la variable est déjà définie, sinon la définir
        if [ -z "${!varname}" ]; then
          export "$varname=$varvalue"
        fi
      fi
    done < "$1"
  fi
}