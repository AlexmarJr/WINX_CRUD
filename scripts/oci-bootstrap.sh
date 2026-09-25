#!/usr/bin/env bash
set -euo pipefail

sudo cloud-init status --wait

if ! swapon --show=NAME --noheadings | grep -qx /swapfile; then
  if [ ! -f /swapfile ]; then
    sudo fallocate -l 4G /swapfile
  fi
  sudo chmod 600 /swapfile
  if ! sudo blkid -s TYPE -o value /swapfile 2>/dev/null | grep -qx swap; then
    sudo mkswap /swapfile
  fi
  sudo swapon /swapfile
fi
if ! grep -q '^/swapfile ' /etc/fstab; then
  printf '/swapfile none swap sw 0 0\n' | sudo tee -a /etc/fstab >/dev/null
fi
printf 'vm.swappiness=20\n' | sudo tee /etc/sysctl.d/99-winx.conf >/dev/null
sudo sysctl -p /etc/sysctl.d/99-winx.conf

sudo apt-get update
sudo env DEBIAN_FRONTEND=noninteractive apt-get install -y ca-certificates curl git
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc
sudo tee /etc/apt/sources.list.d/docker.sources >/dev/null <<EOF
Types: deb
URIs: https://download.docker.com/linux/ubuntu
Suites: $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}")
Components: stable
Architectures: $(dpkg --print-architecture)
Signed-By: /etc/apt/keyrings/docker.asc
EOF
sudo apt-get update
sudo env DEBIAN_FRONTEND=noninteractive apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo systemctl enable --now docker
free -h
sudo docker compose version
