#!/usr/bin/env bash
set -euo pipefail
key_dir=$(mktemp -d /tmp/winx-oci-ssh.XXXXXX)
trap 'rm -rf "$key_dir"' EXIT
install -m 600 /mnt/c/SSH/WINX/ssh-key-2026-09-25.key "$key_dir/id_key"
ssh -F /dev/null -i "$key_dir/id_key" -o BatchMode=yes -o ConnectTimeout=12 -o StrictHostKeyChecking=accept-new -o UserKnownHostsFile=/tmp/winx-oci-new-known_hosts ubuntu@147.15.108.243 "$@"
