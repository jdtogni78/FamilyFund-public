# Family Fund

A simple system to manage fund shares and composition.

See [V1 Specs](specs/V1.spec.md)
See [Remaining Specs](specs/V99.spec.md)

**Setting up on a new machine?** See [SETUP.md](SETUP.md) — covers env files,
DB dump, and shared files on the NAS.

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
| users | password | Rotated for **all** users incl. admin to `devpassword123` — no real prod hash survives |
| users | name / email | "XUser{id}" / "user{id}@dev.familyfund.local" (admin → `Dev Admin` / `admin@dev.familyfund.local`) |
| users | two_factor_secret / recovery / confirmed_at | Cleared (guarded) |
| users | remember_token | Cleared |
| persons | first_name / last_name / email / birthday | "First{id}" / "Last{id}" / `person{id}@dev.familyfund.local` / `1990-01-01` |
| addresses | street / number / complement / city / state / zip_code | Replaced with dev placeholders |
| phones | number | Replaced with `+1555…` placeholder |
| iddocuments | number (CPF/RG/CNH/Passport/SSN) | Replaced with `DEV-{type}-{id}` |
| accounts | nickname / email_cc | "Acct{id}" / `account{id}@dev.familyfund.local` |
| trade_portfolios | account_name | "Portfolio Acct {id}" |
| transactions / cash_deposits / deposit_requests | free-text descr/description | Genericized |
| account_credit_lines, credit_line_adjustments, transaction_reversals, credit_line_delay_notifications | free-text / recipient_email | Genericized (guarded) |
| change_log / sessions / password_resets / personal_access_tokens | * | Deleted |
| login_activities / operation_logs / matching_reminder_logs | * | Deleted (guarded) |

Sections marked *(guarded)* run only if the (newer, post-2026-01) table/column exists, so an
older prod dump is skipped rather than aborting the script.

**Login after running** (no real personal email survives, not even the admin's):
- `admin@dev.familyfund.local` / `devpassword123` — admin (was `admin@dev.familyfund.local`; the
  `system-admin` role is `user_id`-linked so it is retained). The `/dev-login` `admin` alias
  resolves here, falling back to `admin@dev.familyfund.local` on an un-scrubbed prod dump / test baseline.
- `claude@test.local` / `claude-test-2024` — CLI test user (kept).
- every other user / `devpassword123`.

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

### Server Setup
sudo apt install mariadb-client

### Adding a docker user

Create group and user for docker (substitute your deploy user for `<user>`):
```bash
groupadd -g 100999 dockeruser
useradd -u 100999 -G dockeruser dockeruser
usermod -aG dockeruser <user>
```

You should see:
* on /etc/passwd: ```dockeruser:x:100999:100999::/home/dockeruser:/bin/sh```
* on /etc/group: ```dockeruser:x:100999:<user>```

Follow the instructions for rootless docker:
* https://docs.docker.com/engine/security/rootless/

### Optional: Passwordless sudo for deployments

Maintainer-only: see `familyfund-secrets/docs/passwordless-sudo-setup.md` for the deploy-user passwordless-sudo config. Outside operators set up their own deploy automation.

### Deploying to prod

Deploys are handled by `dstrader-docker`'s deploy scripts — see the `dstrader-docker` repo.

## Backup to NAS

* enable NAS: https://kb.synology.com/en-my/DSM/tutorial/How_to_back_up_Linux_computer_to_Synology_NAS
* setup NFS: https://kb.synology.com/en-br/DSM/tutorial/How_to_access_files_on_Synology_NAS_within_the_local_network_NFS
* mount NAS:
  * ```sudo mount -v -t nfs -o vers=3 <NAS_IP>:/volume1/NetBackup /mnt/backup```
  * add to /etc/fstab (use `_netdev` for network mount dependency):
    * ```<NAS_IP>:/volume1/NetBackup    /mnt/backup   nfs    defaults,_netdev 0 0```
* create user with exact same properties of NAS, ex
  * sudo useradd -u 1028 -g 100 backup2
* choose folders to backup:
  * /var/log
  * /home/<user>
  * /etc

### Backup Schedule (on the prod server)

The backup script `dstrader/opt/backup.sh` runs via root crontab 3x daily:
- 3:10 AM, 12:50 PM, 11:10 PM

**What gets backed up (synced to NAS via NFS):**
1. Rsyncs `/home/<user>`, `/var/log`, `/etc` to `/mnt/backup/dstrader_server/` on the NAS
2. Monthly docker image snapshots (`dev-dstrader`, `dev-familyfund`)
3. **Database backup** — dumps `familyfund_prod`, encrypts at rest, gzips. See the encryption-at-rest stub below.

**Database backup requirements:**
- Only needs `db` container running (NOT dstrader)
- Uses `docker exec db mariadb-dump`
- Output: `dstrader/prod/backups/db-backup-prod-YYYY-MM-DD.sql.encr.gz`
- Synced to the NAS via rsync of `/home/<user>/`

Log file: `/var/log/dstrader/backup.log`

### Troubleshooting Backup Sync

If backups to NAS fail, check:

1. **Is NFS mounted?**
   ```bash
   ssh <user>@<PROD_HOST> "mount | grep backup"
   # Should show: <NAS_IP>:/volume1/NetBackup on /mnt/backup type nfs
   ```

2. **Mount manually if needed:**
   ```bash
   ssh <user>@<PROD_HOST> "sudo mount /mnt/backup"
   ```

3. **Is the NAS reachable?**
   ```bash
   ssh <user>@<PROD_HOST> "ping -c 2 <NAS_IP>"
   ```

4. **Check backup log for errors:**
   ```bash
   ssh <user>@<PROD_HOST> "tail -50 /var/log/dstrader/backup.log"
   ```

5. **Common error:** `mkdir "/mnt/backup/..." failed: No such file or directory`
   - Means NFS is not mounted. Run `sudo mount /mnt/backup` on the prod server.

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

1. Check for saved image backup on the NAS:
   ```bash
   ls -lh /mnt/backup/dstrader_server/home/<user>/dev/backups/docker-dev-familyfund_*.tgz
   ```

2. Copy and load the image:
   ```bash
   cp /mnt/backup/dstrader_server/home/<user>/dev/backups/docker-dev-familyfund_*.tgz ~/dev/backups/
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

### Backup encryption-at-rest

Database backups are encrypted at rest before being synced to the NAS. The maintainer-side configuration (key material, container mounts, troubleshooting) lives in the private companion: historical GPG-based setup notes are at `familyfund-secrets/docs/archive/gpg-backup-encryption.md` (the active path is moving to SOPS+age; see `secrets.sh`). Outside operators choose their own backup-encryption mechanism.

### Server Disk Space Management

The root partition on the prod server (`/dev/nvme0n1p6`, 48GB) can fill up and cause DSTrader to fail (mariadb won't start).

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

# Wake the NAS/backup server
wakeonlan <NAS_MAC>

# Wake the dstrader server
wakeonlan <PROD_MAC>
```

| Server | MAC Address | IP Address | Purpose |
|--------|-------------|------------|---------|
| NAS server  | `<NAS_MAC>`  | `<NAS_IP>`   | NAS/Backup |
| prod server | `<PROD_MAC>` | `<PROD_HOST>`| dstrader |

### Server WOL Setup

* enable WOL on BIOS
* enable WOL on OS
  * sudo ethtool -s enp3s0 wol g
  * sudo ethtool enp3s0
  * sudo systemctl enable wol@enp3s0
  * sudo systemctl start wol@enp3s0
* Router-side WoL forwarding (port-forward UDP 9 to a DHCP-reserved internal IP with a static ARP binding) — see your router's WoL / port-forwarding docs. Maintainer's vendor-agnostic notes are in `familyfund-secrets/docs/homelab-wol-setup.md`.

### VNC Server Setup

Open port 5900
* netstat -lntu|grep 5900
* sudo ufw allow 5900
* sudo apt install x11-xserver-utils
* xhost +local:$USER