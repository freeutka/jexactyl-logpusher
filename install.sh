#!/bin/bash

if (( $EUID != 0 )); then
    printf "\033[0;33m<jexactyl-logpusher> \033[0;31m[✕]\033[0m Please run this program as root \n"
    exit
fi

watermark="\033[0;33m<jexactyl-logpusher> \033[0;32m[✓]\033[0m"
target_dir=""

chooseDirectory() {
    echo -e "<jexactyl-logpusher> [1] /var/www/jexactyl   (choose this if you installed the panel using the official Jexactyl documentation)"
    echo -e "<jexactyl-logpusher> [2] /var/www/pterodactyl (choose this if you migrated from Pterodactyl to Jexactyl)"

    while true; do
        read -p "<jexactyl-logpusher> [?] Choose jexactyl directory [1/2]: " choice
        case "$choice" in
            1)
                target_dir="/var/www/jexactyl"
                break
                ;;
            2)
                target_dir="/var/www/pterodactyl"
                break
                ;;
            *)
                echo -e "\033[0;33m<jexactyl-logpusher> \033[0;31m[✕]\033[0m Invalid choice. Please enter 1 or 2."
                ;;
        esac
    done
}

startPterodactyl(){
    curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | sudo -E bash -
    export NVM_DIR="$HOME/.nvm"
    [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
    [ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"
    nvm install node || {
        printf "${watermark} nvm command not found, trying to source nvm script directly... \n"
        . ~/.nvm/nvm.sh
        nvm install node
    }
    apt update

    npm i -g yarn
    yarn
    export NODE_OPTIONS=--openssl-legacy-provider
    yarn build:production || {
        printf "${watermark} node: --openssl-legacy-provider is not allowed in NODE_OPTIONS \n"
        export NODE_OPTIONS=
        yarn build:production
    }
    sudo php artisan optimize:clear
    sudo php artisan migrate --force
}

installModule(){
    chooseDirectory
    printf "${watermark} Installing module... \n"
    cd "$target_dir"
    rm -rvf jexactyl-logpusher
    printf "${watermark} Previous module successfully removed \n"
    git clone https://github.com/freeutka/jexactyl-logpusher.git
    printf "${watermark} Cloning git repository \n"
    rm -f app/Http/Controllers/Api/Client/Servers/LogController.php
    rm -f app/Models/SettingPaste.php
    rm -f database/migrations/2024_08_10_145706_create_settings_table_paste.php
    rm -f resources/scripts/api/server/sendLogs.ts
    rm -f resources/scripts/components/server/console/PowerButtons.tsx
    rm -f routes/api-client.php
    rm -f app/Http/Controllers/Admin/Jexactyl/AppearanceController.php
    rm -f resources/views/admin/jexactyl/appearence.blade.php
    printf "${watermark} Previous files successfully removed \n"
    cd jexactyl-logpusher
    mv resources/LogController.php "$target_dir/app/Http/Controllers/Api/Client/Servers/"
    mv resources/SettingPaste.php "$target_dir/app/Models/"
    mv resources/2024_08_10_145706_create_settings_table_paste.php "$target_dir/database/migrations/"
    mv resources/sendLogs.ts "$target_dir/resources/scripts/api/server/"
    mv resources/PowerButtons.tsx "$target_dir/resources/scripts/components/server/console/"
    mv resources/api-client.php "$target_dir/routes/"
    mv resources/AppearanceController.php "$target_dir/app/Http/Controllers/Admin/Jexactyl/"
    mv resources/appearence.blade.php "$target_dir/resources/views/admin/jexactyl/"
    printf "${watermark} New files successfully installed \n"
    rm -rvf "$target_dir/jexactyl-logpusher"
    printf "${watermark} Git repository deleted \n"
    cd "$target_dir"

    printf "${watermark} Module fully and successfully installed in your jexactyl repository \n"

    while true; do
        read -p '<jexactyl-logpusher> [?] Do you want rebuild panel assets [y/N]? ' yn
        case $yn in
            [Yy]* ) startPterodactyl; break;;
            [Nn]* ) exit;;
            * ) exit;;
        esac
    done
}

while true; do
    read -p '<jexactyl-logpusher> [✓] Are you sure that you want to install "jexactyl-logpusher" module [y/N]? ' yn
    case $yn in
        [Yy]* ) installModule; break;;
        [Nn]* ) printf "${watermark} Canceled \n"; exit;;
        * ) exit;;
    esac
done
