#!/bin/bash
# Wrapper script for managing Gaia Alpha Mail Server

CONTAINER_NAME="mail"

function show_help {
    echo "Usage: ./manage_mail.sh [command] [arguments]"
    echo ""
    echo "Commands:"
    echo "  add <email> <password>     Add a new email account"
    echo "  del <email>                Delete an email account"
    echo "  list                       List all email accounts"
    echo "  alias add <alias> <email>  Add an alias"
    echo "  alias del <alias> <email>  Delete an alias"
    echo "  alias list                 List all aliases"
    echo "  dkim                       Generate DKIM keys (requires restart)"
    echo "  info                       Show mail server info/debug"
    echo ""
}

if [ -z "$1" ]; then
    show_help
    exit 1
fi

COMMAND=$1
shift

if [ "$COMMAND" == "add" ]; then
    if [ -z "$1" ] || [ -z "$2" ]; then
        echo "Error: Email and password required."
        echo "Usage: ./manage_mail.sh add <email> <password>"
        exit 1
    fi
    docker exec -ti $CONTAINER_NAME setup email add "$1" "$2"

elif [ "$COMMAND" == "del" ]; then
    if [ -z "$1" ]; then
        echo "Error: Email required."
        echo "Usage: ./manage_mail.sh del <email>"
        exit 1
    fi
    docker exec -ti $CONTAINER_NAME setup email del "$1"

elif [ "$COMMAND" == "list" ]; then
    docker exec -ti $CONTAINER_NAME setup email list

elif [ "$COMMAND" == "alias" ]; then
    SUBCOMMAND=$1
    shift
    if [ "$SUBCOMMAND" == "add" ]; then
        docker exec -ti $CONTAINER_NAME setup alias add "$1" "$2"
    elif [ "$SUBCOMMAND" == "del" ]; then
        docker exec -ti $CONTAINER_NAME setup alias del "$1" "$2"
    elif [ "$SUBCOMMAND" == "list" ]; then
        docker exec -ti $CONTAINER_NAME setup alias list
    else
        echo "Unknown alias command."
    fi

elif [ "$COMMAND" == "dkim" ]; then
    echo "Generating DKIM keys..."
    docker exec -ti $CONTAINER_NAME setup config dkim
    echo "DKIM keys generated. You may need to restart the mail container to apply changes."
    echo "Check ./docker-data/dms/config/opendkim/keys for the public keys to add to your DNS."

elif [ "$COMMAND" == "info" ]; then
    docker exec -ti $CONTAINER_NAME setup debug login all

else
    echo "Unknown command: $COMMAND"
    show_help
    exit 1
fi
