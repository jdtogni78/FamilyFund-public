# Family Fund

A simple system to manage fund shares and composition.

See [V1 Specs](specs/V1.spec.md)
See [Remaining Specs](specs/V99.spec.md)

**Setting up on a new machine?** See [SETUP.md](SETUP.md) — covers env files,
DB dump, and shared files on melnick.

## Docker

See https://hub.docker.com/r/bitnami/laravel/

* Go to main dir

docker-compose -f docker-compose.yml -f docker-compose.${MYENV}.yml up

* First time run composer accepting errors:

docker-compose exec familyfund composer install

* Build frontend assets (from app1/family-fund-app/):

cd app1/family-fund-app && npm install && npm run build

Note: Must rebuild after changing Blade templates with new Tailwind classes (Tailwind purges unused classes).

* First time setup database / reimport full db

docker-compose exec familyfund php artisan migrate:fresh
mysql -h 127.0.0.1 -u famfun -p1234 familyfund < familyfund_dump.sql

* Dump dev database on Mac

generators/dump_ddl.sh
generators/dump_data.sh

## Database Backup & Restore

### Backup Locations

```
database/
├── familyfund_ddl.sql          # Current schema (no data)
├── drop_all.sql                # Drop all tables script
├── truncate_all.sql            # Truncate all tables script
├── prod_to_dev.sql             # Anonymization script for prod→dev
├── delete_test_data.sql        # Clean up test data
├── dev/
│   └── familyfund_dev_data_*.sql   # Dev environment backups
└── prod/
    └── familyfund_prod_data_*.sql  # Prod environment backups
```

### Export Database Backups

Run from `app1/family-fund-app/generators/`:

The scripts require MySQL connection parameters passed as extra arguments.
Docker exposes MariaDB on `127.0.0.1:3306`.

```bash
# Dev environment credentials
DEV_CONN="--host=127.0.0.1 --port=3306 --user=famfun_dev -p1234"

# Export schema only (DDL)
./dump_ddl.sh dev $DEV_CONN    # Creates database/familyfund_ddl_YYYYMMDD.sql

# Export data only
./dump_data.sh dev $DEV_CONN   # Creates database/dev/familyfund_dev_data_YYYYMMDD.sql
```

For prod, use root credentials from docker-compose:
```bash
PROD_CONN="--host=127.0.0.1 --port=3306 --user=root -p123456"
./dump_data.sh prod $PROD_CONN
```

### Load Prod Backup to Dev

1. **Export prod data** (from prod server or via backup):
   ```bash
   cd app1/family-fund-app/generators
   ./dump_data.sh prod
   ```

2. **Load into dev database**:
   ```bash
   # Reset dev database
   docker-compose exec familyfund php artisan migrate:fresh

   # Load prod data
   mysql -h 127.0.0.1 -u famfun -p1234 familyfund_dev < database/prod/familyfund_prod_data_YYYYMMDD.sql
   ```

3. **Anonymize sensitive data**:
   ```bash
   mysql -h 127.0.0.1 -u famfun -p1234 familyfund_dev < database/prod_to_dev.sql
   ```

### Data Anonymization (prod_to_dev.sql)

The `database/prod_to_dev.sql` script sanitizes production data:

| Table | Field | Action |
|-------|-------|--------|
| users | name | Changed to "XUser{id}" (keeps first initial) |
| users | email | Changed to "user{id}@dev.familyfund.local" |
| users | password | Reset to 'devpassword123' |
| accounts | nickname | Changed to "Acct{id}" |
| accounts | email_cc | Changed to "account{id}@dev.familyfund.local" |
| users | remember_token | Cleared |
| password_resets | * | Deleted |
| personal_access_tokens | * | Deleted |

**Preserved accounts:**
- `admin@dev.familyfund.local` - Admin (original password restored)
- `claude@test.local` - Test user (unchanged)

### Restore Dev from Backup

```bash
# Full restore (schema + data)
mysql -h 127.0.0.1 -u famfun -p1234 familyfund_dev < database/familyfund_ddl.sql
mysql -h 127.0.0.1 -u famfun -p1234 familyfund_dev < database/dev/familyfund_dev_data_YYYYMMDD.sql
```

### Clean Up Test Data

After running tests that create data with IDs > 300:
```bash
mysql -h 127.0.0.1 -u famfun -p1234 familyfund_dev < database/delete_test_data.sql
```


* Create new lines to better see data changes:

sed -e $'s/),(/),\\\n(/g' familyfund_dump.sql > familyfund_dump.sql

## Reverse engineer models

tables=$(mysql -h 127.0.0.1 -u famfun -p1234 familyfund -N -e "show tables" 2> /dev/null | grep -v "+" | grep -v "failed_jobs\|migrations\|password_resets\|personal_access_tokens")

### Generate API CRUD

See https://infyom.com/open-source/laravelgenerator/docs/8.0/introduction
See generators/models.sh

#### Generate from file

for t in $(echo $tables); 
    do echo $t; 
    arr=(${(s:_:)t})
    c=$(printf %s "${(C)arr}" | sed "s/ //g" | sed "s/s$//")
    php artisan infyom:scaffold $c --fieldsFile resources/model_schemas/$c.json --tableName $t --skip dump-autoload
    php artisan infyom:api $c --fieldsFile resources/model_schemas/$c.json --tableName $t --skip dump-autoload
    sed -i.bkp -e 's/private \($.*Repository;\)/protected \1/' app/Http/Controllers/*Controller.php
done;
rm app/Http/Controllers/*Controller.php.bkp


## Generate migrations (point to empty schema)

for t in $(echo $tables); 
    do echo $t; 
    arr=(${(s:_:)t})
    c=$(printf %s "${(C)arr}" | sed "s/ //g")
    docker-compose exec familyfund php artisan infyom:scaffold $c --fieldsFile resources/model_schemas/$c.json \
        --skip model,controllers,api_controller,scaffold_controller,repository,requests,api_requests,scaffold_requests,routes,api_routes,scaffold_routes,views,tests,menu,dump-autoload
done;

php artisan infyom:scaffold Sample --fieldsFile vendor\infyom\laravel-generator\samples\fields_sample.json

## PDF 

### Docker
wget https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6-1/wkhtmltox_0.12.6-1.buster_amd64.deb
sudo apt install ./wkhtmltox_0.12.6-1.buster_amd64.deb

### Laravel
https://github.com/barryvdh/laravel-snappy


## Mail

We are using a separate container for MailHog, but we still need sendmail & mhsendmail on php server & local (unit tests).
See Dockerfile & docker-compose.

### Local setup
// sudo apt-get update
// sudo apt-get install -y sendmail golang-go git
go get github.com/mailhog/mhsendmail
sudo cp ~/go/bin/mhsendmail /usr/local/bin/

#### /etc/hosts 
echo "127.0.0.1 noreply.domain.com mailhog" | sudo tee -a /etc/hosts

#### Update PHP.ini
sendmail_path = "/usr/local/bin/mhsendmail --smtp-addr=mailhog:1025"

#### Test

php tests/TestEmail.php

for f in run_report.log.*.gz; do gunzip -c $f | sed '0,/^.*## Positions$/d' | sed  -n '/.*## Report/q;p'; done|grep SPXL|sort
{"timestamp": "2022-04-01 19:46:50" "source": "FFIB" "symbols": {
{"name": "SPXL" "type": "STK" "position": 41.0}
{"name": "SOXL" "type": "STK" "position": 119.0}
{"name": "TECL" "type": "STK" "position": 82.0}
{"name": "FTBFX" "type": "FUND" "position": 149.851}
{"name": "IAU" "type": "STK" "position": 85.0}
{"name": "BTC" "type": "CRYPTO" "position": 0.02229813}
{"name": "ETH" "type": "CRYPTO" "position": 0.52756584}
{"name": "FIPDX" "type": "FUND" "position": 563.964}
{"name": "LTC" "type": "CRYPTO" "position": 5.67880488}
{"name": "CASH", "type": "CSH", position: 3212.43}
}

#### Start sending reports (email)

php artisan queue:work

docker exec -it familyfund php artisan queue:work

#### Run FamilyFund App on prod

docker-compose -f docker-compose.yml -f docker-compose.${RUNTIME}.yml up mariadb familyfund

### Change/reset Password Command Line

php artisan tinker
    $user = App\Models\UserExt::where('email', 'admin@dev.familyfund.local')->first();
    $user->password = Hash::make('new_password');
    $user->save();


### Jumpbox Setup

Dont recall initial install, but here are some notes:
https://davewpark.medium.com/securing-remote-access-with-a-jumpserver-in-10-steps-ce2d9cd328f6

### Jumpbox

JUMPBOXDNS=REDACTED.example.invalid
JUMPBOX=REDACTED_JUMP_HOST
FFSERVER=REDACTED_LAN_HOST

ssh -J dstrader@${JUMPBOXDNS}:60004 jdtogni@${FFSERVER} -p 22
ssh -J dstrader@${JUMPBOXDNS}:60004 -N jdtogni@${FFSERVER} -L 3001:${FFSERVER}:3001

ssh -J dstrader@${JUMPBOX}:22332 jdtogni@${FFSERVER} -p 22
ssh -J dstrader@${JUMPBOX}:22332 -N jdtogni@${FFSERVER} -L 3000:${FFSERVER}:3000

### Wake on LAN
<router-model-redacted> setup: <wol-router-faq-redacted>
setup server to wake up: https://www.cyberciti.biz/tips/linux-send-wake-on-lan-wol-magic-packets.html
make sure to enable upon reboot:https://pimylifeup.com/ubuntu-enable-wake-on-lan/
mac app: https://apps.apple.com/us/app/wakeoncommand/id1484204619?mt=12

Could not make the WOL work from VPN, only inside the network.
So, some server must be kept on, from that we can wake the other servers.

### Adding an account in FamilyFund

* Create a user via the web interface
* Create an account for that user
* Add a transaction - this will create a balance for the account

### Adding a fund in FamilyFund

* Create an account with no user id for the fund
* Create Fund
* Create portfolio
* Create an initial transaction for the fund
* Check initial balance

### Making an investment into a fund

* Create a transaction for the fund
* When should the new cash be available
* Making transaction before cash was recognized caused miscalculation and validation error

### Adding an account in IBKR

* Add an additional account
* <plaid-monarch-line-redacted>

### Server Setup
sudo apt install mariadb-client

### Adding a docker user

Create group and user for docker:
```bash
groupadd -g 100999 dockeruser
useradd -u 100999 -G dockeruser dockeruser
usermod -aG dockeruser jdtogni
```

You should see:
* on /etc/passwd: ```dockeruser:x:100999:100999::/home/dockeruser:/bin/sh```
* on /etc/group: ```dockeruser:x:100999:jdtogni```

Follow the instructions for rootless docker:
* https://docs.docker.com/engine/security/rootless/

### Optional: Passwordless sudo for deployments

To allow jdtogni to run deployment commands without password prompts, create a sudoers file:

```bash
sudo visudo -f /etc/sudoers.d/jdtogni-deploy
```

Add these lines (adjust paths if needed):
```
# Deployment chown commands for FamilyFund
jdtogni ALL=(ALL) NOPASSWD: /bin/chown jdtogni\:jdtogni /home/jdtogni/dev/FamilyFund/app1/family-fund-app/ -R
jdtogni ALL=(ALL) NOPASSWD: /bin/chown dockeruser\:dockeruser /home/jdtogni/dev/FamilyFund/app1/family-fund-app/ -R

# Admin utilities
jdtogni ALL=(ALL) NOPASSWD: /usr/bin/crontab
jdtogni ALL=(ALL) NOPASSWD: /usr/bin/systemctl
jdtogni ALL=(ALL) NOPASSWD: /usr/bin/journalctl
```

Set correct permissions:
```bash
sudo chmod 440 /etc/sudoers.d/jdtogni-deploy
```

### Deploying DSTrader to prod

FFSERVER=REDACTED_LAN_HOST

* Copy DSTrader.jar from stage to prod
* Review properties in stage and prod
* Verify changes:
  * rsync -avnc --exclude='.git' --exclude=.DS_Store ~/dev/dstrader-docker/ jdtogni@${FFSERVER}:~/dev/dstrader-docker/
* Copy files:
  * rsync -avc --exclude='.git' --exclude=.DS_Store ~/dev/dstrader-docker/ jdtogni@${FFSERVER}:~/dev/dstrader-docker/

### Deploying FamilyFund to prod

FFSERVER=REDACTED_LAN_HOST

* Verify changes
  * rsync -avnc --exclude='.git' --exclude=.DS_Store --exclude='.idea' --exclude=datadir ~/dev/FamilyFund/app1/ jdtogni@${FFSERVER}:~/dev/FamilyFund/app1/
* Change ownership on server
  * sudo chown jdtogni:jdtogni app1/family-fund-app/ -R
* Transfer content of app1
  * rsync -avc --exclude='.git' --exclude=.DS_Store --exclude='.idea' --exclude=datadir ~/dev/FamilyFund/app1/ jdtogni@${FFSERVER}:~/dev/FamilyFund/app1/
* Restore ownership on server
  * sudo chown dockeruser:dockeruser app1/family-fund-app/ -R
  
## Backup to NAS

* enable NAS: https://kb.synology.com/en-my/DSM/tutorial/How_to_back_up_Linux_computer_to_Synology_NAS
* setup NFS: https://kb.synology.com/en-br/DSM/tutorial/How_to_access_files_on_Synology_NAS_within_the_local_network_NFS
* mount NAS:
  * ```sudo mount -v -t nfs -o vers=3 REDACTED_NAS_HOST:/volume1/NetBackup /mnt/backup```
  * add to /etc/fstab (use `_netdev` for network mount dependency):
    * ```REDACTED_NAS_HOST:/volume1/NetBackup    /mnt/backup   nfs    defaults,_netdev 0 0```
* create user with exact same properties of NAS, ex
  * sudo useradd -u 1028 -g 100 backup2
* choose folders to backup:
  * /var/log
  * /home/jdtogni
  * /etc

### Backup Schedule (on spirit)

The backup script `dstrader/opt/backup.sh` runs via root crontab 3x daily:
- 3:10 AM, 12:50 PM, 11:10 PM

**What gets backed up (synced to melnick NAS via NFS):**
1. Rsyncs `/home/jdtogni`, `/var/log`, `/etc` to `/mnt/backup/dstrader_server/` on melnick
2. Monthly docker image snapshots (`dev-dstrader`, `dev-familyfund`)
3. **Database backup** - dumps `familyfund_prod`, encrypts with GPG, gzips

**Database backup requirements:**
- Only needs `db` container running (NOT dstrader)
- Uses `docker exec db mariadb-dump`
- Encrypts with GPG key `docker@example.local`
- Output: `dstrader/prod/backups/db-backup-prod-YYYY-MM-DD.sql.encr.gz`
- Synced to melnick via rsync of `/home/jdtogni/`

Log file: `/var/log/dstrader/backup.log`

### Troubleshooting Backup Sync

If backups to NAS fail, check:

1. **Is NFS mounted?**
   ```bash
   ssh jdtogni@REDACTED_PROD_HOST "mount | grep backup"
   # Should show: REDACTED_NAS_HOST:/volume1/NetBackup on /mnt/backup type nfs
   ```

2. **Mount manually if needed:**
   ```bash
   ssh jdtogni@REDACTED_PROD_HOST "sudo mount /mnt/backup"
   ```

3. **Is melnick reachable?**
   ```bash
   ssh jdtogni@REDACTED_PROD_HOST "ping -c 2 REDACTED_NAS_HOST"
   ```

4. **Check backup log for errors:**
   ```bash
   ssh jdtogni@REDACTED_PROD_HOST "tail -50 /var/log/dstrader/backup.log"
   ```

5. **Common error:** `mkdir "/mnt/backup/..." failed: No such file or directory`
   - Means NFS is not mounted. Run `sudo mount /mnt/backup` on spirit.

6. **Fix fstab if missing `_netdev`:**
   ```bash
   # Check current fstab
   grep backup /etc/fstab
   # If missing _netdev, fix it:
   sudo sed -i 's|/mnt/backup   nfs    defaults|/mnt/backup   nfs    defaults,_netdev|' /etc/fstab
   ```

### Docker Image Management

The FamilyFund container uses `bitnami/laravel` base image. Bitnami periodically removes old version tags.

**If docker build fails with "image not found":**

1. Check for saved image backup on melnick:
   ```bash
   ls -lh /mnt/backup/dstrader_server/home/jdtogni/dev/backups/docker-dev-familyfund_*.tgz
   ```

2. Copy and load the image:
   ```bash
   cp /mnt/backup/dstrader_server/home/jdtogni/dev/backups/docker-dev-familyfund_*.tgz ~/dev/backups/
   gunzip -c ~/dev/backups/docker-dev-familyfund_*.tgz | docker load
   ```

3. Update docker-compose.yml to use pre-built image:
   ```yaml
   # Change from:
   build: ./FamilyFund/app1
   # To:
   image: dev-familyfund:latest
   ```

4. Start containers:
   ```bash
   cd ~/dev && docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
   ```

### GPG Encryption for Backups

Database backups are encrypted using GPG before being stored. The encryption runs inside the dstrader container.

**Configuration:**
- GPG key: `docker@example.local`
- Passphrase file: `dstrader/dstrader.passphrase`
- Encryption lib: `dstrader/opt/encr_files_lib.sh`

**Requirements:**
- Host's `~/.gnupg` must be mounted in container (already configured in docker-compose.yml):
  ```yaml
  volumes:
    - /home/jdtogni/.gnupg:/root/.gnupg
  ```

**If encryption fails with "Unusable public key":**

1. Check if GPG keys are mounted:
   ```bash
   docker exec dstrader gpg --list-keys docker@example.local
   ```

2. If keys missing, verify docker-compose.yml has the gnupg volume mount

3. If keys exist but not trusted, the mount worked but trust was lost. Run:
   ```bash
   docker exec -it dstrader bash -c "echo -e '5\ny\n' | gpg --command-fd 0 --edit-key docker@example.local trust"
   ```

**Test encryption manually:**
```bash
docker exec dstrader bash -c "echo test | gpg --batch --yes --trust-model always -e -r docker@example.local > /dev/null && echo OK"
```

### Server Disk Space Management

The root partition on spirit (`/dev/nvme0n1p6`, 48GB) can fill up and cause DSTrader to fail (mariadb won't start).

**Check disk usage:**
```bash
ssh dstrader "df -h /"
# Should stay below 90%
```

**Common space culprits:**

| Location | Issue | Fix |
|----------|-------|-----|
| `/var/log/journal` | Systemd journal grows unbounded | Set `SystemMaxUse=500M` in `/etc/systemd/journald.conf` then `sudo systemctl restart systemd-journald` |
| `/var/log/dstrader/prod/` | Old log files accumulate | Delete logs older than 6 months: `rm /var/log/dstrader/prod/*2024*` |
| `/tmp` | Temporary backup files | Check with `ls -lh /tmp/*.tar.gz` and remove old ones |

**One-time journald fix (recommended):**
```bash
ssh dstrader
sudo vi /etc/systemd/journald.conf
# Add under [Journal]:
# SystemMaxUse=500M
sudo systemctl restart systemd-journald
```

## VPN Setup

L2TP/IPSec
User/password

## Wake on LAN

### Wake Servers from Mac

```bash
# Install wakeonlan (if not installed)
brew install wakeonlan

# Wake melnick (NAS/backup server)
wakeonlan REDACTED_MAC

# Wake spirit (dstrader server)
wakeonlan E8:FF:1E:D6:6A:70
```

| Server | MAC Address | IP Address | Purpose |
|--------|-------------|------------|---------|
| melnick | REDACTED_MAC | REDACTED_NAS_HOST | NAS/Backup |
| spirit | E8:FF:1E:D6:6A:70 | REDACTED_PROD_HOST | dstrader |

### Server WOL Setup

* enable WOL on BIOS
* enable WOL on OS
  * sudo ethtool -s enp3s0 wol g
  * sudo ethtool enp3s0
  * sudo systemctl enable wol@enp3s0
  * sudo systemctl start wol@enp3s0
* <wol-router-faq-redacted>

### VNC Server Setup

Open port 5900
* netstat -lntu|grep 5900
* sudo ufw allow 5900
* sudo apt install x11-xserver-utils
* xhost +local:$USER